<?php
// get_product_variants.php
require_once '../php/db.php';

header('Content-Type: application/json');

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'], $_POST['color'], $_POST['size'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = (int)$_POST['product_id'];
$color = $_POST['color'];
$size = $_POST['size'];

$stmt = $conn->prepare("SELECT id FROM product_variants WHERE product_id = ? AND color = ? AND size = ?");
$stmt->bind_param('iss', $product_id, $color, $size);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $variant = $result->fetch_assoc();
    echo json_encode(['success' => true, 'variant_id' => $variant['id']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Variant not found']);
}
$stmt->close();
?>