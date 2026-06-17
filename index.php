<?php
require_once 'config.php';

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

$userFavorites = [];
if (is_logged_in()) {
    try {
        $favStmt = $pdo->prepare('SELECT post_id FROM favorites WHERE user_id = ?');
        $favStmt->execute([$_SESSION['user_id']]);
        $userFavorites = $favStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {}
}

function format_post_date(string $dateStr): string {
    $time = strtotime($dateStr);
    $day = date('d', $time);
    $monthNum = date('n', $time); // 1 to 12
    $year = date('Y', $time);
    $timeStr = date('H:i', $time);
    return "{$day} T{$monthNum}, {$year} | {$timeStr}";
}

if (!$isEmbed) {
    require_once 'header.php';
} else {
    // Embed mode - minimal HTML
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Danh sách tin</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
    echo '<link rel="stylesheet" href="assets/css/style.css">';
    echo '<style>body{background:#f1f5f9;margin:0;padding:1rem;}.hero-section,.site-footer{display:none !important;}</style>';
    echo '</head><body>';
}

// Build search/filter query
$where = [];
$params = [];
if (!empty($_GET['q'])) {
        $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
        $params[] = '%'.$_GET['q'].'%';
        $params[] = '%'.$_GET['q'].'%';
}
if (!empty($_GET['type'])) {
        $where[] = 'p.type = ?';
        $params[] = $_GET['type'];
}
if (!empty($_GET['category'])) {
        $where[] = 'p.category = ?';
        $params[] = $_GET['category'];
}
if (!empty($_GET['area'])) {
        $where[] = 'p.area LIKE ?';
        $params[] = '%'.$_GET['area'].'%';
}

$sql = 'SELECT p.*, 
    COALESCE(u.name, u.username, u.full_name) AS author_name, 
    u.verified AS author_verified, 
    u.last_activity AS author_last_activity,
    u.avatar AS author_avatar
FROM posts p 
JOIN users u ON p.user_id = u.id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$isAdmin = is_admin_user();
$primaryAction = 'register.php';
$primaryText = 'Đăng ký miễn phí';
$secondaryAction = 'login.php';
$secondaryText = 'Đăng nhập';
if (is_logged_in() && !$isAdmin) {
    if ($_SESSION['role'] === 'patient') {
        $primaryAction = 'create_recruitment.php';
        $primaryText = 'Đăng tin tìm hỗ trợ';
        $secondaryAction = 'dashboard_patient.php';
        $secondaryText = 'Xem dashboard';
    } else {
        $primaryAction = 'create_application.php';
        $primaryText = 'Đăng tin ứng tuyển';
        $secondaryAction = 'dashboard_student.php';
        $secondaryText = 'Xem dashboard';
    }
}

$visiblePosts = count($posts);

if (!function_exists('encode_relative_url_path')) {
    function encode_relative_url_path(string $path): string {
        $normalized = str_replace('\\', '/', $path);
        $segments = explode('/', $normalized);
        $encoded = array_map('rawurlencode', array_filter($segments, 'strlen'));
        return implode('/', $encoded);
    }
}

// Determine hero image (prefer curated asset, fallback to gallery folder)
$defaultHeroRelative = 'assets/img/hero-home.png';
$heroImageRelative = $defaultHeroRelative;
$defaultHeroPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $defaultHeroRelative);
if (!file_exists($defaultHeroPath)) {
    $galleryDir = __DIR__ . DIRECTORY_SEPARATOR . 'Ảnh Giao diện';
    if (is_dir($galleryDir)) {
        $glob = glob($galleryDir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
        if ($glob) {
            $heroImageRelative = str_replace('\\', '/', substr($glob[0], strlen(__DIR__) + 1));
        }
    }
}
$heroImageSrc = encode_relative_url_path($heroImageRelative);
?>

<section class="homepage-hero my-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <span class="hero-badge">🔍 Kết nối nhanh chóng</span>
            <h1 class="hero-title">Kết Nối Y Tế Giữa Bệnh Nhân Và Sinh Viên Y Khoa</h1>
            <p class="hero-subtitle">Tạo cầu nối an toàn để tìm kiếm sự hỗ trợ chăm sóc tại nhà hoặc cơ hội thực hành lâm sàng chỉ trong vài phút.</p>

            <div class="hero-actions">
                <?php if (!$isAdmin): ?>
                    <a class="btn btn-light btn-lg" href="<?php echo $primaryAction; ?>"><?php echo htmlspecialchars($primaryText); ?></a>
                    <a class="btn btn-outline-light btn-lg" href="<?php echo $secondaryAction; ?>"><?php echo htmlspecialchars($secondaryText); ?></a>
                <?php endif; ?>
                <a class="btn btn-outline-light" href="#posts">Xem tin mới nhất</a>
            </div>

            <div class="hero-stats">
                <div class="stat-bubble">
                    <strong><?php echo number_format($visiblePosts); ?></strong>
                    <span>Tin đang hiển thị</span>
                </div>
                <div class="stat-bubble">
                    <strong>24/7</strong>
                    <span>Hỗ trợ trực tuyến</span>
                </div>
                <div class="stat-bubble">
                    <strong>3 bước</strong>
                    <span>Hoàn tất kết nối</span>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="hero-illustration">
                <img class="hero-main-image" src="<?php echo htmlspecialchars($heroImageSrc); ?>" alt="Nhân viên y tế hỗ trợ bệnh nhân" loading="lazy">
                <div class="hero-floating-stack">
                    <div class="hero-floating-card card-schedule">
                        <div class="icon">📅</div>
                        <div>
                            <strong>30+ lịch hẹn</strong>
                            <small class="meta">Đặt mỗi tuần</small>
                        </div>
                    </div>
                    <div class="hero-floating-card card-rating">
                        <div>
                            <div class="rating-stars">★★★★★</div>
                            <strong>4.9/5</strong>
                            <small class="meta">Từ cộng đồng bệnh nhân</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="steps-section pt-0">
    <div class="steps-shell">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h2 class="fw-bold m-0">Cách Thức Hoạt Động</h2>
        </div>
        <div class="steps-grid">
            <article class="step-pill">
                <div class="step-pill__index">1</div>
                <div>
                    <h5>Đăng Ký Tài Khoản</h5>
                    <p>Tạo tài khoản miễn phí với email thường hoặc email sinh viên (@xxx.edu.vn).</p>
                </div>
            </article>
            <article class="step-pill">
                <div class="step-pill__index">2</div>
                <div>
                    <h5>Đăng Tin</h5>
                    <p>Bệnh nhân đăng tin tuyển dụng chăm sóc; sinh viên đăng tin ứng tuyển thực hành.</p>
                </div>
            </article>
            <article class="step-pill">
                <div class="step-pill__index">3</div>
                <div>
                    <h5>Kết Nối</h5>
                    <p>Xem, tìm kiếm và liên hệ với đối tác phù hợp qua tin nhắn an toàn.</p>
                </div>
            </article>
            <article class="step-pill">
                <div class="step-pill__index">4</div>
                <div>
                    <h5>Bắt Đầu Hợp Tác</h5>
                    <p>Thỏa thuận chi tiết và bắt đầu dịch vụ chăm sóc hoặc thực hành lâm sàng.</p>
                </div>
            </article>
        </div>
    </div>
</section>

<style>
/* Posts Section Styles */
.posts-section { margin-top: 3rem; }
.posts-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.posts-header h2 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
.posts-header h2 i { color: #3b82f6; }
.posts-count { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; padding: 0.5rem 1.25rem; border-radius: 25px; font-weight: 600; font-size: 0.9rem; }

/* Search Box */
.posts-search { background: #fff; border-radius: 20px; padding: 1.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.08); margin-bottom: 2rem; border: 1px solid #e2e8f0; }
.posts-search-grid { display: grid; grid-template-columns: 2fr 1fr 1.5fr 1.5fr auto; gap: 1rem; align-items: end; }
.posts-search-group { position: relative; }
.posts-search-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
.posts-search-group .search-icon { position: absolute; left: 1rem; bottom: 0.85rem; color: #94a3b8; }
.posts-search-group input, .posts-search-group select { width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; transition: all 0.3s ease; background: #f8fafc; }
.posts-search-group input:focus, .posts-search-group select:focus { outline: none; border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
.posts-search-btn { padding: 0.85rem 2rem; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.35); display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; }
.posts-search-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(59, 130, 246, 0.45); }

/* Posts Grid */
.posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem; }

.post-card-new {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}

.post-card-new:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 20px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-color: #cbd5e1;
}

.post-card-top-bar {
    padding: 0.6rem 1.25rem;
    font-size: 0.75rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.post-card-top-bar.application {
    background: linear-gradient(90deg, #dbeafe 0%, #eff6ff 100%);
    color: #1d4ed8;
}

.post-card-top-bar.recruitment {
    background: linear-gradient(90deg, #d1fae5 0%, #e6fdf5 100%);
    color: #059669;
}

.post-card-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    flex-grow: 1;
}

.post-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.post-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.4;
}

.post-card-title a {
    text-decoration: none !important;
    color: #1e293b;
    transition: color 0.2s ease;
}

.post-card-title a:hover {
    color: #2563eb;
}

.post-card-status {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    white-space: nowrap;
    letter-spacing: 0.025em;
    border: 1px solid transparent;
}

.post-card-status.open {
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
    background: #e6fdf5;
}
.post-card-status.inactive {
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #d97706;
    background: #fffbeb;
}
.post-card-status.closed {
    border: 1px solid rgba(100, 116, 139, 0.3);
    color: #64748b;
    background: #f8fafc;
}
.post-card-status.taken {
    border: 1px solid rgba(37, 99, 235, 0.3);
    color: #2563eb;
    background: #eff6ff;
}

.post-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.85rem;
}

.post-card-tag {
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

.post-card-tag.activity.recruitment {
    background: #f1f5f9;
    color: #475569;
}

.post-card-tag.activity.application {
    background: #eff6ff;
    color: #1d4ed8;
}

.post-card-tag.category {
    background: #faf5ff;
    color: #7e22ce;
}

.post-card-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: #f0fdf4;
    border-radius: 8px;
    color: #16a34a;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 1rem;
    width: 100%;
}

.post-card-user-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    padding: 0.6rem 1rem;
    margin-bottom: 1rem;
}

.post-card-user-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.post-card-user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #3b82f6;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    overflow: hidden;
}

