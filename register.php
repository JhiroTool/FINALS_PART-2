<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/email_config.php';
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$success = '';
$error = '';

function sendVerificationEmail($recipientEmail, $recipientName, $code)
{
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = EMAIL_SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_SMTP_USERNAME;
        $mail->Password = EMAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = EMAIL_SMTP_SECURE;
        $mail->Port = EMAIL_SMTP_PORT;
        $mail->CharSet = EMAIL_CHARSET;
        $mail->Encoding = EMAIL_ENCODING;
        $displayName = trim($recipientName) !== '' ? $recipientName : 'PinoyFix Member';
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addReplyTo(EMAIL_REPLY_TO_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($recipientEmail, $displayName);
        $mail->isHTML(true);
        $mail->Subject = 'PinoyFix Verification Code';
        $bodyName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
        $bodyCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
        $mail->Body = '<p>Hi ' . $bodyName . ',</p><p>Your verification code is <strong>' . $bodyCode . '</strong>.</p><p>This code will expire in 60 minutes.</p>';
        $mail->AltBody = 'Your verification code is ' . $code . '. It expires in 60 minutes.';
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

function ensureVerificationStorage($conn)
{
    $createSql = "CREATE TABLE IF NOT EXISTS user_verifications (Verification_ID INT AUTO_INCREMENT PRIMARY KEY, User_ID INT NOT NULL, Role ENUM('client','technician') NOT NULL, Code VARCHAR(6) NOT NULL, Expires_At DATETIME NOT NULL, Verified_At DATETIME DEFAULT NULL, Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_user_role (User_ID, Role)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createSql);
}

function saveVerificationCode($conn, $userId, $role, $code, $expiresAt)
{
    ensureVerificationStorage($conn);
    $stmt_delete = $conn->prepare("DELETE FROM user_verifications WHERE User_ID = ? AND Role = ?");
    $stmt_delete->bind_param("is", $userId, $role);
    $stmt_delete->execute();
    $stmt_delete->close();
    $stmt_insert = $conn->prepare("INSERT INTO user_verifications (User_ID, Role, Code, Expires_At) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("isss", $userId, $role, $code, $expiresAt);
    if (!$stmt_insert->execute()) {
        throw new \Exception('Failed to save verification code.');
    }
    $stmt_insert->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'client';
    
    // For technician-specific fields
    $specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : '';
    $service_pricing = isset($_POST['service_pricing']) ? trim($_POST['service_pricing']) : '';
    $service_location = isset($_POST['service_location']) ? trim($_POST['service_location']) : '';

    // Basic validation
    if (
        empty($firstname) || empty($lastname) ||
        empty($street) || empty($barangay) || empty($city) || empty($province) ||
        empty($email) || empty($phone) || empty($password)
    ) {
        $error = "Please fill in all fields.";
    } else {
        $conn->begin_transaction();
        
        try {
            // Check if email already exists in client or technician tables
            $stmt_check_client = $conn->prepare("SELECT Client_ID FROM client WHERE Client_Email = ?");
            $stmt_check_client->bind_param("s", $email);
            $stmt_check_client->execute();
            $stmt_check_client->store_result();
            
            $stmt_check_tech = $conn->prepare("SELECT Technician_ID FROM technician WHERE Technician_Email = ?");
            $stmt_check_tech->bind_param("s", $email);
            $stmt_check_tech->execute();
            $stmt_check_tech->store_result();
            
            if ($stmt_check_client->num_rows > 0 || $stmt_check_tech->num_rows > 0) {
                $error = "Email already registered.";
                $conn->rollback();
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert address first
                $stmt_addr = $conn->prepare("INSERT INTO address (Street, Barangay, City, Province) VALUES (?, ?, ?, ?)");
                $stmt_addr->bind_param("ssss", $street, $barangay, $city, $province);
                $stmt_addr->execute();
                $address_id = $stmt_addr->insert_id;
                $stmt_addr->close();
                
                $verification_code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', time() + 3600);
                $full_name = trim($firstname . ' ' . $lastname);
                if ($role === 'technician') {
                    $status = 'pending_verification';
                    $ratings = '0.0';
                    $stmt_tech = $conn->prepare("INSERT INTO technician (Technician_FN, Technician_LN, Technician_Email, Technician_Pass, Technician_Phone, Specialization, Service_Pricing, Service_Location, Status, Ratings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_tech->bind_param("ssssssssss", $firstname, $lastname, $email, $hashed_password, $phone, $specialization, $service_pricing, $service_location, $status, $ratings);
                    if (!$stmt_tech->execute()) {
                        throw new \Exception('Failed to create technician.');
                    }
                    $user_id = $stmt_tech->insert_id;
                    $stmt_tech->close();
                    $stmt_tech_addr = $conn->prepare("INSERT INTO technician_address (Technician_ID, Address_ID) VALUES (?, ?)");
                    $stmt_tech_addr->bind_param("ii", $user_id, $address_id);
                    if (!$stmt_tech_addr->execute()) {
                        throw new \Exception('Failed to link technician address.');
                    }
                    $stmt_tech_addr->close();
                    saveVerificationCode($conn, $user_id, 'technician', $verification_code, $expires_at);
                } else {
                    $stmt_client = $conn->prepare("INSERT INTO client (Client_FN, Client_LN, Client_Email, Client_Pass, Client_Phone) VALUES (?, ?, ?, ?, ?)");
                    $stmt_client->bind_param("sssss", $firstname, $lastname, $email, $hashed_password, $phone);
                    if (!$stmt_client->execute()) {
                        throw new \Exception('Failed to create client.');
                    }
                    $user_id = $stmt_client->insert_id;
                    $stmt_client->close();
                    $stmt_client_addr = $conn->prepare("INSERT INTO client_address (Client_ID, Address_ID) VALUES (?, ?)");
                    $stmt_client_addr->bind_param("ii", $user_id, $address_id);
                    if (!$stmt_client_addr->execute()) {
                        throw new \Exception('Failed to link client address.');
                    }
                    $stmt_client_addr->close();
                    saveVerificationCode($conn, $user_id, 'client', $verification_code, $expires_at);
                }
                $conn->commit();
                $email_sent = sendVerificationEmail($email, $full_name, $verification_code);
                if ($email_sent) {
                    $link = 'verify.php?email=' . urlencode($email) . '&role=' . urlencode($role);
                    $success = "Registration successful! A 6-digit verification code was sent to " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . ". <a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "' style='color: #0038A8; text-decoration: none; font-weight: 600;'>Verify your account</a>.";
                } else {
                    $error = "Registration saved, but the verification email could not be sent. Please contact support to request a new code.";
                }
            }
            
            $stmt_check_client->close();
            $stmt_check_tech->close();
            
        } catch (\Exception $e) {
            $conn->rollback();
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join PinoyFix — Connect. Repair. Empower.</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: linear-gradient(135deg, #4169E1 0%, #1e40af 50%, #0038A8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1e293b;
        }
        
        .container {
            max-width: 900px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .logo-circle:hover {
            transform: scale(1.05) rotate(5deg);
        }
        
        .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        .brand-title {
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 8px;
            background: linear-gradient(135deg, #1e293b 0%, #0038A8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 24px;
        }
        
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .col-full {
            grid-column: 1 / -1;
        }
        
        .form-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-section:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #0038A8;
            box-shadow: 0 0 0 3px rgba(0, 56, 168, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #64748b;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #0038A8;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #0038A8 0%, #0052cc 100%);
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(0, 56, 168, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 56, 168, 0.4);
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            text-align: center;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-4 {
            margin-top: 24px;
        }
        
        .link {
            color: #0038A8;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .link:hover {
            text-decoration: underline;
        }
        
        .footer {
            color: #64748b;
            font-size: 14px;
            margin-top: 32px;
        }
        
        #technicianFields {
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                padding: 24px;
            }
            
            .row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .brand-title {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-circle">
                <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
            </div>
            <h1 class="brand-title">PinoyFix</h1>
            <p class="subtitle">Join the community — Connect. Repair. Empower.</p>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <!-- Account Information -->
                <div>
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">🔐</span>
                            Account Information
                        </h3>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="juan@email.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="6">
                                <span class="password-toggle" onclick="togglePassword()">👁️</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Join as</label>
                            <select id="role" name="role" onchange="toggleTechnicianFields()">
                                <option value="client">Customer — Find trusted fixers</option>
                                <option value="technician">Fixer — Offer your services</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div>
                    <div class="form-section">
                        <h3 class="section-title">
                            <span class="section-icon">👤</span>
                            Personal Information
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstname">First Name</label>
                                <input type="text" id="firstname" name="firstname" placeholder="Juan" required>
                            </div>
                            <div class="form-group">
                                <label for="lastname">Last Name</label>
                                <input type="text" id="lastname" name="lastname" placeholder="Dela Cruz" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="col-full">
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon">📍</span>
                        Address Information
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="street">Street Address</label>
                            <input type="text" id="street" name="street" placeholder="123 Rizal Street" required>
                        </div>
                        <div class="form-group">
                            <label for="barangay">Barangay</label>
                            <input type="text" id="barangay" name="barangay" placeholder="Barangay Santo Niño" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City/Municipality</label>
                            <input type="text" id="city" name="city" placeholder="Quezon City" required>
                        </div>
                        <div class="form-group">
                            <label for="province">Province</label>
                            <input type="text" id="province" name="province" placeholder="Metro Manila" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Technician Fields -->
            <div class="col-full" id="technicianFields">
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon">🔧</span>
                        Technician Information
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <select id="specialization" name="specialization">
                                <option value="">Select specialization</option>
                                <option value="Electrical">Electrical</option>
                                <option value="Plumbing">Plumbing</option>
                                <option value="Appliance Repair">Appliance Repair</option>
                                <option value="HVAC">HVAC</option>
                                <option value="Carpentry">Carpentry</option>
                                <option value="Electronics">Electronics</option>
                                <option value="General Maintenance">General Maintenance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="service_pricing">Service Rate (₱/hour)</label>
                            <input type="number" id="service_pricing" name="service_pricing" placeholder="500" min="100" max="5000" step="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_location">Service Coverage</label>
                        <input type="text" id="service_location" name="service_location" placeholder="Metro Manila">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4">
                <button type="submit" class="btn-primary">
                    Create Account & Join PinoyFix
                </button>
            </div>
        </form>

        <!-- Links -->
        <div class="text-center mt-4">
            <p style="color: #64748b; margin-bottom: 8px;">Already part of the community?</p>
            <a href="login.php" class="link">Login to your account</a> | 
            <a href="index.php" class="link">Back to home</a>
        </div>

        <div class="footer text-center">
            <p>&copy; 2025 PinoyFix • Built for Filipino communities</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggle = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggle.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggle.textContent = '👁️';
            }
        }

        function toggleTechnicianFields() {
            const role = document.getElementById('role').value;
            const techFields = document.getElementById('technicianFields');
            const specialization = document.getElementById('specialization');
            const servicePricing = document.getElementById('service_pricing');
            const serviceLocation = document.getElementById('service_location');
            
            if (role === 'technician') {
                techFields.style.display = 'block';
                specialization.required = true;
                servicePricing.required = true;
                serviceLocation.required = true;
            } else {
                techFields.style.display = 'none';
                specialization.required = false;
                servicePricing.required = false;
                serviceLocation.required = false;
            }
        }

        // Initialize
        toggleTechnicianFields();
    </script>
</body>
</html>
