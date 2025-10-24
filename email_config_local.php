<?php
// config/email_config_local.php
// Local email configuration for iMSafe Disaster Monitoring System

return [
    'host' => 'smtp.hostinger.com',
    'smtp_auth' => true,
    'username' => 'admin@imsafe-alert.com',
    'password' => 'Jhiro@123', // Your actual password
    'smtp_secure' => 'ssl', // Use 'ssl' for port 465, 'tls' for port 587
    'port' => 465, // 465 for SSL, 587 for TLS
    'set_from_address' => 'admin@imsafe-alert.com',
    'set_from_name' => 'iMSafe Disaster Monitoring',
    'is_html' => true,
    'alt_body_prefix' => 'This is a plain-text email. Please use an HTML-compatible email client to view the full message.'
];
?>
