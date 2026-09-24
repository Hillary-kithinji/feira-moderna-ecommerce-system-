<?php
// get_product_details.php
require_once '../php/db.php';

header('Content-Type: application/json');

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

$product_id = (int)$_GET['id'];
$query = "SELECT p.id, p.name, p.selling_price, p.stock, p.images, 
                 GROUP_CONCAT(DISTINCT pv.color) as colors, 
                 GROUP_CONCAT(DISTINCT pv.size) as sizes
          FROM products p
          LEFT JOIN product_variants pv ON p.id = pv.product_id
          WHERE p.id = ?
          GROUP BY p.id";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $product['colors'] = $product['colors'] ? explode(',', $product['colors']) : [];
    $product['sizes'] = $product['sizes'] ? explode(',', $product['sizes']) : [];
     $product['images'] = $product['images'] ? explode(',', $product['images']) : []; // <-- add this
    echo json_encode(['success' => true, 'product' => $product]);
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
}
$stmt->close();
?>