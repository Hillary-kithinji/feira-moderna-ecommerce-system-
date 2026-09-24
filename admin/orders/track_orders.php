<?php
// admin/orders/track_orders.php
require_once '../../php/db.php';   // Correct path from admin/orders/ to php/db.php
session_start();

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

try {
    $sql = "
        SELECT 
            o.id,
            o.total,
            o.status,
            DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i') as created_at
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}