<?php
/**
 * Email Configuration for iMSafe Disaster Monitoring System
 * 
 * IMPORTANT: Add this file to .gitignore to keep credentials secure
 * Copy this file and rename to email_config_local.php for your local settings
 */

// Default email configuration
$emailConfig = [
    'host' => 'smtp.hostinger.com', // Your SMTP server
    'smtp_auth' => true,
    'username' => 'admin@imsafe-alert.com', // Your email
    'password' => 'Jhiro@123', // Your password
    'smtp_secure' => 'ssl', // 'ssl' for port 465, 'tls' for port 587
    'port' => 465, // 465 for SSL, 587 for TLS
    'set_from_address' => 'admin@imsafe-alert.com',
    'set_from_name' => 'PinoyFix',
    'is_html' => true,
    'alt_body_prefix' => 'This is a plain-text email. Please use an HTML-compatible email client to view the full message.'
];

// Load local overrides if available
if (file_exists(__DIR__ . '/email_config_local.php')) {
    $localConfig = require __DIR__ . '/email_config_local.php';
    $emailConfig = array_merge($emailConfig, $localConfig);
}

// Legacy constants for backward compatibility with simple email system
define('EMAIL_SMTP_HOST', $emailConfig['host']);
define('EMAIL_SMTP_PORT', $emailConfig['port']);
define('EMAIL_SMTP_USERNAME', $emailConfig['username']);
define('EMAIL_SMTP_PASSWORD', $emailConfig['password']);
define('EMAIL_SMTP_SECURE', $emailConfig['smtp_secure']);

define('EMAIL_FROM_ADDRESS', $emailConfig['set_from_address']);
define('EMAIL_FROM_NAME', $emailConfig['set_from_name']);
define('EMAIL_REPLY_TO_ADDRESS', $emailConfig['set_from_address']);

define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_ENCODING', '8bit');

// System URLs (for email links)
define('BASE_URL', 'http://imsafe-alert.com'); // Fixed URL format
define('TRACK_REPORT_URL', BASE_URL . '/track_report.php');

/**
 * Example email_config_local.php file:
 * 
 * <?php
 * define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
 * define('EMAIL_SMTP_PORT', 587);
 * define('EMAIL_SMTP_USERNAME', 'your-actual-email@gmail.com');
 * define('EMAIL_SMTP_PASSWORD', 'your-actual-app-password');
 * define('EMAIL_SMTP_SECURE', 'tls');
 * 
 * define('EMAIL_FROM_ADDRESS', 'your-actual-email@gmail.com');
 * define('EMAIL_FROM_NAME', 'PinoyFix');
 * define('EMAIL_REPLY_TO_ADDRESS', 'noreply@yourdomain.com');
 * 
 * define('BASE_URL', 'https://yourdomain.com/disaster');
 * define('TRACK_REPORT_URL', BASE_URL . '/track_report.php');
 */
?>

