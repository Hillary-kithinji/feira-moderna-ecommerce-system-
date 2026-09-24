<?php
require_once '../php/db.php';
header('Content-Type: application/json');

try {
    $query = "SELECT id, name FROM categories ORDER BY name";
    $result = $conn->query($query);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch categories: ' . $e->getMessage()
    ]);
}
?>