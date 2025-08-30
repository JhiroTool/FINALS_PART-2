<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get current technician data
$stmt = $conn->prepare("SELECT * FROM technician WHERE Technician_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $service_pricing = trim($_POST['service_pricing']);
    $service_location = trim($_POST['service_location']);
    
    // Handle profile picture upload
    $profile_pic = $technician['Technician_Profile']; // Keep existing if no new upload
    
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pics/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $new_filename = 'tech_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
            // Delete old profile picture if exists
            if (!empty($technician['Technician_Profile']) && file_exists($upload_dir . $technician['Technician_Profile'])) {
                unlink($upload_dir . $technician['Technician_Profile']);
            }
            $profile_pic = $new_filename;
        }
    }
    
    // Update technician profile
    $update_stmt = $conn->prepare("
        UPDATE technician 
        SET Technician_FN = ?, Technician_LN = ?, Technician_Email = ?, 
            Technician_Phone = ?, Specialization = ?, Service_Pricing = ?, 
            Service_Location = ?, Technician_Profile = ?
        WHERE Technician_ID = ?
    ");
    
    $update_stmt->bind_param("ssssssssi", 
        $firstname, $lastname, $email, $phone, 
        $specialization, $service_pricing, $service_location, $profile_pic, $user_id
    );
    
    if ($update_stmt->execute()) {
        $message = "Profile updated successfully!";
        $messageType = "success";
        
        // Refresh technician data
        $stmt = $conn->prepare("SELECT * FROM technician WHERE Technician_ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $technician = $result->fetch_assoc();
        $stmt->close();
    } else {
        $message = "Error updating profile: " . $conn->error;
        $messageType = "error";
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - PinoyFix Technician</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_profile.css">
</head>
<body>
    <div class="tech-container">
        <!-- Header -->
        <header class="tech-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">Profile Management</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="technician_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Profile Form -->
        <section class="profile-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">👤</span>
                    Update Your Profile
                </h2>
            </div>

            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <!-- Profile Picture Section -->
                <div class="profile-pic-section">
                    <div class="current-pic">
                        <?php if (!empty($technician['Technician_Profile'])): ?>
                            <img src="../uploads/profile_pics/<?php echo htmlspecialchars($technician['Technician_Profile']); ?>" alt="Current Profile Picture" id="currentPic">
                        <?php else: ?>
                            <div class="no-pic" id="currentPic">
                                <span><?php echo strtoupper(substr($technician['Technician_FN'], 0, 1)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="pic-upload">
                        <label for="profile_pic" class="upload-btn">
                            📷 Change Photo
                        </label>
                        <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display: none;">
                        <p class="upload-hint">JPG, PNG or GIF (max 2MB)</p>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="form-section">
                    <h3 class="form-section-title">Personal Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="firstname">First Name</label>
                            <input type="text" id="firstname" name="firstname" required 
                                   value="<?php echo htmlspecialchars($technician['Technician_FN']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name</label>
                            <input type="text" id="lastname" name="lastname" required 
                                   value="<?php echo htmlspecialchars($technician['Technician_LN']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($technician['Technician_Email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo htmlspecialchars($technician['Technician_Phone']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Service Information -->
                <div class="form-section">
                    <h3 class="form-section-title">Service Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="specialization">Specialization</label>
                            <select id="specialization" name="specialization" required>
                                <option value="">Select your specialization</option>
                                <option value="Electronics Repair" <?php echo ($technician['Specialization'] === 'Electronics Repair') ? 'selected' : ''; ?>>Electronics Repair</option>
                                <option value="Appliance Repair" <?php echo ($technician['Specialization'] === 'Appliance Repair') ? 'selected' : ''; ?>>Appliance Repair</option>
                                <option value="HVAC Technician" <?php echo ($technician['Specialization'] === 'HVAC Technician') ? 'selected' : ''; ?>>HVAC Technician</option>
                                <option value="Plumbing" <?php echo ($technician['Specialization'] === 'Plumbing') ? 'selected' : ''; ?>>Plumbing</option>
                                <option value="Electrical Work" <?php echo ($technician['Specialization'] === 'Electrical Work') ? 'selected' : ''; ?>>Electrical Work</option>
                                <option value="Computer Repair" <?php echo ($technician['Specialization'] === 'Computer Repair') ? 'selected' : ''; ?>>Computer Repair</option>
                                <option value="Mobile Phone Repair" <?php echo ($technician['Specialization'] === 'Mobile Phone Repair') ? 'selected' : ''; ?>>Mobile Phone Repair</option>
                                <option value="General Maintenance" <?php echo ($technician['Specialization'] === 'General Maintenance') ? 'selected' : ''; ?>>General Maintenance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="service_pricing">Hourly Rate (₱)</label>
                            <input type="number" id="service_pricing" name="service_pricing" min="100" max="5000" step="50" required 
                                   value="<?php echo htmlspecialchars($technician['Service_Pricing']); ?>">
                        </div>
                        <div class="form-group full-width">
                            <label for="service_location">Service Area</label>
                            <input type="text" id="service_location" name="service_location" 
                                   placeholder="e.g., Metro Manila, Quezon City, etc."
                                   value="<?php echo htmlspecialchars($technician['Service_Location']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        💾 Update Profile
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        // Profile picture preview
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentPic = document.getElementById('currentPic');
                    currentPic.innerHTML = `<img src="${e.target.result}" alt="New Profile Picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>