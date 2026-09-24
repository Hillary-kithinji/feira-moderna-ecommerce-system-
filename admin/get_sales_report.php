<?php
require_once '../php/db.php'; 
header('Content-Type: application/json');

$startDate = $_GET['start'] ?? null;
$endDate = $_GET['end'] ?? null;

if (!$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Missing dates']);
    exit;
}

// Fetch total sales (sum) + order count per day for ALL orders
$sql = "
    SELECT DATE(created_at) as order_date,
           SUM(total_amount) as total_sales,
           COUNT(*) as order_count
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY order_date ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'date' => $row['order_date'],
        'total_sales' => (float)$row['total_sales'],
        'order_count' => (int)$row['order_count']
    ];
}

echo json_encode(['success' => true, 'sales' => $data]);
?>
