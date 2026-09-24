<?php
require_once '../../php/db.php';
header('Content-Type: application/json');

try {
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;

    if (!$start_date || !$end_date) {
        throw new Exception('Start and end dates are required');
    }

    $stmt = $conn->prepare(
        "SELECT DATE(created_at) as date, SUM(total_amount) as total_amount, COUNT(*) as order_count 
         FROM orders 
         WHERE created_at BETWEEN ? AND ? AND status = 'delivered' 
         GROUP BY DATE(created_at) 
         ORDER BY created_at"
    );
    $end_date = date('Y-m-d', strtotime($end_date . ' +1 day'));
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'sales' => $sales]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>