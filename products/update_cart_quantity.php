<?php
// update_cart_quantity.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_id'], $_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

// Validate quantity
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit;
}

// Fetch product stock and variant info
$stmt = $conn->prepare("
    SELECT p.stock, c.variant_id 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    LEFT JOIN product_variants pv ON c.variant_id = pv.id AND c.product_id = pv.product_id 
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->bind_param('ii', $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
$stock = $row['variant_id'] ? $row['stock'] : $row['stock']; // Use variant stock if applicable
$stmt->close();

if ($quantity > $stock) {
    echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
    exit;
}

// Update cart
$stmt = $conn->prepare("UPDATE cart SET quantity = ?, created_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->bind_param('iii', $quantity, $cart_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $conn->error]);
}
$stmt->close();
?>