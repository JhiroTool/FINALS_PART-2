<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {
    case 'get':
        // Get all records
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $users]);
        break;
        
    case 'add':
        // Add new record
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if($name && $email) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$name, $email]);
            echo json_encode(['success' => true, 'message' => 'User added successfully', 'id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
        }
        break;
        
    case 'update':
        // Update record
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if($id && $name && $email) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $id]);
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID, name and email are required']);
        }
        break;
        
    case 'delete':
        // Delete record
        $id = $_POST['id'] ?? $_GET['id'] ?? '';
        
        if($id) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
        }
        break;
        
    case 'check_updates':
        // Check for updates since last timestamp
        $lastUpdate = $_GET['last_update'] ?? '1970-01-01 00:00:00';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE updated_at > ? OR created_at > ? ORDER BY id DESC");
        $stmt->execute([$lastUpdate, $lastUpdate]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'data' => $updates,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>