<?php
// log_helper.php
function log_message($message, $file = 'logs/product_operations.log') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>