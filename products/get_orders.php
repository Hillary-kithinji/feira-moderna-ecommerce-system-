<?php
// get_orders.php
require_once '../php/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view orders']);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$query = "SELECT o.id, o.total_amount, o.status, o.created_at,
                 oi.product_id, oi.variant_id, oi.quantity, oi.price,
                 p.name, pv.color, pv.size
          FROM orders o
          JOIN order_items oi ON o.id = oi.order_id
          JOIN products p ON oi.product_id = p.id
          LEFT JOIN product_variants pv ON oi.variant_id = pv.id
          WHERE o.user_id = ?
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
    $stmt->close();
    exit;
}

$orders = [];
$current_order_id = null;
while ($row = $result->fetch_assoc()) {
    if ($current_order_id !== $row['id']) {
        $orders[] = [
            'id' => $row['id'],
            'total_amount' => $row['total_amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
        $current_order_id = $row['id'];
    }
    $orders[array_key_last($orders)]['items'][] = [
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'color' => $row['color'],
        'size' => $row['size']
    ];
}

echo json_encode(['success' => true, 'orders' => $orders]);
$stmt->close();
?>