<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';

$file = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $_GET['file'] ?? '');
if (!$file) { http_response_code(400); exit('Invalid file.'); }

$path = TEMP_DIR . $file;
if (!file_exists($path)) { http_response_code(404); exit('File not found.'); }

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
readfile($path);

// Cleanup after download
register_shutdown_function(function() use ($path) {
    if (file_exists($path)) unlink($path);
});
