<?php
function debug_to_file($message, $data = null) {
    $log_dir = __DIR__ . '/../logs';
    $debug_file = $log_dir . '/debug.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        // Try to create the directory with full permissions
        if (!@mkdir($log_dir, 0777, true)) {
            // If we can't create the directory, silently fail
            return false;
        }
        // Try to set directory permissions after creation
        @chmod($log_dir, 0777);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $log_message .= ": " . print_r($data, true);
    }
    $log_message .= PHP_EOL;
    
    // Try to write to log file, suppress warnings if it fails
    @file_put_contents($debug_file, $log_message, FILE_APPEND);
    return true;
}

