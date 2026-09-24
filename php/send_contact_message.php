<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();
header('Content-Type: application/json');
ob_start(); // Ensure no accidental output breaks JSON

require '../vendor/autoload.php';
require_once '../php/db.php';

// Path to log file (adjust if needed, make sure the directory is writable by PHP)
$logFile = __DIR__ . '/process.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

logMessage('=== New Contact Form Submission Started ===');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and sanitize input
    $email   = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    logMessage("Received input - Email: $email, Message length: " . strlen($message));

    if (!$email || !$message) {
        throw new Exception('All fields are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Insert message into database
    $stmt = $conn->prepare("INSERT INTO contact_messages (email, message) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $email, $message);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $message_id = $stmt->insert_id;
    $stmt->close();

    logMessage("Database insert successful - Message ID: $message_id");

    // Send email notification to admin
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'hillarykithinji755@gmail.com';
        $mail->Password   = 'tixywahkufktpihh'; // REPLACE WITH FRESH APP PASSWORD!
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Optional: Enable debug temporarily to log SMTP conversation
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) { logMessage("SMTP Debug: $str"); };

        $mail->setFrom('hillarykithinji755@gmail.com', 'Feira Moderna Contact Form');
        $mail->addAddress('kithinjihilary@gmail.com', 'Feira Moderna Admin');
        $mail->addReplyTo($email);

        $mail->Subject = 'New Contact Message from Feira Moderna';
        $mail->Body    = "From: $email\n\nMessage:\n$message";

        $mail->send();

        logMessage('Email sent successfully');

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Message sent and saved successfully!',
            'message_id' => $message_id
        ]);

    } catch (Exception $e) {
        logMessage('Email failed: ' . $mail->ErrorInfo);

        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Message saved, but email failed: ' . $mail->ErrorInfo
        ]);
    }

} catch (Exception $e) {
    logMessage('Processing failed: ' . $e->getMessage());

    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Message submission failed: ' . $e->getMessage()
    ]);
}

logMessage('=== Submission Ended ===' . PHP_EOL);
exit();

?>
