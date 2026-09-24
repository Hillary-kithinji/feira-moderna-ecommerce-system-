<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // PHPMailer
require_once '../php/db.php';     // your DB connection

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid request method');

    $email = trim($_POST['email'] ?? '');
    if (!$email) throw new Exception('Email is required');

    // Check user exists
    $stmt = $conn->prepare("SELECT username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) throw new Exception('No account found with that email');

    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();

    // Generate token
    $token = bin2hex(random_bytes(16));
    $created_at = date('Y-m-d H:i:s');
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes', strtotime($created_at)));

    // Remove old tokens
    $stmt = $conn->prepare("DELETE FROM reset_tokens WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    // Insert new token with created_at and expiry
    $stmt = $conn->prepare("INSERT INTO reset_tokens (email, token, created_at, expiry) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $token, $created_at, $expiry);
    $stmt->execute();
    $stmt->close();

    // Reset link
    $reset_link = "http://localhost/shop/php/reset_password.php?token=$token";

    // Send email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your email';
    $mail->Password   = 'your app password ';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('hillarykithinji755@gmail.com', 'Utensil Shop');
    $mail->addAddress($email, $username);
    $mail->Subject = 'Password Reset Link - Utensil Shop';
    $mail->Body = "Hi $username,\n\nClick this link to reset your password:\n$reset_link\nLink expires in 15 minutes.\n\nIf you did not request this, ignore this email.\n\n- Utensil Shop";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Reset link sent to your email.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
