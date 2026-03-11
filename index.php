<?php
require_once __DIR__ . '/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>Bulk Certificate Generator</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Pinyon+Script&family=Parisienne&family=EB+Garamond&family=Libre+Caslon+Text&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
.canvas-container {
    position: relative; width: 100%; max-width: 100%;
    overflow: hidden; border-radius: 0.5rem;
    background-color: #e5e7eb; border: 2px dashed #9ca3af;
    display: flex; justify-content: center; align-items: center;
    min-height: 300px;
}
canvas { max-width: 100%; height: auto; cursor: crosshair; display: block; touch-action: none; }
.step-card { transition: all 0.3s ease; }
.step-card.disabled { opacity: 0.45; pointer-events: none; filter: grayscale(80%); }
.tab-btn { transition: all 0.2s; }
.tab-btn.active { background-color: #2563eb; color: #fff; }
.tab-btn:not(.active) { background-color: #f3f4f6; color: #374151; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
</style>
</head>
<body class="min-h-screen text-gray-800 p-3 md:p-6 pb-20">

<!-- Toast -->
<div id="toast" class="fixed top-4 right-4 max-w-sm w-11/12 translate-x-[150%] opacity-0 px-5 py-4 rounded-xl shadow-xl transition-all duration-500 z-50 flex items-start gap-3 border pointer-events-none">
    <div id="toastIcon" class="text-lg flex-shrink-0 mt-0.5"></div>
    <div class="flex-1 min-w-0">
        <div id="toastTitle" class="font-semibold text-sm"></div>
        <div id="toastMsg"   class="text-xs mt-0.5 opacity-80 break-words"></div>
    </div>
    <button onclick="hideToast()" class="text-gray-400 hover:text-gray-700 text-lg leading-none flex-shrink-0 pointer-events-auto">×</button>
</div>

<div class="max-w-7xl mx-auto space-y-5">

    <!-- Header -->
    <header class="flex items-center justify-between mt-2">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-gray-900">Bulk Certificate Generator</h1>
                <p class="text-gray-400 text-xs hidden sm:block">Generate &amp; send personalized certificates at scale</p>
            </div>
        </div>
        <a href="logout.php" class="text-sm text-gray-500 hover:text-red-600 flex items-center gap-1.5 transition px-3 py-1.5 rounded-lg hover:bg-red-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
        </a>
    </header>

    <!-- Tabs -->
    <div class="flex gap-2 bg-white p-1.5 rounded-xl shadow-sm border border-gray-100 w-fit">
        <button class="tab-btn active px-4 py-2 rounded-lg text-sm font-semibold" data-tab="generator">🎓 Generator</button>
        <button class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold" data-tab="email">📧 Email Queue</button>
        <button class="tab-btn px-4 py-2 rounded-lg text-sm font-semibold" data-tab="logs">📋 Error Logs</button>
    </div>

    <!-- ═══ TAB: GENERATOR ═══ -->
    <div class="tab-panel active" id="tab-generator">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <div class="lg:col-span-4 space-y-4">

                <!-- Step 1 -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 step-card" id="step1">
                    <h2 class="text-base font-bold flex items-center gap-2 mb-3">
                        <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                        Upload Template
                    </h2>
                    <label class="block w-full cursor-pointer bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 border-dashed rounded-lg p-5 text-center transition">
                        <svg class="w-7 h-7 mx-auto text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-blue-700" id="templateLabel">Choose JPG / PNG / PDF</span>
                        <input type="file" id="imageInput" accept="image/png,image/jpeg,.pdf,application/pdf" class="hidden">
                    </label>
                </div>

                <!-- Step 2 -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 step-card disabled" id="step2">
                    <h2 class="text-base font-bold flex items-center gap-2 mb-3">
                        <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                        Upload CSV
                    </h2>
                    <p class="text-xs text-gray-500 mb-3">
                        Required: <code class="bg-gray-100 px-1 rounded">Name</code> column.
                        For emails also add: <code class="bg-gray-100 px-1 rounded">Email</code>
                    </p>
                    <label class="block w-full cursor-pointer bg-green-50 hover:bg-green-100 border-2 border-green-200 border-dashed rounded-lg p-4 text-center transition">
                        <svg class="w-6 h-6 mx-auto text-green-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-sm font-medium text-green-700" id="csvLabelText">Choose CSV File</span>
                        <input type="file" id="csvInput" accept=".csv,text/csv" class="hidden">
                    </label>
                    <div id="csvStatus" class="mt-3 p-3 bg-gray-50 rounded-lg hidden">
                        <div class="flex justify-between text-sm">
                            <span>Names: <strong id="nameCount" class="text-green-600">0</strong></span>
                            <span>With email: <strong id="emailCount" class="text-blue-600">0</strong></span>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 step-card disabled" id="step3">
                    <h2 class="text-base font-bold flex items-center gap-2 mb-3">
                        <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                        Style &amp; Generate
                    </h2>
                    <div class="space-y-3 mb-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-gray-600">Font</label>
                                <button id="uploadFontToggle" class="text-xs text-blue-600 hover:underline">+ Upload font</button>
                            </div>
                            <select id="fontFamily" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 text-sm bg-white">
                                <option value="sans-bold">Sans Serif (Default)</option>
                                <option value="serif-bold">Serif (Default)</option>
                                <option value="mono-bold">Monospace (Default)</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1" id="fontLoadStatus">Loading fonts…</p>

                            <!-- Font upload panel -->
                            <div id="fontUploadPanel" class="hidden mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-xs text-blue-700 font-semibold mb-2">Upload any TTF/OTF font</p>
                                <p class="text-xs text-blue-600 mb-2">Supports Bangla (SolaimanLipi, Kalpurush, Noto Sans Bengali), Arabic, Latin scripts, and more.</p>
                                <label class="flex items-center gap-2 cursor-pointer bg-white border border-blue-300 rounded-lg px-3 py-2 hover:bg-blue-50 transition">
                                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    <span class="text-xs text-blue-700 font-medium" id="fontUploadLabel">Choose .ttf or .otf file</span>
                                    <input type="file" id="fontUploadInput" accept=".ttf,.otf" class="hidden">
                                </label>
                                <div id="fontUploadResult" class="hidden mt-2 text-xs"></div>
                                <!-- List uploaded fonts with delete -->
                                <div id="uploadedFontList" class="mt-2 space-y-1"></div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Text Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="textColor" value="#000000" class="h-9 w-12 p-0.5 border border-gray-300 rounded cursor-pointer">
                                <span class="text-sm font-mono text-gray-600" id="colorHex">#000000</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <button id="generateZipBtn"
                                class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download as ZIP
                        </button>
                        <button id="queueEmailBtn"
                                class="w-full bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold py-2.5 px-4 rounded-lg flex items-center justify-center gap-2 transition text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Generate &amp; Queue Emails
                        </button>
                    </div>

                    <!-- Progress -->
                    <div id="progressContainer" class="mt-4 hidden">
                        <div class="flex justify-between text-xs font-medium text-gray-600 mb-1">
                            <span id="progressText" class="truncate pr-2">Processing...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width:0%"></div>
                        </div>
                    </div>
                </div>

            </div><!-- /left col -->

            <!-- Canvas -->
            <div class="lg:col-span-8">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
                        <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            Design Area
                        </h2>
                        <span id="instructionText" class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full hidden">
                            Drag on the image to mark where the name goes
                        </span>
                    </div>
                    <div class="canvas-container flex-grow" id="canvasContainer">
                        <div id="placeholder" class="text-center p-10 text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm font-medium">Upload a certificate template to begin</p>
                            <p class="text-xs text-gray-300 mt-1">Supports JPG, PNG, and PDF</p>
                        </div>
                        <canvas id="certCanvas" class="hidden shadow-md"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /tab-generator -->

    <!-- ═══ TAB: EMAIL QUEUE ═══ -->
    <div class="tab-panel" id="tab-email">

        <!-- SMTP Test Panel -->
        <div class="bg-white rounded-xl shadow-sm border border-amber-200 p-5 mb-5">
            <h3 class="font-bold text-sm text-amber-800 flex items-center gap-2 mb-3">
                🔧 SMTP Connection Test
                <span class="text-xs font-normal text-amber-600">— verify your email settings work before sending bulk</span>
            </h3>
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Send test email to</label>
                    <input type="email" id="smtpTestTo" placeholder="your@email.com"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                <button id="smtpTestBtn"
                        class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-5 py-2 rounded-lg text-sm transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Test Email
                </button>
            </div>
            <div id="smtpTestResult" class="hidden mt-3 text-xs font-mono bg-gray-900 text-gray-100 rounded-lg p-3 max-h-48 overflow-y-auto whitespace-pre-wrap"></div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold mb-1">Email Queue Manager</h2>
            <p class="text-gray-500 text-sm mb-5">After clicking "Generate &amp; Queue Emails" in the Generator tab, your queue will appear here automatically.</p>

            <div id="noQueueMsg" class="text-center py-14 text-gray-400">
                <svg class="w-14 h-14 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm">No active queue. Use the Generator tab first.</p>
            </div>

            <div id="queueControls" class="hidden">
                <!-- Stats bar -->
                <div class="bg-gray-50 rounded-xl p-4 mb-5 flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Queue ID</div>
                        <code class="text-xs font-mono text-blue-700 break-all" id="queueIdDisplay">—</code>
                    </div>
                    <div class="flex gap-5 text-center">
                        <div><div class="text-2xl font-bold text-gray-800" id="qTotal">0</div><div class="text-xs text-gray-500">Total</div></div>
                        <div><div class="text-2xl font-bold text-emerald-600" id="qSent">0</div><div class="text-xs text-gray-500">Sent</div></div>
                        <div><div class="text-2xl font-bold text-red-500" id="qFailed">0</div><div class="text-xs text-gray-500">Failed</div></div>
                        <div><div class="text-2xl font-bold text-amber-500" id="qPending">0</div><div class="text-xs text-gray-500">Pending</div></div>
                    </div>
                </div>

                <!-- Email content -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Email Subject</label>
                        <input type="text" id="emailSubject" value="<?= htmlspecialchars(EMAIL_SUBJECT) ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div class="flex items-end pb-1">
                        <p class="text-xs text-gray-500">Use <code class="bg-gray-100 px-1 rounded">{NAME}</code> anywhere to personalise each email with the recipient's name.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Email Body (HTML)</label>
                        <textarea id="emailBody" rows="5"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 outline-none resize-y"><?= htmlspecialchars(EMAIL_BODY_HTML) ?></textarea>
                    </div>
                </div>

                <!-- Send buttons -->
                <div class="flex flex-wrap gap-3 mb-4">
                    <button id="sendBatchBtn"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg flex items-center gap-2 text-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Send Next Batch (<?= BATCH_SIZE ?>)
                    </button>
                    <button id="sendAllBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-5 py-2.5 rounded-lg flex items-center gap-2 text-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Send All (Auto)
                    </button>
                    <button id="stopSendBtn" class="hidden bg-red-500 hover:bg-red-600 text-white font-bold px-5 py-2.5 rounded-lg text-sm transition">
                        ⏹ Stop
                    </button>
                </div>

                <!-- Progress bar -->
                <div id="sendProgressWrap" class="hidden bg-gray-50 rounded-lg p-4">
                    <div class="flex justify-between text-xs font-medium text-gray-600 mb-2">
                        <span id="sendProgressText">Ready</span>
                        <span id="sendProgressPct">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div id="sendProgressBar" class="bg-emerald-500 h-3 rounded-full transition-all duration-500" style="width:0%"></div>
                    </div>
                    <div class="mt-2 text-xs text-gray-400" id="sendETA"></div>
                </div>

                <!-- Last batch result -->
                <div id="batchResult" class="hidden mt-3 p-3 rounded-lg text-sm"></div>
            </div>
        </div><!-- /queue card -->
    </div><!-- /tab-email -->

    <!-- ═══ TAB: LOGS ═══ -->
    <div class="tab-panel" id="tab-logs">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-5">
                <h2 class="text-lg font-bold">Error Logs</h2>
                <button id="refreshLogsBtn" class="text-sm text-blue-600 hover:underline">↻ Refresh</button>
            </div>
            <div id="logsContainer"><p class="text-gray-400 text-sm text-center py-10">Loading...</p></div>
            <div id="logDetail" class="hidden mt-5">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-sm" id="logDetailTitle"></h3>
                    <button id="closeLogDetail" class="text-xs text-gray-400 hover:text-gray-700 px-2 py-1 rounded hover:bg-gray-100">✕ Close</button>
                </div>
                <div class="bg-gray-50 rounded-lg overflow-auto max-h-96">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 bg-gray-100">
                            <tr class="text-left text-gray-600">
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Error</th>
                                <th class="px-3 py-2 whitespace-nowrap">Time</th>
                            </tr>
                        </thead>
                        <tbody id="logDetailBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div><!-- /max-w -->

<script>
/* ═══════════════════════════════════════════════════════════
   STATE
   ═══════════════════════════════════════════════════════════ */
const S = {
    imageLoaded: false,
    imageObj:    null,
    isPDF:       false,
    imgW: 0, imgH: 0,
    box:         null,
    isDrawing:   false,
    startX: 0,  startY: 0,
    namesList:   [],
    emailCount:  0,
    rawCSV:      null,
    fontSizePx:  0,     // exact px size computed by canvas — sent to PHP
    // queue
    queueId:        null,
    queueTotal:     0,
    queueOffset:    0,
    sendAllRunning: false,
};

/* ═══════════════════════════════════════════════════════════
   TOAST
   ═══════════════════════════════════════════════════════════ */
let toastTimer = null;
const TOAST_COLORS = {
    error:   'bg-red-50   border-red-200   text-red-900',
    success: 'bg-green-50 border-green-200 text-green-900',
    info:    'bg-blue-50  border-blue-200  text-blue-900',
    warn:    'bg-amber-50 border-amber-200 text-amber-900',
};
const TOAST_ICONS = { error:'⚠️', success:'✅', info:'ℹ️', warn:'⚠️' };

function showToast(title, msg = '', type = 'error') {
    const el = document.getElementById('toast');
    el.className = `fixed top-4 right-4 max-w-sm w-11/12 px-5 py-4 rounded-xl shadow-xl transition-all duration-500 z-50 flex items-start gap-3 border ${TOAST_COLORS[type] || TOAST_COLORS.info}`;
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMsg').textContent   = msg;
    document.getElementById('toastIcon').textContent  = TOAST_ICONS[type] || 'ℹ️';
    el.classList.remove('translate-x-[150%]','opacity-0');
    el.classList.add('pointer-events-auto');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 7000);
}
function hideToast() {
    const el = document.getElementById('toast');
    el.classList.add('translate-x-[150%]','opacity-0');
    el.classList.remove('pointer-events-auto');
}

/* ═══════════════════════════════════════════════════════════
   TABS
   ═══════════════════════════════════════════════════════════ */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b  => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        if (btn.dataset.tab === 'logs') loadLogs();
    });
});

