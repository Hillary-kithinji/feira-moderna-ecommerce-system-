<?php
require_once '../../php/db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $product_id = $input['product_id'] ?? null;

    if (!$product_id) {
        throw new Exception('Product ID is required');
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>