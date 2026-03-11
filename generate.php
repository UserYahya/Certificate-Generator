<?php
/**
 * generate.php
 *
 * Generates certificate PDFs from image or PDF templates.
 *
 * Font system:
 *   - fontKey is either a bare filename stem (e.g. "SolaimanLipi") or one of
 *     the 3 bundled aliases ("sans-bold", "serif-bold", "mono-bold").
 *   - The TTF file is looked up directly from fonts/custom/.
 *   - Any TTF/OTF works — Latin, Bangla, Arabic, CJK, etc.
 *
 * For image templates: GD + imagettftext (supports all Unicode).
 * For PDF templates:   FPDI imports the page, then GD renders the text onto
 *                      a PNG overlay which is merged — same Unicode support.
 */

require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
set_time_limit(600);
ini_set('memory_limit', '512M');

function je(string $msg): void { echo json_encode(['success'=>false,'error'=>$msg]); exit; }

if (!extension_loaded('gd')) je('GD extension not enabled. Enable it in cPanel → Select PHP Version → Extensions.');
if (!file_exists(__DIR__ . '/libs/fpdf/fpdf.php')) je('FPDF missing. Upload fpdf/ folder to libs/fpdf/. See README.');
require_once __DIR__ . '/libs/fpdf/fpdf.php';

/* ── inputs ───────────────────────────────────────────────── */
$action     = $_POST['action']       ?? 'generate_zip';
$box        = [
    'x' => (float)($_POST['box_x'] ?? 0),
    'y' => (float)($_POST['box_y'] ?? 0),
    'w' => (float)($_POST['box_w'] ?? 0),
    'h' => (float)($_POST['box_h'] ?? 0),
];
$imgW       = (float)($_POST['img_w']        ?? 0);
$imgH       = (float)($_POST['img_h']        ?? 0);
$fontKey    = trim($_POST['font']            ?? 'sans-bold');
$colorHex   = trim($_POST['color']           ?? '#000000');
$fontSizePx = (float)($_POST['font_size_px'] ?? 0);

if ($box['w'] < 5 || $box['h'] < 3) je('Text box too small.');
if (!isset($_FILES['template']) || $_FILES['template']['error'] !== UPLOAD_ERR_OK)
    je('Template upload error (code '.($_FILES['template']['error']??'?').'). Check upload_max_filesize in php.ini.');
if (!isset($_FILES['names_csv']) || $_FILES['names_csv']['error'] !== UPLOAD_ERR_OK)
    je('CSV upload error.');

/* ── colour ───────────────────────────────────────────────── */
$hex = str_pad(ltrim($colorHex,'#'),6,'0');
$cR=hexdec(substr($hex,0,2)); $cG=hexdec(substr($hex,2,2)); $cB=hexdec(substr($hex,4,2));

/* ── CSV ──────────────────────────────────────────────────── */
{
    $fp = fopen($_FILES['names_csv']['tmp_name'],'r');
    $bom = fread($fp,3); if($bom!=="\xEF\xBB\xBF") rewind($fp);
    $rawHdr = fgetcsv($fp); if(!$rawHdr) je('CSV is empty.');
    $hdr=array_map(fn($h)=>strtolower(trim(str_replace(['"',"'","\r","\n","\xEF\xBB\xBF"],'',$h))),$rawHdr);
    $nameIdx=array_search('name',$hdr,true); $emailIdx=array_search('email',$hdr,true);
    if($nameIdx===false) je('CSV missing "Name" column. Found: '.implode(', ',array_map('trim',$rawHdr)));
    $recipients=[];
    while(($row=fgetcsv($fp))!==false){
        $name=trim($row[$nameIdx]??''); $email=($emailIdx!==false)?trim($row[$emailIdx]??''):'';
        if($name!=='') $recipients[]=['name'=>$name,'email'=>$email];
    }
    fclose($fp);
    if(empty($recipients)) je('No names found in CSV.');
}

/* ── template type ────────────────────────────────────────── */
$tmpPath=$_FILES['template']['tmp_name'];
$mime=mime_content_type($tmpPath);
$isPDF=($mime==='application/pdf');
$isImage=in_array($mime,['image/jpeg','image/png','image/jpg'],true);
if(!$isPDF&&!$isImage) je("Unsupported template type ($mime). Use JPG, PNG, or PDF.");

