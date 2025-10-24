<?php
/**
 * Simple Email Helper Functions for iMSafe Disaster Monitoring System
 * 
 * Alternative email solution using PHP's built-in mail() function
 * This doesn't require Composer or external dependencies
 */

require_once __DIR__ . '/../config/email_config.php';

/**
 * Send tracking email to reporter after disaster report submission
 * 
 * @param string $recipientEmail Reporter's email address
 * @param string $recipientName Reporter's name (optional)
 * @param string $trackingId Disaster tracking ID
 * @param array $disasterData Disaster report data
 * @return bool True if email sent successfully, false otherwise
 */
function sendTrackingEmailSimple($recipientEmail, $recipientName, $trackingId, $disasterData) {
    try {
        // Validate email address
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email address: {$recipientEmail}");
            return false;
        }
        
        // Prepare email content
        $subject = 'Emergency Report Submitted - Tracking ID: ' . $trackingId;
        $htmlBody = generateTrackingEmailHTML($trackingId, $disasterData, $recipientName);
        $textBody = generateTrackingEmailText($trackingId, $disasterData, $recipientName);
        
        // Email headers
        $headers = [
            'From' => EMAIL_FROM_NAME . ' <' . EMAIL_FROM_ADDRESS . '>',
            'Reply-To' => EMAIL_REPLY_TO_ADDRESS,
            'X-Mailer' => 'PinoyFix',
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="boundary_' . uniqid() . '"'
        ];
        
        // Create multipart message
        $message = "--" . $headers['Content-Type'] . "\r\n";
        $message .= "Content-Type: text/plain; charset=" . EMAIL_CHARSET . "\r\n";
        $message .= "Content-Transfer-Encoding: " . EMAIL_ENCODING . "\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";
        
        $message .= "--" . $headers['Content-Type'] . "\r\n";
        $message .= "Content-Type: text/html; charset=" . EMAIL_CHARSET . "\r\n";
        $message .= "Content-Transfer-Encoding: " . EMAIL_ENCODING . "\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        $message .= "--" . $headers['Content-Type'] . "--";
        
        // Send email
        $result = mail(
            $recipientEmail,
            $subject,
            $message,
            implode("\r\n", array_map(function($key, $value) {
                return $key . ': ' . $value;
            }, array_keys($headers), $headers))
        );
        
        if ($result) {
            error_log("Tracking email sent successfully to: {$recipientEmail} for tracking ID: {$trackingId}");
            return true;
        } else {
            error_log("Failed to send tracking email to {$recipientEmail} for tracking ID {$trackingId}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error sending tracking email to {$recipientEmail}: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML email body for tracking notification
 * 
 * @param string $trackingId
 * @param array $disasterData
 * @param string $recipientName
 * @return string HTML content
 */
function generateTrackingEmailHTML($trackingId, $disasterData, $recipientName) {
    $trackUrl = TRACK_REPORT_URL . '?tracking_id=' . urlencode($trackingId);
    
    // Format disaster details
    $disasterType = htmlspecialchars($disasterData['type_name'] ?? 'Emergency Report');
    $location = htmlspecialchars($disasterData['city'] . ', ' . $disasterData['province'] ?? 'Location not specified');
    $severity = htmlspecialchars($disasterData['severity_display'] ?? 'Not specified');
    $description = htmlspecialchars($disasterData['description'] ?? 'No description provided');
    $reportedDate = date('F d, Y \a\t g:i A', strtotime($disasterData['created_at'] ?? 'now'));
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Emergency Report Submitted</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .tracking-id { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .tracking-id h2 { margin: 0; color: #495057; font-size: 18px; }
            .tracking-id .id { font-family: "Courier New", monospace; font-size: 24px; font-weight: bold; color: #007bff; margin: 10px 0; }
            .summary { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 20px; margin: 20px 0; }
            .summary h3 { margin-top: 0; color: #1976d2; }
            .summary-item { margin: 10px 0; }
            .summary-label { font-weight: bold; color: #424242; }
            .cta-button { text-align: center; margin: 30px 0; }
            .cta-button a { display: inline-block; background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .cta-button a:hover { background: #0056b3; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #dee2e6; }
            .description { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚨 iMSafe Disaster Monitoring System</h1>
                <p>Your emergency report has been received and is being processed</p>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($recipientName) . ',</p>
                
                <p>Thank you for reporting an emergency through the iMSafe Disaster Monitoring System. Your report has been successfully submitted and is now being processed by the appropriate authorities.</p>
                
                <div class="tracking-id">
                    <h2>Your Tracking ID</h2>
                    <div class="id">' . htmlspecialchars($trackingId) . '</div>
                    <p><strong>Please save this tracking ID</strong> - you will need it to check the status of your report.</p>
                </div>
                
                <div class="summary">
                    <h3>📋 Report Summary</h3>
                    <div class="summary-item">
                        <span class="summary-label">Type:</span> ' . $disasterType . '
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Location:</span> ' . $location . '
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Severity:</span> ' . $severity . '
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Reported:</span> ' . $reportedDate . '
                    </div>
                </div>
                
                <div class="description">
                    <strong>Description:</strong><br>
                    ' . nl2br($description) . '
                </div>
                
                <div class="cta-button">
                    <a href="' . $trackUrl . '">🔍 Track Your Report Status</a>
                </div>
                
                <h3>📞 What happens next?</h3>
                <ul>
                    <li>Your report has been assigned a unique tracking ID</li>
                    <li>The appropriate Local Government Unit (LGU) has been notified</li>
                    <li>You will be contacted within 24-48 hours depending on severity</li>
                    <li>Use the tracking ID to check for updates on your report</li>
                </ul>
                
                <p><strong>Important:</strong> If this is a life-threatening emergency, please call emergency services immediately at 911 or your local emergency hotline.</p>
                
                <p>Thank you for helping keep our community safe.</p>
                
                <p>Best regards,<br>
                iMSafe Disaster Monitoring System Team</p>
            </div>
            
            <div class="footer">
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>If you have questions, contact your local government office.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Generate plain text email body for tracking notification
 * 
 * @param string $trackingId
 * @param array $disasterData
 * @param string $recipientName
 * @return string Plain text content
 */
function generateTrackingEmailText($trackingId, $disasterData, $recipientName) {
    $trackUrl = TRACK_REPORT_URL . '?tracking_id=' . urlencode($trackingId);
    
    $disasterType = $disasterData['type_name'] ?? 'Emergency Report';
    $location = $disasterData['city'] . ', ' . $disasterData['province'] ?? 'Location not specified';
    $severity = $disasterData['severity_display'] ?? 'Not specified';
    $description = $disasterData['description'] ?? 'No description provided';
    $reportedDate = date('F d, Y \a\t g:i A', strtotime($disasterData['created_at'] ?? 'now'));
    
    return "
iMSafe Disaster Monitoring System
Emergency Report Submitted

Dear {$recipientName},

Thank you for reporting an emergency through the iMSafe Disaster Monitoring System. Your report has been successfully submitted and is now being processed by the appropriate authorities.

YOUR TRACKING ID: {$trackingId}
Please save this tracking ID - you will need it to check the status of your report.

REPORT SUMMARY:
- Type: {$disasterType}
- Location: {$location}
- Severity: {$severity}
- Reported: {$reportedDate}
- Description: {$description}

TRACK YOUR REPORT:
Visit: {$trackUrl}

WHAT HAPPENS NEXT:
- Your report has been assigned a unique tracking ID
- The appropriate Local Government Unit (LGU) has been notified
- You will be contacted within 24-48 hours depending on severity
- Use the tracking ID to check for updates on your report

IMPORTANT: If this is a life-threatening emergency, please call emergency services immediately at 911 or your local emergency hotline.

Thank you for helping keep our community safe.

Best regards,
iMSafe Disaster Monitoring System Team

---
This is an automated message. Please do not reply to this email.
If you have questions, contact your local government office.
";
}

/**
 * Validate email address format
 * 
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>
