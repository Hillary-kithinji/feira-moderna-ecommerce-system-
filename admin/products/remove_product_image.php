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

    log_message("Starting remove_product_image.php");
    $input = json_decode(file_get_contents('php://input'), true);
    log_message("Received input: " . json_encode($input));

    $product_id = $input['product_id'] ?? null;
    $image_path = $input['image_path'] ?? null;

    if (!$product_id || !$image_path) {
        log_message("Validation failed: Missing product_id or image_path");
        throw new Exception('Product ID and image path are required');
    }

    log_message("Validated input: product_id=$product_id, image_path=$image_path");

    log_message("Fetching existing images for product ID: $product_id");
    $stmt = $conn->prepare("SELECT images FROM products WHERE id = ?");
    if (!$stmt) {
        log_message("Prepare failed: " . $conn->error);
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $stmt->bind_param('i', $product_id);
    if (!$stmt->execute()) {
        log_message("Fetch images failed: " . $stmt->error);
        throw new Exception('Failed to fetch images: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $existing_images = $result->fetch_assoc()['images'] ?? '';
    $stmt->close();
    log_message("Existing images: $existing_images");

    $image_array = $existing_images ? explode(',', $existing_images) : [];
    $image_array = array_filter($image_array, fn($img) => $img !== $image_path);
    $new_images = implode(',', $image_array);
    log_message("New images after removal: $new_images");

    log_message("Updating product images in database");
    $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
    if (!$stmt) {
        log_message("Prepare failed for update: " . $conn->error);
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $stmt->bind_param('si', $new_images, $product_id);
    if (!$stmt->execute()) {
        log_message("Update images failed: " . $stmt->error);
        throw new Exception('Failed to update images: ' . $stmt->error);
    }
    $stmt->close();
    log_message("Images updated successfully");

    if (file_exists($image_path)) {
        log_message("Deleting image file: $image_path");
        if (!unlink($image_path)) {
            log_message("Failed to delete image file: $image_path");
            throw new Exception('Failed to delete image file');
        }
        log_message("Image file deleted successfully");
    } else {
        log_message("Image file not found: $image_path");
    }

    log_message("Image removed successfully");
    echo json_encode(['success' => true, 'message' => 'Image removed successfully', 'images' => $new_images]);
} catch (Exception $e) {
    log_message("Error occurred: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>