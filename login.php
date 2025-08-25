<?php
session_start();
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

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
            if ($role == '0') {
                header('Location: admin/admin_dashboard.php');
            } elseif ($role == '1') {
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="body">
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center p-0">
        <div class="card shadow-lg rounded-4 w-100" style="max-width: 700px;">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <div class="logo-circle mb-2">
                        <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <h2 class="mb-1" style="font-weight:700;color:#007bff;">PinoyFix</h2>
                    <div class="subtitle mb-3" style="color:#343a40;">Your trusted appliance repair partner</div>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger text-center mb-3">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <form id="loginForm" method="POST" action="">
                    <div class="row g-0">
                        <!-- Credentials (left) -->
                        <div class="col-12 col-md-6 p-3 border-end">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
                            </div>
                            <div class="mb-3 position-relative">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                                <span id="togglePassword" style="position:absolute; right:10px; top:38px; cursor:pointer; font-size:1.2em; color:#3182ce;">👁️</span>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                                <label class="form-check-label" for="rememberMe">Remember Me</label>
                            </div>
                        </div>
                        <!-- Social Login & Login Button (right) -->
                        <div class="col-12 col-md-6 p-3 d-flex flex-column align-items-center justify-content-center">
                            <button type="submit" class="login-btn btn btn-primary w-100" id="loginBtn">Login</button>
                            <div class="divider w-100 text-center my-3"><span>or</span></div>
                            <div class="social-login w-100 mb-3">
                                <button type="button" class="btn btn-outline-danger w-100">
                                    <img src="https://img.icons8.com/color/24/google-logo.png" alt="Google"> Google
                                </button>
                                <button type="button" class="btn btn-outline-primary w-100">
                                    <img src="https://img.icons8.com/color/24/facebook-new.png" alt="Facebook"> Facebook
                                </button>
                            </div>
                            <div id="spinner" style="display:none; text-align:center; margin-top:1rem;">
                                <span style="display:inline-block; width:32px; height:32px; border:4px solid #3182ce; border-top:4px solid #fff; border-radius:50%; animation: spin 1s linear infinite;"></span>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="register.php" style="color:#3182ce; text-decoration:underline;">Don't have an account? Register here</a>
                </div>
                <footer class="login-footer mt-4 text-center">&copy; 2025 PinoyFix. All rights reserved.</footer>
            </div>
        </div>
    </div>
    <script src="../js/login.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle for login page
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
            });
        }
    </script>
</body>
</html>
