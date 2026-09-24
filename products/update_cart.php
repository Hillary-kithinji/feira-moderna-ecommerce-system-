<?php
// update_cart.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to update cart']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
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

// Fetch product stock
$stmt = $conn->prepare("SELECT p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?");
$stmt->bind_param('ii', $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    $stmt->close();
    exit;
}

$row = $result->fetch_assoc();
if ($quantity > $row['stock']) {
    echo json_encode(['success' => false, 'message' => 'Requested quantity exceeds available stock']);
    $stmt->close();
    exit;
}
$stmt->close();

// Update cart
$stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param('iii', $quantity, $cart_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart: ' . $conn->error]);
}
$stmt->close();
?>