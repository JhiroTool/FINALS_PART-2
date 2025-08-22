<?php
include 'connection.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
            if ($stmt->execute()) {
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
    <link rel="stylesheet" href="css/login.css">
</head>
<body class="body">
    <div class="login-container">
        <h2>Register</h2>
        <?php if ($success): ?>
            <div class="success" style="color:green; text-align:center; margin-bottom:1em;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error" style="color:red; text-align:center; margin-bottom:1em;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <div class="custom-select-wrapper">
                    <select id="role" name="role" class="custom-select">
                        <option value="user">User</option>
                        <option value="technician">Technician</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="login-btn">Register</button>
        </form>
        <div style="text-align:center; margin-top:1rem;">
            <a href="login.php" style="color:#3182ce; text-decoration:underline;">Already have an account? Login here</a>
        </div>
    </div>
    <style>
        .custom-select-wrapper {
            position: relative;
            width: 100%;
        }
        .custom-select {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border-radius: 8px;
            border: 1px solid #3182ce;
            background: linear-gradient(90deg, #e3f0ff 0%, #f8fbff 100%);
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            color: #222;
            appearance: none;
            outline: none;
            transition: border-color 0.2s;
        }
        .custom-select:focus {
            border-color: #005fa3;
        }
        .custom-select-wrapper::after {
            content: '\25BC';
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            color: #3182ce;
            font-size: 1em;
            pointer-events: none;
        }
    </style>
</body>
</html>