/* ═══════════════════════════════════════════════════════════
   CANVAS
   ═══════════════════════════════════════════════════════════ */
const canvas = document.getElementById('certCanvas');
const ctx    = canvas.getContext('2d');

/* ═══════════════════════════════════════════════════════════
   FONT SYSTEM — dynamic, loaded from server
   ═══════════════════════════════════════════════════════════ */
// Fonts loaded from fonts.php API. Key = font key, value = label.
const LOADED_FONTS = {}; // populated by loadFonts()

async function loadFonts() {
    try {
        const r = await fetch('fonts.php?action=list');
        if (!r.ok) throw new Error('fonts.php returned ' + r.status);
        const res = await r.json();
        if (!res.success || !res.fonts) throw new Error('Invalid response');

        const sel = document.getElementById('fontFamily');
        const currentVal = sel.value; // remember selection
        sel.innerHTML = ''; // safe to clear now
        res.fonts.forEach(f => {
            LOADED_FONTS[f.key] = f;
            const opt = document.createElement('option');
            opt.value       = f.key;
            opt.textContent = f.label + (f.bundled ? '' : ' ★');
            sel.appendChild(opt);
        });
        // Restore previous selection if still valid
        if (currentVal && sel.querySelector(`option[value="${currentVal}"]`)) {
            sel.value = currentVal;
        }
        document.getElementById('fontLoadStatus').textContent =
            res.fonts.length + ' font(s) available. Upload more with "+ Upload font".';
        renderUploadedFontList(res.fonts.filter(f => !f.bundled));
        drawCanvas();
    } catch(e) {
        // fonts.php missing or errored — leave default options in place, show warning
        document.getElementById('fontLoadStatus').textContent =
            'Font list unavailable (upload fonts.php to server). Using defaults.';
    }
}

