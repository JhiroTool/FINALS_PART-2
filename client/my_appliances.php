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
            $message = "🎉 Appliance registered successfully! Now you can request services more easily.";
            $messageType = "success";
        } else {
            throw new Exception("Execute client_appliance failed: " . $client_appliance_stmt->error);
        }
        $client_appliance_stmt->close();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ Error registering appliance: " . $e->getMessage();
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
    $appliance_count = $appliances_result->num_rows;
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
    <link rel="stylesheet" href="../css/client-dashboard-modern.css">
</head>
<body>
    <!-- Modern Header -->
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <!-- Brand -->
                <div class="brand-section">
                    <div class="logo-container">
                        <img src="../images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div class="brand-text">
                            <h1>PinoyFix</h1>
                            <span>My Appliances</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search appliances..." class="search-input" id="applianceSearch">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell" onclick="showNotification('📬 No new notifications', 'info')">
                        <span class="bell-icon">🔔</span>
                        <span class="notification-badge" id="notifBadge" style="display: none;">1</span>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN'] . ' ' . $client['Client_LN']); ?></h3>
                            <p>Premium Client</p>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="client_dashboard.php">🏠 Dashboard</a>
                                <a href="update_profile.php">👤 Profile Settings</a>
                                <a href="my_bookings.php">📋 My Bookings</a>
                                <a href="billing.php">💳 Billing & Payment</a>
                                <a href="support.php">🎧 Support Center</a>
                                <hr>
                                <a href="../logout.php" class="logout-link">🚪 Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="alert alert-<?php echo $messageType; ?> fade-in">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Section -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%); margin-bottom: 3rem;">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>My Appliance Collection 📱</h1>
                        <p>Manage your appliances, track warranty status, and request services with ease. Keep all your device information organized in one place.</p>
                    </div>
                    
                    <div class="status-cards">
                        <div class="status-card appliances">
                            <div class="card-icon">📱</div>
                            <div class="card-content">
                                <h3><?php echo $appliance_count; ?></h3>
                                <p>Registered</p>
                                <span class="card-trend">Total appliances</span>
                            </div>
                        </div>
                        
                        <div class="status-card active">
                            <div class="card-icon">🛡️</div>
                            <div class="card-content">
                                <h3>Protected</h3>
                                <p>Warranty</p>
                                <span class="card-trend">Under coverage</span>
                            </div>
                        </div>
                        
                        <div class="status-card completed">
                            <div class="card-icon">⚡</div>
                            <div class="card-content">
                                <h3>Quick</h3>
                                <p>Service</p>
                                <span class="card-trend">One-click request</span>
                            </div>
                        </div>

                        <div class="status-card rating">
                            <div class="card-icon">📊</div>
                            <div class="card-content">
                                <h3>History</h3>
                                <p>Tracking</p>
                                <span class="card-trend">Full records</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add New Appliance Section -->
            <section class="marketplace-actions" style="margin-bottom: 3rem;">
                <h2 class="section-title">
                    <span class="title-icon">➕</span>
                    Register New Appliance
                </h2>
                
                <form method="POST" class="appliance-form" id="applianceForm">
                    <div class="action-marketplace" style="display: block;">
                        <div class="action-item featured" style="grid-column: 1/-1;">
                            <div class="action-badge">Quick Add</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>📱 Add Your Appliance</h3>
                                <p>Register your appliances for faster service requests and better tracking</p>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                                    <div class="form-group">
                                        <label for="appliance_type">Appliance Type *</label>
                                        <select id="appliance_type" name="appliance_type" required style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                            <option value="">Choose appliance type</option>
                                            <option value="Air Conditioner">❄️ Air Conditioner</option>
                                            <option value="Refrigerator">🧊 Refrigerator</option>
                                            <option value="Washing Machine">🧺 Washing Machine</option>
                                            <option value="Television">📺 Television</option>
                                            <option value="Microwave">🔥 Microwave</option>
                                            <option value="Electric Fan">💨 Electric Fan</option>
                                            <option value="Rice Cooker">🍚 Rice Cooker</option>
                                            <option value="Laptop">💻 Laptop</option>
                                            <option value="Mobile Phone">📱 Mobile Phone</option>
                                            <option value="Water Heater">🚿 Water Heater</option>
                                            <option value="Oven">🔥 Oven</option>
                                            <option value="Dishwasher">🍽️ Dishwasher</option>
                                            <option value="Other">⚡ Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="brand">Brand *</label>
                                        <input type="text" id="brand" name="brand" required 
                                               placeholder="e.g., Samsung, LG, Sony"
                                               style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="model">Model Number</label>
                                        <input type="text" id="model" name="model" 
                                               placeholder="Model number or name (optional)"
                                               style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="purchase_date">Purchase Date</label>
                                        <input type="date" id="purchase_date" name="purchase_date"
                                               style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="warranty_status">Warranty Status *</label>
                                        <select id="warranty_status" name="warranty_status" required style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                            <option value="">Select warranty status</option>
                                            <option value="Active">🛡️ Active (Under warranty)</option>
                                            <option value="Expired">❌ Expired</option>
                                            <option value="Unknown">❓ Unknown</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="text-align: center; margin-top: 2rem;">
                                    <button type="submit" name="add_appliance" class="action-btn" style="min-width: 250px;">
                                        📱 Register Appliance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Registered Appliances -->
            <section class="marketplace-actions">
                <h2 class="section-title">
                    <span class="title-icon">📱</span>
                    Your Registered Appliances
                    <?php if ($appliance_count > 0): ?>
                        <span style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; margin-left: 1rem;"><?php echo $appliance_count; ?> device<?php echo $appliance_count !== 1 ? 's' : ''; ?></span>
                    <?php endif; ?>
                </h2>

                <?php if ($appliance_count > 0): ?>
                    <div class="action-marketplace">
                        <?php 
                        // Reset the result pointer
                        $appliances_stmt = $conn->prepare("
                            SELECT a.*, ca.CAppliance_ID
                            FROM appliance a 
                            INNER JOIN client_appliance ca ON a.Appliance_ID = ca.Appliance_ID 
                            WHERE ca.Client_ID = ? 
                            ORDER BY a.Appliance_ID DESC
                        ");
                        $appliances_stmt->bind_param("i", $user_id);
                        $appliances_stmt->execute();
                        $appliances_result = $appliances_stmt->get_result();
                        
                        while ($appliance = $appliances_result->fetch_assoc()): 
                            $purchase_date = extractFromDescription($appliance['Issue_Description'], 'Purchase Date');
                            $warranty_status = extractFromDescription($appliance['Issue_Description'], 'Warranty');
                            
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
                            $appliance_icon = $icons[$appliance['Appliance_Type']] ?? '⚡';
                        ?>
                            <div class="action-item" data-appliance="<?php echo htmlspecialchars($appliance['Appliance_Type']); ?>">
                                <div class="action-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8;">
                                    <?php echo $appliance_icon; ?>
                                </div>
                                
                                <div class="action-details">
                                    <h3><?php echo htmlspecialchars($appliance['Appliance_Type']); ?></h3>
                                    <p><?php echo htmlspecialchars($appliance['Appliance_Brand']); ?></p>
                                    
                                    <?php if (!empty($appliance['Appliance_Model'])): ?>
                                        <div class="action-meta">
                                            <span>🏷️ <?php echo htmlspecialchars($appliance['Appliance_Model']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1rem 0; padding: 1rem; background: #f8fafc; border-radius: 12px;">
                                        <?php if (!empty($purchase_date)): ?>
                                            <div>
                                                <div style="font-weight: 600; color: #374151; font-size: 0.9rem;">📅 Purchased</div>
                                                <div style="color: #64748b; font-size: 0.9rem;"><?php echo date('M j, Y', strtotime($purchase_date)); ?></div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($warranty_status)): ?>
                                            <div>
                                                <div style="font-weight: 600; color: #374151; font-size: 0.9rem;">🛡️ Warranty</div>
                                                <div style="color: <?php echo $warranty_status === 'Active' ? '#059669' : ($warranty_status === 'Expired' ? '#dc2626' : '#64748b'); ?>; font-weight: 600; font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($warranty_status); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <a href="request_service.php?appliance=<?php echo urlencode($appliance['Appliance_Type']); ?>&appliance_id=<?php echo $appliance['Appliance_ID']; ?>" class="action-btn">
                                    🔧 Request Service
                                </a>
                            </div>
                        <?php endwhile; ?>
                        <?php $appliances_stmt->close(); ?>
                    </div>
                <?php else: ?>
                    <div class="empty-activity">
                        <div class="empty-icon">📱</div>
                        <h3>No Appliances Registered Yet</h3>
                        <p>Start by registering your first appliance to unlock quick service requests, warranty tracking, and maintenance reminders. It only takes a minute!</p>
                        <div class="empty-btn" onclick="document.getElementById('appliance_type').focus(); document.getElementById('appliance_type').scrollIntoView({behavior: 'smooth'});">
                            ➕ Register Your First Appliance
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Benefits Section -->
            <section class="insights-section" style="margin-top: 3rem;">
                <h2 class="section-title">
                    <span class="title-icon">💡</span>
                    Why Register Your Appliances?
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card maintenance">
                        <div class="insight-icon">⚡</div>
                        <h4>Faster Service Requests</h4>
                        <p>Pre-filled appliance information speeds up service booking. No need to re-enter details every time.</p>
                    </div>
                    
                    <div class="insight-card communication">
                        <div class="insight-icon">📊</div>
                        <h4>Complete Service History</h4>
                        <p>Track all repairs, maintenance, and service records for each appliance in one organized place.</p>
                    </div>
                    
                    <div class="insight-card feedback">
                        <div class="insight-icon">🛡️</div>
                        <h4>Warranty Protection</h4>
                        <p>Keep track of warranty status and expiration dates. Never miss important coverage periods.</p>
                    </div>
                    
                    <div class="insight-card performance">
                        <div class="insight-icon">🔔</div>
                        <h4>Smart Reminders</h4>
                        <p>Get notified about scheduled maintenance, warranty expirations, and optimal service times.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>PinoyFix</h4>
                    <p>Your trusted appliance partner since 2025</p>
                </div>
                <div class="footer-section">
                    <h5>Popular Appliances</h5>
                    <a href="#">❄️ Air Conditioners</a>
                    <a href="#">🧊 Refrigerators</a>
                    <a href="#">📺 Televisions</a>
                    <a href="#">🧺 Washing Machines</a>
                </div>
                <div class="footer-section">
                    <h5>Support</h5>
                    <a href="#">📞 24/7 Hotline</a>
                    <a href="#">💬 Live Chat</a>
                    <a href="#">📧 Email Support</a>
                    <a href="#">❓ Help Center</a>
                </div>
                <div class="footer-section">
                    <h5>Warranty Info</h5>
                    <p>🛡️ Service guarantee</p>
                    <p>📋 Parts warranty</p>
                    <p>🔒 Secure registration</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <style>
        .alert {
            padding: 1.5rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 2px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #0038A8 !important;
            box-shadow: 0 0 0 4px rgba(0, 56, 168, 0.1);
            transform: translateY(-2px);
        }
        
        .empty-btn {
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            color: white;
            padding: 16px 32px;
            border-radius: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 56, 168, 0.3);
        }

        .empty-btn:hover {
            background: linear-gradient(135deg, #002d8f, #2563eb);
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(0, 56, 168, 0.4);
        }
    </style>

    <script>
        // Toggle dropdown menu
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const dropdownBtn = document.querySelector('.dropdown-btn');
            
            if (dropdown && !dropdown.contains(event.target) && !dropdownBtn.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Search functionality
        document.getElementById('applianceSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const applianceItems = document.querySelectorAll('[data-appliance]');
            
            applianceItems.forEach(item => {
                const applianceType = item.getAttribute('data-appliance').toLowerCase();
                const applianceText = item.textContent.toLowerCase();
                
                if (applianceType.includes(searchTerm) || applianceText.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Form validation
        document.getElementById('applianceForm').addEventListener('submit', function(e) {
            const applianceType = document.getElementById('appliance_type').value;
            const brand = document.getElementById('brand').value.trim();
            const warrantyStatus = document.getElementById('warranty_status').value;
            
            if (!applianceType) {
                e.preventDefault();
                showNotification('Please select an appliance type', 'error');
                document.getElementById('appliance_type').focus();
                return;
            }
            
            if (!brand) {
                e.preventDefault();
                showNotification('Please enter the appliance brand', 'error');
                document.getElementById('brand').focus();
                return;
            }
            
            if (!warrantyStatus) {
                e.preventDefault();
                showNotification('Please select warranty status', 'error');
                document.getElementById('warranty_status').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[name="add_appliance"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Registering...';
            submitBtn.style.background = '#94a3b8';
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.textContent = message;
            
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '16px 24px',
                borderRadius: '12px',
                color: 'white',
                fontWeight: '600',
                zIndex: '9999',
                maxWidth: '400px',
                boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                transform: 'translateX(400px)',
                transition: 'all 0.3s ease',
                cursor: 'pointer'
            });

            const colors = {
                success: 'linear-gradient(135deg, #10b981, #059669)',
                error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)'
            };
            
            notification.style.background = colors[type] || colors.info;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);

            notification.addEventListener('click', () => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            });
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 6000);

        // Animate items on load
        document.addEventListener('DOMContentLoaded', function() {
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                    item.style.transition = 'all 0.6s ease';
                }, index * 100);
            });
        });
    </script>
</body>
</html>