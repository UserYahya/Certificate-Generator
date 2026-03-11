<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $files = glob(LOG_DIR . '*.json') ?: [];
    $logs  = [];
    foreach ($files as $f) {
        $logs[] = [
            'file'    => basename($f),
            'size'    => filesize($f),
            'modified'=> date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
    usort($logs, fn($a,$b) => strcmp($b['modified'], $a['modified']));
    echo json_encode(['success' => true, 'logs' => $logs]);
    exit;
}

if ($action === 'read') {
    $file = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $_GET['file'] ?? '');
    $path = LOG_DIR . $file;
    if (!$file || !file_exists($path)) {
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => json_decode(file_get_contents($path), true)]);
    exit;
}

if ($action === 'delete') {
    $file = preg_replace('/[^a-zA-Z0-9_\-.]/', '', $_POST['file'] ?? '');
    $path = LOG_DIR . $file;
    if ($file && file_exists($path)) {
        unlink($path);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'File not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
