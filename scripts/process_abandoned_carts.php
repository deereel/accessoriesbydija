<?php
// Process abandoned carts and send reminder emails
// Run this script via cron job, e.g., every hour: 0 * * * * php /path/to/process_abandoned_carts.php

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/includes/email.php'; // Assuming email functionality exists

// Configuration
$abandon_threshold_hours = 24;
$max_emails_per_run = 50; // Limit to avoid spam

try {
    $pdo->beginTransaction();

    // Find abandoned carts: not updated in 24+ hours, have email, not already emailed
    $threshold_time = date('Y-m-d H:i:s', strtotime("-{$abandon_threshold_hours} hours"));

    $stmt = $pdo->prepare("
        SELECT
            c.customer_id,
            c.session_id,
            c.guest_email,
            MIN(c.updated_at) as updated_at,
            GROUP_CONCAT(c.id) as cart_item_ids
        FROM cart c
        LEFT JOIN abandoned_carts ac ON (
            (c.customer_id IS NOT NULL AND ac.customer_id = c.customer_id) OR
            (c.session_id IS NOT NULL AND ac.session_id COLLATE utf8mb4_general_ci = c.session_id) OR
            (c.guest_email IS NOT NULL AND ac.guest_email COLLATE utf8mb4_general_ci = c.guest_email)
        )
        WHERE c.updated_at < ?
        AND (c.customer_id IS NOT NULL OR c.guest_email IS NOT NULL)
        AND ac.id IS NULL
        GROUP BY c.customer_id, c.session_id, c.guest_email
        ORDER BY updated_at ASC
        LIMIT 50
    ");

    $stmt->execute([$threshold_time]);
    $abandoned_carts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $processed = 0;

    foreach ($abandoned_carts as $cart) {
        $customer_id = $cart['customer_id'];
        $session_id = $cart['session_id'];
        $guest_email = $cart['guest_email'];
        $cart_item_ids = explode(',', $cart['cart_item_ids']);

        // Get cart items details
        $placeholders = str_repeat('?,', count($cart_item_ids) - 1) . '?';
        $items_stmt = $pdo->prepare("
            SELECT c.id, c.product_id, c.quantity, c.material_id, c.variation_id, c.size_id, c.selected_price,
                p.name, p.slug, COALESCE(c.selected_price, pv.price_adjustment, p.price) as price,
                m.name as material_name, pv.tag as variation_tag, pv.color, pv.adornment, vs.size
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN materials m ON m.id = c.material_id
            LEFT JOIN product_variations pv ON pv.id = c.variation_id
            LEFT JOIN variation_sizes vs ON vs.id = c.size_id
            WHERE c.id IN ($placeholders)
        ");
        $items_stmt->execute($cart_item_ids);
        $cart_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) continue;

        // Get customer email
        $email = null;
        if ($customer_id) {
            $email_stmt = $pdo->prepare("SELECT email FROM customers WHERE id = ?");
            $email_stmt->execute([$customer_id]);
            $customer = $email_stmt->fetch(PDO::FETCH_ASSOC);
            $email = $customer['email'] ?? null;
        } else {
            $email = $guest_email;
        }

        if (!$email) continue;

        // Send email
        $email_sent = send_abandoned_cart_email($email, $cart_items);

        if ($email_sent) {
            // Record in abandoned_carts table
            $insert_stmt = $pdo->prepare("
                INSERT INTO abandoned_carts (cart_id, customer_id, session_id, guest_email, email_sent, email_sent_at)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $insert_stmt->execute([$cart_item_ids[0], $customer_id, $session_id, $guest_email]);

            $processed++;
        }
    }

    $pdo->commit();

    echo "Processed $processed abandoned cart emails\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

function send_abandoned_cart_email($email, $cart_items) {
    // Calculate cart total
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Build cart items HTML
    $items_html = '';
    foreach ($cart_items as $item) {
        $details = [];
        if (!empty($item['material_name'])) $details[] = $item['material_name'];
        if (!empty($item['color'])) $details[] = $item['color'];
        if (!empty($item['adornment'])) $details[] = $item['adornment'];
        if (!empty($item['size'])) $details[] = $item['size'];
        if (!empty($item['variation_tag'])) $details[] = $item['variation_tag'];
        $detail_str = $details ? ' (' . implode(', ', $details) . ')' : '';

        $items_html .= "
        <tr>
            <td style='padding: 10px; border-bottom: 1px solid #eee;'>
                <strong>{$item['name']}{$detail_str}</strong><br>
                Quantity: {$item['quantity']}<br>
                Price: £" . number_format($item['price'], 2) . "
            </td>
            <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>
                £" . number_format($item['price'] * $item['quantity'], 2) . "
            </td>
        </tr>";
    }

    $checkout_url = "https://" . $_SERVER['HTTP_HOST'] . "/checkout.php";

    $subject = "Your cart is waiting - Complete your order at Dija Accessories";

    $html_body = "
    <html>
    <head>
        <title>Your Abandoned Cart</title>
    </head>
    <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: #f8f9fa; padding: 20px; text-align: center;'>
            <h1 style='color: #c27ba0; margin: 0;'>Dija Accessories</h1>
        </div>

        <div style='padding: 20px;'>
            <h2>Don't forget your items!</h2>
            <p>We noticed you were interested in these beautiful pieces but haven't completed your purchase yet. Your cart is waiting for you!</p>

            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <thead>
                    <tr style='background: #f8f9fa;'>
                        <th style='padding: 10px; text-align: left; border-bottom: 2px solid #c27ba0;'>Item</th>
                        <th style='padding: 10px; text-align: right; border-bottom: 2px solid #c27ba0;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$items_html}
                </tbody>
                <tfoot>
                    <tr style='background: #f8f9fa; font-weight: bold;'>
                        <td style='padding: 10px;'>Subtotal</td>
                        <td style='padding: 10px; text-align: right;'>£" . number_format($subtotal, 2) . "</td>
                    </tr>
                </tfoot>
            </table>

            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$checkout_url}' style='background: #c27ba0; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Complete Your Order</a>
            </div>

            <p style='color: #666; font-size: 12px;'>
                If you no longer wish to receive these emails, <a href='https://" . $_SERVER['HTTP_HOST'] . "/unsubscribe.php?email=" . urlencode($email) . "'>unsubscribe here</a>.
            </p>
        </div>
    </body>
    </html>";

    $text_body = "Your cart is waiting at Dija Accessories!\n\n";
    $text_body .= "Items in your cart:\n";
    foreach ($cart_items as $item) {
        $details = [];
        if (!empty($item['material_name'])) $details[] = $item['material_name'];
        if (!empty($item['color'])) $details[] = $item['color'];
        if (!empty($item['adornment'])) $details[] = $item['adornment'];
        if (!empty($item['size'])) $details[] = $item['size'];
        if (!empty($item['variation_tag'])) $details[] = $item['variation_tag'];
        $detail_str = $details ? ' (' . implode(', ', $details) . ')' : '';

        $text_body .= "- {$item['name']}{$detail_str} x{$item['quantity']} - £" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }
    $text_body .= "\nSubtotal: £" . number_format($subtotal, 2) . "\n\n";
    $text_body .= "Complete your order: {$checkout_url}\n\n";
    $text_body .= "Unsubscribe: https://" . $_SERVER['HTTP_HOST'] . "/unsubscribe.php?email=" . urlencode($email);

    // Send HTML email
    return send_email_smtp($email, $subject, $html_body, true);
}
?>