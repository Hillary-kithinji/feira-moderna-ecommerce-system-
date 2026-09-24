<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../../php/db.php';
require_once '../log_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Start output buffering
ob_start();

try {
    // Create logs directory
    $log_dir = 'logs/';
    if (!is_dir($log_dir) && !mkdir($log_dir, 0777, true)) {
        throw new Exception('Failed to create logs directory');
    }

    // Create images directory
    $upload_dir = 'images/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
        throw new Exception('Failed to create images directory');
    }

    log_message("Starting update_product.php");
    log_message("Received POST data: " . json_encode($_POST));
    log_message("Received FILES data: " . json_encode($_FILES));

    // Validate input
    $id            = $_POST['editProductId'] ?? null;
    $name          = $_POST['editProductName'] ?? '';
    $category_id   = $_POST['editProductCategory'] ?? null;
    $stock         = $_POST['editProductStock'] ?? null;
    $buying_price  = $_POST['editProductBuyingPrice'] ?? null;
    $selling_price = $_POST['editProductSellingPrice'] ?? null;
    $description   = $_POST['editProductDescription'] ?? '';
    $variants_json = $_POST['variants'] ?? '[]';

    // Decode variants
    $variants = json_decode($variants_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid variants JSON: ' . json_last_error_msg());
    }

    // Validate required fields
    if (!$id || !$name || !$category_id || $stock === null || $buying_price === null || $selling_price === null) {
        throw new Exception('Required fields are missing');
    }

    // Type casting
    $stock         = (int)$stock;
    $buying_price  = (float)$buying_price;
    $selling_price = (float)$selling_price;

    // Existing images
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_images = $result->fetch_assoc()['images'] ?? '';
    $stmt->close();

    $image_paths = $existing_images ? explode(',', $existing_images) : [];

    // Handle file uploads
    if (isset($_FILES['productImages']) && $_FILES['productImages']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        foreach ($_FILES['productImages']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['productImages']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['productImages']['name'][$key], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    throw new Exception('Invalid image format: ' . $_FILES['productImages']['name'][$key]);
                }
                $filename = uniqid('product_') . '.' . $ext;
                $dest = $upload_dir . $filename;
                if (move_uploaded_file($tmp_name, $dest)) {
                    $image_paths[] = $dest;
                } else {
                    throw new Exception('Failed to upload image: ' . $_FILES['productImages']['name'][$key]);
                }
            }
        }
    }

    // Begin transaction
    $conn->begin_transaction();

    // Update product
    $stmt = $conn->prepare(
        "UPDATE products 
         SET name = ?, stock = ?, buying_price = ?, selling_price = ?, description = ?, images = ?, category_id = ? 
         WHERE id = ?"
    );
    $images = implode(',', $image_paths);
    $stmt->bind_param('siddssii', $name, $stock, $buying_price, $selling_price, $description, $images, $category_id, $id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product: ' . $stmt->error);
    }
    $stmt->close();

    // Handle variants
    if (empty($variants)) {
        $stmt = $conn->prepare("DELETE FROM product_variants WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Fetch existing variants
        $stmt = $conn->prepare("SELECT id, color, size FROM product_variants WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_variants = [];
        while ($row = $result->fetch_assoc()) {
            $existing_variants[] = $row;
        }
        $stmt->close();

        // Process variants
        foreach ($variants as $variant) {
            $color = $variant['color'] ?? null;
            $size = $variant['size'] ?? null;
            $additional_price = $variant['additional_price'] ?? 0;
            $variant_stock = $variant['stock'] ?? 0;

            $existing_variant = null;
            foreach ($existing_variants as $ev) {
                if ($ev['color'] == $color && $ev['size'] == $size) {
                    $existing_variant = $ev;
                    break;
                }
            }

            if ($existing_variant) {
                $stmt = $conn->prepare("UPDATE product_variants SET additional_price = ?, stock = ? WHERE id = ?");
                $stmt->bind_param('dii', $additional_price, $variant_stock, $existing_variant['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO product_variants (product_id, color, size, additional_price, stock) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('issdi', $id, $color, $size, $additional_price, $variant_stock);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear output buffer and send JSON
    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    exit;
} catch (Exception $e) {
    if ($conn && $conn->errno === 0) {
        $conn->rollback();
    }
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
