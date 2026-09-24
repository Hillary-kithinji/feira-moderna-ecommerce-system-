<?php
session_start();
require_once '../php/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Check if product exists
$stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    $stmt->close();
    exit;
}
$stmt->close();

// Insert review
$stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, username, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param('iisis', $product_id, $user_id, $username, $rating, $comment);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}

$conn->close();
?>