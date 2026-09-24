<?php
$host = 'localhost';
$dbname = 'feira_moderna';
$username = 'root'; // Default XAMPP username
$password = ''; // Default XAMPP password

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>