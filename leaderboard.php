<?php
require_once 'config.php';

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

if (!$isEmbed) {
    require_once 'header.php';
    echo '<style>.premium-navbar { display: none !important; } body { padding-top: 0 !important; margin: 0 !important; } .container.py-4 { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }</style>';
} else {
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Bảng xếp hạng</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
    echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">';
    echo '</head><body style="margin:0;padding:0;background:#f0f4ff;">';
}

// Query top students with ratings
$sql = "
    SELECT 
        u.id,
        u.name,
        u.avatar,
        u.verified,
        u.bio,
        u.location,
        u.created_at AS joined,
        ROUND(AVG(r.rating), 1) AS avg_rating,
        COUNT(r.id) AS review_count,
        SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) AS positive_count,
        ROUND(SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) / COUNT(r.id) * 100) AS satisfaction,
        (
            ROUND(AVG(r.rating), 2) * 0.6 +
            (SUM(CASE WHEN r.rating >= 4 THEN 1 ELSE 0 END) / COUNT(r.id) * 5) * 0.2 +
            (LEAST(COUNT(r.id), 20) / 20 * 5) * 0.2
        ) AS total_score
    FROM users u
    INNER JOIN ratings r ON r.rated_user_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id
    HAVING review_count >= 1
    ORDER BY total_score DESC, avg_rating DESC
    LIMIT 20
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $students = [];
    error_log('Leaderboard query error: ' . $e->getMessage());
}

// Count total students with ratings
$totalStudents = 0;
try {
    $countStmt = $pdo->query("SELECT COUNT(DISTINCT r.rated_user_id) FROM ratings r JOIN users u ON u.id = r.rated_user_id WHERE u.role = 'student'");
    $totalStudents = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.lb-page {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.lb-page::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(236, 72, 153, 0.05) 0%, transparent 50%);
    animation: lb-bg-rotate 20s linear infinite;
    z-index: 0;
}

@keyframes lb-bg-rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.lb-container {
    max-width: 1000px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Header */
.lb-header {
    text-align: center;
    margin-bottom: 2.5rem;
    animation: lb-fade-in 0.6s ease;
}

.lb-trophy {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    display: inline-block;
    animation: lb-trophy-bounce 2s ease-in-out infinite;
}

@keyframes lb-trophy-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.lb-title {
    font-size: 2.5rem;
    font-weight: 900;
    background: linear-gradient(135deg, #fbbf24, #f59e0b, #fbbf24);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: lb-shine 3s linear infinite;
    margin-bottom: 0.5rem;
}

@keyframes lb-shine {
    to { background-position: 200% center; }
}

.lb-subtitle {
    color: #94a3b8;
    font-size: 1rem;
    font-weight: 500;
}

.lb-stats-bar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.lb-stats-item {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    text-align: center;
}

.lb-stats-item .value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
}

.lb-stats-item .label {
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Podium - Top 3 */
.lb-podium {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
    align-items: end;
}

.lb-podium-card {
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    padding: 2rem 1.5rem;
    text-align: center;
    position: relative;
    border: 1px solid rgba(255,255,255,0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    overflow: hidden;
}

.lb-podium-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 24px;
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 0;
}

.lb-podium-card:hover {
    transform: translateY(-8px);
}

.lb-podium-card:hover::before {
    opacity: 1;
}

/* Gold - 1st place */
.lb-podium-card.gold {
    order: 2;
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(245, 158, 11, 0.1));
    border-color: rgba(251, 191, 36, 0.3);
    padding-top: 2.5rem;
    padding-bottom: 2.5rem;
}
.lb-podium-card.gold::before { background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(245, 158, 11, 0.1)); }

/* Silver - 2nd place */
.lb-podium-card.silver {
    order: 1;
    background: linear-gradient(135deg, rgba(148, 163, 184, 0.15), rgba(100, 116, 139, 0.1));
    border-color: rgba(148, 163, 184, 0.3);
}
.lb-podium-card.silver::before { background: linear-gradient(135deg, rgba(148, 163, 184, 0.2), rgba(100, 116, 139, 0.1)); }

/* Bronze - 3rd place */
.lb-podium-card.bronze {
    order: 3;
    background: linear-gradient(135deg, rgba(217, 119, 6, 0.15), rgba(180, 83, 9, 0.1));
    border-color: rgba(217, 119, 6, 0.3);
}
.lb-podium-card.bronze::before { background: linear-gradient(135deg, rgba(217, 119, 6, 0.2), rgba(180, 83, 9, 0.1)); }

.lb-rank-badge {
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 2.5rem;
    z-index: 2;
}

.lb-podium-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    margin: 0.5rem auto 1rem;
    position: relative;
    z-index: 1;
}

.lb-podium-card.gold .lb-podium-avatar {
    width: 110px;
    height: 110px;
}

.lb-podium-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255,255,255,0.3);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}

.lb-podium-avatar .placeholder-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    border: 3px solid rgba(255,255,255,0.3);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}

