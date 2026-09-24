<?php
session_start();
require_once '../php/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check users table
    $query = "SELECT id, username, password FROM users WHERE email = '$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            $_SESSION['is_admin'] = false; // Regular user
            header('Location: ../index.php'); // Redirect to homepage
            exit();
        } else {
            $_SESSION['error'] = 'Invalid password or Email';
            header('Location: ../index.php?login=failed'); // Redirect with error
            exit();
        }
    } else {
        // Check admins table
        $query = "SELECT id, name, password FROM admins WHERE email = '$email'";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['username'] = $admin['name'];
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = true; // Admin user
                header('Location: ../admin/admin_dashboard.php'); // Redirect to admin dashboard
                exit();
            } else {
                $_SESSION['error'] = 'Invalid password';
                header('Location: ../index.php?login=failed'); // Redirect with error
                exit();
            }
        } else {
            $_SESSION['error'] = 'User not found';
            header('Location: ../index.php?login=failed'); // Redirect with error
            exit();
        }
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../index.php?login=failed'); // Redirect with error
    exit();
}

$conn->close();
?>