function renderUploadedFontList(userFonts) {
    const el = document.getElementById('uploadedFontList');
    if (!userFonts.length) { el.innerHTML = ''; return; }
    el.innerHTML = '<p class="text-xs text-gray-500 font-semibold mt-2 mb-1">Uploaded fonts:</p>' +
        userFonts.map(f => `
        <div class="flex items-center justify-between bg-white rounded px-2 py-1 border border-gray-200">
            <span class="text-xs truncate text-gray-700">${esc(f.label)} <span class="text-gray-400">(${f.file})</span></span>
            <button onclick="deleteFont('${esc(f.file)}')" class="text-red-400 hover:text-red-600 text-xs ml-2 flex-shrink-0">✕</button>
        </div>`).join('');
}

async function deleteFont(file) {
    if (!confirm('Delete font "' + file + '"?')) return;
    await fetch('fonts.php', { method: 'POST', body: new URLSearchParams({ action: 'delete', file }) });
    loadFonts();
}

// Toggle upload panel
document.getElementById('uploadFontToggle').addEventListener('click', () => {
    const p = document.getElementById('fontUploadPanel');
    p.classList.toggle('hidden');
});

// Font file upload
document.getElementById('fontUploadInput').addEventListener('change', async e => {
    const file = e.target.files[0];
    if (!file) return;
    document.getElementById('fontUploadLabel').textContent = file.name;
    const resultEl = document.getElementById('fontUploadResult');
    resultEl.className = 'mt-2 text-xs text-blue-600';
    resultEl.textContent = 'Uploading…';
    resultEl.classList.remove('hidden');

    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('font', file);
    try {
        const res = await fetch('fonts.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
            resultEl.className = 'mt-2 text-xs text-green-600 font-semibold';
            resultEl.textContent = '✅ "' + res.font.label + '" uploaded successfully!';
            await loadFonts();
            // Auto-select the new font
            document.getElementById('fontFamily').value = res.font.key;
            drawCanvas();
            showToast('Font uploaded!', res.font.label + ' is now available.', 'success');
        } else {
            resultEl.className = 'mt-2 text-xs text-red-600';
            resultEl.textContent = '❌ ' + (res.error || 'Upload failed.');
        }
    } catch(err) {
        resultEl.className = 'mt-2 text-xs text-red-600';
        resultEl.textContent = '❌ ' + err.message;
    }
    e.target.value = ''; // reset input
});

