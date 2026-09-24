<?php
require_once '../../php/db.php';
header('Content-Type: application/json');

try {
    $order_id = $_GET['order_id'] ?? null;
    if (!$order_id) {
        throw new Exception('Order ID is required');
    }

    $stmt = $conn->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception('Order not found');
    }

    $stmt = $conn->prepare(
        "SELECT oi.*, p.name, pv.color, pv.size 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
         WHERE oi.order_id = ?"
    );
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'order' => $order, 'user' => ['username' => $order['username']], 'items' => $items]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>