.lb-podium-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.25rem;
    position: relative;
    z-index: 1;
}

.lb-podium-verified {
    color: #3b82f6;
    font-size: 0.9rem;
}

.lb-podium-stars {
    color: #fbbf24;
    font-size: 1.2rem;
    margin: 0.5rem 0;
    position: relative;
    z-index: 1;
}

.lb-podium-rating {
    font-size: 1.8rem;
    font-weight: 900;
    color: #fbbf24;
    position: relative;
    z-index: 1;
}

.lb-podium-card.gold .lb-podium-rating { font-size: 2.2rem; }

.lb-podium-meta {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 0.75rem;
    position: relative;
    z-index: 1;
}

.lb-podium-meta span {
    font-size: 0.75rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* List - Rank 4+ */
.lb-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.lb-list-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    animation: lb-slide-up 0.4s ease forwards;
    opacity: 0;
}

.lb-list-item:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    transform: translateX(8px);
}

@keyframes lb-slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.lb-list-rank {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1rem;
    color: #94a3b8;
    flex-shrink: 0;
}

.lb-list-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    flex-shrink: 0;
}

.lb-list-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.2);
}

.lb-list-avatar .placeholder-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    font-weight: 700;
    border: 2px solid rgba(255,255,255,0.2);
}

.lb-list-info {
    flex: 1;
    min-width: 0;
}

.lb-list-name {
    font-weight: 700;
    color: #fff;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.lb-list-location {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 0.15rem;
}

.lb-list-stats {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-shrink: 0;
}

.lb-list-rating {
    text-align: center;
}

.lb-list-rating .stars {
    color: #fbbf24;
    font-size: 0.85rem;
}

.lb-list-rating .score {
    font-size: 1.25rem;
    font-weight: 800;
    color: #fbbf24;
}

.lb-list-reviews {
    text-align: center;
}

.lb-list-reviews .count {
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
}

.lb-list-reviews .label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
}

.lb-list-satisfaction {
    text-align: center;
    min-width: 60px;
}

.lb-satisfaction-ring {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    color: #10b981;
    border: 3px solid #10b981;
    margin: 0 auto;
}

.lb-satisfaction-ring.medium {
    color: #f59e0b;
    border-color: #f59e0b;
}

.lb-satisfaction-ring.low {
    color: #ef4444;
    border-color: #ef4444;
}

/* Empty state */
.lb-empty {
    text-align: center;
    padding: 4rem 2rem;
    color: #94a3b8;
}

.lb-empty i {
    font-size: 4rem;
    color: #475569;
    margin-bottom: 1rem;
}

.lb-empty h4 {
    color: #e2e8f0;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

/* Back button */
.lb-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.25rem;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}

