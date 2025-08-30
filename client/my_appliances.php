<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if connection file exists
if (!file_exists('../connection.php')) {
    die('Error: connection.php file not found');
}

include '../connection.php';

// Check if connection is successful
if (!isset($conn) || $conn->connect_error) {
    die('Database connection failed: ' . (isset($conn) ? $conn->connect_error : 'Connection object not found'));
}

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get client info with error handling
try {
    $stmt = $conn->prepare("SELECT Client_FN, Client_LN FROM client WHERE Client_ID = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $client = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    die('Error getting client info: ' . $e->getMessage());
}

// Handle form submission for adding appliance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_appliance'])) {
    try {
        $appliance_type = trim($_POST['appliance_type']);
        $brand = trim($_POST['brand']);
        $model = trim($_POST['model']);
        $purchase_date = $_POST['purchase_date'];
        $warranty_status = $_POST['warranty_status'];
        
        // Validate required fields
        if (empty($appliance_type) || empty($brand) || empty($warranty_status)) {
            throw new Exception("Required fields are missing");
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // First, insert into appliance table with all fields
        $appliance_stmt = $conn->prepare("
            INSERT INTO appliance (Appliance_Type, Appliance_Brand, Appliance_Model, Issue_Description, Description_Image) 
            VALUES (?, ?, ?, ?, '')
        ");
        
        if (!$appliance_stmt) {
            throw new Exception("Prepare appliance failed: " . $conn->error);
        }
        
        $issue_desc = "Purchase Date: " . ($purchase_date ? $purchase_date : 'Not specified') . 
                     ", Warranty: " . $warranty_status;
        
        $appliance_stmt->bind_param("ssss", $appliance_type, $brand, $model, $issue_desc);
        
        if (!$appliance_stmt->execute()) {
            throw new Exception("Execute appliance failed: " . $appliance_stmt->error);
        }
        
        $appliance_id = $conn->insert_id;
        $appliance_stmt->close();
        
        // Get the next CAppliance_ID manually
        $max_id_stmt = $conn->prepare("SELECT MAX(CAppliance_ID) as max_id FROM client_appliance");
        $max_id_stmt->execute();
        $max_result = $max_id_stmt->get_result();
        $max_row = $max_result->fetch_assoc();
        $next_id = ($max_row['max_id'] ?? 0) + 1;
        $max_id_stmt->close();
        
        // Then, link client to appliance with explicit CAppliance_ID
        $client_appliance_stmt = $conn->prepare("
            INSERT INTO client_appliance (CAppliance_ID, Client_ID, Appliance_ID) 
            VALUES (?, ?, ?)
        ");
        
        if (!$client_appliance_stmt) {
            throw new Exception("Prepare client_appliance failed: " . $conn->error);
        }
        
        $client_appliance_stmt->bind_param("iii", $next_id, $user_id, $appliance_id);
        
        if ($client_appliance_stmt->execute()) {
            $conn->commit();
            $message = "Appliance registered successfully!";
            $messageType = "success";
        } else {
            throw new Exception("Execute client_appliance failed: " . $client_appliance_stmt->error);
        }
        $client_appliance_stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error registering appliance: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get registered appliances with JOIN
try {
    $appliances_stmt = $conn->prepare("
        SELECT a.*, ca.CAppliance_ID
        FROM appliance a 
        INNER JOIN client_appliance ca ON a.Appliance_ID = ca.Appliance_ID 
        WHERE ca.Client_ID = ? 
        ORDER BY a.Appliance_ID DESC
    ");
    
    if (!$appliances_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $appliances_stmt->bind_param("i", $user_id);
    if (!$appliances_stmt->execute()) {
        throw new Exception("Execute failed: " . $appliances_stmt->error);
    }
    
    $appliances_result = $appliances_stmt->get_result();
    $appliances_stmt->close();
} catch (Exception $e) {
    die('Error getting appliances: ' . $e->getMessage());
}

// Function to extract data from Issue_Description
function extractFromDescription($description, $field) {
    if (strpos($description, $field . ':') !== false) {
        $parts = explode($field . ':', $description);
        if (isset($parts[1])) {
            $value = trim(explode(',', $parts[1])[0]);
            return $value !== 'Not specified' ? $value : '';
        }
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appliances - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client_appliances.css">
</head>
<body>
    <div class="client-container">
        <!-- Header -->
        <header class="client-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">My Appliances</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="client_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Appliance Section -->
        <section class="add-appliance-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">➕</span>
                    Register New Appliance
                </h2>
            </div>

            <form method="POST" class="appliance-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="appliance_type">Appliance Type</label>
                        <select id="appliance_type" name="appliance_type" required>
                            <option value="">Select appliance type</option>
                            <option value="Air Conditioner">Air Conditioner</option>
                            <option value="Refrigerator">Refrigerator</option>
                            <option value="Washing Machine">Washing Machine</option>
                            <option value="Television">Television</option>
                            <option value="Microwave">Microwave</option>
                            <option value="Electric Fan">Electric Fan</option>
                            <option value="Rice Cooker">Rice Cooker</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Mobile Phone">Mobile Phone</option>
                            <option value="Water Heater">Water Heater</option>
                            <option value="Oven">Oven</option>
                            <option value="Dishwasher">Dishwasher</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" required placeholder="e.g., Samsung, LG, Sony">
                    </div>
                    
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" placeholder="Model number or name">
                    </div>
                    
                    <div class="form-group">
                        <label for="purchase_date">Purchase Date</label>
                        <input type="date" id="purchase_date" name="purchase_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="warranty_status">Warranty Status</label>
                        <select id="warranty_status" name="warranty_status" required>
                            <option value="">Select warranty status</option>
                            <option value="Active">Active</option>
                            <option value="Expired">Expired</option>
                            <option value="Unknown">Unknown</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_appliance" class="btn-primary">
                        📱 Register Appliance
                    </button>
                </div>
            </form>
        </section>

        <!-- Registered Appliances -->
        <section class="appliances-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">📱</span>
                    Your Registered Appliances
                </h2>
            </div>

            <?php if ($appliances_result->num_rows > 0): ?>
                <div class="appliances-grid">
                    <?php while ($appliance = $appliances_result->fetch_assoc()): ?>
                        <?php
                        $purchase_date = extractFromDescription($appliance['Issue_Description'], 'Purchase Date');
                        $warranty_status = extractFromDescription($appliance['Issue_Description'], 'Warranty');
                        ?>
                        <div class="appliance-card">
                            <div class="appliance-icon">
                                <?php
                                $icons = [
                                    'Air Conditioner' => '❄️',
                                    'Refrigerator' => '🧊',
                                    'Washing Machine' => '🧺',
                                    'Television' => '📺',
                                    'Microwave' => '🔥',
                                    'Electric Fan' => '💨',
                                    'Rice Cooker' => '🍚',
                                    'Laptop' => '💻',
                                    'Mobile Phone' => '📱',
                                    'Water Heater' => '🚿',
                                    'Oven' => '🔥',
                                    'Dishwasher' => '🍽️',
                                    'Other' => '⚡'
                                ];
                                echo $icons[$appliance['Appliance_Type']] ?? '⚡';
                                ?>
                            </div>
                            
                            <div class="appliance-info">
                                <h3><?php echo htmlspecialchars($appliance['Appliance_Type']); ?></h3>
                                <p class="brand"><?php echo htmlspecialchars($appliance['Appliance_Brand']); ?></p>
                                <?php if (!empty($appliance['Appliance_Model'])): ?>
                                    <p class="model"><?php echo htmlspecialchars($appliance['Appliance_Model']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appliance-details">
                                <?php if (!empty($purchase_date)): ?>
                                    <div class="detail-item">
                                        <span class="label">📅 Purchased:</span>
                                        <span class="value"><?php echo date('M j, Y', strtotime($purchase_date)); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($warranty_status)): ?>
                                    <div class="detail-item">
                                        <span class="label">🛡️ Warranty:</span>
                                        <span class="value warranty-<?php echo strtolower($warranty_status); ?>">
                                            <?php echo htmlspecialchars($warranty_status); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="appliance-actions">
                                <a href="request_service.php?appliance=<?php echo urlencode($appliance['Appliance_Type']); ?>&appliance_id=<?php echo $appliance['Appliance_ID']; ?>" class="btn-primary">
                                    🔧 Request Service
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📱</div>
                    <h3>No Appliances Registered</h3>
                    <p>Register your appliances to keep track of them and make service requests easier.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Benefits Section -->
        <section class="benefits-section">
            <h2 class="section-title">
                <span class="section-icon">💡</span>
                Why Register Your Appliances?
            </h2>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">⚡</div>
                    <h4>Faster Service</h4>
                    <p>Pre-filled information speeds up service requests</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h4>Service History</h4>
                    <p>Track all repairs and maintenance for each appliance</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🛡️</div>
                    <h4>Warranty Tracking</h4>
                    <p>Keep track of warranty status and expiration dates</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🔔</div>
                    <h4>Maintenance Reminders</h4>
                    <p>Get notified when regular maintenance is due</p>
                </div>
            </div>
        </section>
    </div>

    <script>
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