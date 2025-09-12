<?php
session_start();
include 'connection.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check in client table first
        $stmt_client = $conn->prepare("SELECT Client_ID, Client_Pass, Client_FN, Client_LN FROM client WHERE Client_Email = ?");
        $stmt_client->bind_param("s", $email);
        $stmt_client->execute();
        $stmt_client->store_result();

        if ($stmt_client->num_rows > 0) {
            $stmt_client->bind_result($id, $hashed_password, $firstname, $lastname);
            $stmt_client->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['user_type'] = 'client';
                $_SESSION['user_name'] = $firstname . ' ' . $lastname;
                $_SESSION['email'] = $email;
                header('Location: client/client_dashboard.php');
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            // Check in technician table
            $stmt_tech = $conn->prepare("SELECT Technician_ID, Technician_Pass, Technician_FN, Technician_LN, Status FROM technician WHERE Technician_Email = ?");
            $stmt_tech->bind_param("s", $email);
            $stmt_tech->execute();
            $stmt_tech->store_result();

            if ($stmt_tech->num_rows > 0) {
                $stmt_tech->bind_result($id, $hashed_password, $firstname, $lastname, $status);
                $stmt_tech->fetch();
                if (password_verify($password, $hashed_password)) {
                    if ($status === 'approved') {
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_type'] = 'technician';
                        $_SESSION['user_name'] = $firstname . ' ' . $lastname;
                        $_SESSION['email'] = $email;
                        header('Location: technician/technician_dashboard.php');
                        exit();
                    } else {
                        $error = "Your technician account is pending approval. Please wait for admin verification.";
                    }
                } else {
                    $error = "Invalid password.";
                }
            } else {
                // Check in administrator table
                $stmt_admin = $conn->prepare("SELECT Admin_ID, Admin_Pass FROM administrator WHERE Admin_Email = ?");
                $stmt_admin->bind_param("s", $email);
                $stmt_admin->execute();
                $stmt_admin->store_result();

                if ($stmt_admin->num_rows > 0) {
                    $stmt_admin->bind_result($id, $hashed_password);
                    $stmt_admin->fetch();
                    if (password_verify($password, $hashed_password)) {
                        $_SESSION['user_id'] = $id;
                        $_SESSION['user_type'] = 'admin';
                        $_SESSION['user_name'] = 'Administrator';
                        $_SESSION['email'] = $email;
                        header('Location: admin/admin_dashboard.php');
                        exit();
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "Account not found.";
                }
                $stmt_admin->close();
            }
            $stmt_tech->close();
        }
        $stmt_client->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login to PinoyFix — Your trusted repair partner</title>
    <meta name="description" content="Login to your PinoyFix account - connect with trusted local fixers or manage your repair services.">
    <link rel="icon" type="image/png" sizes="32x32" href="images/pinoyfix.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* Login Page Styles */
* { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
}

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
    max-width: 800px;
    width: 100%;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(10px);
}

/* Logo Section */
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

/* Typography */
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

/* Login Sections */
.login-sections {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

/* Form Sections */
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

/* Form Elements */
.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1e293b;
}

input, select {
    width: 100%;
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
    font-family: inherit;
}

input:focus, select:focus {
    outline: none;
    border-color: #0038A8;
    box-shadow: 0 0 0 3px rgba(0, 56, 168, 0.1);
}

/* Password Toggle */
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

/* Remember & Forgot */
.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 14px;
    color: #64748b;
}

.checkbox-container input {
    width: auto;
    margin-right: 8px;
    padding: 0;
}

.forgot-link {
    font-size: 14px;
    color: #0038A8;
    text-decoration: none;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
}

.quick-buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.quick-button {
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

.quick-button:hover {
    border-color: #0038A8;
    background: #f8fafc;
    transform: translateX(4px);
}

.quick-button.active {
    border-color: #0038A8;
    background: rgba(0, 56, 168, 0.05);
}

.quick-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0;
}