if($isPDF){
    if(!file_exists(__DIR__.'/libs/fpdi/autoload.php')) je('PDF templates need FPDI. See README. Or use JPG/PNG.');
    require_once __DIR__.'/libs/fpdi/autoload.php';
}

/* ════════════════════════════════════════════════════════════
   FONT RESOLVER
   Accepts either:
     • "sans-bold" / "serif-bold" / "mono-bold"  → bundled fonts
     • Any other string → treated as filename stem, looks for
       {key}.ttf or {key}.otf in fonts/custom/
   ════════════════════════════════════════════════════════════ */
$bundledFonts = [
    'sans-bold'  => 'FreeSansBold.ttf',
    'serif-bold' => 'FreeSerifBold.ttf',
    'mono-bold'  => 'FreeMonoBold.ttf',
    // legacy keys from older versions — map to bundled equivalents
    'helvetica'  => 'FreeSansBold.ttf',
    'times'      => 'FreeSerifBold.ttf',
    'courier'    => 'FreeMonoBold.ttf',
    'garamond'   => 'FreeSerifBold.ttf',
    'baskerville'=> 'FreeSerifBold.ttf',
];

function resolveTTF(string $key): ?string {
    global $bundledFonts;
    $dir = FONTS_DIR . 'custom/';

    // Check bundled map first
    if (isset($bundledFonts[$key])) {
        $p = $dir . $bundledFonts[$key];
        return file_exists($p) ? $p : null;
    }

    // Try as direct filename stem (user-uploaded font)
    foreach (['.ttf','.otf','.TTF','.OTF'] as $ext) {
        $p = $dir . $key . $ext;
        if (file_exists($p)) return $p;
    }

    // Last resort: try the key itself as a full filename
    $p = $dir . $key;
    if (file_exists($p)) return $p;

    return null;
}

/* ════════════════════════════════════════════════════════════
   GD TEXT RENDERER
   Draws $name into $canvas at the correct size and position.
   $baseFontSizePx = max font size (from box height).
   Shrinks only if this specific name is wider than box.
   Supports ALL Unicode including Bangla via imagettftext().
   ════════════════════════════════════════════════════════════ */
function drawTextGD(
    \GdImage $canvas,
    string   $name,
    float    $bx, float $by, float $bw, float $bh,
    string   $fontKey,
    int      $cR, int $cG, int $cB,
    float    $baseFontSizePx
): void {
    $ttfPath = resolveTTF($fontKey);
    $gdColor = imagecolorallocate($canvas, $cR, $cG, $cB);

    if ($ttfPath && function_exists('imagettfbbox') && function_exists('imagettftext')) {
        $fontSize = ($baseFontSizePx > 0) ? $baseFontSizePx : ($bh * 0.72);

        // Measure and shrink proportionally if this name overflows width
        $bbox = @imagettfbbox($fontSize, 0, $ttfPath, $name);
        if ($bbox !== false) {
            $tw = abs($bbox[4] - $bbox[0]);
            if ($tw > $bw * 0.97 && $tw > 0) {
                $fontSize *= ($bw * 0.97) / $tw;
            }
        }

        // Final measurement for exact positioning
        $bbox = @imagettfbbox($fontSize, 0, $ttfPath, $name);
        if (!$bbox) return;

        $tw       = abs($bbox[4] - $bbox[0]); // text width
        $ascent   = abs($bbox[7] - $bbox[1]); // full height (top to bottom)
        $baseline = abs($bbox[1]);             // descent below baseline

        // Center horizontally, center vertically in box
        $tx = (int)round($bx + ($bw - $tw) / 2);
        $ty = (int)round($by + $bh / 2 + $ascent / 2 - $baseline);

        imagettftext($canvas, $fontSize, 0, $tx, $ty, $gdColor, $ttfPath, $name);

    } else {
        // GD built-in font — tiny but always works; no Unicode support
        $bestFont = 5;
        foreach ([5,4,3,2,1] as $f) {
            if (imagefontwidth($f) * mb_strlen($name) <= $bw * 0.95) { $bestFont=$f; break; }
        }
        $fw=imagefontwidth($bestFont); $fh=imagefontheight($bestFont);
        imagestring($canvas, $bestFont,
            (int)($bx+($bw-$fw*mb_strlen($name))/2),
            (int)($by+($bh-$fh)/2),
            $name, $gdColor);
    }
}

