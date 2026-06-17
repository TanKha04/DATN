<?php
require_once 'config.php';

$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($postId <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT p.*, u.name AS author_name, u.email AS author_email, u.verified AS author_verified, u.avatar AS author_avatar, u.role AS author_role, u.school AS author_school, u.class_code AS author_class_code, u.created_at AS author_created_at FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ? LIMIT 1');
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    require_once 'header.php';
    echo '<div class="alert alert-warning">Bài đăng không tồn tại hoặc đã bị xóa.</div>';
    require_once 'footer.php';
    exit;
}

// Load up to 3 similar posts
$similarPosts = [];
try {
    $similarStmt = $pdo->prepare('SELECT p.*, u.name AS author_name, u.avatar AS author_avatar FROM posts p JOIN users u ON u.id = p.user_id WHERE p.type = ? AND p.id != ? AND p.status = "open" ORDER BY p.created_at DESC LIMIT 3');
    $similarStmt->execute([$post['type'], $postId]);
    $similarPosts = $similarStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $similarPosts = [];
}

$loggedIn = is_logged_in();
$isAdmin = is_admin_user();
$currentUserId = $loggedIn ? (int)$_SESSION['user_id'] : null;
$isOwner = $loggedIn && (int)($post['user_id'] ?? 0) === $currentUserId;

// Define role and verification variables globally
$needsVerification = $loggedIn && !$isAdmin && (($_SESSION['role'] ?? '') === 'student') && !is_student_verified();
$currentUserRole = $_SESSION['role'] ?? '';
$authorRole = $post['author_role'] ?? '';
$isPatientViewingPatient = ($currentUserRole === 'patient' && $authorRole === 'patient');

$isFavorite = false;
if ($loggedIn && !$isOwner) {
    try {
        $favStmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND post_id = ? LIMIT 1');
        $favStmt->execute([$currentUserId, $postId]);
        $isFavorite = (bool)$favStmt->fetchColumn();
    } catch (Throwable $e) {}
}

$favoriteStatus = $_GET['fav'] ?? null;
$favoriteError = $_GET['fav_error'] ?? null;
$favoriteLoginUrl = 'login.php?redirect=' . urlencode('view_post.php?id=' . $postId);
$areaValue = trim((string)($post['area'] ?? ''));
$areaIsLink = filter_var($areaValue, FILTER_VALIDATE_URL) !== false;

// Tách kỹ năng từ content nếu có
$content = $post['content'];
$skills = [];
if (preg_match('/Kỹ năng nổi bật:\s*(.+?)(?:\n|$)/i', $content, $matches)) {
    $skillsText = trim($matches[1]);
    $skills = array_map('trim', preg_split('/[,;]/', $skillsText));
    $content = preg_replace('/Kỹ năng nổi bật:\s*.+?(?:\n|$)/i', '', $content);
}

// Load comments
$comments = [];
try {
    $showAll = $isAdmin;
    $sql = 'SELECT c.id, c.content AS comment, c.parent_id, c.is_hidden, c.created_at, c.updated_at, c.user_id, 
            u.name AS author_name, u.avatar AS author_avatar,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) AS like_count
            FROM comments c JOIN users u ON u.id=c.user_id WHERE c.post_id = ?';
    if (!$showAll) { $sql .= ' AND (c.is_hidden IS NULL OR c.is_hidden = 0)'; }
    $sql .= ' ORDER BY c.parent_id IS NULL DESC, c.created_at ASC';
    $cstmt = $pdo->prepare($sql);
    $cstmt->execute([$postId]);
    $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh sách comment đã like của user hiện tại
    $userLikedComments = [];
    if ($loggedIn) {
        $likeStmt = $pdo->prepare('SELECT comment_id FROM comment_likes WHERE user_id = ?');
        $likeStmt->execute([$currentUserId]);
        $userLikedComments = $likeStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (Throwable $e) { $comments = []; $userLikedComments = []; }

require_once 'header.php';

// Hàm render comment
function renderComment($c, $repliesMap, $currentUserId, $isAdmin, $loggedIn, $userLikedComments) {
    $isOwner = $currentUserId && $c['user_id'] == $currentUserId;
    $isHidden = !empty($c['is_hidden']);
    $isLiked = in_array($c['id'], $userLikedComments);
    $likeCount = (int)($c['like_count'] ?? 0);
    $replies = $repliesMap[$c['id']] ?? [];
    
    ob_start();
    ?>
    <div class="vp-comment <?php echo $isHidden ? 'vp-comment-hidden' : ''; ?>" data-comment-id="<?php echo $c['id']; ?>">
        <div class="vp-comment-avatar">
            <?php if (!empty($c['author_avatar'])): ?>
                <img src="<?php echo htmlspecialchars($c['author_avatar']); ?>" alt="">
            <?php else: ?>
                <?php echo strtoupper(substr($c['author_name'], 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="vp-comment-content">
            <div class="vp-comment-header">
                <span class="vp-comment-author">
                    <?php echo htmlspecialchars($c['author_name']); ?>
                    <?php if ($isHidden && $isAdmin): ?>
                        <span class="vp-comment-hidden-badge"><i class="bi bi-eye-slash"></i> Đã ẩn</span>
                    <?php endif; ?>
                </span>
                <span class="vp-comment-date">
                    <i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                    <?php if (!empty($c['updated_at'])): ?>
                        <span class="vp-comment-edited">(đã sửa)</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="vp-comment-text" id="comment-text-<?php echo $c['id']; ?>"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>
            
            <!-- Comment Actions -->
            <div class="vp-comment-actions">
                <?php if ($loggedIn): ?>
                    <button class="vp-comment-action <?php echo $isLiked ? 'liked' : ''; ?>" onclick="likeComment(<?php echo $c['id']; ?>, this)">
                        <i class="bi bi-heart<?php echo $isLiked ? '-fill' : ''; ?>"></i>
                        <span class="like-count"><?php echo $likeCount > 0 ? $likeCount : ''; ?></span>
                    </button>
                    <button class="vp-comment-action" onclick="showReplyForm(<?php echo $c['id']; ?>)">
                        <i class="bi bi-reply"></i> Trả lời
                    </button>
                <?php else: ?>
                    <span class="vp-comment-action" style="cursor:default;">
                        <i class="bi bi-heart"></i> <?php echo $likeCount > 0 ? $likeCount : ''; ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($isOwner): ?>
                    <button class="vp-comment-action" onclick="editComment(<?php echo $c['id']; ?>)">
                        <i class="bi bi-pencil"></i> Sửa
                    </button>
                    <button class="vp-comment-action" onclick="deleteComment(<?php echo $c['id']; ?>)">
                        <i class="bi bi-trash"></i> Xóa
                    </button>
                <?php endif; ?>
                
                <?php if ($loggedIn && !$isOwner): ?>
                    <button class="vp-comment-action" onclick="showReportModal(<?php echo $c['id']; ?>)">
                        <i class="bi bi-flag"></i> Báo cáo
                    </button>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                    <button class="vp-comment-action" onclick="toggleHideComment(<?php echo $c['id']; ?>, this)">
                        <i class="bi bi-<?php echo $isHidden ? 'eye' : 'eye-slash'; ?>"></i>
                        <?php echo $isHidden ? 'Hiện' : 'Ẩn'; ?>
                    </button>
                    <?php if (!$isOwner): ?>
                    <button class="vp-comment-action" onclick="deleteComment(<?php echo $c['id']; ?>)" style="color:#ef4444;">
                        <i class="bi bi-trash"></i> Xóa (Admin)
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Reply Form -->
            <div class="vp-reply-form" id="reply-form-<?php echo $c['id']; ?>">
                <textarea placeholder="Viết trả lời..." id="reply-text-<?php echo $c['id']; ?>"></textarea>
                <div class="vp-reply-form-actions">
                    <button class="vp-reply-btn secondary" onclick="hideReplyForm(<?php echo $c['id']; ?>)">Hủy</button>
                    <button class="vp-reply-btn primary" onclick="submitReply(<?php echo $c['id']; ?>)">
                        <i class="bi bi-send"></i> Gửi
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($replies)): ?>
    <div class="vp-comment-replies">
        <?php foreach ($replies as $reply): ?>
            <?php echo renderComment($reply, $repliesMap, $currentUserId, $isAdmin, $loggedIn, $userLikedComments); ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

:root {
    --vp-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    --vp-primary: #0f766e;
    --vp-primary-hover: #0d9488;
    --vp-primary-light: #f0fdf4;
    --vp-success: #10b981;
    --vp-success-light: #ecfdf5;
    --vp-success-border: #bbf7d0;
    --vp-warning: #f59e0b;
    --vp-warning-light: #fef3c7;
    --vp-danger: #ef4444;
    --vp-danger-light: #fee2e2;
    --vp-info: #06b6d4;
    --vp-text-main: #1e293b;
    --vp-text-muted: #64748b;
    --vp-card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
    --vp-card-shadow-hover: 0 20px 40px -15px rgba(15, 118, 110, 0.15), 0 1px 3px rgba(0, 0, 0, 0.02);
    --vp-radius-lg: 24px;
    --vp-radius-md: 16px;
    --vp-radius-sm: 12px;
}

/* Page Reset & Base Styles */
.vp-page {
    font-family: var(--vp-font);
    background-color: #f8fafc;
    background-image: radial-gradient(at 0% 0%, rgba(219, 234, 254, 0.4) 0, transparent 50%), 
                      radial-gradient(at 50% 0%, rgba(243, 244, 246, 0.3) 0, transparent 50%), 
                      radial-gradient(at 100% 0%, rgba(219, 234, 254, 0.4) 0, transparent 50%);
    min-height: 100vh;
    margin: -1.5rem -0.75rem;
    padding: 2.5rem 1rem;
    color: var(--vp-text-main);
}
.vp-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 2rem;
}

/* Layout 1: Application Hero (Image 1 Style) */
.vp-app-hero {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #0f766e 0%, #1e3a8a 100%);
    border-radius: var(--vp-radius-lg);
    padding: 2.5rem;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    box-shadow: 0 20px 50px rgba(15, 118, 110, 0.25);
    position: relative;
    overflow: hidden;
}
.vp-app-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Cpath d='M27 3h6v24h24v6H33v24h-6V33H3v-6h24V3z' fill='%23ffffff' fill-opacity='0.035' fill-rule='evenodd'/%3E%3C/svg%3E");
    pointer-events: none;
}
.vp-app-hero-content {
    flex: 1;
    z-index: 1;
}
.vp-app-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    opacity: 0.9;
    margin-bottom: 1.25rem;
}
.vp-app-breadcrumb a {
    color: white;
    text-decoration: none;
    opacity: 0.8;
    transition: opacity 0.2s;
}
.vp-app-breadcrumb a:hover { opacity: 1; }
.vp-app-title {
    font-size: 2.25rem;
    font-weight: 800;
    margin: 0 0 1.5rem;
    letter-spacing: -0.5px;
}
.vp-app-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: center;
}
.vp-app-author-card {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 0.6rem 1.25rem;
    border-radius: var(--vp-radius-md);
    border: 1px solid rgba(255, 255, 255, 0.25);
}
.vp-app-author-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--vp-primary);
    font-weight: 800;
    font-size: 1.15rem;
    overflow: hidden;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}