// For canvas preview: we can't load arbitrary server TTFs into canvas,
// so use system/Google font approximations for preview only.
// The PDF output uses the actual TTF, so it will look different from preview
// if a custom font is used — that's expected.
const PREVIEW_FONT_CSS = {
    'sans-bold':   'bold {s}px Arial, sans-serif',
    'serif-bold':  'bold {s}px "Times New Roman", serif',
    'mono-bold':   'bold {s}px "Courier New", monospace',
    // legacy
    'helvetica':   'bold {s}px Arial, sans-serif',
    'times':       'bold {s}px "Times New Roman", serif',
    'courier':     'bold {s}px "Courier New", monospace',
    'garamond':    'bold {s}px "EB Garamond", "Times New Roman", serif',
    'baskerville': 'bold {s}px "Libre Baskerville", "Times New Roman", serif',
    'greatvibes':  '{s}px "Great Vibes", cursive',
    'pinyon':      '{s}px "Pinyon Script", cursive',
    'parisienne':  '{s}px "Parisienne", cursive',
    'librecaslon': 'bold {s}px "Libre Caslon Text", serif',
};

function getPos(e) {
    const r = canvas.getBoundingClientRect();
    const sx = canvas.width / r.width, sy = canvas.height / r.height;
    let cx = e.clientX, cy = e.clientY;
    if (e.touches?.length)        { cx = e.touches[0].clientX;        cy = e.touches[0].clientY; }
    if (e.changedTouches?.length) { cx = e.changedTouches[0].clientX; cy = e.changedTouches[0].clientY; }
    return { x: (cx - r.left) * sx, y: (cy - r.top) * sy };
}

function drawCanvas() {
    if (!S.imageLoaded) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(S.imageObj, 0, 0);
    if (!S.box) return;
    const { x, y, w, h } = S.box;
    ctx.fillStyle = 'rgba(59,130,246,0.12)';
    ctx.fillRect(x, y, w, h);
    ctx.strokeStyle = '#3b82f6';
    ctx.lineWidth   = Math.max(2, canvas.width * 0.002);
    ctx.setLineDash([10, 5]);
    ctx.strokeRect(x, y, w, h);
    ctx.setLineDash([]);
    // preview text — compute size for display only
    const sample  = S.namesList.length ? S.namesList[0] : 'Sample Name';
    const fontKey = document.getElementById('fontFamily').value;
    const tpl     = (PREVIEW_FONT_CSS[fontKey] || 'bold {s}px Arial, sans-serif');
    // Base size from box height — this is what PHP will use as the maximum
    const baseSz  = h * 0.72;
    // Shrink only for the preview display so it looks right on screen
    let displaySz = baseSz;
    ctx.font = tpl.replace('{s}', displaySz);
    const tw = ctx.measureText(sample).width;
    if (tw > w * 0.95) displaySz *= (w * 0.95) / tw;
    // Send the BASE size to PHP (not shrunk). PHP will shrink per-name as needed.
    S.fontSizePx = baseSz;
    ctx.font         = tpl.replace('{s}', displaySz);
    ctx.fillStyle    = document.getElementById('textColor').value;
    ctx.textAlign    = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(sample, x + w / 2, y + h / 2);

    // Show note if custom font selected (preview uses system font approximation)
    const isCustom = LOADED_FONTS[fontKey] && !LOADED_FONTS[fontKey].bundled;
    if (isCustom) {
        const inst = document.getElementById('instructionText');
        inst.classList.remove('hidden');
        inst.textContent = '⚠ Custom font — preview is approximate, PDF will use exact font';
        inst.className   = 'text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full';
    }
}