/* ════════════════════════════════════════════════════════════
   IMAGE → PDF
   Wraps a GD image resource into a PDF via FPDF.
   ════════════════════════════════════════════════════════════ */
function gdImageToPDF(\GdImage $img, float $imgW, float $imgH): string {
    $tmpJpeg = tempnam(sys_get_temp_dir(), 'cert_') . '.jpg';
    if (!imagejpeg($img, $tmpJpeg, 95)) throw new \RuntimeException('GD could not save temp JPEG.');

    $PX2MM = 25.4 / 96;
    $docW  = $imgW * $PX2MM;
    $docH  = $imgH * $PX2MM;
    $pdf   = new FPDF(($docW > $docH ? 'L' : 'P'), 'mm', [$docW, $docH]);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->AddPage();
    $pdf->Image($tmpJpeg, 0, 0, $docW, $docH, 'JPEG');
    $result = $pdf->Output('S');
    @unlink($tmpJpeg);
    return $result;
}

/* ════════════════════════════════════════════════════════════
   MAKE CERTIFICATE — IMAGE TEMPLATE
   ════════════════════════════════════════════════════════════ */
function makeCertFromImage(
    string $tplPath, string $name,
    float $imgW, float $imgH, array $box,
    string $fontKey, int $cR, int $cG, int $cB,
    float $baseFontSizePx
): string {
    $mime   = mime_content_type($tplPath);
    $isPNG  = ($mime === 'image/png');
    $canvas = $isPNG ? imagecreatefrompng($tplPath) : imagecreatefromjpeg($tplPath);
    if (!$canvas) throw new \RuntimeException('GD failed to load template image.');

    // Ensure true-colour
    if (!imageistruecolor($canvas)) {
        $tc = imagecreatetruecolor((int)$imgW, (int)$imgH);
        imagecopyresampled($tc, $canvas, 0, 0, 0, 0, (int)$imgW, (int)$imgH, (int)$imgW, (int)$imgH);
        imagedestroy($canvas);
        $canvas = $tc;
    }

    drawTextGD($canvas, $name, $box['x'], $box['y'], $box['w'], $box['h'], $fontKey, $cR, $cG, $cB, $baseFontSizePx);
    $result = gdImageToPDF($canvas, $imgW, $imgH);
    imagedestroy($canvas);
    return $result;
}

/* ════════════════════════════════════════════════════════════
   MAKE CERTIFICATE — PDF TEMPLATE
   Strategy: use FPDI to get page size and render the template
   page as a background, then draw text as a GD overlay on a
   transparent PNG, merge both, and output via FPDF.
   This gives full Unicode/font support for PDF templates too.
   ════════════════════════════════════════════════════════════ */