.lb-back:hover {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

/* Responsive */
@media (max-width: 767px) {
    .lb-page { padding: 1rem; }
    .lb-title { font-size: 1.75rem; }
    .lb-podium { grid-template-columns: 1fr; gap: 1rem; }
    .lb-podium-card.gold { order: 1; }
    .lb-podium-card.silver { order: 2; }
    .lb-podium-card.bronze { order: 3; }
    .lb-list-item { flex-wrap: wrap; }
    .lb-list-stats { width: 100%; justify-content: space-around; margin-top: 0.5rem; }
    .lb-stats-bar { gap: 0.75rem; }
}

@keyframes lb-fade-in {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.lb-podium-card { animation: lb-podium-enter 0.6s ease forwards; opacity: 0; }
.lb-podium-card.silver { animation-delay: 0.2s; }
.lb-podium-card.gold { animation-delay: 0.4s; }
.lb-podium-card.bronze { animation-delay: 0.3s; }

@keyframes lb-podium-enter {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<div class="lb-page">
    <div class="lb-container">
        
        <?php if (!$isEmbed): ?>
        <a href="javascript:history.back()" class="lb-back">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
        <?php endif; ?>

        <!-- Header -->
        <div class="lb-header">
            <div class="lb-trophy">🏆</div>
            <h1 class="lb-title">Bảng Xếp Hạng Sinh Viên</h1>
            <p class="lb-subtitle">Top sinh viên y khoa được đánh giá cao nhất trên nền tảng</p>
            
            <div class="lb-stats-bar">
                <div class="lb-stats-item">
                    <div class="value"><?php echo $totalStudents; ?></div>
                    <div class="label">Sinh viên</div>
                </div>
                <div class="lb-stats-item">
                    <div class="value"><?php echo count($students); ?></div>
                    <div class="label">Xếp hạng</div>
                </div>
                <div class="lb-stats-item">
                    <div class="value">
                        <?php 
                        $totalReviews = 0;
                        foreach ($students as $s) $totalReviews += $s['review_count'];
                        echo $totalReviews;
                        ?>
                    </div>
                    <div class="label">Lượt đánh giá</div>
                </div>
            </div>
        </div>

        <?php if (empty($students)): ?>
        <div class="lb-empty">
            <i class="bi bi-trophy"></i>
            <h4>Chưa có dữ liệu xếp hạng</h4>
            <p>Bảng xếp hạng sẽ hiển thị khi có sinh viên được đánh giá</p>
        </div>
        <?php else: ?>

        <!-- Podium - Top 3 -->
        <?php if (count($students) >= 1): ?>
        <div class="lb-podium">
            <?php 
            $medals = ['gold', 'silver', 'bronze'];
            $emojis = ['🥇', '🥈', '🥉'];
            for ($i = 0; $i < min(3, count($students)); $i++):
                $s = $students[$i];
                $medalClass = $medals[$i];
                $emoji = $emojis[$i];
                $avatarUrl = !empty($s['avatar']) ? htmlspecialchars($s['avatar']) : '';
            ?>
            <div class="lb-podium-card <?php echo $medalClass; ?>" onclick="window.open('view_profile.php?id=<?php echo $s['id']; ?>', '_blank')">
                <div class="lb-rank-badge"><?php echo $emoji; ?></div>
                <div class="lb-podium-avatar">
                    <?php if ($avatarUrl): ?>
                        <img src="<?php echo $avatarUrl; ?>" alt="<?php echo htmlspecialchars($s['name']); ?>">
                    <?php else: ?>
                        <div class="placeholder-avatar"><?php echo mb_strtoupper(mb_substr($s['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lb-podium-name">
                    <?php echo htmlspecialchars($s['name']); ?>
                    <?php if ($s['verified']): ?>
                        <i class="bi bi-patch-check-fill lb-podium-verified"></i>
                    <?php endif; ?>
                </div>
                <div class="lb-podium-stars">
                    <?php for ($star = 1; $star <= 5; $star++): ?>
                        <i class="<?php echo $star <= round($s['avg_rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                    <?php endfor; ?>
                </div>
                <div class="lb-podium-rating"><?php echo $s['avg_rating']; ?></div>
                <div class="lb-podium-meta">
                    <span><i class="bi bi-chat-dots-fill"></i> <?php echo $s['review_count']; ?> đánh giá</span>
                    <span><i class="bi bi-emoji-smile-fill"></i> <?php echo $s['satisfaction']; ?>%</span>
                </div>
            </div>
            <?php endfor; ?>
            
            <?php // Fill empty podium spots if less than 3
            for ($i = count($students); $i < 3; $i++): ?>
            <div class="lb-podium-card <?php echo $medals[$i]; ?>" style="opacity:0.3; pointer-events:none;">
                <div class="lb-rank-badge"><?php echo $emojis[$i]; ?></div>
                <div class="lb-podium-avatar">
                    <div class="placeholder-avatar">?</div>
                </div>
                <div class="lb-podium-name" style="color:#64748b;">Chưa có</div>
                <div class="lb-podium-rating" style="color:#64748b;">--</div>
            </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <!-- List - Rank 4+ -->
        <?php if (count($students) > 3): ?>
        <div class="lb-list">
            <?php for ($i = 3; $i < count($students); $i++):
                $s = $students[$i];
                $rank = $i + 1;
                $avatarUrl = !empty($s['avatar']) ? htmlspecialchars($s['avatar']) : '';
                $satClass = $s['satisfaction'] >= 80 ? '' : ($s['satisfaction'] >= 50 ? 'medium' : 'low');
            ?>
            <div class="lb-list-item" style="animation-delay: <?php echo ($i - 3) * 0.1; ?>s" onclick="window.open('view_profile.php?id=<?php echo $s['id']; ?>', '_blank')">
                <div class="lb-list-rank">#<?php echo $rank; ?></div>
                <div class="lb-list-avatar">
                    <?php if ($avatarUrl): ?>
                        <img src="<?php echo $avatarUrl; ?>" alt="<?php echo htmlspecialchars($s['name']); ?>">
                    <?php else: ?>
                        <div class="placeholder-avatar"><?php echo mb_strtoupper(mb_substr($s['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lb-list-info">
                    <div class="lb-list-name">
                        <?php echo htmlspecialchars($s['name']); ?>
                        <?php if ($s['verified']): ?>
                            <i class="bi bi-patch-check-fill" style="color:#3b82f6;font-size:0.85rem;"></i>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($s['location'])): ?>
                    <div class="lb-list-location"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($s['location']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lb-list-stats">
                    <div class="lb-list-rating">
                        <div class="stars">
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                                <i class="<?php echo $star <= round($s['avg_rating']) ? 'fas' : 'far'; ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="score"><?php echo $s['avg_rating']; ?></div>
                    </div>
                    <div class="lb-list-reviews">
                        <div class="count"><?php echo $s['review_count']; ?></div>
                        <div class="label">Đánh giá</div>
                    </div>
                    <div class="lb-list-satisfaction">
                        <div class="lb-satisfaction-ring <?php echo $satClass; ?>">
                            <?php echo $s['satisfaction']; ?>%
                        </div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php
if (!$isEmbed) {
    require_once 'footer.php';
} else {
    echo '</body></html>';
}
?>
