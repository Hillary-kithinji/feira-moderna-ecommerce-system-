<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'feira_moderna');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $otp_input = trim($_POST['otp'] ?? '');

    if (!$email || !$otp_input) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required']);
        exit;
    }

    // Fetch the latest OTP for this email
    $stmt = $conn->prepare("SELECT token, expiry FROM otps WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($otp_record = $result->fetch_assoc()) {
        $stored_otp = $otp_record['token'];
        $expires_at = $otp_record['expiry'];
        $now = date('Y-m-d H:i:s');

        if ($now > $expires_at) {
            echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
            exit;
        }

        if ($otp_input === $stored_otp) {
            // OTP matches, you can now allow password reset
            echo json_encode(['success' => true, 'message' => 'OTP verified successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect OTP']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No OTP record found']);
    }

    $stmt->close();
    $conn->close();
}
?>