function makeCertFromPDF(
    string $tplPath, string $name,
    float $canvasW, float $canvasH, array $box,
    string $fontKey, int $cR, int $cG, int $cB,
    float $baseFontSizePx
): string {
    if (!class_exists('\setasign\Fpdi\Fpdi'))
        throw new \RuntimeException('FPDI not loaded. Check libs/fpdi/src/Fpdi.php exists.');

    // Get PDF page dimensions
    $probe = new \setasign\Fpdi\Fpdi('P','pt');
    $probe->setSourceFile($tplPath);
    $tpl  = $probe->importPage(1);
    $sz   = $probe->getTemplateSize($tpl);
    $docW = (float)$sz['width'];   // pts
    $docH = (float)$sz['height'];  // pts

    // We render at 150 DPI for quality
    // 1 pt = 1/72 inch; at 150dpi: px = pt * 150/72
    $DPI   = 150;
    $PT2PX = $DPI / 72.0;
    $rW    = (int)round($docW * $PT2PX);
    $rH    = (int)round($docH * $PT2PX);

    // Scale box from canvas px → rasterized px
    $sx  = $rW / $canvasW;
    $sy  = $rH / $canvasH;
    $rbx = $box['x'] * $sx;
    $rby = $box['y'] * $sy;
    $rbw = $box['w'] * $sx;
    $rbh = $box['h'] * $sy;
    $rFontSz = $baseFontSizePx * $sx; // scale font size too

    // Rasterize PDF page to PNG using Imagick if available, else use GS, else blank canvas
    $bgCanvas = null;

    if (class_exists('Imagick')) {
        try {
            $im = new \Imagick();
            $im->setResolution($DPI, $DPI);
            $im->readImage($tplPath . '[0]');
            $im->setImageFormat('png');
            $pngData = $im->getImageBlob();
            $im->clear();
            $bgCanvas = imagecreatefromstring($pngData);
            // Resize to our target resolution if needed
            if (imagesx($bgCanvas) !== $rW || imagesy($bgCanvas) !== $rH) {
                $resized = imagecreatetruecolor($rW, $rH);
                imagecopyresampled($resized, $bgCanvas, 0,0,0,0, $rW,$rH, imagesx($bgCanvas),imagesy($bgCanvas));
                imagedestroy($bgCanvas);
                $bgCanvas = $resized;
            }
        } catch (\Throwable $e) {
            $bgCanvas = null;
        }
    }

    if (!$bgCanvas) {
        // No Imagick — create blank white canvas and use FPDI text overlay
        // Fall back to FPDI + Helvetica (no Unicode) if no rasterizer
        return makeCertFromPDF_fpdiOnly($tplPath, $name, $canvasW, $canvasH, $box,
            $fontKey, $cR, $cG, $cB, $baseFontSizePx);
    }

    // Draw the name onto the rasterized background
    drawTextGD($bgCanvas, $name, $rbx, $rby, $rbw, $rbh, $fontKey, $cR, $cG, $cB, $rFontSz);

    // Wrap as PDF at the original PDF dimensions
    $tmpJpeg = tempnam(sys_get_temp_dir(), 'cert_') . '.jpg';
    imagejpeg($bgCanvas, $tmpJpeg, 95);
    imagedestroy($bgCanvas);

    $pdf = new FPDF(($docW > $docH ? 'L' : 'P'), 'pt', [$docW, $docH]);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0,0,0);
    $pdf->AddPage();
    $pdf->Image($tmpJpeg, 0, 0, $docW, $docH, 'JPEG');
    $result = $pdf->Output('S');
    @unlink($tmpJpeg);
    return $result;
}

/* ── FPDI-only fallback (PDF template, no Imagick) ───────── */
function makeCertFromPDF_fpdiOnly(
    string $tplPath, string $name,
    float $canvasW, float $canvasH, array $box,
    string $fontKey, int $cR, int $cG, int $cB,
    float $baseFontSizePx
): string {
    $pdf = new \setasign\Fpdi\Fpdi('P','pt');
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0,0,0);
    $pdf->setSourceFile($tplPath);
    $tpl  = $pdf->importPage(1);
    $sz   = $pdf->getTemplateSize($tpl);
    $docW = (float)$sz['width'];
    $docH = (float)$sz['height'];
    $pdf->AddPage(($docW>$docH?'L':'P'),[$docW,$docH]);
    $pdf->useTemplate($tpl,0,0,$docW,$docH);

    $sx=$docW/$canvasW; $sy=$docH/$canvasH;
    $bx=$box['x']*$sx; $by=$box['y']*$sy; $bw=$box['w']*$sx; $bh=$box['h']*$sy;
    // px → pt: at 96dpi, 1px=0.75pt; scaled by $sx
    $ptSize = ($baseFontSizePx > 0) ? ($baseFontSizePx * $sx) : ($bh * 0.72);

    $pdf->SetFont('Helvetica','B');
    $pdf->SetTextColor($cR,$cG,$cB);
    $pdf->SetFontSize($ptSize);
    if ($pdf->GetStringWidth($name) > $bw * 0.97) {
        $ptSize *= $bw * 0.97 / $pdf->GetStringWidth($name);
        $pdf->SetFontSize($ptSize);
    }
    $lineH = $ptSize * 1.2;
    $pdf->SetXY($bx, $by + ($bh-$lineH)/2);
    $pdf->Cell($bw, $lineH, $name, 0, 0, 'C');
    return $pdf->Output('S');
}

