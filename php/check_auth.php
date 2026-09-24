<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'is_authenticated' => true]);
} else {
    echo json_encode(['success' => true, 'is_authenticated' => false]);
}
exit();
?>