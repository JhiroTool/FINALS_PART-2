<?php
session_start();
include '../connection.php';

// Only allow logged-in technicians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current profile info
$stmt = $conn->prepare("SELECT firstname, lastname, email, phone FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email, $phone);
$stmt->fetch();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_firstname = trim($_POST['firstname']);
    $new_lastname = trim($_POST['lastname']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']);

    if (
        empty($new_firstname) || empty($new_lastname) ||
        empty($new_email) || empty($new_phone)
    ) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("ssssi", $new_firstname, $new_lastname, $new_email, $new_phone, $user_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully!";
            // Update variables for display
            $firstname = $new_firstname;
            $lastname = $new_lastname;
            $email = $new_email;
            $phone = $new_phone;
        } else {
            $error = "Failed to update profile.";
        }
        $stmt->close();
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file['error'] === 0 && in_array($ext, $allowed)) {
        $target_dir = "../uploads/profile_pics/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = "profile_" . $user_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
            $stmt->bind_param("si", $filename, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Profile picture updated!";
        } else {
            $error = "Profile picture upload failed.";
        }
    } else {
        $error = "Invalid profile picture file type or upload error.";
    }
}

// Fetch current profile picture
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_pic);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Technician Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body technician-bg">
    <div class="container py-5">
        <div class="card mx-auto" style="max-width:500px;">
            <div class="card-body">
                <h3 class="mb-3 text-center">Update Profile</h3>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>
                    <!-- Show current profile picture -->
                    <?php if (!empty($profile_pic)): ?>
                        <div class="mb-3 text-center">
                            <img src="../uploads/profile_pics/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" style="max-width:120px;max-height:120px;border-radius:50%;">
                        </div>
                    <?php endif; ?>

                    <!-- Profile picture upload form -->
                    <div class="mb-3">
                        <label for="profile_pic" class="form-label">Upload Profile Picture (JPG/PNG)</label>
                        <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="technician_dashboard.php" class="btn btn-link">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>