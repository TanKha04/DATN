<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_conversations':
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u.name as other_user_name, 
                       u.id as other_user_id,
                       u.role as other_user_role,
                       u.avatar as other_user_avatar,
                       u.verified as other_user_verified,
                       u.last_activity as other_user_last_activity,
                       dm.message as last_message,
                       dm.created_at as last_message_time
                FROM conversations c
                JOIN users u ON (u.id = CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END)
                LEFT JOIN direct_messages dm ON dm.conversation_id = c.id 
                    AND dm.id = (SELECT MAX(id) FROM direct_messages WHERE conversation_id = c.id)
                WHERE c.user1_id = ? OR c.user2_id = ?
                ORDER BY c.last_message_at DESC
            ");
            $stmt->execute([$userId, $userId, $userId]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;
            
        case 'get_friends':
            $status = $_GET['status'] ?? 'accepted';
            
            if ($status === 'pending') {
                // Lời mời kết bạn đang chờ
                $stmt = $pdo->prepare("
                    SELECT f.id as friendship_id, u.id, u.name, u.avatar, u.role, u.verified, u.last_activity
                    FROM friendships f
                    JOIN users u ON u.id = f.user_id
                    WHERE f.friend_id = ? AND f.status = 'pending'
                    ORDER BY f.created_at DESC
                ");
                $stmt->execute([$userId]);
            } else {
                // Bạn bè đã chấp nhận
                $stmt = $pdo->prepare("
                    SELECT u.id, u.name, u.avatar, u.role, u.verified, u.last_activity
                    FROM friendships f
                    JOIN users u ON (u.id = CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END)
                    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
                    ORDER BY u.last_activity DESC
                ");
                $stmt->execute([$userId, $userId, $userId]);
            }
            
            $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get pending count
            $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM friendships WHERE friend_id = ? AND status = 'pending'");
            $pendingStmt->execute([$userId]);
            $pendingCount = (int)$pendingStmt->fetchColumn();
            
            echo json_encode(['success' => true, 'friends' => $friends, 'pending_count' => $pendingCount]);
            break;
            
        case 'get_messages':
            $otherUserId = (int)($_GET['user_id'] ?? 0);
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            
            if (!$otherUserId && !$conversationId) {
                echo json_encode(['success' => false, 'error' => 'Missing user_id or conversation_id']);
                break;
            }
            
            // Get or create conversation
            if (!$conversationId && $otherUserId) {
                $stmt = $pdo->prepare("
                    SELECT id FROM conversations 
                    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
                ");
                $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
                $conv = $stmt->fetch();
                
                if ($conv) {
                    $conversationId = $conv['id'];
                }
            }
            
            // Get other user info
            $userStmt = $pdo->prepare("SELECT id, name, avatar, role, verified, last_activity FROM users WHERE id = ?");
            $userStmt->execute([$otherUserId ?: $conversationId]);
            
            if ($conversationId && !$otherUserId) {
                $convStmt = $pdo->prepare("SELECT user1_id, user2_id FROM conversations WHERE id = ?");
                $convStmt->execute([$conversationId]);
                $convData = $convStmt->fetch();
                $otherUserId = $convData['user1_id'] == $userId ? $convData['user2_id'] : $convData['user1_id'];
                $userStmt->execute([$otherUserId]);
            }
            
            $otherUser = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // Get messages
            $messages = [];
            if ($conversationId) {
                $msgStmt = $pdo->prepare("
                    SELECT dm.*, (dm.sender_id = ?) as is_mine
                    FROM direct_messages dm
                    WHERE dm.conversation_id = ?
                    ORDER BY dm.created_at ASC
                    LIMIT 100
                ");
                $msgStmt->execute([$userId, $conversationId]);
                $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true, 
                'conversation_id' => $conversationId,
                'user' => $otherUser,
                'messages' => $messages
            ]);
            break;
            
        case 'get_new_messages':
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            
            if (!$conversationId) {
                echo json_encode(['success' => false, 'error' => 'Missing conversation_id']);
                break;
            }
            
            $msgStmt = $pdo->prepare("
                SELECT dm.*, (dm.sender_id = ?) as is_mine
                FROM direct_messages dm
                WHERE dm.conversation_id = ?
                ORDER BY dm.created_at ASC
            ");
            $msgStmt->execute([$userId, $conversationId]);
            $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            break;
            
        case 'send_message':
            $otherUserId = (int)($_POST['user_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $conversationId = (int)($_POST['conversation_id'] ?? 0);
            
            if (!$otherUserId || !$message) {
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                break;
            }
            
            // Get or create conversation
            if (!$conversationId) {
                $stmt = $pdo->prepare("
                    SELECT id FROM conversations 
                    WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)
                ");
                $stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
                $conv = $stmt->fetch();
                
                if ($conv) {
                    $conversationId = $conv['id'];
                } else {
                    // Create new conversation
                    $createStmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
                    $createStmt->execute([$userId, $otherUserId]);
                    $conversationId = $pdo->lastInsertId();
                }
            }
            
            // Insert message
            $insertStmt = $pdo->prepare("INSERT INTO direct_messages (conversation_id, sender_id, message) VALUES (?, ?, ?)");
            $insertStmt->execute([$conversationId, $userId, $message]);
            
            // Update conversation last_message_at
            $updateStmt = $pdo->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?");
            $updateStmt->execute([$conversationId]);
            
            echo json_encode(['success' => true, 'conversation_id' => $conversationId, 'message_id' => $pdo->lastInsertId()]);
            break;
            
        case 'handle_friend_request':
            $friendshipId = (int)($_POST['friendship_id'] ?? 0);
            $response = $_POST['response'] ?? '';
            
            if (!$friendshipId || !in_array($response, ['accept', 'reject'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                break;
            }
            
            if ($response === 'accept') {
                $stmt = $pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ? AND friend_id = ?");
            } else {
                $stmt = $pdo->prepare("DELETE FROM friendships WHERE id = ? AND friend_id = ?");
            }
            $stmt->execute([$friendshipId, $userId]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log('Chat API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