.vp-app-author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-app-author-name {
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.vp-app-author-role {
    font-size: 0.8rem;
    opacity: 0.85;
    margin-top: 0.15rem;
}
.vp-app-verified {
    color: #10b981;
}
.vp-app-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    opacity: 0.9;
    font-weight: 500;
}
.vp-app-status {
    padding: 0.5rem 1.25rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}
.vp-app-status.open { background: #10b981; color: white; }
.vp-app-status.closed { background: rgba(255, 255, 255, 0.2); color: white; }
.vp-app-status.taken { background: #3b82f6; color: white; }
.vp-medical-id-card {
    background: white;
    border-radius: var(--vp-radius-md);
    width: 210px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
    z-index: 1;
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    font-family: var(--vp-font);
}
.vp-id-card-header {
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    padding: 0.6rem;
    text-align: center;
    color: white;
    border-bottom: 2px solid #0284c7;
}
.vp-id-card-hospital {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}
.vp-id-card-hospital i {
    color: #38bdf8;
}
.vp-id-card-sub {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 1px;
    opacity: 0.9;
    color: #e0f2fe;
}
.vp-id-card-body {
    padding: 1rem 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #fff;
    flex-grow: 1;
}
.vp-id-avatar-container {
    position: relative;
    margin-bottom: 0.75rem;
}
.vp-id-avatar {
    width: 76px;
    height: 76px;
    border-radius: 8px;
    object-fit: cover;
    border: 2px solid #e2e8f0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.vp-id-avatar-placeholder {
    width: 76px;
    height: 76px;
    border-radius: 8px;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    border: 2px solid #e2e8f0;
}
.vp-id-verified-seal {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: white;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
.vp-id-verified-seal i {
    color: #10b981;
    font-size: 1rem;
}
.vp-id-details {
    width: 100%;
    text-align: center;
}
.vp-id-name {
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
}
.vp-id-info-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    line-height: 1.4;
    border-bottom: 1px dashed #f1f5f9;
    padding: 0.25rem 0;
}
.vp-id-info-row:last-child {
    border-bottom: none;
}
.vp-id-label {
    color: #64748b;
    font-weight: 600;
    text-align: left;
}
.vp-id-value {
    color: #1e293b;
    font-weight: 700;
    text-align: right;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.vp-id-card-footer {
    background: #f8fafc;
    padding: 0.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: center;
    align-items: center;
}
.vp-id-barcode {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.vp-barcode-lines {
    width: 120px;
    height: 20px;
    background: repeating-linear-gradient(
        90deg,
        #000,
        #000 2px,
        transparent 2px,
        transparent 4px,
        #000 4px,
        #000 5px,
        transparent 5px,
        transparent 8px
    );
    opacity: 0.8;
}
.vp-barcode-number {
    font-size: 0.6rem;
    font-family: monospace;
    color: #64748b;
    margin-top: 2px;
    letter-spacing: 1.5px;
}

/* Layout 2: Recruitment Header Card (Image 2 Style) */
.vp-rec-header-card {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
    border-radius: var(--vp-radius-md);
    padding: 1rem 1.75rem;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 10px 30px rgba(30, 64, 175, 0.15);
    flex-wrap: wrap;
    gap: 1rem;
}
.vp-rec-header-author {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}
.vp-rec-header-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e40af;
    font-weight: 800;
    font-size: 1.1rem;
    overflow: hidden;
}
.vp-rec-header-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-rec-header-name {
    font-size: 0.95rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.vp-rec-header-role {
    font-size: 0.8rem;
    opacity: 0.85;
    margin-top: 0.1rem;
}
.vp-rec-header-meta {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    font-size: 0.85rem;
}
.vp-rec-header-date {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    opacity: 0.9;
}
.vp-rec-header-status {
    padding: 0.4rem 1rem;
    border-radius: 15px;
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
}
.vp-rec-header-status.open { background: #10b981; color: white; }
.vp-rec-header-status.closed { background: rgba(255, 255, 255, 0.2); color: white; }
.vp-rec-header-status.taken { background: #3b82f6; color: white; }

/* Main Columns Layout */
.vp-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}
.vp-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Card Base Styles */
.vp-card {
    background: white;
    border-radius: var(--vp-radius-md);
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    overflow: hidden;
    transition: box-shadow 0.3s ease, border-color 0.3s ease;
}
.vp-card:hover {
    box-shadow: var(--vp-card-shadow-hover);
    border-color: rgba(15, 118, 110, 0.25);
}
.vp-card-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.vp-card-header i {
    font-size: 1.15rem;
    color: var(--vp-primary);
}
.vp-card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: var(--vp-text-main);
}
.vp-card-body {
    padding: 1.5rem;
}

/* recruitment Style Title Card */
.vp-rec-title-card {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 2rem;
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.vp-rec-title-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.vp-rec-title-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.85rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}
.vp-rec-title-tag.type-rec {
    background: #eff6ff;
    color: #1e40af;
}
.vp-rec-title-tag.type-rec-full {
    background: #ecfdf5;
    color: #047857;
}
.vp-rec-title-tag.type-rec-night {
    background: #faf5ff;
    color: #7e22ce;
}
.vp-rec-title-tag.urgent {
    background: #fee2e2;
    color: #b91c1c;
    animation: urgentPulse 1.5s infinite;
}
@keyframes urgentPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.03); }
}
.vp-rec-title-text {
    font-size: 1.85rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.35;
    margin: 0;
}
.vp-rec-title-time {
    font-size: 0.8rem;
    color: var(--vp-text-muted);
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.25rem;
}

/* Quick Actions Card (Image 1 Style) */
.vp-app-quick-actions {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 0.75rem 1.25rem;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02);
    border: 1px solid #f1f5f9;
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}
.vp-quick-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    cursor: pointer;
}
.vp-quick-btn.apply {
    background: linear-gradient(135deg, var(--vp-primary) 0%, var(--vp-primary-hover) 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
}
.vp-quick-btn.apply:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(15, 118, 110, 0.25);
    color: white;
}
.vp-quick-btn.save {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}
.vp-quick-btn.save:hover {
    background: #fde68a;
    transform: translateY(-1px);
}
.vp-quick-btn.save.active {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(217, 119, 6, 0.15);
}
.vp-quick-btn.save.active:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(217, 119, 6, 0.25);
}
.vp-quick-btn.back {
    background: #f8fafc;
    color: #64748b;
    border-color: #e2e8f0;
}
.vp-quick-btn.back:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #475569;
    transform: translateY(-1px);
}
.vp-quick-btn.edit {
    background: #f0f9ff;
    color: #0369a1;
    border-color: #bae6fd;
}
.vp-quick-btn.edit:hover {
    background: #e0f2fe;
    border-color: #7dd3fc;
    color: #0369a1;
    transform: translateY(-1px);
}

/* Tags & Skills Styles */
.vp-skills-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.vp-skill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1rem;
    background: var(--vp-primary-light);
    color: var(--vp-primary);
    border: 1px solid #ccfbf1;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
}
.vp-skill:hover {
    background: #ccfbf1;
    transform: translateY(-1px);
}

/* Custom grid layout helpers */
@media (min-width: 768px) {
    .vp-border-md-end {
        border-right: 1px solid #f1f5f9;
        padding-right: 1.5rem !important;
    }
    .vp-padding-md-start {
        padding-left: 1.5rem !important;
    }
}

/* Content typography */
.vp-content {
    font-size: 0.95rem;
    line-height: 1.75;
    color: #334155;
    white-space: pre-line;
}

/* Styled Evidence Documents (Image 2 style) */
.vp-doc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 1rem;
}
.vp-doc-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: var(--vp-radius-sm);
    padding: 1.25rem 0.75rem;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
    cursor: pointer;
}
.vp-doc-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    background: white;
    border-color: var(--vp-primary);
}
.vp-doc-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--vp-primary-light);
    color: var(--vp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}
.vp-doc-card.pdf .vp-doc-icon {
    background: #fee2e2;
    color: #ef4444;
}
.vp-doc-card.image .vp-doc-icon {
    background: #ccfbf1;
    color: #0f766e;
}
.vp-doc-card.lock .vp-doc-icon {
    background: #f1f5f9;
    color: #94a3b8;
}
.vp-doc-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: #475569;
    word-break: break-all;
    line-height: 1.35;
}

