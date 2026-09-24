<?php
// place_order.php
require_once '../php/db.php';
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to place an order');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    $user_id = $_SESSION['user_id'];
    $items = $input['items'] ?? [];

    if (empty($items)) {
        throw new Exception('Cart is empty');
    }

    // Fetch user info including phone
    $stmt = $conn->prepare("SELECT username AS name, email, phone FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new Exception('User not found');
    }

    $total_amount = 0;
    $validated_items = [];
    foreach ($items as $item) {
        $stmt = $conn->prepare(
            "SELECT p.name, p.selling_price, p.stock, pv.color, pv.size, pv.additional_price
             FROM products p
             LEFT JOIN product_variants pv ON pv.id = ?
             WHERE p.id = ?"
        );
        $variant_id = $item['variant_id'] ?? null;
        $stmt->bind_param('ii', $variant_id, $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception("Product not found: {$item['product_id']}");
        }

        if ($item['quantity'] > $product['stock']) {
            throw new Exception("Insufficient stock for product: {$product['name']}");
        }

        $price = $product['selling_price'] + ($product['additional_price'] ?? 0);
        $total_amount += $price * $item['quantity'];
        $validated_items[] = [
            'cart_id' => $item['cart_id'],
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'quantity' => $item['quantity'],
            'name' => $product['name'],
            'price' => $price,
            'color' => $item['color'] ?? $product['color'] ?? 'N/A',
            'size' => $item['size'] ?? $product['size'] ?? 'N/A',
            'stock' => $product['stock']
        ];
    }

    $conn->begin_transaction();

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Pending')");
    $stmt->bind_param('id', $user_id, $total_amount);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // Insert order items and update stock
    foreach ($validated_items as $item) {
        $stmt = $conn->prepare(
            "INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
             VALUES (?, ?, ?, ?, ?)"
        );
        $variant_id = $item['variant_id'] ?: null;
        $stmt->bind_param('iiiii', $order_id, $item['product_id'], $variant_id, $item['quantity'], $item['price']);
        $stmt->execute();
        $stmt->close();

        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmt->execute();
        $stmt->close();

        if ($item['variant_id']) {
            $stmt = $conn->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");
            $stmt->bind_param('ii', $item['quantity'], $item['variant_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();

    // Prepare HTML for items table
    $items_html = '';
    foreach ($validated_items as $item) {
        $items_html .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['color']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['size']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>Ksh {$item['price']}</td>
            </tr>";
    }

    // Send emails
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'hillarykithinji755@gmail.com';
    $mail->Password = 'tixywahkufktpihh';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Customer email
    $mail->setFrom('hillarykithinji755@gmail.com', 'Utensil Shop');
    $mail->addAddress($user['email'], $user['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Thank You for Your Order!';
    $mail->Body = "
    <h2>Thank You for Your Order!</h2>
    <p>Dear {$user['name']},</p>
    <p>Your order has been successfully placed. Below are the details:</p>
    <table border='1' cellpadding='5' cellspacing='0'>
        <tr>
            <th>Product</th><th>Color</th><th>Size</th><th>Quantity</th><th>Price</th>
        </tr>
        {$items_html}
    </table>
    <p><strong>Total Amount:</strong> Ksh {$total_amount}</p>
    ";

    $mail->send();

    // Admin notifications
    $admin_emails = [];
    $result = $conn->query("SELECT email FROM admins");
    while ($row = $result->fetch_assoc()) {
        $admin_emails[] = $row['email'];
    }

    foreach ($admin_emails as $admin_email) {
        $mail->clearAddresses();
        $mail->addAddress($admin_email);
        $mail->Subject = 'New Order Placed';
        $mail->Body = "
        <h2>New Order Notification</h2>
        <p>Order ID: $order_id</p>
        <p>User: {$user['name']}</p>
        <p>Email: {$user['email']}</p>
        <p>Phone: {$user['phone']}</p>
        <p>Total Amount: Ksh {$total_amount}</p>
        <p>Order Items:</p>
        <table border='1' cellpadding='5' cellspacing='0'>
            <tr>
                <th>Product</th><th>Color</th><th>Size</th><th>Quantity</th><th>Price</th>
            </tr>
            {$items_html}
        </table>
        ";
        $mail->send();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    error_log('Order placement error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
}
?>