.post-card-user-name {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.post-card-user-name .verified {
    color: #3b82f6;
    font-size: 0.95rem;
}

.post-card-user-status {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.65rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.post-card-user-status.online {
    background: #d1fae5;
    color: #065f46;
}

.post-card-user-status.online .status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
}

.post-card-user-status.offline {
    background: #f1f5f9;
    color: #64748b;
}

.post-card-user-status.offline .status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #94a3b8;
}

.post-card-desc {
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.post-card-skills {
    font-size: 0.85rem;
    color: #1e293b;
    background: #f8fafc;
    border-left: 3px solid #3b82f6;
    padding: 0.35rem 0.75rem;
    margin-bottom: 1rem;
    border-radius: 0 8px 8px 0;
}

.post-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid #f1f5f9;
    margin-top: auto;
}

.post-card-footer-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.post-card-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 1rem;
    background: #2563eb;
    color: #ffffff !important;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.8rem;
    text-decoration: none !important;
    transition: all 0.2s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
    position: relative;
    z-index: 10;
}

.post-card-btn:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.15);
}

.post-card-btn-fav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    color: #64748b;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    z-index: 10;
}

.post-card-btn-fav:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #cbd5e1;
}

.post-card-btn-fav.active {
    color: #ef4444;
    border-color: #fca5a5;
    background: #fef2f2;
}

