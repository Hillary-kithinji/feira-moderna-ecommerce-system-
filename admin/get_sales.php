<?php
require_once '../php/db.php'; // Adjust path to your db.php

header('Content-Type: application/json');

$filter_type = $_GET['filter_type'] ?? '';
$filter_value = $_GET['filter_value'] ?? '';

if (!$filter_type || !$filter_value || !in_array($filter_type, ['day', 'week', 'month'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid filter parameters']);
    exit;
}

$where = "o.status = 'delivered'";
$date = date('Y-m-d', strtotime($filter_value));

if ($filter_type === 'day') {
    $where .= " AND DATE(o.created_at) = ?";
    $params = [$date];
    $types = 's';
} elseif ($filter_type === 'week') {
    $start_date = date('Y-m-d', strtotime('monday this week', strtotime($date)));
    $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
    $where .= " AND o.created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $types = 'ss';
} elseif ($filter_type === 'month') {
    $start_date = date('Y-m-01', strtotime($date));
    $end_date = date('Y-m-t', strtotime($date));
    $where .= " AND o.created_at BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    $types = 'ss';
}

// Fetch total sales
$sql = "SELECT SUM(o.total_amount) as total_sales FROM orders o WHERE $where";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$total_sales = $result->fetch_assoc()['total_sales'] ?? 0;
$stmt->close();

// Fetch item details
$sql_items = "SELECT p.name, oi.quantity, (oi.quantity * oi.price) as item_total
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              JOIN orders o ON oi.order_id = o.id
              WHERE $where";
$stmt_items = $conn->prepare($sql_items);
if (!empty($params)) {
    $stmt_items->bind_param($types, ...$params);
}
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$items = [];
while ($row = $result_items->fetch_assoc()) {
    $items[] = [
        'name' => $row['name'],
        'quantity' => $row['quantity'],
        'item_total' => (float)$row['item_total']
    ];
}
$stmt_items->close();

echo json_encode([
    'success' => true,
    'total_sales' => (float)$total_sales,
    'items' => $items
]);
?>