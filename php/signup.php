<?php
session_start();
header('Content-Type: application/json');

require_once '../php/db.php'; // Your database connection

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and sanitize input
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$phone || !$password) {
        throw new Exception('All fields are required');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    $stmt->close();

    // Check if phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Phone number already exists']);
        exit;
    }
    $stmt->close();

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed (insert): " . $conn->error);
    }

    $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed (insert): " . $stmt->error);
    }

    $user_id = $stmt->insert_id;
    $stmt->close();

    // Set session for logged-in user
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $name;

    echo json_encode([
        'success' => true,
        'message' => 'Signup successful. You can now log in.',
        'user_id' => $user_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Signup failed: ' . $e->getMessage()
    ]);
}
?>