/* Recruitment Sidebar Card 1: Proposed Salary (Image 2) */
.vp-rec-salary-card {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 1.75rem;
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.vp-rec-salary-header {
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 1rem;
}
.vp-rec-salary-label {
    font-size: 0.8rem;
    color: var(--vp-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.vp-rec-salary-value {
    font-size: 1.65rem;
    font-weight: 800;
    color: #10b981;
    margin-top: 0.25rem;
}
.vp-rec-salary-details {
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}
.vp-rec-salary-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.4;
}
.vp-rec-salary-detail-item i {
    color: var(--vp-text-muted);
    font-size: 0.95rem;
    margin-top: 0.15rem;
    width: 16px;
    text-align: center;
}

/* Recruitment Sidebar Card 2: Author info card */
.vp-rec-author-card {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 1.75rem;
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.vp-rec-author-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e3a8a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.75rem;
}
.vp-rec-author-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.vp-rec-author-avatar {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    background: var(--vp-primary-light);
    color: var(--vp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.25rem;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid #ccfbf1;
}
.vp-rec-author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-rec-author-meta {
    flex: 1;
}
.vp-rec-author-name {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.vp-rec-author-joined {
    font-size: 0.75rem;
    color: var(--vp-text-muted);
    margin-top: 0.15rem;
}
.vp-rec-author-stats {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    background: #f8fafc;
    padding: 0.85rem;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
}
.vp-rec-author-stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #475569;
}
.vp-rec-author-stat-value {
    font-weight: 700;
    color: #0f172a;
}
.vp-rec-author-verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--vp-primary);
    font-weight: 600;
}

/* Similar Postings Sidebar Card */
.vp-rec-similar-card {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 1.75rem;
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.vp-rec-similar-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.75rem;
}
.vp-rec-similar-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.vp-rec-similar-item {
    display: flex;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s;
}
.vp-rec-similar-item:hover {
    transform: translateX(4px);
}
.vp-rec-similar-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}
.vp-rec-similar-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-rec-similar-info {
    flex: 1;
}
.vp-rec-similar-name {
    font-size: 0.8rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.vp-rec-similar-meta {
    font-size: 0.75rem;
    color: var(--vp-text-muted);
    margin-top: 0.2rem;
    display: flex;
    justify-content: space-between;
}
.vp-rec-similar-btn-all {
    display: block;
    text-align: center;
    padding: 0.7rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    color: #475569;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.2s;
}
.vp-rec-similar-btn-all:hover {
    background: var(--vp-primary-light);
    border-color: #ccfbf1;
    color: var(--vp-primary-hover);
}

/* Sidebar action card (Image 1 Style) */
.vp-action-card {
    background: white;
    border-radius: var(--vp-radius-md);
    padding: 1.75rem;
    box-shadow: var(--vp-card-shadow);
    border: 1px solid rgba(226, 232, 240, 0.8);
}
.vp-action-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.vp-action-title i { color: var(--vp-primary); }
.vp-action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.vp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
}
.vp-btn-primary {
    background: linear-gradient(135deg, #0f766e 0%, #1e3a8a 100%);
    color: white;
    box-shadow: 0 4px 14px rgba(15, 118, 110, 0.25);
}
.vp-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(15, 118, 110, 0.35);
    color: white;
}
.vp-btn-outline {
    background: white;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.vp-btn-outline:hover {
    background: #f8fafc;
    border-color: var(--vp-primary);
    color: var(--vp-primary);
}
.vp-btn-fav {
    background: #fee2e2;
    color: #b91c1c;
}
.vp-btn-fav:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}
.vp-btn-fav.active {
    background: #ef4444;
    color: white;
}
.vp-btn-disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

/* Evidence details and Lightbox styles */
.vp-evidence-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.vp-evidence-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: var(--vp-radius-md);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.vp-evidence-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.vp-evidence-title i {
    color: #10b981;
}
.vp-evidence-img-container {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: #e2e8f0;
    aspect-ratio: 4 / 3;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #cbd5e1;
}
.vp-evidence-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.vp-evidence-img-container:hover .vp-evidence-img {
    transform: scale(1.05);
}
.vp-evidence-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.85rem;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.vp-evidence-img-container:hover .vp-evidence-overlay {
    opacity: 1;
}
.vp-evidence-desc {
    font-size: 0.8rem;
    color: #475569;
    line-height: 1.5;
    background: var(--vp-primary-light);
    padding: 0.6rem 0.85rem;
    border-radius: var(--vp-radius-sm);
    border-left: 3px solid #10b981;
}

/* Styled Video Section */
.vp-video-card {
    background: white;
    border-radius: var(--vp-radius-md);
    box-shadow: var(--vp-card-shadow);
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, 0.8);
}
.vp-premium-video-wrapper {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    background: #0f172a;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}
.vp-premium-video-player {
    width: 100%;
    max-height: 400px;
    display: block;
}
.vp-video-placeholder {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}
.vp-video-placeholder-icon {
    width: 56px;
    height: 56px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}
.vp-video-placeholder-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.5rem;
}
.vp-video-placeholder-text {
    font-size: 0.8rem;
    color: #64748b;
    max-width: 400px;
    margin: 0 auto;
    line-height: 1.5;
}
.vp-video-placeholder-info {
    margin-top: 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--vp-primary-light);
    border: 1px solid #ccfbf1;
    color: var(--vp-primary);
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 20px;
    text-align: left;
}

