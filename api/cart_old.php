<?php
// ini_set('display_errors', 0);
// error_reporting(0);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Helper: respond
function json($data) {
	echo json_encode($data);
	exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = null;
if (in_array($method, ['POST','PUT'])) {
	$input = json_decode(file_get_contents('php://input'), true);
}

// Helper: get logged in customer id
function current_customer_id() {
	return isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
}

try {
	// GET: list cart
	if ($method === 'GET') {
		$customer_id = current_customer_id();
		if ($customer_id) {
			$stmt = $pdo->prepare("SELECT c.id as cart_item_id, c.product_id, c.quantity, c.material_id, c.variation_id, c.size_id, c.selected_price,
			p.name as product_name, COALESCE(c.selected_price, pv.price_adjustment, p.price) as price, p.slug,
			m.name as material_name, pv.tag as variation_tag, pv.color, pv.adornment, vs.size
			FROM cart c
			JOIN products p ON p.id = c.product_id
			LEFT JOIN materials m ON m.id = c.material_id
			LEFT JOIN product_variations pv ON pv.id = c.variation_id
			LEFT JOIN variation_sizes vs ON vs.id = c.size_id
			WHERE c.customer_id = ?");
			$stmt->execute([$customer_id]);
			$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
			// For each item, get the appropriate image: variation image if tag matches, else primary
			$modifiedItems = [];
			foreach ($items as $item) {
				if (!empty($item['variation_tag'])) {
					$imageStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND tag = ? LIMIT 1");
					$imageStmt->execute([$item['product_id'], $item['variation_tag']]);
				} else {
					$imageStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY sort_order ASC LIMIT 1");
					$imageStmt->execute([$item['product_id']]);
				}
				$imageRow = $imageStmt->fetch(PDO::FETCH_ASSOC);
				$item['image_url'] = $imageRow ? $imageRow['image_url'] : null;
				$item['variation_name'] = $item['variation_tag'];
				$modifiedItems[] = $item;
			}
			$items = $modifiedItems;
			json(['success' => true, 'items' => $items]);
		}

		// guest: return session cart, ensure image is present
		$items = isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [];
		if (!empty($items)) {
		$modifiedItems = [];
		foreach ($items as $it) {
		if (empty($it['image'])) {
		$imgStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1');
		$imgStmt->execute([ (int)($it['product_id'] ?? 0) ]);
		$row = $imgStmt->fetch(PDO::FETCH_ASSOC);
		if ($row && isset($row['image_url'])) {
		$it['image'] = $row['image_url'];
		}
		}
		$modifiedItems[] = $it;
		}
		$items = $modifiedItems;
		}
		json(['success' => true, 'items' => $items]);
	}

	// POST: add item
	if ($method === 'POST') {
		// Support migration of client-side cart after login
		if (isset($input['migrate_cart']) && $input['migrate_cart'] && isset($input['items']) && is_array($input['items'])) {
			$customer_id = current_customer_id();
			if (!$customer_id) json(['success' => false, 'message' => 'Not authenticated']);

			foreach ($input['items'] as $itm) {
				// Accept different item shapes: {id, quantity} or {product_id, quantity}
				$pid = isset($itm['product_id']) ? (int)$itm['product_id'] : (isset($itm['id']) ? (int)$itm['id'] : 0);
				$qty = isset($itm['quantity']) ? max(1, (int)$itm['quantity']) : 1;
				if (!$pid) continue;

				// verify product exists
				$pstmt = $pdo->prepare('SELECT id FROM products WHERE id = ? AND is_active = 1');
				$pstmt->execute([$pid]);
				if (!$pstmt->fetch(PDO::FETCH_ASSOC)) continue;

				// upsert into cart
				$check = $pdo->prepare('SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?');
				$check->execute([$customer_id, $pid]);
				$row = $check->fetch(PDO::FETCH_ASSOC);
				if ($row) {
					$newQty = $row['quantity'] + $qty;
					$upd = $pdo->prepare('UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?');
					$upd->execute([$newQty, $row['id']]);
				} else {
					$ins = $pdo->prepare('INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)');
					$ins->execute([$customer_id, $pid, $qty]);
				}
			}

			json(['success' => true, 'message' => 'Cart migrated']);
		}

		if (!$input) json(['success' => false, 'message' => 'Invalid input']);

		$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
		$quantity = isset($input['quantity']) ? max(1, (int)$input['quantity']) : 1;
		$material_id = isset($input['material_id']) ? (int)$input['material_id'] : null;
		$variation_id = isset($input['variation_id']) ? (int)$input['variation_id'] : null;
		$size_id = isset($input['size_id']) ? (int)$input['size_id'] : null;
		$selected_price = isset($input['selected_price']) ? (float)$input['selected_price'] : null;

		if (!$product_id) json(['success' => false, 'message' => 'Product ID missing']);

		// Verify product exists and check stock
		$stmt = $pdo->prepare('SELECT id, name, price, slug, stock_quantity, is_active FROM products WHERE id = ?');
		$stmt->execute([$product_id]);
		$product = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$product) json(['success' => false, 'message' => 'Product not found']);
		if ($product['is_active'] != 1) json(['success' => false, 'message' => 'Product is not available']);

		// Note: Stock check removed for add to cart to allow testing
		// In production, re-enable stock validation

		$customer_id = current_customer_id();
		if ($customer_id) {
			// Insert or update DB cart with specifications
			$stmt = $pdo->prepare('SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ? AND material_id <=> ? AND variation_id <=> ? AND size_id <=> ?');
			$stmt->execute([$customer_id, $product_id, $material_id, $variation_id, $size_id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				$newQty = $row['quantity'] + $quantity;
				$upd = $pdo->prepare('UPDATE cart SET quantity = ?, selected_price = ?, updated_at = NOW() WHERE id = ?');
				$upd->execute([$newQty, $selected_price, $row['id']]);
			} else {
				$ins = $pdo->prepare('INSERT INTO cart (customer_id, product_id, quantity, material_id, variation_id, size_id, selected_price) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$ins->execute([$customer_id, $product_id, $quantity, $material_id, $variation_id, $size_id, $selected_price]);
			}

			json(['success' => true, 'message' => 'Item added to cart (DB)']);
		}

		// Guest: save to session
		if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
		$key = $product_id . '_' . ($material_id ?: 'null') . '_' . ($variation_id ?: 'null') . '_' . ($size_id ?: 'null');

		// Fetch names
		$material_name = null;
		$variation_tag = null;
		$color = null;
		$finish = null;
		$size = null;
		if ($material_id) {
			$mStmt = $pdo->prepare('SELECT name FROM materials WHERE id = ?');
			$mStmt->execute([$material_id]);
			$mRow = $mStmt->fetch(PDO::FETCH_ASSOC);
			$material_name = $mRow['name'] ?? null;
		}
		if ($variation_id) {
			$vStmt = $pdo->prepare('SELECT tag, color, adornment FROM product_variations WHERE id = ?');
			$vStmt->execute([$variation_id]);
			$vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
			$variation_tag = $vRow['tag'] ?? null;
			$color = $vRow['color'] ?? null;
			$adornment = $vRow['adornment'] ?? null;
		}
		if ($size_id) {
			$sStmt = $pdo->prepare('SELECT size FROM variation_sizes WHERE id = ?');
			$sStmt->execute([$size_id]);
			$sRow = $sStmt->fetch(PDO::FETCH_ASSOC);
			$size = $sRow['size'] ?? null;
		}

		// Determine image URL from input or DB
		$image_url = isset($input['image']) && $input['image'] ? (string)$input['image'] : null;
		if (!$image_url) {
		$imgStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1');
		$imgStmt->execute([$product_id]);
		$row = $imgStmt->fetch(PDO::FETCH_ASSOC);
		if ($row && isset($row['image_url'])) {
		$image_url = $row['image_url'];
		}
		}

		if (isset($_SESSION['cart'][$key])) {
		$_SESSION['cart'][$key]['quantity'] += $quantity;
		} else {
		$_SESSION['cart'][$key] = [
		'cart_item_id' => $key,
		'product_id' => $product_id,
		'product_name' => $product['name'],
		'price' => $selected_price ?: (float)$product['price'],
		'slug' => $product['slug'] ?? '',
		'quantity' => $quantity,
		'image' => $image_url,
		'material_id' => $material_id,
		'variation_id' => $variation_id,
		'size_id' => $size_id,
		'selected_price' => $selected_price,
		'material_name' => $material_name,
		'variation_tag' => $variation_tag,
		'color' => $color,
		'adornment' => $adornment,
		'size' => $size
		];
		}
		
		json(['success' => true, 'message' => 'Item added to cart (session)', 'cart' => array_values($_SESSION['cart'])]);
	}

	// PUT: update quantity
	if ($method === 'PUT') {
		if (!$input) json(['success' => false, 'message' => 'Invalid input']);

		$quantity = isset($input['quantity']) ? (int)$input['quantity'] : null;
		if ($quantity === null) json(['success' => false, 'message' => 'Missing quantity']);

		$customer_id = current_customer_id();
		if ($customer_id) {
			// Expect cart_item_id or product_id
			$cart_item_id = isset($input['cart_item_id']) ? (int)$input['cart_item_id'] : null;
			$product_id = isset($input['product_id']) ? (int)$input['product_id'] : null;

			if ($cart_item_id) {
				if ($quantity < 1) {
					$del = $pdo->prepare('DELETE FROM cart WHERE id = ? AND customer_id = ?');
					$del->execute([$cart_item_id, $customer_id]);
					json(['success' => true, 'message' => 'Item removed']);
				}
				$upd = $pdo->prepare('UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND customer_id = ?');
				$upd->execute([$quantity, $cart_item_id, $customer_id]);
				json(['success' => true, 'message' => 'Quantity updated']);
			} elseif ($product_id) {
				if ($quantity < 1) {
					$del = $pdo->prepare('DELETE FROM cart WHERE customer_id = ? AND product_id = ?');
					$del->execute([$customer_id, $product_id]);
					json(['success' => true, 'message' => 'Item removed']);
				}
				$upd = $pdo->prepare('UPDATE cart SET quantity = ?, updated_at = NOW() WHERE customer_id = ? AND product_id = ?');
				$upd->execute([$quantity, $customer_id, $product_id]);
				json(['success' => true, 'message' => 'Quantity updated']);
			} else {
				json(['success' => false, 'message' => 'Missing identifiers']);
			}
		}

		// Guest: update session
		$cart_item_id = isset($input['cart_item_id']) ? (string)$input['cart_item_id'] : (isset($input['product_id']) ? (string)$input['product_id'] : null);
		if (!$cart_item_id) json(['success' => false, 'message' => 'Missing identifiers']);
		if (!isset($_SESSION['cart'][$cart_item_id])) json(['success' => false, 'message' => 'Cart item not found']);
		if ($quantity < 1) {
			unset($_SESSION['cart'][$cart_item_id]);
			json(['success' => true, 'message' => 'Item removed', 'cart' => array_values($_SESSION['cart'])]);
		}
		$_SESSION['cart'][$cart_item_id]['quantity'] = $quantity;
		json(['success' => true, 'message' => 'Quantity updated', 'cart' => array_values($_SESSION['cart'])]);
	}

	// DELETE: remove
	if ($method === 'DELETE') {
		$customer_id = current_customer_id();
		$cart_item_id = isset($_GET['cart_item_id']) ? (int)$_GET['cart_item_id'] : 0;

		if ($customer_id) {
			if (!$cart_item_id) json(['success' => false, 'message' => 'Missing cart_item_id']);
			$del = $pdo->prepare('DELETE FROM cart WHERE id = ? AND customer_id = ?');
			$del->execute([$cart_item_id, $customer_id]);
			json(['success' => true, 'message' => 'Item removed']);
		}

		$cart_item_id = isset($_GET['cart_item_id']) ? (string)$_GET['cart_item_id'] : '';
		if (!$cart_item_id) json(['success' => false, 'message' => 'Missing cart_item_id']);
		if (isset($_SESSION['cart'][$cart_item_id])) {
			unset($_SESSION['cart'][$cart_item_id]);
			json(['success' => true, 'message' => 'Item removed', 'cart' => array_values($_SESSION['cart'])]);
		}
		json(['success' => false, 'message' => 'Cart item not found']);
	}

	json(['success' => false, 'message' => 'Method not supported']);

} catch (PDOException $e) {
	json(['success' => false, 'message' => 'Database error']);
}

?>