function onPointerStart(e) { if (!S.imageLoaded) return; const p = getPos(e); S.startX=p.x; S.startY=p.y; S.isDrawing=true; S.box=null; checkReady(); }
function onPointerMove(e)  { if (!S.isDrawing) return; const p=getPos(e); const ww=p.x-S.startX,hh=p.y-S.startY; S.box={x:ww<0?p.x:S.startX,y:hh<0?p.y:S.startY,w:Math.abs(ww),h:Math.abs(hh)}; drawCanvas(); }
function onPointerEnd()    { if (!S.isDrawing) return; S.isDrawing=false; if (!S.box||S.box.w<20||S.box.h<8){S.box=null;} drawCanvas(); checkReady(); }

canvas.addEventListener('mousedown',  onPointerStart);
canvas.addEventListener('mousemove',  onPointerMove);
canvas.addEventListener('mouseup',    onPointerEnd);
canvas.addEventListener('mouseleave', onPointerEnd);
canvas.addEventListener('touchstart', onPointerStart, {passive:true});
canvas.addEventListener('touchmove',  onPointerMove,  {passive:true});
canvas.addEventListener('touchend',   onPointerEnd);
canvas.addEventListener('touchcancel',onPointerEnd);

document.getElementById('textColor').addEventListener('input', e => {
    document.getElementById('colorHex').textContent = e.target.value.toUpperCase();
    drawCanvas();
});
document.getElementById('fontFamily').addEventListener('change', drawCanvas);

function checkReady() {
    const ready = S.imageLoaded && S.box !== null && S.namesList.length > 0;
    document.getElementById('step3').classList.toggle('disabled', !ready);
    const inst = document.getElementById('instructionText');
    inst.classList.remove('hidden');
    if (S.imageLoaded && !S.box) {
        inst.textContent  = 'Draw a box where the name should appear';
        inst.className    = 'text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-full';
    } else if (ready) {
        inst.textContent  = '✓ Ready to generate';
        inst.className    = 'text-xs font-semibold text-green-600 bg-green-50 px-3 py-1.5 rounded-full';
    } else if (S.imageLoaded) {
        inst.textContent  = S.namesList.length === 0 ? 'Upload CSV next' : 'Draw a box on the template';
        inst.className    = 'text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full';
    }
}

/* ═══════════════════════════════════════════════════════════
   TEMPLATE UPLOAD
   ═══════════════════════════════════════════════════════════ */
document.getElementById('imageInput').addEventListener('change', async e => {
    const file = e.target.files[0];
    if (!file) return;
    document.getElementById('templateLabel').textContent = file.name;
    S.isPDF = file.type === 'application/pdf';

    if (S.isPDF) {
        try {
            if (typeof pdfjsLib === 'undefined') {
                await loadScript('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }
            const url  = URL.createObjectURL(file);
            const pdf  = await pdfjsLib.getDocument(url).promise;
            const page = await pdf.getPage(1);
            const vp   = page.getViewport({ scale: 1.5 });
            canvas.width  = vp.width;
            canvas.height = vp.height;
            await page.render({ canvasContext: ctx, viewport: vp }).promise;
            // Capture rendered image for preview redraws
            S.imageObj = new Image();
            S.imageObj.src = canvas.toDataURL('image/png');
            await new Promise(r => S.imageObj.onload = r);
            S.imgW = vp.width; S.imgH = vp.height;
        } catch(err) {
            showToast('PDF Preview Error', err.message);
            return;
        }
    } else {
        const dataURL = await fileToDataURL(file);
        const img     = new Image();
        await new Promise(r => { img.onload = r; img.src = dataURL; });
        canvas.width  = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        S.imageObj = img;
        S.imgW = img.width; S.imgH = img.height;
    }

    S.imageLoaded = true;
    S.box = null;
    document.getElementById('placeholder').classList.add('hidden');
    canvas.classList.remove('hidden');
    document.getElementById('step2').classList.remove('disabled');
    checkReady();
});

function fileToDataURL(file) {
    return new Promise((res, rej) => {
        const r = new FileReader();
        r.onload  = e => res(e.target.result);
        r.onerror = rej;
        r.readAsDataURL(file);
    });
}

function loadScript(src) {
    return new Promise((res, rej) => {
        const s = document.createElement('script');
        s.src = src; s.onload = res; s.onerror = () => rej(new Error('Failed to load ' + src));
        document.head.appendChild(s);
    });
}

/* ═══════════════════════════════════════════════════════════
   CSV UPLOAD
   ═══════════════════════════════════════════════════════════ */
document.getElementById('csvInput').addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    document.getElementById('csvLabelText').textContent = file.name;
    S.rawCSV = file;
    const reader = new FileReader();
    reader.onload = ev => {
        let text = ev.target.result;
        // Strip BOM
        if (text.charCodeAt(0) === 0xFEFF) text = text.slice(1);
        const lines = text.split(/\r?\n/).filter(l => l.trim());
        if (!lines.length) { showToast('CSV Error', 'File is empty.'); return; }

        // Simple CSV split (handles basic quoting)
        const parseRow = row => row.split(',').map(c => c.trim().replace(/^"|"$/g, ''));

        const hdr    = parseRow(lines[0]).map(h => h.toLowerCase());
        const nameIdx  = hdr.indexOf('name');
        const emailIdx = hdr.indexOf('email');

        if (nameIdx === -1) {
            showToast('CSV Error', `No "Name" column found. Your headers: ${parseRow(lines[0]).join(', ')}`);
            return;
        }

        S.namesList  = [];
        S.emailCount = 0;
        for (let i = 1; i < lines.length; i++) {
            const cols  = parseRow(lines[i]);
            const name  = (cols[nameIdx] || '').trim();
            const email = emailIdx >= 0 ? (cols[emailIdx] || '').trim() : '';
            if (name) { S.namesList.push(name); if (email) S.emailCount++; }
        }

        if (!S.namesList.length) { showToast('CSV Error', 'Name column found but no data rows.'); return; }

        document.getElementById('nameCount').textContent  = S.namesList.length;
        document.getElementById('emailCount').textContent = S.emailCount;
        document.getElementById('csvStatus').classList.remove('hidden');
        drawCanvas();
        checkReady();
    };
    reader.readAsText(file);
});

