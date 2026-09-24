<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once '../../php/db.php';
require_once '../log_helper.php';
header('Content-Type: application/json');

try {
    // Create logs directory
    $log_dir = 'logs/';
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception('Failed to create logs directory');
        }
    }
    // Create images directory
    $upload_dir = 'images/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create images directory');
        }
    }

    log_message("Starting add_product.php");
    log_message("Received POST data: " . json_encode($_POST));
    log_message("Received FILES data: " . json_encode($_FILES));

    $name = $_POST['productName'] ?? '';
    $category_id = $_POST['productCategory'] ?? null;
    $buying_price = $_POST['productBuyingPrice'] ?? 0;
    $selling_price = $_POST['productSellingPrice'] ?? 0;
    $description = $_POST['productDescription'] ?? '';
    $variants = json_decode($_POST['variants'] ?? '[]', true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        log_message("JSON decode error for variants: " . json_last_error_msg());
        throw new Exception('Invalid variants JSON: ' . json_last_error_msg());
    }

    if (!$name || !$category_id || !$selling_price) {
        log_message("Validation failed: Missing required fields (name: '$name', category_id: '$category_id', selling_price: '$selling_price')");
        throw new Exception('Required fields (name, category, selling price) are missing');
    }

    // Calculate total stock from variants
    // Stock handling
if (!empty($variants)) {
    // Sum stock from variants
    $stock = 0;
    foreach ($variants as $variant) {
        $variant_stock = $variant['stock'] ?? 0;
        $stock += (int)$variant_stock;
    }
    log_message("Calculated total stock from variants: $stock");
} else {
    // Use manual stock input if no variants
    $stock = isset($_POST['productStock']) ? (int)$_POST['productStock'] : 0;
    log_message("Using manual stock input: $stock");
}

    log_message("Calculated total stock from variants: $stock");

    log_message("Validated input: name='$name', category_id=$category_id, stock=$stock, buying_price=$buying_price, selling_price=$selling_price");

    $image_paths = [];
    if (isset($_FILES['productImages']) && $_FILES['productImages']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        log_message("Processing image uploads");
        foreach ($_FILES['productImages']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['productImages']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['productImages']['name'][$key], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    log_message("Invalid image extension for file: {$_FILES['productImages']['name'][$key]}");
                    throw new Exception('Invalid image format: ' . $_FILES['productImages']['name'][$key]);
                }
                $filename = uniqid('product_') . '.' . $ext;
                $dest = $upload_dir . $filename;
                log_message("Attempting to move uploaded file to: $dest");
                if (move_uploaded_file($tmp_name, $dest)) {
                    $image_paths[] = $dest;
                    log_message("Successfully uploaded image: $dest");
                } else {
                    log_message("Failed to move uploaded file: {$_FILES['productImages']['name'][$key]}");
                    throw new Exception('Failed to upload image: ' . $_FILES['productImages']['name'][$key]);
                }
            } elseif ($_FILES['productImages']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                log_message("Upload error for file {$_FILES['productImages']['name'][$key]}: " . $_FILES['productImages']['error'][$key]);
                throw new Exception('Image upload error: ' . $_FILES['productImages']['name'][$key]);
            }
        }
    } else {
        log_message("No images uploaded");
    }

    log_message("Starting database transaction");
    $conn->begin_transaction();

    log_message("Inserting product into database");
    $stmt = $conn->prepare(
        "INSERT INTO products (name, stock, buying_price, selling_price, description, images, category_id) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        log_message("Prepare failed: " . $conn->error);
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $images = implode(',', $image_paths);
    $stmt->bind_param('siddssi', $name, $stock, $buying_price, $selling_price, $description, $images, $category_id);
    if (!$stmt->execute()) {
        log_message("Product insert failed: " . $stmt->error);
        throw new Exception('Failed to insert product: ' . $stmt->error);
    }
    $product_id = $conn->insert_id;
    $stmt->close();
    log_message("Product inserted with ID: $product_id");

    log_message("Processing variants: " . json_encode($variants));
    foreach ($variants as $variant) {
        $color = $variant['color'] ?? null;
        $size = $variant['size'] ?? null;
        $additional_price = $variant['additional_price'] ?? 0;
        $variant_stock = $variant['stock'] ?? 0;

        log_message("Inserting variant: color='$color', size='$size', additional_price=$additional_price, stock=$variant_stock");
        $stmt = $conn->prepare(
            "INSERT INTO product_variants (product_id, color, size, additional_price, stock) 
             VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            log_message("Prepare failed for variant: " . $conn->error);
            throw new Exception('Database prepare error for variant: ' . $conn->error);
        }
        $stmt->bind_param('issdi', $product_id, $color, $size, $additional_price, $variant_stock);
        if (!$stmt->execute()) {
            log_message("Variant insert failed: " . $stmt->error);
            throw new Exception('Failed to insert variant: ' . $stmt->error);
        }
        $stmt->close();
        log_message("Variant inserted successfully");
    }

    log_message("Committing transaction");
    $conn->commit();
    log_message("Product and variants added successfully");
    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
} catch (Exception $e) {
    log_message("Error occurred: " . $e->getMessage());
    $conn->rollback();
    foreach ($image_paths as $path) {
        if (file_exists($path)) {
            log_message("Cleaning up uploaded image: $path");
            unlink($path);
        }
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>