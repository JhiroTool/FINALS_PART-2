<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/email_config.php';
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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
        throw new Exception('Failed to save verification code.');
    }
    $stmt_insert->close();
}

function markUserVerified($conn, $userId, $role)
{
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE user_verifications SET Verified_At = ? WHERE User_ID = ? AND Role = ?");
    $stmt->bind_param("sis", $timestamp, $userId, $role);
    $stmt->execute();
    $stmt->close();

    if ($role === 'technician') {
        $update = $conn->prepare("UPDATE technician SET Status = 'approved' WHERE Technician_ID = ?");
        $update->bind_param("i", $userId);
        $update->execute();
        $update->close();
    }
}

$role = isset($_GET['role']) && in_array($_GET['role'], ['client', 'technician'], true) ? $_GET['role'] : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = isset($_POST['role']) && in_array($_POST['role'], ['client', 'technician'], true) ? $_POST['role'] : '';
    $email = trim($_POST['email']);
    $code = trim($_POST['code']);

    if ($role === '' || $email === '' || $code === '') {
        $error = 'Please provide all required fields.';
    } else {
        $code = str_pad($code, 6, '0', STR_PAD_LEFT);
        if ($role === 'client') {
            $stmt_user = $conn->prepare("SELECT Client_ID, Client_FN, Client_LN FROM client WHERE Client_Email = ?");
        } else {
            $stmt_user = $conn->prepare("SELECT Technician_ID, Technician_FN, Technician_LN FROM technician WHERE Technician_Email = ?");
        }
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $stmt_user->store_result();

        if ($stmt_user->num_rows === 0) {
            $error = 'Account not found.';
        } else {
            if ($role === 'client') {
                $stmt_user->bind_result($userId, $firstName, $lastName);
            } else {
                $stmt_user->bind_result($userId, $firstName, $lastName);
            }
            $stmt_user->fetch();
            $stmt_user->close();

            ensureVerificationStorage($conn);
            $stmt_check = $conn->prepare("SELECT Code, Expires_At, Verified_At FROM user_verifications WHERE User_ID = ? AND Role = ?");
            $stmt_check->bind_param("is", $userId, $role);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows === 0) {
                $error = 'No verification code found. Please request a new one.';
                $stmt_check->close();
            } else {
                $stmt_check->bind_result($storedCode, $expiresAt, $verifiedAt);
                $stmt_check->fetch();
                $stmt_check->close();

                if (!is_null($verifiedAt)) {
                    $error = 'This account is already verified. You can log in now.';
                } elseif ($code !== $storedCode) {
                    $error = 'Invalid verification code. Please check and try again.';
                } elseif (strtotime($expiresAt) < time()) {
                    $error = 'Verification code expired. Please request a new code.';
                } else {
                    markUserVerified($conn, $userId, $role);
                    $_SESSION['verification_success'] = 'Your account has been verified! You can now log in.';
                    header('Location: login.php');
                    exit();
                }
            }
        }
    }
}

if (isset($_POST['resend']) && isset($_POST['role']) && isset($_POST['email'])) {
    $role = $_POST['role'];
    $email = trim($_POST['email']);

    if ($role === '' || $email === '') {
        $error = 'Invalid resend request.';
    } else {
        if ($role === 'client') {
            $stmt_user = $conn->prepare("SELECT Client_ID, Client_FN, Client_LN FROM client WHERE Client_Email = ?");
        } else {
            $stmt_user = $conn->prepare("SELECT Technician_ID, Technician_FN, Technician_LN FROM technician WHERE Technician_Email = ?");
        }
        $stmt_user->bind_param("s", $email);
        $stmt_user->execute();
        $stmt_user->store_result();

        if ($stmt_user->num_rows === 0) {
            $error = 'Account not found. Please register first.';
        } else {
            $stmt_user->bind_result($userId, $firstName, $lastName);
            $stmt_user->fetch();
            $stmt_user->close();

            $verification_code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', time() + 3600);
            $full_name = trim($firstName . ' ' . $lastName);
            saveVerificationCode($conn, $userId, $role, $verification_code, $expires_at);

            if (sendVerificationEmail($email, $full_name, $verification_code)) {
                $_SESSION['verification_info'] = 'A new verification code was sent to your email.';
                header('Location: verify.php?email=' . urlencode($email) . '&role=' . urlencode($role));
                exit();
            } else {
                $error = 'Could not send verification email. Please try again later.';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - PinoyFix</title>
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
            max-width: 500px;
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
        }
        .subtitle {
            color: #64748b;
            font-size: 16px;
            margin-bottom: 24px;
        }
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            text-align: center;
        }
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2, #fecaca);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert-info {
            background: linear-gradient(135deg, #eff6ff, #bfdbfe);
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
        }
        input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
            margin-bottom: 16px;
        }
        input:focus {
            outline: none;
            border-color: #0038A8;
            box-shadow: 0 0 0 3px rgba(0, 56, 168, 0.1);
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
            margin-bottom: 12px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 56, 168, 0.4);
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
            text-align: center;
        }
        .resend-button {
            background: transparent;
            border: none;
            color: #1d4ed8;
            cursor: pointer;
            font-weight: 600;
            text-decoration: underline;
            margin-top: 8px;
        }
        .resend-button:hover {
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <div class="logo-circle">
                <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
            </div>
            <h1 class="brand-title">PinoyFix</h1>
            <p class="subtitle">Almost there — verify your account.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['verification_info'])): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($_SESSION['verification_info'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['verification_info']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <h3 class="section-title">Enter Verification Code</h3>
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="code">6-digit Code</label>
            <input type="text" id="code" name="code" placeholder="123456" maxlength="6" pattern="\d{6}" required>

            <button type="submit" class="btn-primary">Verify Account</button>
        </form>

        <form method="POST" style="text-align: center;">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="resend-button">Resend Verification Code</button>
        </form>

        <div class="footer">
            <p>Need help? <a href="contact.php" class="link">Contact support</a></p>
            <p>&copy; 2025 PinoyFix • Built for Filipino communities</p>
        </div>
    </div>
</body>
</html>
