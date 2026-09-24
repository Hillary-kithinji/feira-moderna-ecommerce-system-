<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'feira_moderna');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);  // Make sure this is securely handled
    $otp_input = trim($_POST['otp']);

    // Check OTP
    $stmt = $conn->prepare("SELECT otp, expires_at FROM otps WHERE user_id = ? ORDER BY expires_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($otp_record = $result->fetch_assoc()) {
        $expires_at = $otp_record['expires_at'];
        $stored_otp = $otp_record['otp'];
        $now = date('Y-m-d H:i:s');

        if ($now > $expires_at) {
            echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
            exit;
        }

        if ($otp_input === $stored_otp) {
            // OTP matches, update user verification
            $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $update->bind_param("i", $user_id);
            if ($update->execute()) {
                // Delete OTP record or you can keep it for logs
                $conn->query("DELETE FROM otps WHERE user_id = $user_id");

                echo json_encode(['success' => true, 'message' => 'Verification successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update verification status']);
            }
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
