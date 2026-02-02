<?php
require_once 'config.php';
require_login();

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';
$userId = $_SESSION['user_id'];

// Đánh dấu đã đọc nếu có request
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    $stmt->execute([$_POST['notification_id'], $userId]);
    if ($isEmbed) {
        echo json_encode(['success' => true]);
        exit;
    }
}

// Đánh dấu tất cả đã đọc
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
    $stmt->execute([$userId]);
    if ($isEmbed) {
        echo json_encode(['success' => true]);
        exit;
    }
}

// Lấy danh sách thông báo
$notifications = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Fetch notifications failed: ' . $e->getMessage());
}

// Đếm chưa đọc
$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}

if ($isEmbed): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body style="background:#f1f5f9;margin:0;padding:0;">
<?php else:
    require_once 'header.php';
endif; ?>

<style>
.notifications-page {
    padding: 1.5rem;
    max-width: 800px;
    margin: 0 auto;
}
.notifications-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
}
.notifications-header h4 {
    margin: 0;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.notifications-header .badge {
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
}
.btn-mark-all {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn-mark-all:hover {
    background: rgba(255,255,255,0.3);
}
.notification-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.notification-item {
    background: #fff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
}
.notification-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.notification-item.unread {
    background: #eff6ff;
    border-left-color: #3b82f6;
}
.notification-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.notification-icon.type-comment { background: #dbeafe; color: #2563eb; }
.notification-icon.type-message { background: #d1fae5; color: #059669; }
.notification-icon.type-appointment { background: #fef3c7; color: #d97706; }
.notification-icon.type-system { background: #e0e7ff; color: #4f46e5; }
.notification-icon.type-rating { background: #fce7f3; color: #db2777; }
.notification-content {
    flex: 1;
}
.notification-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}
.notification-message {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}
.notification-time {
    font-size: 0.8rem;
    color: #94a3b8;
}
.notification-actions {
    display: flex;
    gap: 0.5rem;
}
.btn-action {
    padding: 0.4rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-view {
    background: #3b82f6;
    color: #fff;
}
.btn-view:hover {
    background: #2563eb;
}
.btn-mark {
    background: #f1f5f9;
    color: #64748b;
}
.btn-mark:hover {
    background: #e2e8f0;
}
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<div class="notifications-page">
    <div class="notifications-header">
        <h4>
            <i class="bi bi-bell-fill"></i>
            Thông báo
            <?php if ($unreadCount > 0): ?>
            <span class="badge"><?php echo $unreadCount; ?> chưa đọc</span>
            <?php endif; ?>
        </h4>
        <?php if ($unreadCount > 0): ?>
        <form method="post" style="margin:0;">
            <button type="submit" name="mark_all_read" class="btn-mark-all">
                <i class="bi bi-check-all me-1"></i> Đánh dấu tất cả đã đọc
            </button>
        </form>
        <?php endif; ?>
    </div>

    <div class="notification-list">
        <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h5>Chưa có thông báo nào</h5>
            <p>Các thông báo mới sẽ xuất hiện ở đây</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $notification): 
            $iconClass = 'type-system';
            $icon = 'bi-info-circle-fill';
            switch ($notification['type']) {
                case 'comment':
                    $iconClass = 'type-comment';
                    $icon = 'bi-chat-dots-fill';
                    break;
                case 'message':
                    $iconClass = 'type-message';
                    $icon = 'bi-envelope-fill';
                    break;
                case 'appointment':
                    $iconClass = 'type-appointment';
                    $icon = 'bi-calendar-check-fill';
                    break;
                case 'rating':
                    $iconClass = 'type-rating';
                    $icon = 'bi-star-fill';
                    break;
            }
        ?>
        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
            <div class="notification-icon <?php echo $iconClass; ?>">
                <i class="bi <?php echo $icon; ?>"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title"><?php echo htmlspecialchars($notification['title'] ?? $notification['type']); ?></div>
                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                <div class="notification-time">
                    <i class="bi bi-clock me-1"></i>
                    <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                </div>
            </div>
            <div class="notification-actions">
                <?php if (!empty($notification['link'])): ?>
                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn-action btn-view">
                    <i class="bi bi-eye"></i> Xem
                </a>
                <?php endif; ?>
                <?php if (!$notification['is_read']): ?>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                    <button type="submit" name="mark_read" class="btn-action btn-mark">
                        <i class="bi bi-check"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($isEmbed): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else:
    require_once 'footer.php';
endif; ?>
