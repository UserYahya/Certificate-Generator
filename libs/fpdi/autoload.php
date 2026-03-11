<?php
/**
 * FPDI Autoloader shim.
 * 
 * On cPanel: download FPDI from https://github.com/setasign/fpdi/releases
 * and place the src/ folder inside /libs/fpdi/src/
 * 
 * Or use the free FPDI version (for non-protected PDFs) which is included below.
 */

/**
 * This file lives at:  certgen/libs/fpdi/autoload.php
 * FPDI src/ must be at: certgen/libs/fpdi/src/
 *
 * Upload instructions:
 *   1. Download FPDI from https://github.com/setasign/fpdi/releases
 *   2. Extract the zip — you will see a folder called "src" inside it
 *   3. Upload that "src" folder to: certgen/libs/fpdi/src/
 *   Result: certgen/libs/fpdi/src/Fpdi.php should exist
 */

$fpdiSrc = __DIR__ . '/src/';

if (!is_dir($fpdiSrc)) {
    throw new \RuntimeException(
        'FPDI src/ folder not found at ' . $fpdiSrc . '. ' .
        'Download FPDI from github.com/setasign/fpdi/releases, extract it, ' .
        'and upload the src/ folder to libs/fpdi/src/. ' .
        'After upload, libs/fpdi/src/Fpdi.php must exist.'
    );
}

spl_autoload_register(function (string $class) use ($fpdiSrc) {
    $prefix = 'setasign\\Fpdi\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file     = $fpdiSrc . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
