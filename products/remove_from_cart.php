<?php
// remove_from_cart.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to remove items from cart']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart_id = (int)$_POST['cart_id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $cart_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item from cart: ' . $conn->error]);
}
$stmt->close();
?>