/* ── dispatcher ───────────────────────────────────────────── */
function makeCert(
    string $name, string $tplPath, bool $isPDF,
    float $imgW, float $imgH, array $box,
    string $fontKey, int $cR, int $cG, int $cB, float $fontSizePx
): string {
    if ($isPDF)
        return makeCertFromPDF($tplPath, $name, $imgW, $imgH, $box, $fontKey, $cR, $cG, $cB, $fontSizePx);
    return makeCertFromImage($tplPath, $name, $imgW, $imgH, $box, $fontKey, $cR, $cG, $cB, $fontSizePx);
}

function safeFile(string $n): string {
    return trim(str_replace(' ','_',preg_replace('/[^a-zA-Z0-9 _\-]/','', $n))) ?: 'certificate';
}

foreach([TEMP_DIR,LOG_DIR] as $d){ if(!is_dir($d)) @mkdir($d,0755,true); }
if(!is_writable(TEMP_DIR)) je('temp/ not writable. chmod 755 temp/');

/* ════════════════════════════════════════════════════════════
   ACTION: generate_zip
   ════════════════════════════════════════════════════════════ */
if ($action === 'generate_zip') {
    if (!class_exists('ZipArchive')) je('ZipArchive not available. Enable zip in cPanel PHP extensions.');
    $zipName='Certificates_'.date('Ymd_His').'.zip';
    $zipPath=TEMP_DIR.$zipName;
    $zip=new ZipArchive();
    if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) je('Cannot create ZIP. Check temp/ permissions.');
    $ok=$bad=[];
    foreach($recipients as $rec){
        try{
            $zip->addFromString(safeFile($rec['name']).'_Certificate.pdf',
                makeCert($rec['name'],$tmpPath,$isPDF,$imgW,$imgH,$box,$fontKey,$cR,$cG,$cB,$fontSizePx));
            $ok[]=$rec['name'];
        }catch(\Throwable $e){ $bad[]=['name'=>$rec['name'],'error'=>$e->getMessage()]; }
    }
    $zip->close();
    if(!empty($bad)) @file_put_contents(LOG_DIR.'gen_errors_'.date('Ymd_His').'.json',json_encode($bad,JSON_PRETTY_PRINT));
    echo json_encode(['success'=>true,'zip_file'=>$zipName,'total'=>count($recipients),'generated'=>count($ok),'failed'=>count($bad)]);
    exit;
}

/* ════════════════════════════════════════════════════════════
   ACTION: generate_email_queue
   ════════════════════════════════════════════════════════════ */
if ($action === 'generate_email_queue') {
    $queueId='q_'.date('Ymd_His').'_'.substr(md5(uniqid('',true)),0,8);
    $queueDir=TEMP_DIR.$queueId.'/';
    if(!mkdir($queueDir,0755,true)) je('Cannot create queue folder. Check temp/ permissions.');
    $queue=[];$pending=$skipped=$failed=0;
    foreach($recipients as $rec){
        if(empty($rec['email'])){ $queue[]=['name'=>$rec['name'],'email'=>'','status'=>'skipped_no_email','file'=>'']; $skipped++; continue; }
        try{
            $pdfData=makeCert($rec['name'],$tmpPath,$isPDF,$imgW,$imgH,$box,$fontKey,$cR,$cG,$cB,$fontSizePx);
            $fname=safeFile($rec['name']).'_Certificate.pdf';
            file_put_contents($queueDir.$fname,$pdfData);
            $queue[]=['name'=>$rec['name'],'email'=>$rec['email'],'status'=>'pending','file'=>$fname];
            $pending++;
        }catch(\Throwable $e){
            $queue[]=['name'=>$rec['name'],'email'=>$rec['email'],'status'=>'generation_failed','file'=>'','error'=>$e->getMessage()];
            $failed++;
        }
    }
    $manifest=['id'=>$queueId,'created'=>date('c'),'email_subject'=>EMAIL_SUBJECT,'email_body'=>EMAIL_BODY_HTML,
               'total'=>count($recipients),'pending_total'=>$pending,'queue'=>$queue];
    file_put_contents($queueDir.'manifest.json',json_encode($manifest,JSON_PRETTY_PRINT));
    echo json_encode(['success'=>true,'queue_id'=>$queueId,'total'=>count($recipients),'with_email'=>$pending,'skipped'=>$skipped,'failed'=>$failed]);
    exit;
}

je('Unknown action.');