/* ═══════════════════════════════════════════════════════════
   PROGRESS UI
   ═══════════════════════════════════════════════════════════ */
function setProgress(show, text = '', pct = 0) {
    const c = document.getElementById('progressContainer');
    c.classList.toggle('hidden', !show);
    if (show) {
        document.getElementById('progressText').textContent    = text;
        document.getElementById('progressPercent').textContent = pct + '%';
        document.getElementById('progressBar').style.width     = pct + '%';
    }
}

/* ═══════════════════════════════════════════════════════════
   UPLOAD WITH SIMULATED PROGRESS
   ═══════════════════════════════════════════════════════════ */
async function postFormData(fd, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        // Upload phase: 0→40%
        xhr.upload.addEventListener('progress', ev => {
            if (ev.lengthComputable) {
                onProgress(Math.round((ev.loaded / ev.total) * 40));
            }
        });

        // Simulate server processing: 40→90%
        let fakePct = 40;
        const fakeIv = setInterval(() => {
            fakePct = Math.min(fakePct + 1, 90);
            onProgress(fakePct);
        }, 600);

        xhr.addEventListener('load', () => {
            clearInterval(fakeIv);
            onProgress(100);
            try {
                const data = JSON.parse(xhr.responseText);
                resolve(data);
            } catch(err) {
                reject(new Error('Server returned non-JSON: ' + xhr.responseText.substring(0, 200)));
            }
        });

        xhr.addEventListener('error', () => { clearInterval(fakeIv); reject(new Error('Network error — check your connection.')); });
        xhr.addEventListener('abort', () => { clearInterval(fakeIv); reject(new Error('Upload aborted.')); });

        xhr.open('POST', 'generate.php');
        xhr.send(fd);
    });
}

function buildFD(action) {
    const fd = new FormData();
    fd.append('action',      action);
    fd.append('template',    document.getElementById('imageInput').files[0]);
    fd.append('names_csv',   S.rawCSV);
    fd.append('box_x',       S.box.x);
    fd.append('box_y',       S.box.y);
    fd.append('box_w',       S.box.w);
    fd.append('box_h',       S.box.h);
    fd.append('img_w',       S.imgW);
    fd.append('img_h',       S.imgH);
    fd.append('font',        document.getElementById('fontFamily').value);
    fd.append('color',       document.getElementById('textColor').value);
    fd.append('font_size_px', S.fontSizePx || 0);
    return fd;
}

function lockGenButtons(lock) {
    ['generateZipBtn','queueEmailBtn'].forEach(id => {
        document.getElementById(id).disabled = lock;
    });
}

/* ═══════════════════════════════════════════════════════════
   GENERATE ZIP
   ═══════════════════════════════════════════════════════════ */
document.getElementById('generateZipBtn').addEventListener('click', async () => {
    if (!S.imageLoaded || !S.box || !S.rawCSV) {
        showToast('Not Ready', 'Upload template, draw a box, and upload a CSV first.');
        return;
    }
    lockGenButtons(true);
    setProgress(true, 'Uploading template & CSV…', 5);
    try {
        const res = await postFormData(buildFD('generate_zip'), p => setProgress(true, p < 50 ? 'Uploading…' : 'Generating PDFs…', p));
        if (!res.success) {
            showToast('Generation Failed', res.error || 'Unknown error');
            return;
        }
        // Trigger download via hidden link
        const a = document.createElement('a');
        a.href  = 'download.php?file=' + encodeURIComponent(res.zip_file);
        a.download = res.zip_file;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        showToast('Download Ready!', `${res.generated} of ${res.total} certificates generated.`, 'success');
        if (res.failed > 0) showToast('Some Failed', `${res.failed} certificate(s) failed. See Logs tab.`, 'warn');
    } catch(err) {
        showToast('Upload Error', err.message);
    } finally {
        lockGenButtons(false);
        setProgress(false);
    }
});

/* ═══════════════════════════════════════════════════════════
   GENERATE & QUEUE EMAILS
   ═══════════════════════════════════════════════════════════ */
document.getElementById('queueEmailBtn').addEventListener('click', async () => {
    if (!S.imageLoaded || !S.box || !S.rawCSV) {
        showToast('Not Ready', 'Upload template, draw a box, and upload a CSV first.');
        return;
    }
    if (S.emailCount === 0) {
        showToast('No Emails', 'Your CSV has no Email column or no email addresses were found.');
        return;
    }
    lockGenButtons(true);
    setProgress(true, 'Uploading & generating certificates…', 5);
    try {
        const res = await postFormData(buildFD('generate_email_queue'), p => setProgress(true, p < 50 ? 'Uploading…' : `Generating ${S.namesList.length} certificates…`, p));
        if (!res.success) {
            showToast('Failed', res.error || 'Unknown error');
            return;
        }
        // Store queue state
        S.queueId     = res.queue_id;
        S.queueTotal  = res.with_email;   // only those with emails
        S.queueOffset = 0;
        // Activate email tab
        showEmailQueue(res);
        // Switch to email tab
        document.querySelector('[data-tab="email"]').click();
        showToast(`Queue Ready! ${res.with_email} emails queued.`,
            res.skipped > 0 ? `${res.skipped} skipped (no email).` : '', 'success');
    } catch(err) {
        showToast('Upload Error', err.message);
    } finally {
        lockGenButtons(false);
        setProgress(false);
    }
});

/* ═══════════════════════════════════════════════════════════
   EMAIL QUEUE UI
   ═══════════════════════════════════════════════════════════ */
