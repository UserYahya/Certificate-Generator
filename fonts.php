<?php
/**
 * fonts.php — Font management API
 * GET  ?action=list              → list all available fonts
 * POST action=upload             → upload a new TTF/OTF font
 * POST action=delete&file=x.ttf → delete a font
 */
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$fontsDir = FONTS_DIR . 'custom/';
if (!is_dir($fontsDir)) @mkdir($fontsDir, 0755, true);

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

/* ── built-in bundled fonts (always present) ─────────────── */
$bundled = [
    ['key' => 'sans-bold',   'label' => 'Sans Serif (Default)',  'file' => 'FreeSansBold.ttf',   'bundled' => true],
    ['key' => 'serif-bold',  'label' => 'Serif (Default)',       'file' => 'FreeSerifBold.ttf',  'bundled' => true],
    ['key' => 'mono-bold',   'label' => 'Monospace (Default)',   'file' => 'FreeMonoBold.ttf',   'bundled' => true],
];

/* ── LIST ─────────────────────────────────────────────────── */
if ($action === 'list') {
    $fonts = [];

    // Always add bundled fonts first (if files exist)
    foreach ($bundled as $b) {
        if (file_exists($fontsDir . $b['file'])) {
            $fonts[] = $b;
        }
    }

    // Scan for user-uploaded fonts
    $bundledFiles = array_column($bundled, 'file');
    foreach (glob($fontsDir . '*.{ttf,otf,TTF,OTF}', GLOB_BRACE) as $path) {
        $file = basename($path);
        if (in_array($file, $bundledFiles, true)) continue; // skip bundled, already listed
        $key   = preg_replace('/\.[^.]+$/', '', $file); // strip extension
        $label = str_replace(['-', '_', '.ttf', '.otf'], [' ', ' ', '', ''], $file);
        $label = ucwords(strtolower($label));
        $fonts[] = [
            'key'     => $key,
            'label'   => $label,
            'file'    => $file,
            'bundled' => false,
            'size'    => filesize($path),
        ];
    }

    echo json_encode(['success' => true, 'fonts' => $fonts]);
    exit;
}

/* ── UPLOAD ───────────────────────────────────────────────── */
if ($action === 'upload') {
    if (!isset($_FILES['font']) || $_FILES['font']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . ($_FILES['font']['error'] ?? 'no file')]);
        exit;
    }

    $origName = $_FILES['font']['name'];
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['ttf', 'otf'], true)) {
        echo json_encode(['success' => false, 'error' => 'Only .ttf and .otf files are allowed.']);
        exit;
    }

    // Sanitize filename
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($origName));
    if (!$safeName) $safeName = 'font_' . time() . '.' . $ext;

    $dest = $fontsDir . $safeName;
    if (!move_uploaded_file($_FILES['font']['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'error' => 'Could not save font file. Check fonts/custom/ permissions (chmod 755).']);
        exit;
    }

    // Quick validation: try to use it with GD
    if (function_exists('imagettfbbox')) {
        $test = @imagettfbbox(12, 0, $dest, 'Test');
        if ($test === false) {
            @unlink($dest);
            echo json_encode(['success' => false, 'error' => 'Font file appears invalid or corrupt. Please upload a valid TTF/OTF.']);
            exit;
        }
    }

    $key   = preg_replace('/\.[^.]+$/', '', $safeName);
    $label = ucwords(str_replace(['-', '_'], ' ', $key));

    echo json_encode([
        'success' => true,
        'font'    => ['key' => $key, 'label' => $label, 'file' => $safeName, 'bundled' => false],
    ]);
    exit;
}

/* ── DELETE ───────────────────────────────────────────────── */
if ($action === 'delete') {
    $file = basename($_POST['file'] ?? '');
    if (!$file) { echo json_encode(['success' => false, 'error' => 'No file specified.']); exit; }

    // Prevent deleting bundled fonts
    $bundledFiles = array_column($bundled, 'file');
    if (in_array($file, $bundledFiles, true)) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete bundled fonts.']); exit;
    }

    $path = $fontsDir . $file;
    if (!file_exists($path)) { echo json_encode(['success' => false, 'error' => 'File not found.']); exit; }

    @unlink($path);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
