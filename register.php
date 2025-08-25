<?php
include 'connection.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $street = trim($_POST['street']);
    $barangay = trim($_POST['barangay']);
    $city = trim($_POST['city']);
    $province = trim($_POST['province']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role_str = isset($_POST['role']) ? $_POST['role'] : 'user';

    // Map role string to integer
    $role_map = [
        'admin' => 0,
        'technician' => 1,
        'user' => 2
    ];
    $role = isset($role_map[$role_str]) ? $role_map[$role_str] : 2;

    // Basic validation
    if (
        empty($firstname) || empty($lastname) ||
        empty($street) || empty($barangay) || empty($city) || empty($province) ||
        empty($email) || empty($phone) || empty($username) || empty($password)
    ) {
        $error = "Please fill in all fields.";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or email already taken.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert new user (remove address from query and binding)
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, phone, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $firstname, $lastname, $email, $phone, $username, $hashed_password, $role);
            if ($stmt->execute()) {
                // Get the new user's ID
                $user_id = $stmt->insert_id;
                // Insert address into user_addresses table
                $stmt_addr = $conn->prepare("INSERT INTO user_addresses (user_id, street, barangay, city, province) VALUES (?, ?, ?, ?, ?)");
                $stmt_addr->bind_param("issss", $user_id, $street, $barangay, $city, $province);
                $stmt_addr->execute();
                $stmt_addr->close();

                $success = "Registration successful. You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Registration failed.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PinoyFix</title>
    <link rel="icon" type="image/png" sizes="32x32" href="images/pinoyfix.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/register.css">
</head>
<body class="body">
    <div class="floating-shape shape1"></div>
    <div class="floating-shape shape2"></div>
    <div class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center p-0">
        <div class="login-container w-100" style="max-width:600px;">
            <div class="logo-circle mx-auto mb-2">
                <img src="images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
            </div>
            <h2 class="text-center mb-1">PinoyFix</h2>
            <div class="subtitle text-center mb-3">Create your account</div>
            <?php if ($success): ?>
                <div class="alert alert-success text-center mb-3">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger text-center mb-3">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form id="registerForm" method="POST" action="" class="w-100">
                <div class="row">
                    <!-- Left: Credentials -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
                        </div>
                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                            <span id="togglePassword" style="position:absolute; right:10px; top:38px; cursor:pointer; font-size:1.2em; color:#3182ce;">👁️</span>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select">
                                <option value="user">User</option>
                                <option value="technician">Technician</option>
                            </select>
                        </div>
                    </div>
                    <!-- Right: Personal Info -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" id="firstname" name="firstname" class="form-control" required autocomplete="given-name">
                        </div>
                        <div class="mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" id="lastname" name="lastname" class="form-control" required autocomplete="family-name">
                        </div>
                        <div class="mb-3">
                            <label for="street" class="form-label">Street</label>
                            <input type="text" id="street" name="street" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="barangay" class="form-label">Barangay</label>
                            <input type="text" id="barangay" name="barangay" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" id="province" name="province" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" required autocomplete="email">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 mt-2" id="registerBtn">Register</button>
                    </div>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="login.php" class="link-login">Already have an account? Login here</a>
            </div>
            <footer class="login-footer mt-4 text-center">&copy; 2025 PinoyFix. All rights reserved.</footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle for register page
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