function showEmailQueue(data) {
    document.getElementById('noQueueMsg').classList.add('hidden');
    document.getElementById('queueControls').classList.remove('hidden');
    document.getElementById('queueIdDisplay').textContent = data.queue_id || S.queueId;
    document.getElementById('qTotal').textContent   = data.total   || 0;
    document.getElementById('qSent').textContent    = 0;
    document.getElementById('qFailed').textContent  = 0;
    document.getElementById('qPending').textContent = data.with_email || 0;
    document.getElementById('sendProgressWrap').classList.remove('hidden');
    updateSendBar(0, data.with_email || 0);
}

function updateQueueStats(res) {
    document.getElementById('qSent').textContent    = res.total_sent    ?? 0;
    document.getElementById('qFailed').textContent  = res.total_failed  ?? 0;
    document.getElementById('qPending').textContent = res.total_pending ?? 0;
    const sent  = res.total_sent  ?? 0;
    const total = S.queueTotal    || 1;
    updateSendBar(sent, total - sent);
}

function updateSendBar(sent, pending) {
    const total = S.queueTotal || (sent + pending);
    const pct   = total > 0 ? Math.round((sent / total) * 100) : 0;
    document.getElementById('sendProgressBar').style.width  = pct + '%';
    document.getElementById('sendProgressPct').textContent  = pct + '%';
    document.getElementById('sendProgressText').textContent = `Sent ${sent} of ${total}`;
}

/* ── send one batch ────────────────────────────────────────── */
async function sendOneBatch() {
    if (!S.queueId) throw new Error('No active queue. Generate one first.');
    const body = new URLSearchParams({
        queue_id:      S.queueId,
        offset:        S.queueOffset,
        email_subject: document.getElementById('emailSubject').value,
        email_body:    document.getElementById('emailBody').value,
    });
    const res = await fetch('send_batch.php', { method: 'POST', body }).then(async r => {
        const text = await r.text();
        try { return JSON.parse(text); }
        catch(e) { throw new Error('Server error: ' + text.substring(0, 300)); }
    });
    if (!res.success) throw new Error(res.error || 'Batch failed');
    S.queueOffset = res.offset;
    updateQueueStats(res);
    return res;
}

/* ── manual: send one batch ───────────────────────────────── */
document.getElementById('sendBatchBtn').addEventListener('click', async () => {
    const btn = document.getElementById('sendBatchBtn');
    btn.disabled = true;
    const br = document.getElementById('batchResult');
    br.className = 'mt-3 p-3 rounded-lg text-sm bg-blue-50 text-blue-700';
    br.textContent = 'Sending batch…';
    br.classList.remove('hidden');
    try {
        const res = await sendOneBatch();
        const msg = `Batch done: ${res.batch_sent} sent, ${res.batch_failed} failed.`;
        br.className  = res.batch_failed > 0
            ? 'mt-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-800'
            : 'mt-3 p-3 rounded-lg text-sm bg-green-50 text-green-800';
        br.textContent = msg + (res.done ? ' ✅ All done!' : ` ${res.total_pending} still pending.`);
        if (res.done) showToast('All Done!', 'Every email has been processed.', 'success');
    } catch(err) {
        br.className  = 'mt-3 p-3 rounded-lg text-sm bg-red-50 text-red-800';
        br.textContent = 'Error: ' + err.message;
        showToast('Batch Error', err.message);
    } finally {
        btn.disabled = false;
    }
});

/* ── auto: send all ───────────────────────────────────────── */
document.getElementById('sendAllBtn').addEventListener('click', async () => {
    if (S.sendAllRunning) return;
    if (!S.queueId) { showToast('No Queue', 'Generate a queue first in the Generator tab.'); return; }
    S.sendAllRunning = true;
    document.getElementById('sendAllBtn').classList.add('hidden');
    document.getElementById('stopSendBtn').classList.remove('hidden');

    const br = document.getElementById('batchResult');
    br.classList.remove('hidden');

    let done = false;
    const startTime = Date.now();
    let sentAtStart = parseInt(document.getElementById('qSent').textContent) || 0;

    try {
        while (!done && S.sendAllRunning) {
            br.className  = 'mt-3 p-3 rounded-lg text-sm bg-blue-50 text-blue-700';
            br.textContent = `Sending batch starting at index ${S.queueOffset}…`;

            const res = await sendOneBatch();
            done = res.done;

            br.className  = res.batch_failed > 0
                ? 'mt-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-800'
                : 'mt-3 p-3 rounded-lg text-sm bg-green-50 text-green-800';
            br.textContent = `Sent ${res.total_sent} total • ${res.total_pending} pending • ${res.total_failed} failed`;

            // ETA
            const elapsed   = (Date.now() - startTime) / 1000;
            const sentSince = (res.total_sent || 0) - sentAtStart;
            if (sentSince > 0 && res.total_pending > 0) {
                const etaSec = Math.ceil(res.total_pending / (sentSince / elapsed));
                document.getElementById('sendETA').textContent = `Estimated time remaining: ~${Math.ceil(etaSec / 60)} min`;
            }

            if (!done) await sleep(1500); // pause between batches
        }

        if (done && S.sendAllRunning) {
            br.className  = 'mt-3 p-3 rounded-lg text-sm bg-green-50 text-green-900 font-semibold';
            br.textContent = '✅ All emails processed successfully!';
            showToast('All Done! 🎉', 'All certificates have been emailed.', 'success');
        } else if (!S.sendAllRunning) {
            br.className  = 'mt-3 p-3 rounded-lg text-sm bg-amber-50 text-amber-800';
            br.textContent = '⏹ Stopped. Click "Send All" to resume.';
        }
    } catch(err) {
        br.className  = 'mt-3 p-3 rounded-lg text-sm bg-red-50 text-red-800';
        br.textContent = 'Error: ' + err.message;
        showToast('Send Error', err.message);
    } finally {
        S.sendAllRunning = false;
        document.getElementById('sendAllBtn').classList.remove('hidden');
        document.getElementById('stopSendBtn').classList.add('hidden');
    }
});