/* Comments Section Style updates */
.vp-comments-body {
    padding: 1.5rem 2rem;
}
.vp-comment {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    background: #f8fafc;
    border-radius: var(--vp-radius-md);
    margin-bottom: 1.25rem;
    transition: all 0.2s;
    border: 1px solid #f1f5f9;
}
.vp-comment:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
    border-color: #e2e8f0;
}
.vp-comment-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0f766e 0%, #1e3a8a 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    overflow: hidden;
}
.vp-comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.vp-comment-content { flex: 1; }
.vp-comment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.vp-comment-author { font-weight: 700; color: #0f172a; font-size: 0.9rem; }
.vp-comment-date { font-size: 0.75rem; color: var(--vp-text-muted); }
.vp-comment-text { color: #334155; line-height: 1.6; font-size: 0.9rem; }
.vp-comment-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.75rem;
    flex-wrap: wrap;
}
.vp-comment-action {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    background: #f1f5f9;
    color: #475569;
}
.vp-comment-action:hover { background: #e2e8f0; color: #1e293b; }
.vp-comment-action.liked { background: #fee2e2; color: #ef4444; }
.vp-comment-action.liked:hover { background: #fecaca; }
.vp-comment-replies {
    margin-left: 3.25rem;
    margin-top: -0.5rem;
    padding-left: 1rem;
    border-left: 2px solid #e2e8f0;
}
.vp-comment-replies .vp-comment {
    background: #ffffff;
    margin-bottom: 1rem;
    border: 1px solid #f1f5f9;
}

/* Comment inputs */
.vp-comment-form {
    padding: 1.5rem 2rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
}
.vp-comment-form label {
    font-weight: 700;
    color: var(--vp-text-main);
    margin-bottom: 0.75rem;
    display: block;
    font-size: 0.9rem;
}
.vp-comment-form textarea {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: var(--vp-radius-sm);
    font-size: 0.9rem;
    resize: vertical;
    min-height: 80px;
    transition: all 0.2s;
    background: white;
}
.vp-comment-form textarea:focus {
    outline: none;
    border-color: var(--vp-primary);
    box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.1);
}
.vp-comment-form .btn-submit {
    margin-top: 1rem;
    padding: 0.75rem 1.75rem;
    background: var(--vp-primary);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.85rem;
}
.vp-comment-form .btn-submit:hover {
    background: var(--vp-primary-hover);
    transform: translateY(-1px);
}

/* Modals & Popups (Glassmorphism & Slide Effects) */
.apply-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    transition: all 0.3s;
}
.apply-modal-overlay.show {
    display: flex;
}
.apply-modal {
    background: white;
    border-radius: var(--vp-radius-lg);
    max-width: 520px;
    width: 100%;
    box-shadow: 0 25px 80px -15px rgba(0,0,0,0.3);
    overflow: hidden;
    animation: modalSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(40px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.apply-modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
    padding: 1.5rem;
    color: white;
    position: relative;
}
.apply-modal-header h3 {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.apply-modal-close {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    background: rgba(255,255,255,0.15);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
.apply-modal-close:hover {
    background: rgba(255,255,255,0.3);
}
.apply-modal-body {
    padding: 1.75rem;
}
.apply-modal-post-title {
    background: var(--vp-primary-light);
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.25rem;
    font-weight: 700;
    color: var(--vp-primary);
    font-size: 0.85rem;
    border: 1px solid #ccfbf1;
}
.apply-modal-body label {
    display: block;
    font-weight: 700;
    color: var(--vp-text-main);
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}
.apply-modal-body textarea {
    width: 100%;
    padding: 0.85rem;
    border: 2px solid #e2e8f0;
    border-radius: var(--vp-radius-sm);
    font-size: 0.9rem;
    resize: vertical;
    min-height: 100px;
    transition: all 0.2s;
}
.apply-modal-body textarea:focus {
    outline: none;
    border-color: var(--vp-primary);
}
.apply-modal-footer {
    padding: 0 1.75rem 1.75rem;
    display: flex;
    gap: 0.75rem;
}
.apply-modal-btn {
    flex: 1;
    padding: 0.85rem 1.5rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.apply-modal-btn.primary {
    background: var(--vp-primary);
    color: white;
}
.apply-modal-btn.primary:hover {
    background: var(--vp-primary-hover);
}
.apply-modal-btn.secondary {
    background: #f1f5f9;
    color: #475569;
}

/* Lightbox Modal */
.vp-lightbox-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.95);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.vp-lightbox-overlay.show {
    display: flex;
    opacity: 1;
}
.vp-lightbox-container {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}
.vp-lightbox-img {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 12px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    border: 3px solid rgba(255,255,255,0.1);
}
.vp-lightbox-close {
    position: absolute;
    top: -45px;
    right: 0;
    background: rgba(255,255,255,0.2);
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    color: white;
    font-size: 1.3rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}
.vp-lightbox-close:hover {
    background: rgba(255,255,255,0.35);
}

/* Alerts custom styling */
.vp-alert {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-radius: var(--vp-radius-md);
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: var(--vp-card-shadow);
}
.vp-alert.success { background: var(--vp-success-light); color: #065f46; border: 1px solid var(--vp-success-border); }
.vp-alert.info { background: var(--vp-primary-light); color: #1e40af; border: 1px solid #bfdbfe; }

/* Responsive adaptibility */
@media (max-width: 900px) {
    .vp-container {
        grid-template-columns: 1fr;
    }
    .vp-app-hero { flex-direction: column; text-align: center; }
    .vp-app-logo-card { order: -1; }
    .vp-app-hero-meta { justify-content: center; }
    .vp-app-breadcrumb { justify-content: center; }
    .vp-sidebar { order: -1; }
}
</style>


<div class="vp-page <?php echo $post['type'] === 'recruitment' ? 'vp-type-recruitment' : 'vp-type-application'; ?>">
    <div class="vp-container">
        <!-- Alerts -->
        <?php if ($favoriteStatus === 'added'): ?>
            <div class="vp-alert success"><i class="bi bi-check-circle-fill"></i> Đã lưu bài viết vào danh sách yêu thích.</div>
        <?php elseif ($favoriteStatus === 'removed'): ?>
            <div class="vp-alert info"><i class="bi bi-info-circle-fill"></i> Đã xóa bài viết khỏi danh sách yêu thích.</div>
        <?php endif; ?>

        <?php if ($post['type'] === 'recruitment'): ?>
            <!-- ========================================== -->
            <!-- 1. PATIENT RECRUITMENT LAYOUT (Image 2 style) -->
            <!-- ========================================== -->
            
            <!-- Recruitment Header Card -->
            <div class="vp-rec-header-card">
                <div class="vp-rec-header-author">
                    <div class="vp-rec-header-avatar">
                        <?php if (!empty($post['author_avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="" onerror="this.style.display='none';this.parentElement.innerHTML='<?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>';">
                        <?php else: ?>
                            <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="vp-rec-header-name">
                            <?php echo htmlspecialchars($post['author_name']); ?>
                            <?php if (!empty($post['author_verified'])): ?>
                                <i class="bi bi-patch-check-fill text-info vp-app-verified"></i>
                            <?php endif; ?>
                        </div>
                        <div class="vp-rec-header-role">
                            <?php echo $post['author_role'] === 'patient' ? 'Bệnh nhân' : 'Sinh viên Y khoa'; ?>
                        </div>
                    </div>
                </div>
                <div class="vp-rec-header-meta">
                    <div class="vp-rec-header-date">
                        <i class="bi bi-calendar3"></i>
                        <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                    </div>
                    <?php 
                    $pst = $post['status'] ?? 'open';
                    $statusText = ['open' => 'Đang tuyển', 'closed' => 'Đã đóng', 'taken' => 'Đã nhận'];
                    ?>
                    <span class="vp-rec-header-status <?php echo $pst; ?>"><?php echo $statusText[$pst] ?? $pst; ?></span>
                </div>
            </div>

            <!-- Left Main Column -->
            <div class="vp-main">
                <!-- Job Title Card -->
                <div class="vp-rec-title-card">
                    <div class="vp-rec-title-tags">
                        <?php 
                        $jobType = $post['job_type'] ?? 'part_time';
                        $jobTypeLabels = [
                            'part_time' => ['label' => 'Bán thời gian', 'icon' => 'bi-clock', 'class' => 'type-rec'],
                            'full_time' => ['label' => 'Toàn thời gian', 'icon' => 'bi-briefcase', 'class' => 'type-rec-full'],
                            'night_shift' => ['label' => 'Trực đêm', 'icon' => 'bi-moon-stars', 'class' => 'type-rec-night']
                        ];
                        $jobTypeInfo = $jobTypeLabels[$jobType] ?? $jobTypeLabels['part_time'];
                        ?>
                        <span class="vp-rec-title-tag <?php echo $jobTypeInfo['class']; ?>">
                            <i class="bi <?php echo $jobTypeInfo['icon']; ?>"></i> <?php echo $jobTypeInfo['label']; ?>
                        </span>
                        
                        <?php if (!empty($post['is_urgent'])): ?>
                            <span class="vp-rec-title-tag urgent"><i class="bi bi-fire"></i> Cần gấp</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="vp-rec-title-text"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="vp-rec-title-time">
                        <i class="bi bi-clock-history"></i> Cập nhật <?php echo date('d/m/Y H:i', strtotime($post['updated_at'])); ?>
                    </div>
                </div>

                <!-- Detailed Description Card -->
                <div class="vp-card">
                    <div class="vp-card-header" style="background: linear-gradient(135deg, var(--vp-primary-light) 0%, #eff6ff 100%);">
                        <i class="bi bi-file-text-fill" style="color: var(--vp-primary);"></i>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--vp-text-main);">Mô tả chi tiết</h3>
                    </div>
                    <div class="vp-card-body" style="padding: 1.5rem;">
                        <div class="vp-expandable-wrapper" style="position: relative;">
                            <div class="vp-content vp-expandable-content" style="max-height: 160px; overflow: hidden; font-size: 0.95rem; line-height: 1.6; color: #334155; background: #fafafa; padding: 1.25rem; padding-bottom: 2rem; border-radius: 8px; border-left: 4px solid var(--vp-primary); margin: 0; white-space: pre-wrap; word-break: break-word; transition: max-height 0.3s ease;">
                                <?php echo nl2br(htmlspecialchars(trim($content))); ?>
                            </div>
                            <div class="vp-expandable-fade" style="position: absolute; bottom: 0; left: 4px; right: 0; height: 60px; background: linear-gradient(to bottom, transparent, #fafafa); pointer-events: none; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;"></div>
                            <div class="vp-expandable-btn-wrapper" style="text-align: center; margin-top: 0.75rem; display: none;">
                                <button type="button" class="vp-expandable-toggle-btn" style="background: none; border: none; color: var(--vp-primary); font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; transition: color 0.2s;">
                                    <span>Xem thêm</span> <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Card -->
                <div class="vp-video-card">
                    <?php 
                    $hasVideo = !empty($post['video_path']) && file_exists($post['video_path']);
                    $videoTitle = 'Video tình trạng sức khỏe';
                    $videoIcon = $hasVideo ? 'bi-camera-video-fill' : 'bi-camera-video-off';
                    $iconColor = $hasVideo ? '#10b981' : '#94a3b8';
                    ?>
                    <div class="vp-card-header">
                        <i class="bi <?php echo $videoIcon; ?>" style="color: <?php echo $iconColor; ?>;"></i>
                        <h3><?php echo $videoTitle; ?></h3>
                    </div>
                    <div class="vp-card-body">
                        <?php if ($hasVideo): ?>
                            <div class="vp-premium-video-wrapper">
                                <video class="vp-premium-video-player" controls preload="metadata">
                                    <source src="<?php echo htmlspecialchars($post['video_path']); ?>" type="video/mp4">
                                    Trình duyệt của bạn không hỗ trợ phát video.
                                </video>
                            </div>
                        <?php else: ?>
                            <div class="vp-video-placeholder">
                                <div class="vp-video-placeholder-icon">
                                    <i class="bi bi-camera-video-off"></i>
                                </div>
                                <div class="vp-video-placeholder-title">Không có video đính kèm</div>
                                <div class="vp-video-placeholder-text">Người đăng tin chưa đính kèm video mô tả tình trạng sức khỏe.</div>
                                <div class="vp-video-placeholder-info">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Việc đính kèm video ngắn (tùy chọn) sẽ giúp sinh viên Y khoa dễ dàng đánh giá chính xác mức độ cần hỗ trợ.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medical Evidences / Documents -->
                <?php 
                $hasPrescription = (!empty($post['prescription_file']) && file_exists(__DIR__ . '/' . $post['prescription_file']));
                $hasEvidence = (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image']));
                $hasTestResult = (!empty($post['test_result_file']) && file_exists(__DIR__ . '/' . $post['test_result_file']));
                ?>
                <div class="vp-card">
                    <div class="vp-card-header">
                        <i class="bi bi-shield-check-fill" style="color: #10b981;"></i>
                        <h3 style="color: #15803d; margin: 0;">Ảnh minh chứng y tế / Hồ sơ bệnh án</h3>
                    </div>
                    <div class="vp-card-body">
                        <?php if ($hasPrescription || $hasEvidence || $hasTestResult): ?>
                            <div class="vp-doc-grid">
                                <?php if ($hasPrescription): 
                                    $isPrescriptionPdf = strtolower(pathinfo($post['prescription_file'], PATHINFO_EXTENSION)) === 'pdf';
                                    if ($isPrescriptionPdf): ?>
                                        <a href="<?php echo htmlspecialchars($post['prescription_file']); ?>" target="_blank" class="vp-doc-card pdf" style="text-decoration: none; color: inherit;">
                                            <div class="vp-doc-icon" style="color: #ef4444;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                            <div class="vp-doc-name">Toa thuốc (PDF)</div>
                                        </a>
                                    <?php else: ?>
                                        <div class="vp-doc-card image" onclick="openLightbox('<?php echo htmlspecialchars($post['prescription_file']); ?>')">
                                            <div class="vp-doc-icon" style="color: #0d9488;"><i class="bi bi-file-earmark-image-fill"></i></div>
                                            <div class="vp-doc-name">Toa thuốc (Ảnh)</div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($hasEvidence): ?>
                                    <div class="vp-doc-card image" onclick="openLightbox('<?php echo htmlspecialchars($post['evidence_image']); ?>')">
                                        <div class="vp-doc-icon" style="color: #10b981;"><i class="bi bi-file-earmark-image-fill"></i></div>
                                        <div class="vp-doc-name">Hồ sơ bệnh án</div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasTestResult): 
                                    $isTestResultPdf = strtolower(pathinfo($post['test_result_file'], PATHINFO_EXTENSION)) === 'pdf';
                                    if ($isTestResultPdf): ?>
                                        <a href="<?php echo htmlspecialchars($post['test_result_file']); ?>" target="_blank" class="vp-doc-card pdf" style="text-decoration: none; color: inherit;">
                                            <div class="vp-doc-icon" style="color: #ef4444;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                                            <div class="vp-doc-name">KQ xét nghiệm (PDF)</div>
                                        </a>
                                    <?php else: ?>
                                        <div class="vp-doc-card image" onclick="openLightbox('<?php echo htmlspecialchars($post['test_result_file']); ?>')">
                                            <div class="vp-doc-icon" style="color: #0284c7;"><i class="bi bi-file-earmark-image-fill"></i></div>
                                            <div class="vp-doc-name">KQ xét nghiệm (Ảnh)</div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; color: #64748b;">
                                <i class="bi bi-file-earmark-medical" style="font-size: 2.5rem; color: #cbd5e1; display: block; margin-bottom: 0.5rem;"></i>
                                Chưa có minh chứng y tế/hồ sơ bệnh án nào được đính kèm.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column -->
            <div class="vp-sidebar">
                <!-- Proposed Salary Card -->
                <div class="vp-rec-salary-card">
                    <div class="vp-rec-salary-header">
                        <div class="vp-rec-salary-label">Mức lương đề xuất</div>
                        <div class="vp-rec-salary-value">
                            <?php 
                            $price = (int)($post['suggested_price'] ?? 0);
                            echo $price > 0 ? number_format($price) . ' đ/giờ' : '50,000 - 80,000 đ/giờ';
                            ?>
                        </div>
                    </div>
                    <div class="vp-rec-salary-details">
                        <div class="vp-rec-salary-detail-item">
                            <i class="bi bi-clock-history"></i>
                            <div>
                                <strong>Thời gian làm việc:</strong><br>
                                <?php echo !empty($post['work_time']) ? htmlspecialchars($post['work_time']) : 'Linh hoạt (Khoảng 4-6 tiếng/ngày)'; ?>
                            </div>
                        </div>
                        <div class="vp-rec-salary-detail-item">
                            <i class="bi bi-clipboard2-pulse"></i>
                            <div>
                                <strong>Chuyên khoa / Loại chăm sóc:</strong><br>
                                <?php echo htmlspecialchars($post['category'] ?? 'Chăm sóc người cao tuổi, Phục hồi chức năng'); ?>
                            </div>
                        </div>
                        <div class="vp-rec-salary-detail-item">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <strong>Địa chỉ / Khu vực:</strong><br>
                                <?php if (!empty($areaValue)): ?>
                                    <?php if ($areaIsLink): ?>
                                        <a href="<?php echo htmlspecialchars($areaValue); ?>" target="_blank" style="color:var(--vp-primary); text-decoration:none;">Xem bản đồ</a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($areaValue); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Quận 1, TP. Hồ Chí Minh
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="vp-action-buttons">
                        <?php 
                        // Kiểm tra sinh viên đã ứng tuyển chưa
                        $hasApplied = false;
                        if ($loggedIn && $currentUserRole === 'student') {
                            try {
                                $checkApply = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND post_id = ? AND message LIKE 'Sinh viên ứng tuyển:%'");
                                $checkApply->execute([$currentUserId, $postId]);
                                $hasApplied = (int)$checkApply->fetchColumn() > 0;
                            } catch (Throwable $e) {}
                        }
                        ?>
                        
                        <?php if ($loggedIn && !$isOwner): ?>
                            <?php if ($pst === 'taken'): ?>
                                <button class="vp-btn vp-btn-disabled" disabled><i class="bi bi-check-circle"></i> Đã có người nhận</button>
                            <?php elseif ($isPatientViewingPatient): ?>
                                <span class="vp-btn vp-btn-disabled"><i class="bi bi-eye"></i> Chỉ xem</span>
                            <?php elseif ($needsVerification): ?>
                                <a class="vp-btn vp-btn-primary" href="request_verification.php"><i class="bi bi-shield-check"></i> Xin xác thực để liên hệ</a>
                            <?php else: ?>
                                <?php if ($currentUserRole === 'student'): ?>
                                    <?php if ($hasApplied): ?>
                                        <button class="vp-btn vp-btn-disabled" disabled>
                                            <i class="bi bi-check2-circle"></i> Đã ứng tuyển
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="vp-btn vp-btn-primary" onclick="showApplyModal(<?php echo $postId; ?>, '<?php echo htmlspecialchars(addslashes($post['title'])); ?>')">
                                            <i class="bi bi-hand-index-thumb-fill"></i> Ứng tuyển ngay
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <a class="vp-btn vp-btn-outline" href="chat.php?user_id=<?php echo (int)$post['user_id']; ?>">
                                    <i class="bi bi-chat-dots-fill"></i> Liên hệ
                                </a>
                            <?php endif; ?>
                            
                            <form method="post" action="toggle_favorite.php" style="margin:0;">
                                <input type="hidden" name="post_id" value="<?php echo (int)$postId; ?>">
                                <input type="hidden" name="redirect" value="view_post.php?id=<?php echo $postId; ?>">
                                <input type="hidden" name="action" value="<?php echo $isFavorite ? 'remove' : 'add'; ?>">
                                <button type="submit" class="vp-btn vp-btn-outline <?php echo $isFavorite ? 'active' : ''; ?>" style="width:100%;">
                                    <i class="bi bi-heart<?php echo $isFavorite ? '-fill' : ''; ?>"></i>
                                    <?php echo $isFavorite ? 'Đã lưu tin' : 'Lưu tin'; ?>
                                </button>
                            </form>
                        <?php elseif (!$loggedIn): ?>
                            <a class="vp-btn vp-btn-primary" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Đăng nhập để liên hệ</a>
                            <a class="vp-btn vp-btn-outline" href="<?php echo htmlspecialchars($favoriteLoginUrl); ?>">
                                <i class="bi bi-heart"></i> Lưu tin
                            </a>
                        <?php else: ?>
                            <a class="vp-btn vp-btn-outline" href="edit_post.php?id=<?php echo $postId; ?>">
                                <i class="bi bi-pencil"></i> Chỉnh sửa tin
                            </a>
                        <?php endif; ?>
                        
                        <a class="vp-btn vp-btn-outline" href="index.php#posts">
                            <i class="bi bi-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>

                <!-- Contact Info Card (only shown if not empty) -->
                <?php if (!empty($post['contact_info'])): ?>
                <div class="vp-rec-author-card" style="margin-bottom:0;">
                    <div class="vp-rec-author-title">
                        <i class="bi bi-telephone-fill"></i> Thông tin liên hệ
                    </div>
                    <div class="vp-rec-author-stat-row" style="background:#f8fafc; padding:0.85rem; border-radius:12px; border:1px solid #f1f5f9;">
                        <span id="contactInfo" style="font-weight:700; font-size:1rem; color:#1e3a8a;"><?php echo htmlspecialchars($post['contact_info']); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-primary" style="padding:0.2rem 0.5rem;" onclick="copyContact()" title="Sao chép">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Author Info Card -->
                <div class="vp-rec-author-card">
                    <div class="vp-rec-author-title">
                        <i class="bi bi-person-fill"></i> Thông tin người đăng
                    </div>
                    <div class="vp-rec-author-profile">
                        <div class="vp-rec-author-avatar">
                            <?php if (!empty($post['author_avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="vp-rec-author-meta">
                            <div class="vp-rec-author-name">
                                <?php echo htmlspecialchars($post['author_name']); ?>
                                <?php if (!empty($post['author_verified'])): ?>
                                    <i class="bi bi-patch-check-fill text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="vp-rec-author-joined">
                                Thành viên từ <?php echo date('m/Y', strtotime($post['author_created_at'] ?? $post['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="vp-rec-author-stats">
                        <div class="vp-rec-author-stat-row">
                            <span>Tỉ lệ phản hồi:</span>
                            <span class="vp-rec-author-stat-value text-success">95%</span>
                        </div>
                        <div class="vp-rec-author-stat-row">
                            <span>Xác thực danh tính:</span>
                            <span class="vp-rec-author-verified-badge">
                                <i class="bi bi-patch-check-fill"></i> Đã xác minh
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Similar Postings Sidebar list -->
                <div class="vp-rec-similar-card">
                    <div class="vp-rec-similar-title">
                        <i class="bi bi-briefcase"></i> Tin tuyển dụng tương tự
                    </div>
                    <div class="vp-rec-similar-list">
                        <?php if (empty($similarPosts)): ?>
                            <a href="#" class="vp-rec-similar-item">
                                <div class="vp-rec-similar-avatar">
                                    <span style="background:#eff6ff;color:#3b82f6;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">ĐD</span>
                                </div>
                                <div class="vp-rec-similar-info">
                                    <div class="vp-rec-similar-name">Điều dưỡng chăm sóc tại gia</div>
                                    <div class="vp-rec-similar-meta">
                                        <span>Quận 3</span>
                                        <span class="text-success font-weight-bold">70K/giờ</span>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="vp-rec-similar-item">
                                <div class="vp-rec-similar-avatar">
                                    <span style="background:#ecfdf5;color:#10b981;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">PH</span>
                                </div>
                                <div class="vp-rec-similar-info">
                                    <div class="vp-rec-similar-name">Hỗ trợ phục hồi chức năng</div>
                                    <div class="vp-rec-similar-meta">
                                        <span>Quận 7</span>
                                        <span class="text-success font-weight-bold">85K/giờ</span>
                                    </div>
                                </div>
                            </a>
                            <a href="#" class="vp-rec-similar-item">
                                <div class="vp-rec-similar-avatar">
                                    <span style="background:#fee2e2;color:#ef4444;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;">SV</span>
                                </div>
                                <div class="vp-rec-similar-info">
                                    <div class="vp-rec-similar-name">Sinh viên Y trực đêm</div>
                                    <div class="vp-rec-similar-meta">
                                        <span>Bình Thạnh</span>
                                        <span class="text-success font-weight-bold">60K/giờ</span>
                                    </div>
                                </div>
                            </a>
                        <?php else: ?>
                            <?php foreach ($similarPosts as $simPost): ?>
                                <a href="view_post.php?id=<?php echo $simPost['id']; ?>" class="vp-rec-similar-item">
                                    <div class="vp-rec-similar-avatar">
                                        <?php if (!empty($simPost['author_avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($simPost['author_avatar']); ?>" alt="">
                                        <?php else: ?>
                                            <span style="background:#eff6ff;color:#3b82f6;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:0.8rem;"><?php echo strtoupper(substr($simPost['author_name'], 0, 1)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="vp-rec-similar-info">
                                        <div class="vp-rec-similar-name"><?php echo htmlspecialchars($simPost['title']); ?></div>
                                        <div class="vp-rec-similar-meta">
                                            <span><?php echo htmlspecialchars($simPost['area'] ?: 'TP.HCM'); ?></span>
                                            <span class="text-success font-weight-bold">
                                                <?php 
                                                $simPrice = (int)($simPost['suggested_price'] ?? 0);
                                                echo $simPrice > 0 ? number_format($simPrice) . ' đ' : 'Thỏa thuận';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?type=recruitment#posts" class="vp-rec-similar-btn-all">Xem tất cả tin tương tự</a>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================== -->
            <!-- 2. STUDENT APPLICATION LAYOUT (Image 1 style) -->
            <!-- ========================================== -->
            
            <!-- Application Hero Banner -->
            <div class="vp-app-hero">
                <div class="vp-app-hero-content">
                    <div class="vp-app-breadcrumb">
                        <a href="index.php"><i class="bi bi-house-fill"></i> Trang chủ</a>
                        <i class="bi bi-chevron-right"></i>
                        <span>Tin ứng tuyển</span>
                    </div>
                    <h1 class="vp-app-title">Sinh Viên Y Khoa</h1>
                    
                    <div class="vp-app-hero-meta">
                        <div class="vp-app-author-card">
                            <div class="vp-app-author-avatar">
                                <?php if (!empty($post['author_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="" onerror="this.style.display='none';this.parentElement.innerHTML='<?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>';">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($post['author_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="vp-app-author-name">
                                    <?php echo htmlspecialchars($post['author_name']); ?>
                                    <?php if (!empty($post['author_verified'])): ?>
                                        <i class="bi bi-patch-check-fill vp-app-verified"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="vp-app-author-role">
                                    Sinh viên Y khoa
                                    <?php 
                                    $studentCode = !empty($post['student_code']) ? $post['student_code'] : '';
                                    if (empty($studentCode) && !empty($post['author_email'])) {
                                        $parts = explode('@', $post['author_email']);
                                        $studentCode = $parts[0];
                                    }
                                    if (!empty($studentCode)): ?>
                                        &bull; MSSV: <?php echo htmlspecialchars($studentCode); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="vp-app-date">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                        </div>
                        <?php 
                        $pst = $post['status'] ?? 'open';
                        $statusText = ['open' => 'Đang mở', 'closed' => 'Đã đóng', 'taken' => 'Đã nhận'];
                        ?>
                        <span class="vp-app-status <?php echo $pst; ?>"><?php echo $statusText[$pst] ?? $pst; ?></span>
                    </div>
                </div>
                
                <?php 
                $school = !empty($post['author_school']) ? $post['author_school'] : '';
                if (empty($school) && !empty($post['author_email'])) {
                    if (strpos($post['author_email'], 'tvu.edu.vn') !== false) {
                        $school = 'Đại học Trà Vinh';
                    } else if (strpos($post['author_email'], 'ump.edu.vn') !== false) {
                        $school = 'ĐH Y Dược TP.HCM';
                    } else {
                        $school = 'Đại học Y Dược';
                    }
                }
                ?>
                <div class="vp-medical-id-card">
                    <div class="vp-id-card-header">
                        <div class="vp-id-card-hospital"><i class="fa-solid fa-house-medical"></i> THẺ SINH VIÊN</div>
                        <div class="vp-id-card-sub">Y KHOA</div>
                    </div>
                    <div class="vp-id-card-body">
                        <div class="vp-id-avatar-container">
                            <?php if (!empty($post['author_avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" class="vp-id-avatar" alt="">
                            <?php else: ?>
                                <div class="vp-id-avatar-placeholder"><?php echo strtoupper(substr($post['author_name'], 0, 1)); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($post['author_verified'])): ?>
                                <div class="vp-id-verified-seal" title="Đã xác minh thông tin"><i class="bi bi-patch-check-fill"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="vp-id-details">
                            <div class="vp-id-name"><?php echo htmlspecialchars($post['author_name']); ?></div>
                            <div class="vp-id-info-row">
                                <span class="vp-id-label">MSSV:</span>
                                <span class="vp-id-value"><?php echo htmlspecialchars($studentCode); ?></span>
                            </div>
                            <div class="vp-id-info-row">
                                <span class="vp-id-label">Lớp:</span>
                                <span class="vp-id-value"><?php echo htmlspecialchars($post['student_class'] ?? $post['author_class_code'] ?? 'Y Khoa'); ?></span>
                            </div>
                            <div class="vp-id-info-row">
                                <span class="vp-id-label">Trường:</span>
                                <span class="vp-id-value"><?php echo htmlspecialchars($school); ?></span>
                            </div>
                            <div class="vp-id-info-row">
                                <span class="vp-id-label">Chi phí:</span>
                                <span class="vp-id-value" style="color: #10b981; font-weight: 800;">
                                    <?php 
                                    $price = (int)($post['suggested_price'] ?? 0);
                                    echo $price > 0 ? number_format($price) . ' đ/h' : '22.700 đ/h';
                                    ?>
                                </span>
                            </div>
                            <div class="vp-id-info-row">
                                <span class="vp-id-label">Khu vực:</span>
                                <span class="vp-id-value">
                                    <?php 
                                    $appArea = trim((string)($post['area'] ?? ''));
                                    if (!empty($appArea)): 
                                        if (filter_var($appArea, FILTER_VALIDATE_URL) !== false): ?>
                                            <a href="<?php echo htmlspecialchars($appArea); ?>" target="_blank" style="color: var(--vp-primary); text-decoration: underline; font-weight: 700;"><i class="fa-solid fa-map-location-dot"></i> Bản đồ</a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($appArea); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        Chưa cập nhật
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="vp-id-card-footer">
                        <div class="vp-id-barcode">
                            <div class="vp-barcode-lines"></div>
                            <div class="vp-barcode-number"><?php echo htmlspecialchars($studentCode); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Left Main Column -->
            <div class="vp-main" style="grid-column: 1 / -1;">
                <!-- Quick actions card -->
                <div class="vp-app-quick-actions">
                    <?php if ($loggedIn && !$isOwner): ?>
                        <a class="vp-quick-btn apply" href="chat.php?user_id=<?php echo (int)$post['user_id']; ?>">
                            <i class="bi bi-chat-dots"></i> Liên hệ / Ứng tuyển
                        </a>
                        
                        <form method="post" action="toggle_favorite.php" style="margin:0;">
                            <input type="hidden" name="post_id" value="<?php echo (int)$postId; ?>">
                            <input type="hidden" name="redirect" value="view_post.php?id=<?php echo $postId; ?>">
                            <input type="hidden" name="action" value="<?php echo $isFavorite ? 'remove' : 'add'; ?>">
                            <button type="submit" class="vp-quick-btn save <?php echo $isFavorite ? 'active' : ''; ?>" style="border:none;">
                                <i class="bi bi-bookmark-fill"></i> <?php echo $isFavorite ? 'Đã lưu tin' : 'Lưu tin'; ?>
                            </button>
                        </form>
                    <?php elseif (!$loggedIn): ?>
                        <a class="vp-quick-btn apply" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập để liên hệ
                        </a>
                        <a class="vp-quick-btn save" href="<?php echo htmlspecialchars($favoriteLoginUrl); ?>">
                            <i class="bi bi-bookmark"></i> Lưu tin
                        </a>
                    <?php else: ?>
                        <a class="vp-quick-btn edit" href="edit_post.php?id=<?php echo $postId; ?>">
                            <i class="bi bi-pencil-fill"></i> Chỉnh sửa tin ứng tuyển
                        </a>
                    <?php endif; ?>
                    
                    <a class="vp-quick-btn back" href="index.php#posts">
                        <i class="bi bi-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>

                <!-- Contact Info & Price Card -->
                <div class="vp-card" style="border-left: 5px solid var(--vp-primary); margin-bottom: 1.5rem;">
                    <div class="vp-card-header" style="background: linear-gradient(135deg, var(--vp-primary-light) 0%, #eff6ff 100%);">
                        <i class="bi bi-telephone-inbound-fill" style="color: var(--vp-primary); font-size: 1.1rem;"></i>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--vp-text-main);">Thông tin liên hệ &amp; Chi phí đề xuất</h3>
                    </div>
                    <div class="vp-card-body" style="padding: 1.5rem;">
                        <div class="row g-4">
                            <!-- Price Column -->
                            <div class="col-md-6 vp-border-md-end" style="display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; padding-top: 0; padding-bottom: 0;">
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--vp-primary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.35rem;">
                                    <i class="bi bi-cash-coin" style="font-size: 1.1rem; color: #10b981;"></i> Chi phí hỗ trợ đề xuất
                                </div>
                                <div style="display: flex; align-items: baseline; gap: 0.5rem; margin-top: 0.25rem;">
                                    <span style="font-size: 1.8rem; font-weight: 800; color: #10b981; line-height: 1;">
                                        <?php 
                                        $price = (int)($post['suggested_price'] ?? 0);
                                        echo $price > 0 ? number_format($price) : '22.700';
                                        ?>
                                    </span>
                                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--vp-text-muted);">VNĐ/giờ</span>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--vp-text-muted); line-height: 1.4; margin-top: 0.25rem;">
                                    <i class="bi bi-info-circle-fill" style="color: var(--vp-primary); font-size: 0.85rem;"></i> Mức chi phí đề xuất tham khảo từ quy chế hỗ trợ lâm sàng của sinh viên.
                                </div>
                            </div>
                            
                            <!-- Contact Column -->
                            <div class="col-md-6 vp-padding-md-start" style="display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; padding-top: 0; padding-bottom: 0;">
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--vp-primary); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.35rem;">
                                    <i class="bi bi-telephone-fill" style="font-size: 1rem; color: #0284c7;"></i> Phương thức liên hệ trực tiếp
                                </div>
                                
                                <?php if ($loggedIn): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 0.75rem 1rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 0.25rem;">
                                        <span id="contactInfo" style="font-weight: 700; font-size: 1.1rem; color: #1e3a8a; letter-spacing: 0.25px;">
                                            <?php echo htmlspecialchars($post['contact_info'] ?? 'Chưa cập nhật'); ?>
                                        </span>
                                        <button type="button" class="vp-copy-btn" onclick="copyContact()" title="Sao chép thông tin" style="background: none; border: 1px solid #cbd5e1; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all 0.2s;">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--vp-text-muted); line-height: 1.4; margin-top: 0.25rem;">
                                        <i class="bi bi-shield-lock-fill" style="color: #10b981; font-size: 0.85rem;"></i> Chỉ người dùng đã đăng nhập mới xem được thông tin liên hệ này.
                                    </div>
                                <?php else: ?>
                                    <div style="background: #fffbeb; padding: 0.75rem 1rem; border-radius: 12px; border: 1px dashed #f59e0b; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="bi bi-lock-fill" style="font-size: 1.2rem; color: #d97706;"></i>
                                        <span style="font-size: 0.85rem; color: #b45309; font-weight: 600;">
                                            Hãy <a href="login.php" style="color: var(--vp-primary); text-decoration: underline; font-weight: 700;">đăng nhập</a> để xem thông tin liên hệ.
                                        </span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--vp-text-muted); line-height: 1.4; margin-top: 0.25rem;">
                                        <i class="bi bi-info-circle-fill" style="color: var(--vp-warning); font-size: 0.85rem;"></i> Cần đăng nhập tài khoản để bảo mật thông tin cá nhân của sinh viên.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Skills & Description Card -->
                <div class="vp-card">
                    <div class="vp-card-header" style="background: linear-gradient(135deg, var(--vp-primary-light) 0%, #eff6ff 100%);">
                        <i class="bi bi-person-badge-fill" style="color: var(--vp-primary);"></i>
                        <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--vp-text-main);">Hồ sơ năng lực &amp; Mô tả chi tiết</h3>
                    </div>
                    <div class="vp-card-body" style="padding: 1.5rem;">
                        <?php if (!empty($skills)): ?>
                            <div class="vp-skills-section" style="margin-bottom: 1.5rem; border-bottom: 1px dashed #e2e8f0; padding-bottom: 1.25rem;">
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--vp-primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <i class="bi bi-star-fill" style="color: #f59e0b;"></i> Kỹ năng nổi bật
                                </div>
                                <div class="vp-skills-grid" style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                                    <?php foreach ($skills as $skill): ?>
                                        <?php if (trim($skill)): ?>
                                        <span class="vp-skill" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 1rem; background: var(--vp-primary-light); color: var(--vp-primary); border: 1px solid #ccfbf1; border-radius: 50px; font-size: 0.85rem; font-weight: 600; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                            <i class="bi bi-patch-check-fill" style="color: var(--vp-primary);"></i>
                                            <?php echo htmlspecialchars(trim($skill)); ?>
                                        </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="vp-description-section">
                            <div style="font-weight: 700; font-size: 0.85rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.35rem;">
                                <i class="bi bi-file-text-fill" style="color: var(--vp-primary);"></i> Giới thiệu bản thân &amp; Kinh nghiệm lâm sàng
                            </div>
                            <div class="vp-expandable-wrapper" style="position: relative;">
                                <div class="vp-content vp-expandable-content" style="max-height: 160px; overflow: hidden; font-size: 0.95rem; line-height: 1.6; color: #334155; background: #fafafa; padding: 1.25rem; padding-bottom: 2rem; border-radius: 8px; border-left: 4px solid var(--vp-primary); margin: 0; white-space: pre-wrap; word-break: break-word; transition: max-height 0.3s ease;">
                                    <?php echo nl2br(htmlspecialchars(trim($content))); ?>
                                </div>
                                <div class="vp-expandable-fade" style="position: absolute; bottom: 0; left: 4px; right: 0; height: 60px; background: linear-gradient(to bottom, transparent, #fafafa); pointer-events: none; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;"></div>
                                <div class="vp-expandable-btn-wrapper" style="text-align: center; margin-top: 0.75rem; display: none;">
                                    <button type="button" class="vp-expandable-toggle-btn" style="background: none; border: none; color: var(--vp-primary); font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; transition: color 0.2s;">
                                        <span>Xem thêm</span> <i class="bi bi-chevron-down"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Evidence Documents Section -->
                <?php 
                $showCardImg = ($post['type'] === 'application' && !empty($post['card_image']) && file_exists(__DIR__ . '/' . $post['card_image']));
                $showEvidenceImg = (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image']));
                ?>
                <?php if ($showCardImg || $showEvidenceImg): ?>
                <div class="vp-card" style="border-color: var(--vp-success-border);">
                    <div class="vp-card-header" style="background: linear-gradient(135deg, var(--vp-success-light) 0%, #dcfce7 100%); border-bottom: 1px solid var(--vp-success-border);">
                        <i class="bi bi-shield-check-fill" style="color: var(--vp-success);"></i>
                        <h3 style="color: #15803d; margin: 0;">Tài liệu minh chứng & Xác thực</h3>
                    </div>
                    <div class="vp-card-body">
                        <div class="vp-evidence-list" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php if ($showCardImg): ?>
                                <div class="vp-evidence-file-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; flex-wrap: wrap; gap: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #3b82f6; display: flex; align-items: center;"><i class="bi bi-person-vcard-fill"></i></div>
                                        <div>
                                            <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b;">Ảnh thẻ sinh viên</div>
                                            <div style="font-size: 0.75rem; color: #64748b;">Ảnh thẻ sinh viên Y khoa đối chiếu chính chủ</div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary" onclick="openLightbox('<?php echo htmlspecialchars($post['card_image']); ?>')" style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; padding: 0.4rem 1rem; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border: none;">
                                        <i class="bi bi-eye-fill me-1"></i> Xem tài liệu
                                    </button>
                                </div>
                            <?php endif; ?>

                            <?php if ($showEvidenceImg): ?>
                                <div class="vp-evidence-file-item" style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; flex-wrap: wrap; gap: 0.5rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #10b981; display: flex; align-items: center;"><i class="bi bi-patch-check-fill"></i></div>
                                        <div>
                                            <div style="font-weight: 700; font-size: 0.85rem; color: #1e293b;">Chứng chỉ / Bằng cấp lâm sàng khác</div>
                                            <div style="font-size: 0.75rem; color: #64748b;"><?php echo !empty($post['evidence_description']) ? htmlspecialchars($post['evidence_description']) : 'Ảnh chụp chứng nhận năng lực hoặc bằng cấp liên quan'; ?></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-primary" onclick="openLightbox('<?php echo htmlspecialchars($post['evidence_image']); ?>')" style="border-radius: 8px; font-weight: 600; font-size: 0.8rem; padding: 0.4rem 1rem; background: linear-gradient(135deg, #3b82f6, #1d4ed8); border: none;">
                                        <i class="bi bi-eye-fill me-1"></i> Xem tài liệu
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Video Card -->
                <div class="vp-video-card">
                    <?php 
                    $hasVideo = !empty($post['video_path']) && file_exists($post['video_path']);
                    $videoTitle = 'Video giới thiệu';
                    $videoIcon = $hasVideo ? 'bi-camera-video-fill' : 'bi-camera-video-off';
                    $iconColor = $hasVideo ? '#10b981' : '#94a3b8';
                    ?>
                    <div class="vp-card-header">
                        <i class="bi <?php echo $videoIcon; ?>" style="color: <?php echo $iconColor; ?>;"></i>
                        <h3><?php echo $videoTitle; ?></h3>
                    </div>
                    <div class="vp-card-body">
                        <?php if ($hasVideo): ?>
                            <div class="vp-premium-video-wrapper">
                                <video class="vp-premium-video-player" controls preload="metadata">
                                    <source src="<?php echo htmlspecialchars($post['video_path']); ?>" type="video/mp4">
                                    Trình duyệt của bạn không hỗ trợ phát video.
                                </video>
                            </div>
                        <?php else: ?>
                            <div class="vp-video-placeholder">
                                <div class="vp-video-placeholder-icon">
                                    <i class="bi bi-camera-video-off"></i>
                                </div>
                                <div class="vp-video-placeholder-title">Không có video đính kèm</div>
                                <div class="vp-video-placeholder-text">Sinh viên chưa đính kèm video giới thiệu kỹ năng lâm sàng.</div>
                                <div class="vp-video-placeholder-info">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Tải lên video ngắn giới thiệu kỹ năng thực hành lâm sàng sẽ giúp tạo ấn tượng chuyên nghiệp hơn.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        <?php endif; ?>

        <!-- Comments Section -->
        <div class="vp-card vp-comments" style="grid-column: 1 / -1;">
            <div class="vp-card-header">
                <i class="bi bi-chat-square-text-fill"></i>
                <h3>Bình luận (<?php echo count($comments); ?>)</h3>
            </div>
            
            <div class="vp-comments-body">
                <?php if (empty($comments)): ?>
                    <div class="vp-no-comments">
                        <i class="bi bi-chat-dots"></i>
                        <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    // Tổ chức comments theo cấu trúc cây
                    $parentComments = array_filter($comments, fn($c) => empty($c['parent_id']));
                    $childComments = array_filter($comments, fn($c) => !empty($c['parent_id']));
                    $repliesMap = [];
                    foreach ($childComments as $child) {
                        $repliesMap[$child['parent_id']][] = $child;
                    }
                    ?>
                    <?php foreach ($parentComments as $c): ?>
                        <?php echo renderComment($c, $repliesMap, $currentUserId, $isAdmin, $loggedIn, $userLikedComments); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="vp-comment-form">
                <?php if ($loggedIn): ?>
                    <form method="post" action="add_comment.php">
                        <input type="hidden" name="post_id" value="<?php echo (int)$postId; ?>">
                        <label><i class="bi bi-pencil-square"></i> Viết bình luận</label>
                        <textarea name="comment" placeholder="Nhập nội dung bình luận..."></textarea>
                        <button type="submit" class="btn-submit"><i class="bi bi-send"></i> Gửi bình luận</button>
                    </form>
                <?php else: ?>
                    <div class="vp-login-prompt">
                        <i class="bi bi-box-arrow-in-right"></i> Hãy <a href="login.php">đăng nhập</a> để bình luận.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const postId = <?php echo $postId; ?>;

function copyContact() {
    const text = document.getElementById('contactInfo').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.vp-copy-btn');
        btn.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        }, 2000);
    });
}

// ========== COMMENT FUNCTIONS ==========

// Like/Unlike comment
function likeComment(commentId, btn) {
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=like&comment_id=' + commentId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector('i');
            const countSpan = btn.querySelector('.like-count');
            if (data.liked) {
                btn.classList.add('liked');
                icon.className = 'bi bi-heart-fill';
            } else {
                btn.classList.remove('liked');
                icon.className = 'bi bi-heart';
            }
            countSpan.textContent = data.like_count > 0 ? data.like_count : '';
        } else {
            alert(data.message);
        }
    });
}

// Show/Hide reply form
function showReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).classList.add('show');
    document.getElementById('reply-text-' + commentId).focus();
}

function hideReplyForm(commentId) {
    document.getElementById('reply-form-' + commentId).classList.remove('show');
    document.getElementById('reply-text-' + commentId).value = '';
}

// Submit reply
function submitReply(parentId) {
    const content = document.getElementById('reply-text-' + parentId).value.trim();
    if (!content) {
        alert('Vui lòng nhập nội dung trả lời');
        return;
    }
    
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&post_id=' + postId + '&parent_id=' + parentId + '&content=' + encodeURIComponent(content)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Edit comment
function editComment(commentId) {
    const textEl = document.getElementById('comment-text-' + commentId);
    const currentText = textEl.innerText;
    document.getElementById('editCommentId').value = commentId;
    document.getElementById('editCommentText').value = currentText;
    document.getElementById('editModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

function submitEdit() {
    const commentId = document.getElementById('editCommentId').value;
    const content = document.getElementById('editCommentText').value.trim();
    if (!content) {
        alert('Vui lòng nhập nội dung');
        return;
    }
    
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang lưu...';
    
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=edit&comment_id=' + commentId + '&content=' + encodeURIComponent(content)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-text-' + commentId).innerHTML = data.content.replace(/\n/g, '<br>');
            closeEditModal();
        } else {
            alert(data.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Lưu thay đổi';
    });
}

// Delete comment
function deleteComment(commentId) {
    if (!confirm('Bạn có chắc muốn xóa bình luận này?')) return;
    
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&comment_id=' + commentId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Report comment
function showReportModal(commentId) {
    document.getElementById('reportCommentId').value = commentId;
    document.getElementById('reportReason').value = '';
    document.getElementById('reportDescription').value = '';
    document.getElementById('reportModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReportModal() {
    document.getElementById('reportModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

function submitReport() {
    const commentId = document.getElementById('reportCommentId').value;
    const reason = document.getElementById('reportReason').value;
    const description = document.getElementById('reportDescription').value.trim();
    
    if (!reason) {
        alert('Vui lòng chọn lý do báo cáo');
        return;
    }
    
    const btn = document.getElementById('reportSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang gửi...';
    
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=report&comment_id=' + commentId + '&reason=' + encodeURIComponent(reason) + '&description=' + encodeURIComponent(description)
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeReportModal();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-flag-fill"></i> Gửi báo cáo';
    });
}

// Admin: Toggle hide comment
function toggleHideComment(commentId, btn) {
    fetch('api/comments.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_hide&comment_id=' + commentId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Apply Job Modal Functions
function showApplyModal(postId, postTitle) {
    document.getElementById('applyPostId').value = postId;
    document.getElementById('applyPostTitle').textContent = postTitle;
    document.getElementById('applyMessage').value = '';
    document.getElementById('applyModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeApplyModal() {
    document.getElementById('applyModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

function submitApply() {
    const postId = document.getElementById('applyPostId').value;
    const message = document.getElementById('applyMessage').value.trim();
    const btn = document.getElementById('applySubmitBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang gửi...';
    
    fetch('apply_job.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'post_id=' + postId + '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeApplyModal();
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'vp-alert success';
            alertDiv.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.message;
            document.querySelector('.vp-container').insertBefore(alertDiv, document.querySelector('.vp-main'));
            
            // Reload page after 2 seconds to update button state
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Gửi ứng tuyển';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra. Vui lòng thử lại.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Gửi ứng tuyển';
    });
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('applyModalOverlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeApplyModal();
            }
        });
    }
});

// ========== LIGHTBOX FUNCTIONS ==========
function openLightbox(imgUrl) {
    const overlay = document.getElementById('lightboxOverlay');
    const img = document.getElementById('lightboxImg');
    img.src = imgUrl;
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const overlay = document.getElementById('lightboxOverlay');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

// ========== EXPANDABLE DESCRIPTION FUNCTIONS ==========
document.addEventListener('DOMContentLoaded', function() {
    const wrappers = document.querySelectorAll('.vp-expandable-wrapper');
    wrappers.forEach(wrapper => {
        const content = wrapper.querySelector('.vp-expandable-content');
        const fade = wrapper.querySelector('.vp-expandable-fade');
        const btnWrapper = wrapper.querySelector('.vp-expandable-btn-wrapper');
        const btn = wrapper.querySelector('.vp-expandable-toggle-btn');
        
        if (!content) return;
        
        // Wait a small moment to ensure accurate layout calculation
        setTimeout(() => {
            if (content.scrollHeight > 165) {
                if (btnWrapper) btnWrapper.style.display = 'block';
            } else {
                content.style.maxHeight = 'none';
                content.style.paddingBottom = '1.25rem';
                if (fade) fade.style.display = 'none';
            }
        }, 100);

        if (btn) {
            btn.addEventListener('click', function() {
                const isCollapsed = content.style.maxHeight !== 'none' && content.style.maxHeight !== '';
                if (isCollapsed) {
                    content.style.maxHeight = 'none';
                    content.style.paddingBottom = '1.25rem';
                    if (fade) fade.style.display = 'none';
                    btn.querySelector('span').textContent = 'Rút gọn';
                    btn.querySelector('i').className = 'bi bi-chevron-up';
                } else {
                    content.style.maxHeight = '160px';
                    content.style.paddingBottom = '2rem';
                    if (fade) fade.style.display = 'block';
                    btn.querySelector('span').textContent = 'Xem thêm';
                    btn.querySelector('i').className = 'bi bi-chevron-down';
                    wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }
    });
});
</script>

<!-- Lightbox Modal for Evidence Images -->
<div id="lightboxOverlay" class="vp-lightbox-overlay" onclick="closeLightbox()">
    <div class="vp-lightbox-container" onclick="event.stopPropagation()">
        <button type="button" class="vp-lightbox-close" onclick="closeLightbox()">
            <i class="bi bi-x-lg"></i>
        </button>
        <img id="lightboxImg" src="" alt="Lightbox Image" class="vp-lightbox-img">
    </div>
</div>

<!-- Apply Job Modal -->
<div id="applyModalOverlay" class="apply-modal-overlay">
    <div class="apply-modal">
        <div class="apply-modal-header">
            <h3><i class="bi bi-hand-index-thumb-fill"></i> Ứng tuyển công việc</h3>
            <button type="button" class="apply-modal-close" onclick="closeApplyModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="apply-modal-body">
            <input type="hidden" id="applyPostId" value="">
            <div class="apply-modal-post-title">
                <i class="bi bi-file-earmark-text"></i> <span id="applyPostTitle"></span>
            </div>
            <label for="applyMessage">
                <i class="bi bi-chat-left-text"></i> Lời nhắn gửi người đăng tin (không bắt buộc)
            </label>
            <textarea id="applyMessage" placeholder="Giới thiệu ngắn về bản thân, kinh nghiệm hoặc lý do bạn muốn nhận công việc này..."></textarea>
        </div>
        <div class="apply-modal-footer">
            <button type="button" class="apply-modal-btn secondary" onclick="closeApplyModal()">
                <i class="bi bi-x-circle"></i> Hủy
            </button>
            <button type="button" id="applySubmitBtn" class="apply-modal-btn primary" onclick="submitApply()">
                <i class="bi bi-send-fill"></i> Gửi ứng tuyển
            </button>
        </div>
    </div>
</div>

<!-- Report Comment Modal -->
<div id="reportModalOverlay" class="apply-modal-overlay">
    <div class="apply-modal">
        <div class="apply-modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
            <h3><i class="bi bi-flag-fill"></i> Báo cáo bình luận</h3>
            <button type="button" class="apply-modal-close" onclick="closeReportModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="apply-modal-body">
            <input type="hidden" id="reportCommentId" value="">
            <label><i class="bi bi-exclamation-triangle"></i> Lý do báo cáo</label>
            <select id="reportReason" style="width:100%;padding:0.75rem;border:2px solid #e2e8f0;border-radius:10px;font-size:1rem;margin-bottom:1rem;">
                <option value="">-- Chọn lý do --</option>
                <option value="spam">Spam / Quảng cáo</option>
                <option value="offensive">Nội dung xúc phạm</option>
                <option value="harassment">Quấy rối / Bắt nạt</option>
                <option value="misinformation">Thông tin sai lệch</option>
                <option value="inappropriate">Nội dung không phù hợp</option>
                <option value="other">Lý do khác</option>
            </select>
            <label><i class="bi bi-chat-left-text"></i> Mô tả thêm (không bắt buộc)</label>
            <textarea id="reportDescription" placeholder="Mô tả chi tiết vấn đề..."></textarea>
        </div>
        <div class="apply-modal-footer">
            <button type="button" class="apply-modal-btn secondary" onclick="closeReportModal()">
                <i class="bi bi-x-circle"></i> Hủy
            </button>
            <button type="button" id="reportSubmitBtn" class="apply-modal-btn primary" style="background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%);" onclick="submitReport()">
                <i class="bi bi-flag-fill"></i> Gửi báo cáo
            </button>
        </div>
    </div>
</div>

<!-- Edit Comment Modal -->
<div id="editModalOverlay" class="apply-modal-overlay">
    <div class="apply-modal">
        <div class="apply-modal-header" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
            <h3><i class="bi bi-pencil-fill"></i> Sửa bình luận</h3>
            <button type="button" class="apply-modal-close" onclick="closeEditModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="apply-modal-body">
            <input type="hidden" id="editCommentId" value="">
            <label><i class="bi bi-chat-left-text"></i> Nội dung bình luận</label>
            <textarea id="editCommentText" placeholder="Nhập nội dung..."></textarea>
        </div>
        <div class="apply-modal-footer">
            <button type="button" class="apply-modal-btn secondary" onclick="closeEditModal()">
                <i class="bi bi-x-circle"></i> Hủy
            </button>
            <button type="button" id="editSubmitBtn" class="apply-modal-btn primary" onclick="submitEdit()">
                <i class="bi bi-check-lg"></i> Lưu thay đổi
            </button>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
