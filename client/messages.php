<?php
session_start();
include '../connection.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX message sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_send'])) {
    header('Content-Type: application/json');
    
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message_text = $_POST['message_text'];
    $booking_id = !empty($_POST['booking_id']) ? $_POST['booking_id'] : null;
    
    try {
        $stmt = $conn->prepare("INSERT INTO messages (Sender_ID, Sender_Type, Receiver_ID, Receiver_Type, Booking_ID, Subject, Message) VALUES (?, 'client', ?, 'technician', ?, ?, ?)");
        $stmt->bind_param("iisss", $user_id, $receiver_id, $booking_id, $subject, $message_text);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Message sent successfully! 💬']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error sending message. Please try again.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Mark messages as read
if (isset($_GET['mark_read']) && $_GET['mark_read'] && isset($_GET['conversation_id'])) {
    $conversation_id = $_GET['conversation_id'];
    try {
        $stmt = $conn->prepare("UPDATE messages SET Is_Read = 1 WHERE Receiver_ID = ? AND Receiver_Type = 'client' AND Sender_ID = ? AND Sender_Type = 'technician'");
        $stmt->bind_param("ii", $user_id, $conversation_id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently handle error
    }
}

// Get client info
try {
    $client_stmt = $conn->prepare("SELECT Client_FN, Client_LN, Client_Email, Client_Phone FROM client WHERE Client_ID = ?");
    $client_stmt->bind_param("i", $user_id);
    $client_stmt->execute();
    $client_result = $client_stmt->get_result();
    $client = $client_result->fetch_assoc();
    $client_stmt->close();
} catch (Exception $e) {
    header("Location: ../login.php");
    exit();
}

// Get active bookings count for notification badge
try {
    $active_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE Client_ID = ? AND Status IN ('pending', 'in-progress')");
    $active_stmt->bind_param("i", $user_id);
    $active_stmt->execute();
    $active_bookings = $active_stmt->get_result()->fetch_assoc()['count'];
    $active_stmt->close();
} catch (Exception $e) {
    $active_bookings = 0;
}

// Get conversations with enhanced info
try {
    $conversations_query = "
        SELECT DISTINCT 
            t.Technician_ID as contact_id,
            t.Technician_FN as contact_name,
            t.Technician_LN as contact_lastname,
            t.Specialization,
            (SELECT m.Message 
             FROM messages m 
             WHERE ((m.Sender_ID = ? AND m.Sender_Type = 'client' AND m.Receiver_ID = t.Technician_ID AND m.Receiver_Type = 'technician')
                OR (m.Sender_ID = t.Technician_ID AND m.Sender_Type = 'technician' AND m.Receiver_ID = ? AND m.Receiver_Type = 'client'))
             ORDER BY m.Created_At DESC 
             LIMIT 1) as last_message,
            (SELECT m.Created_At 
             FROM messages m 
             WHERE ((m.Sender_ID = ? AND m.Sender_Type = 'client' AND m.Receiver_ID = t.Technician_ID AND m.Receiver_Type = 'technician')
                OR (m.Sender_ID = t.Technician_ID AND m.Sender_Type = 'technician' AND m.Receiver_ID = ? AND m.Receiver_Type = 'client'))
             ORDER BY m.Created_At DESC 
             LIMIT 1) as last_message_time,
            (SELECT COUNT(*) 
             FROM messages m 
             WHERE m.Sender_ID = t.Technician_ID 
             AND m.Sender_Type = 'technician' 
             AND m.Receiver_ID = ? 
             AND m.Receiver_Type = 'client' 
             AND m.Is_Read = 0) as unread_count
        FROM technician t
        INNER JOIN booking b ON t.Technician_ID = b.Technician_ID
        WHERE b.Client_ID = ?
        ORDER BY last_message_time DESC, t.Technician_FN ASC
    ";
    
    $conversations_stmt = $conn->prepare($conversations_query);
    $conversations_stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $conversations_stmt->execute();
    $conversations = $conversations_stmt->get_result();
    $conversations_stmt->close();
} catch (Exception $e) {
    $conversations = null;
}

// Get messages for selected conversation
$selected_conversation = null;
$messages_result = null;
if (isset($_GET['conversation_id'])) {
    $conversation_id = $_GET['conversation_id'];
    
    try {
        // Get contact info
        $contact_stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Specialization FROM technician WHERE Technician_ID = ?");
        $contact_stmt->bind_param("i", $conversation_id);
        $contact_stmt->execute();
        $selected_conversation = $contact_stmt->get_result()->fetch_assoc();
        $contact_stmt->close();
        
        // Get messages
        $messages_stmt = $conn->prepare("
            SELECT m.*, 
                   CASE 
                       WHEN m.Sender_Type = 'client' THEN c.Client_FN
                       ELSE t.Technician_FN
                   END as sender_name
            FROM messages m
            LEFT JOIN client c ON m.Sender_ID = c.Client_ID AND m.Sender_Type = 'client'
            LEFT JOIN technician t ON m.Sender_ID = t.Technician_ID AND m.Sender_Type = 'technician'
            WHERE ((m.Sender_ID = ? AND m.Sender_Type = 'client' AND m.Receiver_ID = ? AND m.Receiver_Type = 'technician')
               OR (m.Sender_ID = ? AND m.Sender_Type = 'technician' AND m.Receiver_ID = ? AND m.Receiver_Type = 'client'))
            ORDER BY m.Created_At ASC
        ");
        $messages_stmt->bind_param("iiii", $user_id, $conversation_id, $conversation_id, $user_id);
        $messages_stmt->execute();
        $messages_result = $messages_stmt->get_result();
        $messages_stmt->close();
    } catch (Exception $e) {
        $selected_conversation = null;
        $messages_result = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - PinoyFix Client</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client-dashboard-modern.css">
    <link rel="stylesheet" href="../css/messages2.css">
</head>
<body>
    <!-- Modern Header from Dashboard -->
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <!-- Brand -->
                <div class="brand-section">
                    <div class="logo-container">
                        <img src="../images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div class="brand-text">
                            <h1>PinoyFix</h1>
                            <span>Messages</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search conversations..." class="search-input" id="searchConversations">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell">
                        <span class="bell-icon">🔔</span>
                        <?php if ($active_bookings > 0): ?>
                        <span class="notification-badge"><?php echo $active_bookings; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN']); ?></h3>
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

    <!-- Main Content -->
    <main class="main-content" style="margin-top: 80px;">
        <div class="container">
            <!-- Messages Layout -->
            <div class="messages-layout">
                <!-- Conversations Sidebar -->
                <div class="conversations-sidebar">
                    <div class="sidebar-header">
                        <h2>Conversations</h2>
                        <div class="conversation-filter">
                            <select id="conversationFilter">
                                <option value="all">All Messages</option>
                                <option value="unread">Unread Only</option>
                                <option value="recent">Recent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="conversations-list">
                        <?php if ($conversations && $conversations->num_rows > 0): ?>
                            <?php while ($conv = $conversations->fetch_assoc()): ?>
                                <div class="conversation-item <?php echo (isset($_GET['conversation_id']) && $_GET['conversation_id'] == $conv['contact_id']) ? 'active' : ''; ?>"
                                     onclick="selectConversation(<?php echo $conv['contact_id']; ?>)"
                                     data-unread="<?php echo $conv['unread_count']; ?>">
                                    <div class="conversation-avatar">
                                        <span><?php echo strtoupper(substr($conv['contact_name'], 0, 1)); ?></span>
                                    </div>
                                    <div class="conversation-details">
                                        <div class="conversation-header">
                                            <h4><?php echo htmlspecialchars($conv['contact_name'] . ' ' . $conv['contact_lastname']); ?></h4>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="conversation-preview">
                                            <?php if ($conv['last_message']): ?>
                                                <?php echo htmlspecialchars(strlen($conv['last_message']) > 40 ? substr($conv['last_message'], 0, 40) . '...' : $conv['last_message']); ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($conv['Specialization']); ?>
                                            <?php endif; ?>
                                        </p>
                                        <div class="conversation-meta">
                                            <?php if ($conv['last_message_time']): ?>
                                                <span class="conversation-time">
                                                    <?php 
                                                        $time = new DateTime($conv['last_message_time']);
                                                        $now = new DateTime();
                                                        $diff = $now->diff($time);
                                                        
                                                        if ($diff->days == 0) {
                                                            echo $time->format('g:i A');
                                                        } elseif ($diff->days == 1) {
                                                            echo 'Yesterday';
                                                        } elseif ($diff->days < 7) {
                                                            echo $time->format('D');
                                                        } else {
                                                            echo $time->format('M j');
                                                        }
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-chat">
                                <div class="empty-icon">💬</div>
                                <h3>No Conversations</h3>
                                <p>Start messaging your technicians when you book services.</p>
                                <a href="request_service.php" class="empty-btn">Book Your First Service</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($selected_conversation): ?>
                        <!-- Chat Header -->
                        <div class="chat-header">
                            <div class="chat-avatar">
                                <span><?php echo strtoupper(substr($selected_conversation['Technician_FN'], 0, 1)); ?></span>
                            </div>
                            <div class="chat-info">
                                <h3><?php echo htmlspecialchars($selected_conversation['Technician_FN'] . ' ' . $selected_conversation['Technician_LN']); ?></h3>
                                <p><?php echo htmlspecialchars($selected_conversation['Specialization']); ?> • <span class="online-status">Available</span></p>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="chat-messages" id="messagesContainer">
                            <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                                <?php while ($msg = $messages_result->fetch_assoc()): ?>
                                    <div class="message <?php echo ($msg['Sender_Type'] == 'client') ? 'sent' : 'received'; ?>">
                                        <div class="message-avatar">
                                            <span><?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?></span>
                                        </div>
                                        <div class="message-content">
                                            <?php if (!empty($msg['Subject']) && $msg['Subject'] != 'Service Question'): ?>
                                                <div style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                                    <?php echo htmlspecialchars($msg['Subject']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <p class="message-text"><?php echo nl2br(htmlspecialchars($msg['Message'])); ?></p>
                                            <div class="message-time">
                                                <?php echo date('M j, Y g:i A', strtotime($msg['Created_At'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-chat">
                                    <div class="empty-icon">💬</div>
                                    <h3>Start the Conversation</h3>
                                    <p>Send your first message to this technician.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Message Input -->
                        <div class="chat-input">
                            <form id="messageForm" class="input-container">
                                <input type="hidden" name="receiver_id" value="<?php echo htmlspecialchars($_GET['conversation_id']); ?>">
                                <input type="hidden" name="subject" value="Service Question">
                                <textarea name="message_text" id="messageText" class="message-input" placeholder="Type your message..." required maxlength="1000"></textarea>
                                <div class="input-actions">
                                    <button type="submit" id="sendBtn" class="send-btn">
                                        <span id="btnText">📤</span>
                                        <span id="btnLoader" style="display: none;">⏳</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="empty-chat">
                            <div class="empty-icon">💬</div>
                            <h3>Select a Conversation</h3>
                            <p>Choose a technician from the sidebar to start messaging.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function selectConversation(technicianId) {
            window.location.href = `messages.php?conversation_id=${technicianId}&mark_read=1`;
        }

        // Custom toast notification
        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.custom-toast');
            if (existingToast) {
                existingToast.remove();
            }

            const toast = document.createElement('div');
            toast.className = `custom-toast toast-${type}`;
            
            const icon = type === 'success' ? '✅' : '❌';
            
            toast.innerHTML = `
                <div class="toast-content">
                    <span class="toast-icon">${icon}</span>
                    <span class="toast-message">${message}</span>
                </div>
                <div class="toast-progress"></div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => toast.classList.add('show'), 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Add new message to chat
        function addMessageToChat(messageText, senderName) {
            const messagesContainer = document.getElementById('messagesContainer');
            const currentTime = new Date().toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });

            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <span>${senderName.charAt(0).toUpperCase()}</span>
                </div>
                <div class="message-content">
                    <p class="message-text">${messageText.replace(/\n/g, '<br>')}</p>
                    <div class="message-time">${currentTime}</div>
                </div>
            `;

            // Remove empty chat if it exists
            const emptyChat = messagesContainer.querySelector('.empty-chat');
            if (emptyChat) {
                emptyChat.remove();
            }

            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const messagesContainer = document.getElementById('messagesContainer');
            const messageForm = document.getElementById('messageForm');
            const messageText = document.getElementById('messageText');
            const sendBtn = document.getElementById('sendBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const searchInput = document.getElementById('searchConversations');
            const conversationFilter = document.getElementById('conversationFilter');

            // Auto-scroll to bottom on load
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            // Search conversations
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const conversations = document.querySelectorAll('.conversation-item');
                    
                    conversations.forEach(conv => {
                        const name = conv.querySelector('h4').textContent.toLowerCase();
                        const preview = conv.querySelector('.conversation-preview').textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || preview.includes(searchTerm)) {
                            conv.style.display = 'flex';
                        } else {
                            conv.style.display = 'none';
                        }
                    });
                });
            }

            // Filter conversations
            if (conversationFilter) {
                conversationFilter.addEventListener('change', function(e) {
                    const filterValue = e.target.value;
                    const conversations = document.querySelectorAll('.conversation-item');
                    
                    conversations.forEach(conv => {
                        switch(filterValue) {
                            case 'unread':
                                conv.style.display = parseInt(conv.dataset.unread) > 0 ? 'flex' : 'none';
                                break;
                            case 'recent':
                                // Show only conversations with messages from last 3 days
                                const time = conv.querySelector('.conversation-time');
                                const isRecent = time && !time.textContent.includes('/');
                                conv.style.display = isRecent ? 'flex' : 'none';
                                break;
                            default:
                                conv.style.display = 'flex';
                        }
                    });
                });
            }

            // Handle form submission with AJAX
            if (messageForm) {
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(messageForm);
                    formData.append('ajax_send', '1');
                    
                    // Show loading state
                    sendBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline';
                    
                    fetch('messages.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Add message to chat immediately
                            addMessageToChat(messageText.value, '<?php echo $client['Client_FN']; ?>');
                            
                            // Clear form
                            messageText.value = '';
                            messageText.style.height = 'auto';
                            
                            // Show success toast
                            showToast(data.message, 'success');
                        } else {
                            showToast(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Network error. Please try again.', 'error');
                    })
                    .finally(() => {
                        // Reset button state
                        sendBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                    });
                });
            }

            // Auto-resize textarea
            if (messageText) {
                messageText.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 150) + 'px';
                });

                // Send message with Enter (Shift+Enter for new line)
                messageText.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        messageForm.dispatchEvent(new Event('submit'));
                    }
                });
            }

            // Header search functionality
            const headerSearchInput = document.querySelector('.search-input');
            if (headerSearchInput) {
                headerSearchInput.addEventListener('focus', function() {
                    this.parentElement.classList.add('search-focused');
                });

                headerSearchInput.addEventListener('blur', function() {
                    this.parentElement.classList.remove('search-focused');
                });
            }

            // Dropdown menu functionality
            const dropdownBtn = document.querySelector('.dropdown-btn');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function() {
                    dropdownMenu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>