document.getElementById('stopSendBtn').addEventListener('click', () => {
    S.sendAllRunning = false;
});

const sleep = ms => new Promise(r => setTimeout(r, ms));

/* ═══════════════════════════════════════════════════════════
   LOGS
   ═══════════════════════════════════════════════════════════ */
async function loadLogs() {
    const c = document.getElementById('logsContainer');
    c.innerHTML = '<p class="text-gray-400 text-sm text-center py-10">Loading…</p>';
    try {
        const res = await fetch('logs.php?action=list').then(r => r.json());
        if (!res.logs?.length) {
            c.innerHTML = '<p class="text-gray-400 text-sm text-center py-10">🎉 No error logs found.</p>';
            return;
        }
        c.innerHTML = `<table class="w-full text-sm">
            <thead><tr class="text-left text-xs text-gray-500 border-b">
                <th class="pb-2 pr-4">Log File</th><th class="pb-2 pr-4">Modified</th>
                <th class="pb-2 pr-4">Size</th><th class="pb-2">Actions</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
            ${res.logs.map(l => `<tr class="hover:bg-gray-50">
                <td class="py-2 pr-4 font-mono text-xs text-blue-700 break-all">${esc(l.file)}</td>
                <td class="py-2 pr-4 text-gray-500 text-xs whitespace-nowrap">${esc(l.modified)}</td>
                <td class="py-2 pr-4 text-gray-500 text-xs">${(l.size/1024).toFixed(1)} KB</td>
                <td class="py-2 flex gap-3 text-xs">
                    <button onclick="viewLog('${esc(l.file)}')" class="text-blue-600 hover:underline">View</button>
                    <button onclick="deleteLog('${esc(l.file)}')" class="text-red-500 hover:underline">Delete</button>
                </td>
            </tr>`).join('')}
            </tbody></table>`;
    } catch(e) {
        c.innerHTML = `<p class="text-red-500 text-sm text-center py-10">Failed to load logs: ${e.message}</p>`;
    }
}

async function viewLog(file) {
    const res = await fetch(`logs.php?action=read&file=${encodeURIComponent(file)}`).then(r => r.json());
    if (!res.success) { showToast('Error', res.error); return; }
    document.getElementById('logDetailTitle').textContent = file;
    document.getElementById('logDetailBody').innerHTML = (res.data || []).map(row => `
        <tr class="hover:bg-red-50">
            <td class="px-3 py-1.5 font-medium">${esc(row.name||'—')}</td>
            <td class="px-3 py-1.5 text-gray-600">${esc(row.email||'—')}</td>
            <td class="px-3 py-1.5 text-red-600">${esc(row.error||'—')}</td>
            <td class="px-3 py-1.5 text-gray-400 whitespace-nowrap">${esc((row.time||'').replace('T',' ').substring(0,19))}</td>
        </tr>`).join('');
    document.getElementById('logDetail').classList.remove('hidden');
}

async function deleteLog(file) {
    if (!confirm(`Delete "${file}"?`)) return;
    await fetch('logs.php?action=delete', { method:'POST', body: new URLSearchParams({file}) });
    loadLogs();
    document.getElementById('logDetail').classList.add('hidden');
}

document.getElementById('closeLogDetail').addEventListener('click', () => {
    document.getElementById('logDetail').classList.add('hidden');
});
document.getElementById('refreshLogsBtn').addEventListener('click', loadLogs);

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ═══════════════════════════════════════════════════════════
   SMTP TEST
   ═══════════════════════════════════════════════════════════ */
document.getElementById('smtpTestBtn').addEventListener('click', async () => {
    const to  = document.getElementById('smtpTestTo').value.trim();
    const btn = document.getElementById('smtpTestBtn');
    const out = document.getElementById('smtpTestResult');

    if (!to) { showToast('Enter email', 'Type an email address to send the test to.', 'warn'); return; }

    btn.disabled    = true;
    btn.textContent = '⏳ Sending…';
    out.classList.remove('hidden');
    out.textContent = 'Connecting to SMTP server…';

    try {
        const res = await fetch('test_smtp.php', {
            method: 'POST',
            body: new URLSearchParams({ to, subject: 'CertGen SMTP Test' }),
        }).then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); }
            catch(e) { throw new Error('Server error: ' + text.substring(0, 500)); }
        });

        if (res.success) {
            out.className = 'mt-3 text-xs font-mono bg-gray-900 text-green-300 rounded-lg p-3 max-h-48 overflow-y-auto whitespace-pre-wrap';
            out.textContent = '✅ SUCCESS — Test email sent to ' + to + '\n\nSMTP Config:\n' +
                JSON.stringify(res.config, null, 2) +
                '\n\nSMTP Log:\n' + (res.smtp_log || '(no log)');
            showToast('Test Sent!', 'Check your inbox at ' + to, 'success');
        } else {
            out.className = 'mt-3 text-xs font-mono bg-gray-900 text-red-300 rounded-lg p-3 max-h-48 overflow-y-auto whitespace-pre-wrap';
            out.textContent = '❌ FAILED\n\nError: ' + (res.error || 'Unknown') +
                '\n\nSMTP Config:\n' + JSON.stringify(res.config, null, 2) +
                '\n\nSMTP Log:\n' + (res.smtp_log || '(no log)');
            showToast('SMTP Failed', res.error || 'Check the log below.', 'error');
        }
    } catch(err) {
        out.className = 'mt-3 text-xs font-mono bg-gray-900 text-red-300 rounded-lg p-3 max-h-48 overflow-y-auto whitespace-pre-wrap';
        out.textContent = '❌ Request Error: ' + err.message;
        showToast('Error', err.message);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Send Test Email';
    }
});

// ── init ──────────────────────────────────────────────────
loadFonts();
</script>
</body>
</html>
