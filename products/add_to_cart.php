<?php
// add_to_cart.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'], $_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];
$variant_id = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
$quantity = (int)$_POST['quantity'];

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Check stock availability
if ($variant_id) {
    $stock_stmt = $conn->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ?");
    $stock_stmt->bind_param('ii', $variant_id, $product_id);
} else {
    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_stmt->bind_param('i', $product_id);
}
$stock_stmt->execute();
$stock_result = $stock_stmt->get_result();

if ($stock_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product or variant not found']);
    $stock_stmt->close();
    exit;
}

$stock = $stock_result->fetch_assoc()['stock'];
$stock_stmt->close();

if ($quantity > $stock) {
    echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
    exit;
}

// Check if product/variant is already in cart for this user
if ($variant_id) {
    $existing_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id = ?");
    $existing_stmt->bind_param('iii', $user_id, $product_id, $variant_id);
} else {
    $existing_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND variant_id IS NULL");
    $existing_stmt->bind_param('ii', $user_id, $product_id);
}
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();

if ($existing_result->num_rows > 0) {
    // Update existing cart entry
    $existing = $existing_result->fetch_assoc();
    $cart_id = $existing['id'];
    $new_quantity = $existing['quantity'] + $quantity;

    if ($new_quantity > $stock) {
        echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
        $existing_stmt->close();
        exit;
    }

    $update_stmt = $conn->prepare("UPDATE cart SET quantity = ?, created_at = NOW() WHERE id = ?");
    $update_stmt->bind_param('ii', $new_quantity, $cart_id);
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $conn->error]);
    }
    $update_stmt->close();
} else {
    // Insert new cart entry
    $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insert_stmt->bind_param('iiii', $user_id, $product_id, $variant_id, $quantity);
    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added to cart successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item to cart: ' . $conn->error]);
    }
    $insert_stmt->close();
}
$existing_stmt->close();
?>