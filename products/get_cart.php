<?php
// get_cart.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view cart']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT c.id, c.product_id, c.variant_id, c.quantity, p.name, p.selling_price, p.images, p.stock, pv.color, pv.size, pv.additional_price
          FROM cart c
          JOIN products p ON c.product_id = p.id
          LEFT JOIN product_variants pv ON c.variant_id = pv.id
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
    exit;
}

$cart = [];
$total = 0;
while ($row = $result->fetch_assoc()) {
    $item_total = ($row['selling_price'] + ($row['additional_price'] ?? 0)) * $row['quantity'];
    $total += $item_total;

    // Split images and take only the first one
    $images = array_map('trim', explode(',', $row['images'] ?? ''));
    $first_image = !empty($images[0]) ? $images[0] : null;

    $cart[] = [
        'cart_id'          => $row['id'],
        'product_id'       => $row['product_id'],
        'name'             => $row['name'],
        'quantity'         => $row['quantity'],
        'price'            => $row['selling_price'],
        'image'            => $first_image,
        'stock'            => $row['stock'],
        'color'            => $row['color'],
        'size'             => $row['size'],
        'additional_price' => $row['additional_price'] ?? 0,
        'item_total'       => $item_total
    ];
}

echo json_encode(['success' => true, 'cart' => $cart, 'total' => $total]);
$stmt->close();
?>