.post-card-date {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

/* Empty State */
.posts-empty { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); }
.posts-empty-icon { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2.5rem; color: #3b82f6; }
.posts-empty h4 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 0.5rem; }
.posts-empty p { color: #64748b; margin: 0; }

@media (max-width: 991px) { .posts-search-grid { grid-template-columns: 1fr 1fr; } .posts-search-btn { width: 100%; justify-content: center; } }
@media (max-width: 767px) { .posts-search-grid { grid-template-columns: 1fr; } .posts-grid { grid-template-columns: 1fr; } .posts-header { flex-direction: column; align-items: flex-start; gap: 1rem; } }
</style>

<section id="posts" class="posts-section">
    <div class="posts-header">
        <h2><i class="bi bi-newspaper"></i> Tin mới nhất</h2>
        <span class="posts-count"><?php echo count($posts); ?> bài đăng</span>
    </div>

    <div class="posts-search">
        <form method="get">
            <div class="posts-search-grid">
                <div class="posts-search-group">
                    <label>Tìm kiếm</label>
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Tìm theo tiêu đề hoặc nội dung...">
                </div>
                <div class="posts-search-group">
                    <label>Loại tin</label>
                    <i class="bi bi-filter search-icon"></i>
                    <select name="type">
                        <option value="">Tất cả loại</option>
                        <option value="recruitment" <?php if(($_GET['type'] ?? '')=='recruitment') echo 'selected'; ?>>Tuyển dụng</option>
                        <option value="application" <?php if(($_GET['type'] ?? '')=='application') echo 'selected'; ?>>Ứng tuyển</option>
                    </select>
                </div>
                <div class="posts-search-group">
                    <label>Khu vực</label>
                    <i class="bi bi-geo-alt search-icon"></i>
                    <input type="text" name="area" placeholder="Nhập khu vực..." value="<?php echo htmlspecialchars($_GET['area'] ?? ''); ?>">
                </div>
                <div class="posts-search-group">
                    <label>Chuyên khoa</label>
                    <i class="bi bi-tag search-icon"></i>
                    <input type="text" name="category" placeholder="Chuyên khoa / Loại" value="<?php echo htmlspecialchars($_GET['category'] ?? ''); ?>">
                </div>
                <button type="submit" class="posts-search-btn"><i class="bi bi-search"></i> Tìm kiếm</button>
            </div>
        </form>
    </div>

    <?php if (!$posts): ?>
        <div class="posts-empty">
            <div class="posts-empty-icon"><i class="bi bi-inbox"></i></div>
            <h4>Chưa có tin nào</h4>
            <p>Hãy là người đầu tiên đăng tin trên hệ thống!</p>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $p): ?>
                <article class="post-card-new">
                    <div class="post-card-top-bar <?php echo $p['type']; ?>">
                        <?php if ($p['type'] === 'application'): ?>
                            <i class="bi bi-mortarboard-fill"></i>
                            <span>Sinh viên Y khoa ứng tuyển</span>
                        <?php else: ?>
                            <i class="bi bi-heart-pulse-fill"></i>
                            <span>Bệnh nhân cần chăm sóc</span>
                        <?php endif; ?>
                    </div>
                    <div class="post-card-body">
                        <!-- Header: Title + Status -->
                        <div class="post-card-header">
                            <h3 class="post-card-title">
                                <a href="view_post.php?id=<?php echo $p['id']; ?>" class="stretched-link"><?php echo htmlspecialchars($p['title']); ?></a>
                            </h3>
                            <?php 
                            $status = $p['status'] ?? 'open';
                            $statusText = ['open' => 'ĐANG MỞ', 'inactive' => 'CHƯA HOẠT ĐỘNG', 'closed' => 'ĐÃ ĐÓNG', 'taken' => 'ĐÃ NHẬN'];
                            ?>
                            <span class="post-card-status <?php echo $status; ?>"><?php echo $statusText[$status] ?? mb_strtoupper($status, 'UTF-8'); ?></span>
                        </div>
                        
                        <!-- Tags: Activity + Category -->
                        <div class="post-card-tags">
                            <?php if ($p['type'] === 'recruitment'): ?>
                                <span class="post-card-tag activity recruitment">Hoạt động: Tuyển dụng</span>
                            <?php else: ?>
                                <span class="post-card-tag activity application">Hoạt động: Ứng tuyển</span>
                            <?php endif; ?>
                            <?php if (!empty($p['category'])): ?>
                                <span class="post-card-tag category">Lĩnh vực: <?php echo htmlspecialchars($p['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Location -->
                        <?php if (!empty($p['area'])): ?>
                        <div class="post-card-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?php echo htmlspecialchars($p['area']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Author User Info Box -->
                        <div class="post-card-user-box">
                            <div class="post-card-user-left">
                                <div class="post-card-user-avatar">
                                    <?php if (!empty($p['author_avatar']) && upload_exists($p['author_avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars(public_url_for($p['author_avatar'])); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($p['author_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="post-card-user-name">
                                    <?php echo htmlspecialchars($p['author_name']); ?>
                                    <?php if (!empty($p['author_verified'])): ?>
                                        <i class="bi bi-patch-check-fill verified" title="Đã xác minh"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php $isOnline = is_user_online($p['author_last_activity'] ?? null); ?>
                            <div class="post-card-user-status <?php echo $isOnline ? 'online' : 'offline'; ?>">
                                <span class="status-dot"></span>
                                <span><?php echo $isOnline ? 'Đang online' : 'Ngoại tuyến'; ?></span>
                            </div>
                        </div>
                        
                        <!-- Description & Skills -->
                        <?php 
                        $rawContent = strip_tags($p['content']);
                        $skillsText = '';
                        $mainDesc = $rawContent;
                        if (preg_match('/(Kỹ năng nổi bật|Kỹ năng thực hành|Kỹ năng)[:\-]\s*(.*)$/iu', $rawContent, $matches)) {
                            $skillsText = trim($matches[2]);
                            $mainDesc = trim(str_replace($matches[0], '', $rawContent));
                        }
                        ?>
                        <p class="post-card-desc"><?php echo htmlspecialchars($mainDesc); ?></p>
                        
                        <?php if (!empty($skillsText)): ?>
                        <p class="post-card-skills">
                            <strong>Kỹ năng thực hành:</strong> <?php echo htmlspecialchars($skillsText); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Footer: Button + Bookmark & Time -->
                        <div class="post-card-footer">
                            <div class="post-card-footer-left">
                                <a href="view_post.php?id=<?php echo $p['id']; ?>" class="post-card-btn">
                                    <i class="bi bi-eye"></i> Xem chi tiết
                                </a>
                                <?php if (is_logged_in()): ?>
                                    <?php $isFav = in_array($p['id'], $userFavorites); ?>
                                    <form action="toggle_favorite.php" method="POST" class="d-inline-block bookmark-form" style="position:relative; z-index:10;">
                                        <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="redirect" value="index.php#posts">
                                        <button type="submit" class="post-card-btn-fav <?php echo $isFav ? 'active' : ''; ?>" title="<?php echo $isFav ? 'Bỏ lưu tin' : 'Lưu tin'; ?>">
                                            <i class="bi bi-bookmark<?php echo $isFav ? '-fill' : ''; ?>"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-card-date">
                                <i class="bi bi-clock"></i>
                                <span><?php echo format_post_date($p['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($isEmbed): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else: ?>
<?php require_once 'footer.php'; ?>
<?php endif; ?>