.client-icon {
    background: linear-gradient(135deg, #0038A8, #3b82f6);
}

.tech-icon {
    background: linear-gradient(135deg, #CE1126, #ef4444);
}

.admin-icon {
    background: linear-gradient(135deg, #FFD400, #f59e0b);
    color: #1f2937;
}

.home-icon {
    background: linear-gradient(135deg, #10b981, #059669);
}

.quick-button h4 {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 4px;
    color: #1e293b;
}

.quick-button p {
    font-size: 12px;
    color: #64748b;
    margin: 0;
    line-height: 1.3;
}

/* Button */
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
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 56, 168, 0.4);
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-loader {
    display: flex;
    align-items: center;
    gap: 8px;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Social Section */
.social-section {
    margin: 32px 0;
}

.divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e2e8f0;
}

.divider span {
    background: rgba(255, 255, 255, 0.95);
    padding: 0 16px;
    color: #64748b;
    font-size: 14px;
}

.social-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: white;
    color: #64748b;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.social-btn:hover {
    border-color: #0038A8;
    color: #0038A8;
    transform: translateY(-1px);
}

.google-btn:hover {
    border-color: #ea4335;
    color: #ea4335;
}

.facebook-btn:hover {
    border-color: #1877f2;
    color: #1877f2;
}

/* Alerts */
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

/* Utility Classes */
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

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        margin: 10px;
        padding: 24px;
    }
    
    .login-sections {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .social-buttons {
        grid-template-columns: 1fr;
    }
    
    .brand-title {
        font-size: 28px;
    }
    
    .remember-forgot {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
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
            <p class="subtitle">Welcome back — Connect. Repair. Empower.</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="login-sections">
                <!-- Login Credentials -->
                <div class="form-section">
                    <h3 class="section-title">
                        <span class="section-icon">🔐</span>
                        Login to Your Account
                    </h3>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="juan@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <span class="password-toggle" onclick="togglePassword()">👁️</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="remember-forgot">
                            <label class="checkbox-container">
                                <input type="checkbox" name="remember" id="remember">
                                <span class="checkmark"></span>
                                Remember me
                            </label>
                            <a href="forgot_password.php" class="link forgot-link">Forgot password?</a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="form-section quick-actions">
                    <h3 class="section-title">
                        <span class="section-icon">⚡</span>
                        Quick Access
                    </h3>

                    <div class="quick-buttons">
                        <div class="quick-button" onclick="window.location.href='guest_view.php'">
                            <div class="quick-icon client-icon">👥</div>
                            <h4>Customer Login</h4>
                            <p>Find trusted fixers for your appliances</p>
                        </div>
                        
                        <div class="quick-button" onclick="window.location.href='technician_view.php'">
                            <div class="quick-icon tech-icon">🔧</div>
                            <h4>Fixer Login</h4>
                            <p>Manage your repair services</p>
                        </div>
                        
                        <div class="quick-button" onclick="window.location.href='index.php'">
                            <div class="quick-icon home-icon">⚙️</div>
                            <h4>Home View</h4>
                            <p>Homepage Guest Access</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-4">
                <button type="submit" class="btn-primary" id="loginBtn">
                    <span class="btn-text">Login to PinoyFix</span>
                    <span class="btn-loader" style="display: none;">
                        <span class="spinner"></span>
                        Logging in...
                    </span>
                </button>
            </div>
        </form>

        <!-- Register Link -->
        <div class="text-center mt-4">
            <p style="color: #64748b; margin-bottom: 8px;">New to PinoyFix?</p>
            <a href="register.php" class="link">Create your account</a> | 
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

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = btn.querySelector('.btn-text');
            const btnLoader = btn.querySelector('.btn-loader');
            
            btnText.style.display = 'none';
            btnLoader.style.display = 'flex';
            btn.disabled = true;
        });

        // Quick button interactions
        document.querySelectorAll('.quick-button').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.quick-button').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
