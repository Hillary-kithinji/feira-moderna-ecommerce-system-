<?php
require 'db.php'; // Your DB connection
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get POST data
$token = trim($_POST['token'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if (!$token || !$new_password || !$confirm_password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Verify token
$stmt = $conn->prepare("SELECT email, expiry FROM reset_tokens WHERE token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (strtotime($row['expiry']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Token expired. Please request a new password reset.']);
        exit;
    }
    $email = $row['email'];
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid token. Please request a new password reset.']);
    exit;
}
$stmt->close();

// Update password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);

if ($stmt->execute()) {
    // Delete the token
    $stmt = $conn->prepare("DELETE FROM reset_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Password reset successful. You can now login.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
}

$conn->close();
