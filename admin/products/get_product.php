<?php
require_once '../../php/db.php';
header('Content-Type: application/json');

try {
    $product_id = $_GET['product_id'] ?? null;
    if (!$product_id) {
        throw new Exception('Product ID is required');
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        throw new Exception('Product not found');
    }

    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'product' => $product, 'variants' => $variants]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>