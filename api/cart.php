<?php
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
			$stmt = $pdo->prepare("SELECT c.id as cart_item_id, c.product_id, c.quantity, p.name as product_name, p.price, p.slug,
			(SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 ORDER BY is_primary DESC, sort_order ASC LIMIT 1) AS image_url
			FROM cart c JOIN products p ON p.id = c.product_id WHERE c.customer_id = ?");
			$stmt->execute([$customer_id]);
			$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
			json(['success' => true, 'items' => $items]);
		}

		// guest: return session cart, ensure image is present
		$items = isset($_SESSION['cart']) ? array_values($_SESSION['cart']) : [];
		if (!empty($items)) {
		foreach ($items as &$it) {
		if (empty($it['image'])) {
		$imgStmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1');
		$imgStmt->execute([ (int)($it['product_id'] ?? 0) ]);
		$row = $imgStmt->fetch(PDO::FETCH_ASSOC);
		if ($row && isset($row['image_url'])) {
		$it['image'] = $row['image_url'];
		}
		}
		}
		unset($it);
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

		if (!$product_id) json(['success' => false, 'message' => 'Product ID missing']);

		// Verify product exists and check stock
		$stmt = $pdo->prepare('SELECT id, name, price, slug, stock_quantity FROM products WHERE id = ? AND is_active = 1');
		$stmt->execute([$product_id]);
		$product = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$product) json(['success' => false, 'message' => 'Product not found']);
		
		// Check if product has enough stock for requested quantity
		if ($product['stock_quantity'] <= 0) {
			json(['success' => false, 'message' => 'This product is out of stock']);
		}
		if ($product['stock_quantity'] < $quantity) {
			json(['success' => false, 'message' => 'Only ' . $product['stock_quantity'] . ' units available in stock']);
		}

		$customer_id = current_customer_id();
		if ($customer_id) {
			// Insert or update DB cart
			$stmt = $pdo->prepare('SELECT id, quantity FROM cart WHERE customer_id = ? AND product_id = ?');
			$stmt->execute([$customer_id, $product_id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				$newQty = $row['quantity'] + $quantity;
				$upd = $pdo->prepare('UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?');
				$upd->execute([$newQty, $row['id']]);
			} else {
				$ins = $pdo->prepare('INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)');
				$ins->execute([$customer_id, $product_id, $quantity]);
			}

			json(['success' => true, 'message' => 'Item added to cart (DB)']);
		}

		// Guest: save to session
		if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
		$key = (string)$product_id;
		
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
		if (empty($_SESSION['cart'][$key]['image']) && $image_url) {
		$_SESSION['cart'][$key]['image'] = $image_url;
		}
		} else {
		$_SESSION['cart'][$key] = [
		'cart_item_id' => $key,
		'product_id' => $product_id,
		'product_name' => $product['name'],
		'price' => (float)$product['price'],
		'slug' => $product['slug'] ?? '',
		'quantity' => $quantity,
		'image' => $image_url
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
