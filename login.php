<?php
session_start();
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            // Redirect based on role
            if ($role == 'admin') {
                header('Location: admin/admin_dashboard.php');
            } elseif ($role == 'technician') {
                header('Location: technician/technician_dashboard.php');
            } else {
                header('Location: user/user_dashboard.php');
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PinoyFix</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/pinoyfix.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="body">
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="login-container">
        <div class="logo-circle">
            <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
        </div>
        <h2>PinoyFix</h2>
        <div class="subtitle">Your trusted appliance repair partner</div>
        <div id="errorMsg" class="error" style="display:none;"><?php if(isset($error)) echo $error; ?></div>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group" style="position:relative;">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <span id="togglePassword" style="position:absolute; right:10px; top:38px; cursor:pointer; font-size:1.2em; color:#3182ce;">👁️</span>
            </div>
            <div class="form-group" style="display:flex; align-items:center;">
                <input type="checkbox" id="rememberMe" name="rememberMe" style="margin-right:8px;">
                <label for="rememberMe" style="margin:0;">Remember Me</label>
            </div>
            <button type="submit" class="login-btn" id="loginBtn">Login</button>
            <div class="divider"><span>or</span></div>
            <div class="social-login">
                <button type="button" class="social-btn google"><img src="https://img.icons8.com/color/24/google-logo.png" alt="Google"> Google</button>
                <button type="button" class="social-btn facebook"><img src="https://img.icons8.com/color/24/facebook-new.png" alt="Facebook"> Facebook</button>
            </div>
            <div id="spinner" style="display:none; text-align:center; margin-top:1rem;">
                <span style="display:inline-block; width:32px; height:32px; border:4px solid #3182ce; border-top:4px solid #fff; border-radius:50%; animation: spin 1s linear infinite;"></span>
            </div>
        </form>
        <div style="text-align:center; margin-top:1rem;">
            <a href="register.php" style="color:#3182ce; text-decoration:underline;">Don't have an account? Register here</a>
        </div>
        <footer class="login-footer">&copy; 2025 PinoyFix. All rights reserved.</footer>
    </div>
    <script src="../js/login.js"></script>
</body>
</html>
