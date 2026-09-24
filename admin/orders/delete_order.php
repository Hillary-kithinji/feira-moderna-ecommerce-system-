<?php
require_once '../../php/db.php';
header('Content-Type: application/json');

try {
    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $order_id = $input['order_id'] ?? null;

    if (!$order_id) {
        throw new Exception('Order ID is required');
    }

    $conn->begin_transaction();

    // Delete order items first
    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $stmt->close();

    // Delete the order itself
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
