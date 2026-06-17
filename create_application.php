<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config.php';
require_login();

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

// Admin accounts should not use student features
if (is_admin_user()) {
    if ($isEmbed) {
        // Khi embed, hiển thị thông báo thay vì redirect (tránh load sidebar trong iframe)
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"><style>body{background:#f1f5f9;padding:2rem;display:flex;align-items:center;justify-content:center;min-height:80vh;}.admin-notice{text-align:center;background:#fff;padding:3rem;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,0.1);max-width:500px;}.admin-notice i{font-size:4rem;color:#3b82f6;margin-bottom:1rem;}.admin-notice h3{color:#1e293b;margin-bottom:0.5rem;}.admin-notice p{color:#64748b;margin-bottom:1.5rem;}.admin-notice a{display:inline-flex;align-items:center;gap:0.5rem;background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;padding:0.75rem 1.5rem;border-radius:12px;text-decoration:none;font-weight:600;transition:transform 0.2s;}.admin-notice a:hover{transform:translateY(-2px);}</style></head><body>';
        echo '<div class="admin-notice">';
        echo '<i class="bi bi-shield-check"></i>';
        echo '<h3>Tài khoản Quản trị viên</h3>';
        echo '<p>Quản trị viên vui lòng sử dụng trang quản trị để tạo bài viết.</p>';
        echo '<a href="admin_posts.php?create=application" target="_top"><i class="bi bi-box-arrow-up-right"></i> Đi đến trang quản trị</a>';
        echo '</div></body></html>';
        exit;
    }
    header('Location: admin.php');
    exit;
}
if ($_SESSION['role'] !== 'student') {
        die('Chỉ sinh viên mới có thể tạo tin ứng tuyển.');
}

 $stmtUser = $pdo->prepare('SELECT id, name, student_id, verified, phone, location, school, can_post FROM users WHERE id = ?');
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();
if (!$currentUser) {
    // user no longer exists (database reset?) -> force re-login
    session_unset();
    session_destroy();
    header('Location: login.php?session=expired');
    exit;
}

$_SESSION['verified'] = !empty($currentUser['verified']) ? 1 : 0;

$isEmbedEarly = $isEmbed;

if (empty($currentUser['can_post'])) {
    // show latest posting/verification request if any
    $stmtReq = $pdo->prepare('SELECT * FROM posting_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmtReq->execute([$currentUser['id']]);
    $latestRequest = $stmtReq->fetch();

    if ($isEmbedEarly) {
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"><style>body{background:#f1f5f9;padding:1rem;}</style></head><body>';
    } else {
        require_once 'header.php';
    }
    ?>
    <style>
    .permission-required-page {
        min-height: calc(100vh - 200px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .permission-card {
        max-width: 650px;
        width: 100%;
        background: #fff;
        border-radius: 28px;
        box-shadow: 0 25px 80px rgba(11, 63, 145, 0.15);
        overflow: hidden;
        animation: permCardSlide 0.5s ease-out;
    }
    @keyframes permCardSlide {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .permission-header {
        background: linear-gradient(135deg, #0b3f91 0%, #1e40af 50%, #3b82f6 100%);
        padding: 2.5rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .permission-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 80%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        pointer-events: none;
    }
    .permission-icon {
        width: 90px;
        height: 90px;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
        font-size: 2.5rem;
        color: #fff;
        border: 2px solid rgba(255,255,255,0.2);
        position: relative;
        z-index: 1;
    }
    .permission-header h2 {
        color: #fff;
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 0.5rem;
        position: relative;
        z-index: 1;
    }
    .permission-header p {
        color: rgba(255,255,255,0.85);
        font-size: 1rem;
        margin: 0;
        position: relative;
        z-index: 1;
        line-height: 1.6;
    }
    .permission-body {
        padding: 2rem;
    }
    .perm-status-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 18px;
        margin-bottom: 1.5rem;
        animation: statusPop 0.4s ease-out 0.2s backwards;
    }
    @keyframes statusPop {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .perm-status-card.pending {
        background: linear-gradient(135deg, #fef9c3 0%, #fef3c7 100%);
        border: 1px solid #fcd34d;
    }
    .perm-status-card.rejected {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border: 1px solid #fca5a5;
    }
    .perm-status-card.info {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border: 1px solid #93c5fd;
    }
    .perm-status-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        flex-shrink: 0;
    }
    .perm-status-card.pending .perm-status-icon {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
    }
    .perm-status-card.rejected .perm-status-icon {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.35);
    }
    .perm-status-card.info .perm-status-icon {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.35);
    }
    .perm-status-content h4 {
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0 0 0.5rem;
    }
    .perm-status-card.pending .perm-status-content h4 { color: #92400e; }
    .perm-status-card.rejected .perm-status-content h4 { color: #991b1b; }
    .perm-status-card.info .perm-status-content h4 { color: #1e40af; }
    .perm-status-content p {
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.6;
    }
    .perm-status-card.pending .perm-status-content p { color: #a16207; }
    .perm-status-card.rejected .perm-status-content p { color: #b91c1c; }
    .perm-status-card.info .perm-status-content p { color: #1d4ed8; }
    .perm-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .btn-perm-primary {
        flex: 1;
        min-width: 180px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        padding: 1.1rem 1.75rem;
        background: linear-gradient(135deg, #0b3f91 0%, #3b82f6 100%);
        color: #fff;
        border: none;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.35);
    }
    .btn-perm-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(59, 130, 246, 0.45);
        color: #fff;
    }
    .btn-perm-secondary {
        flex: 1;
        min-width: 180px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        padding: 1.1rem 1.75rem;
        background: #f1f5f9;
        color: #475569;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .btn-perm-secondary:hover {
        background: #e2e8f0;
        color: #1e293b;
        border-color: #cbd5e1;
    }
    @media (max-width: 576px) {
        .permission-header { padding: 2rem 1.5rem; }
        .permission-body { padding: 1.5rem; }
        .permission-icon { width: 70px; height: 70px; font-size: 2rem; }
        .permission-header h2 { font-size: 1.4rem; }
        .perm-actions { flex-direction: column; }
        .btn-perm-primary, .btn-perm-secondary { width: 100%; }
    }
    </style>

    <div class="permission-required-page">
        <div class="permission-card">
            <div class="permission-header">
                <div class="permission-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Cần cấp quyền trước khi đăng tin</h2>
                <p>Tài khoản sinh viên của bạn chưa được quản trị viên xác thực nên tạm thời chưa thể tạo tin ứng tuyển.</p>
            </div>
            
            <div class="permission-body">
                <?php if ($latestRequest): ?>
                    <?php if ($latestRequest['status'] === 'pending'): ?>
                        <div class="perm-status-card pending">
                            <div class="perm-status-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="perm-status-content">
                                <h4>Đơn xin cấp quyền đang được xem xét</h4>
                                <p>
                                    <i class="far fa-calendar-alt"></i> Gửi ngày: <?php echo date('d/m/Y H:i', strtotime($latestRequest['created_at'])); ?><br>
                                    Bạn sẽ được thông báo khi quản trị viên duyệt hồ sơ.
                                </p>
                            </div>
                        </div>
                    <?php elseif ($latestRequest['status'] === 'rejected'): ?>
                        <div class="perm-status-card rejected">
                            <div class="perm-status-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="perm-status-content">
                                <h4>Đơn xin cấp quyền bị từ chối</h4>
                                <p>
                                    <?php if (!empty($latestRequest['admin_note'])): ?>
                                        <strong>Lý do:</strong> <?php echo nl2br(htmlspecialchars($latestRequest['admin_note'])); ?><br>
                                    <?php endif; ?>
                                    Vui lòng điều chỉnh thông tin và gửi lại yêu cầu.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="perm-status-card info">
                        <div class="perm-status-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="perm-status-content">
                            <h4>Yêu cầu xác thực tài khoản</h4>
                            <p>Bạn cần gửi yêu cầu cấp quyền với giấy tờ minh chứng (thẻ sinh viên, giấy xác nhận thực tập) để quản trị viên phê duyệt.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="perm-actions">
                    <?php if ($isEmbedEarly): ?>
                    <a class="btn-perm-primary" href="request_posting_permission.php?embed=1" target="_self">
                        <i class="fas fa-file-signature"></i> Xin cấp quyền
                    </a>
                    <?php else: ?>
                    <a class="btn-perm-primary" href="request_posting_permission.php">
                        <i class="fas fa-file-signature"></i> Xin cấp quyền
                    </a>
                    <a class="btn-perm-secondary" href="dashboard_student.php">
                        <i class="fas fa-arrow-left"></i> Quay lại bảng điều khiển
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    if ($isEmbedEarly) {
        echo '</body></html>';
    } else {
        require_once 'footer.php';
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $studentFullname = trim($_POST['student_fullname'] ?? ($currentUser['name'] ?? ''));
    $studentCode = trim($_POST['student_code'] ?? ($currentUser['student_id'] ?? ''));
    $studentClass = trim($_POST['student_class'] ?? '');
    $school = trim($_POST['school'] ?? '');
    $suggestedPrice = isset($_POST['suggested_price']) ? (int)preg_replace('/[^0-9]/', '', $_POST['suggested_price']) : 22700;
    $cardImagePath = null;
    $videoPath = null;

    // Ensure columns exist (auto-migrate if needed)
    try {
        $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'suggested_price'");
        $check->execute();
        if ((int)$check->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN suggested_price INT NULL AFTER recruiter_fullname");
        }
        
        $checkVideo = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'video_path'");
        $checkVideo->execute();
        if ((int)$checkVideo->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN video_path VARCHAR(255) NULL AFTER suggested_price");
        }
    } catch (Exception $e) { /* ignore */ }

    if (!$title || !$content || !$contact || !$studentFullname || !$studentCode || !$studentClass) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } else {
        // Combine skills into content if provided
        if ($skills) {
            $content .= "\n\nKỹ năng nổi bật: " . $skills;
        }

        $evidenceImagePath = null;

        // Handle card image upload
        if (!empty($_FILES['card_image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if ($_FILES['card_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải ảnh thẻ thất bại. Vui lòng thử lại.';
            } else {
                $detectedType = null;
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($_FILES['card_image']['tmp_name']);
                }
                if (!$detectedType && isset($_FILES['card_image']['type'])) {
                    $detectedType = $_FILES['card_image']['type'];
                }
                if ($detectedType && !in_array($detectedType, $allowedTypes)) {
                    $error = 'Chỉ hỗ trợ ảnh JPEG, PNG, WEBP.';
                } elseif ($_FILES['card_image']['size'] > 3 * 1024 * 1024) {
                    $error = 'Ảnh thẻ tối đa 3MB.';
                } else {
                    $targetDir = __DIR__ . '/uploads/student_cards';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['card_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'card_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'jpg');
                    $targetPath = $targetDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['card_image']['tmp_name'], $targetPath)) {
                        $cardImagePath = 'uploads/student_cards/' . $filename;
                    } else {
                        $error = 'Không thể lưu ảnh thẻ sinh viên. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle evidence image upload
        if (!empty($_FILES['evidence_image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if ($_FILES['evidence_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải ảnh minh chứng thất bại. Vui lòng thử lại.';
            } else {
                $detectedType = null;
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($_FILES['evidence_image']['tmp_name']);
                }
                if (!$detectedType && isset($_FILES['evidence_image']['type'])) {
                    $detectedType = $_FILES['evidence_image']['type'];
                }
                if ($detectedType && !in_array($detectedType, $allowedTypes)) {
                    $error = 'Ảnh minh chứng chỉ hỗ trợ định dạng JPEG, PNG, WEBP.';
                } elseif ($_FILES['evidence_image']['size'] > 5 * 1024 * 1024) {
                    $error = 'Ảnh minh chứng tối đa 5MB.';
                } else {
                    $targetDir = __DIR__ . '/uploads/evidence_images';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['evidence_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'evidence_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'jpg');
                    $targetPath = $targetDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['evidence_image']['tmp_name'], $targetPath)) {
                        $evidenceImagePath = 'uploads/evidence_images/' . $filename;
                    } else {
                        $error = 'Không thể lưu ảnh minh chứng. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle video upload
        if (!empty($_FILES['health_video']['name']) && !$error) {
            $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
            $uploadError = $_FILES['health_video']['error'];
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'Video vượt quá giới hạn upload_max_filesize trong php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'Video vượt quá giới hạn MAX_FILE_SIZE trong form.',
                    UPLOAD_ERR_PARTIAL => 'Video chỉ được tải lên một phần.',
                    UPLOAD_ERR_NO_FILE => 'Không có video nào được tải lên.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm.',
                    UPLOAD_ERR_CANT_WRITE => 'Không thể ghi video vào đĩa.',
                    UPLOAD_ERR_EXTENSION => 'Một extension PHP đã dừng việc tải lên.',
                ];
                $error = $errorMessages[$uploadError] ?? 'Tải video thất bại. Mã lỗi: ' . $uploadError;
            } else {
                $detectedType = null;
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($_FILES['health_video']['tmp_name']);
                }
                if (!$detectedType && isset($_FILES['health_video']['type'])) {
                    $detectedType = $_FILES['health_video']['type'];
                }
                if ($detectedType && !in_array($detectedType, $allowedVideoTypes)) {
                    $error = 'Chỉ hỗ trợ video MP4, WebM, MOV, AVI.';
                } elseif ($_FILES['health_video']['size'] > 50 * 1024 * 1024) {
                    $error = 'Video tối đa 50MB.';
                } else {
                    $targetDir = __DIR__ . '/uploads/health_videos';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['health_video']['name'], PATHINFO_EXTENSION);
                    $filename = 'video_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'mp4');
                    $targetPath = $targetDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['health_video']['tmp_name'], $targetPath)) {
                        $videoPath = 'uploads/health_videos/' . $filename;
                    } else {
                        $error = 'Không thể lưu video. Vui lòng thử lại.';
                    }
                }
            }
        }

        if (!$error) {
            if (!empty($school)) {
                try {
                    $updateSchool = $pdo->prepare('UPDATE users SET school = ? WHERE id = ?');
                    $updateSchool->execute([$school, $_SESSION['user_id']]);
                } catch (Throwable $e) {}
            }
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, content, type, area, category, contact_info, student_fullname, student_code, student_class, suggested_price, card_image, evidence_image, video_path, evidence_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $content,
                'application',
                $area,
                $category,
                $contact,
                $studentFullname,
                $studentCode,
                $studentClass,
                max(0, $suggestedPrice),
                $cardImagePath,
                $evidenceImagePath,
                $videoPath,
                'Ảnh thẻ sinh viên hoặc giấy tờ minh chứng'
            ]);
            
            // Nếu đang trong iframe (embed mode), redirect về trang thành công trong iframe
            // hoặc dùng JavaScript để reload parent
            if ($isEmbed) {
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
                echo '<script>window.top.location.href = "dashboard_student.php";</script>';
                echo '</body></html>';
                exit;
            }
            header('Location: dashboard_student.php');
            exit;
        }
    }
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

if ($isEmbed): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo tin ứng tuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; margin: 0; padding: 0; }
        /* Loại bỏ khoảng trắng khi embed */
        .create-app-page { padding: 0 !important; }
        .create-app-card { max-width: 100% !important; margin: 0 !important; border-radius: 0 !important; box-shadow: none !important; }
    </style>
</head>
<body>
<?php else:
    require_once 'header.php';
endif; ?>

<style>
.create-app-page {
    min-height: calc(100vh - 200px);
    padding: 1rem 0.5rem;
}
.create-app-card {
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(11, 63, 145, 0.08);
    overflow: hidden;
    animation: cardFadeIn 0.4s ease-out;
}
@keyframes cardFadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
.create-app-header {
    background: linear-gradient(135deg, #0b3f91 0%, #1e40af 50%, #3b82f6 100%);
    padding: 1.25rem 1.5rem;
    position: relative;
    overflow: hidden;
}
.create-app-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    pointer-events: none;
}
.create-app-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.create-app-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    border: 2px solid rgba(255,255,255,0.2);
    flex-shrink: 0;
}
.create-app-header h1 {
    color: #fff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}
.create-app-header p {
    color: rgba(255,255,255,0.85);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.4;
}
.create-app-body {
    padding: 1.25rem 1.5rem;
}
.form-section {
    margin-bottom: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #e2e8f0;
}
.form-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}
.form-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.875rem;
}
.form-section-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1d4ed8;
    font-size: 0.9rem;
}
.form-floating-custom {
    position: relative;
    margin-bottom: 0.75rem;
}
.form-floating-custom .form-control,
.form-floating-custom .form-select {
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.6rem 0.875rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}
.form-floating-custom .form-control:focus,
.form-floating-custom .form-select:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.form-floating-custom label {
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.35rem;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
}
.form-floating-custom label .required {
    color: #ef4444;
    font-weight: 700;
}
.form-floating-custom .form-hint {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.25rem;
}
.form-floating-custom textarea.form-control {
    min-height: 100px;
    resize: vertical;
}
.input-group-custom {
    display: flex;
    align-items: stretch;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: #f8fafc;
}
.input-group-custom:focus-within {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.input-group-custom .input-icon {
    padding: 0.6rem 0.75rem;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}
.input-group-custom .form-control {
    border: none;
    background: transparent;
    padding: 0.6rem 0.75rem;
    font-size: 0.875rem;
    box-shadow: none !important;
}
.file-upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}
.file-upload-area:hover {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}
.file-upload-area.dragover {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}
.file-upload-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.file-upload-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.1rem;
    margin: 0 auto 0.5rem;
}
.file-upload-text {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.15rem;
    font-size: 0.85rem;
}
.file-upload-hint {
    font-size: 0.75rem;
    color: #94a3b8;
}
.form-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    margin-top: 1rem;
}
.btn-submit {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: linear-gradient(135deg, #0b3f91 0%, #3b82f6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}
.btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.75rem 1.25rem;
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}
.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e293b;
    border-color: #cbd5e1;
}
.alert-custom {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    animation: alertSlide 0.4s ease-out;
}
@keyframes alertSlide {
    from { opacity: 0; transform: translateX(-15px); }
    to { opacity: 1; transform: translateX(0); }
}
.alert-custom.error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.alert-custom .alert-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.alert-custom.error .alert-icon {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
}
.video-upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}
.video-upload-area:hover {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}
.video-upload-area.dragover {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}
.video-upload-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.video-upload-icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    margin: 0 auto 0.75rem;
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.25);
}
.video-upload-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 0.35rem;
}
.video-upload-text {
    font-weight: 600;
    color: #3b82f6;
    margin-bottom: 0.2rem;
    font-size: 0.85rem;
}
.video-upload-hint {
    font-size: 0.75rem;
    color: #6b7280;
}
.video-benefits {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 0.75rem;
}
.video-benefit-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.65rem;
    background: rgba(255,255,255,0.8);
    border-radius: 15px;
    font-size: 0.7rem;
    color: #1e40af;
    font-weight: 500;
}
.video-benefit-tag i {
    color: #3b82f6;
}
.video-preview-container {
    margin-top: 1rem;
    display: none;
}
.video-preview-container.show {
    display: block;
}
.video-preview {
    width: 100%;
    max-height: 300px;
    border-radius: 16px;
    background: #000;
}
.video-file-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 12px;
    margin-top: 1rem;
    border: 1px solid #d1fae5;
}
.video-file-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.3rem;
}
.video-file-details {
    flex: 1;
}
.video-file-name {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
    word-break: break-all;
}
.video-file-size {
    font-size: 0.85rem;
    color: #64748b;
}
.btn-remove-video {
    padding: 0.5rem;
    background: #fee2e2;
    border: none;
    border-radius: 8px;
    color: #dc2626;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-remove-video:hover {
    background: #fecaca;
}
@media (max-width: 768px) {
    .create-app-header { padding: 1rem; }
    .create-app-body { padding: 1rem; }
    .create-app-header-content { flex-direction: column; text-align: center; }
    .create-app-icon { width: 45px; height: 45px; font-size: 1.3rem; }
    .create-app-header h1 { font-size: 1.1rem; }
    .form-actions { flex-direction: column; }
    .video-benefits { flex-direction: column; align-items: center; }
}
</style>

<div class="create-app-page">
    <div class="create-app-card">
        <!-- Header -->
        <div class="create-app-header">
            <div class="create-app-header-content">
                <div class="create-app-icon">🩺</div>
                <div>
                    <h1>Đăng Tin Ứng Tuyển</h1>
                    <p>Giới thiệu kinh nghiệm và mong muốn thực hành lâm sàng của bạn để kết nối với bệnh nhân cần hỗ trợ</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="create-app-body">
            <?php if ($error): ?>
                <div class="alert-custom error">
                    <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="<?php echo $isEmbed ? 'create_application.php?embed=1' : ''; ?>">
                <!-- Section: Thông tin cơ bản -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="fas fa-user-graduate"></i></div>
                        Thông tin sinh viên
                    </div>
                    
                    <div class="form-floating-custom">
                        <label><i class="fas fa-heading"></i> Tiêu đề bài đăng <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ví dụ: Sinh viên Y năm 4 tìm cơ hội thực hành nội khoa" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-user"></i> Họ và tên <span class="required">*</span></label>
                                <input type="text" name="student_fullname" class="form-control" placeholder="Nhập họ tên đầy đủ" required value="<?php echo htmlspecialchars($_POST['student_fullname'] ?? ($currentUser['name'] ?? '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-id-card"></i> Mã số sinh viên <span class="required">*</span></label>
                                <input type="text" name="student_code" class="form-control" placeholder="Ví dụ: 20DH123" required value="<?php echo htmlspecialchars($_POST['student_code'] ?? ($currentUser['student_id'] ?? '')); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-users"></i> Mã lớp <span class="required">*</span></label>
                                <input type="text" name="student_class" class="form-control" placeholder="Ví dụ: DHY4A" required value="<?php echo htmlspecialchars($_POST['student_class'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-school"></i> Trường đại học <span class="required">*</span></label>
                                <input type="text" name="school" class="form-control" placeholder="Ví dụ: Đại học Trà Vinh" required value="<?php echo htmlspecialchars($_POST['school'] ?? ($currentUser['school'] ?? '')); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .vp-suggestion-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.5rem;
                    margin-top: 0.5rem;
                    margin-bottom: 0.6rem;
                    align-items: center;
                }
                .vp-suggestion-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.25rem;
                    padding: 0.35rem 0.75rem;
                    background: #f0fdf4;
                    color: #0f766e;
                    border: 1px dashed #ccfbf1;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    user-select: none;
                }
                .vp-suggestion-chip:hover {
                    background: #ccfbf1;
                    border-color: #0d9488;
                    transform: translateY(-1px);
                }
                .vp-suggestion-chip.template {
                    background: #f0f9ff;
                    color: #0369a1;
                    border-color: #bae6fd;
                }
                .vp-suggestion-chip.template:hover {
                    background: #e0f2fe;
                    border-color: #0284c7;
                }
                </style>

                <!-- Section: Kinh nghiệm & Kỹ năng -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="fas fa-briefcase-medical"></i></div>
                        Kinh nghiệm & Kỹ năng
                    </div>

                    <div class="form-floating-custom">
                        <label><i class="fas fa-file-alt"></i> Giới thiệu / Kinh nghiệm <span class="required">*</span></label>
                        <textarea name="content" class="form-control" placeholder="Tóm tắt kỹ năng, kinh nghiệm thực tập, thời gian có thể hỗ trợ, các chứng chỉ đã có..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                        <div class="vp-suggestion-container">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600; margin-right:0.25rem;">Gợi ý mẫu nhanh:</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('nursing')"><i class="fas fa-file-prescription"></i> Sinh viên Điều dưỡng</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('general')"><i class="fas fa-user-md"></i> Sinh viên Đa khoa</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('other')"><i class="fas fa-hands-helping"></i> Hỗ trợ khác</span>
                        </div>
                        <div class="form-hint">Mô tả chi tiết giúp bệnh nhân hiểu rõ hơn về năng lực của bạn</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-star"></i> Kỹ năng nổi bật</label>
                                <input type="text" name="skills" class="form-control" placeholder="Ví dụ: Chăm sóc bệnh mãn tính, tiêm truyền, đo huyết áp" value="<?php echo htmlspecialchars($_POST['skills'] ?? ''); ?>">
                                <div class="vp-suggestion-container">
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Đo huyết áp')">+ Đo huyết áp</span>
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Tiêm truyền')">+ Tiêm truyền</span>
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Chăm sóc vết thương')">+ Chăm sóc vết thương</span>
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Thay băng')">+ Thay băng</span>
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Sơ cứu cơ bản')">+ Sơ cứu</span>
                                    <span class="vp-suggestion-chip" onclick="appendSkill('Phục hồi chức năng')">+ PHCN</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-stethoscope"></i> Chuyên ngành mong muốn</label>
                                <input type="text" name="category" class="form-control" placeholder="Ví dụ: Nội khoa, Nhi, Sản, Ngoại" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                                <div class="vp-suggestion-container">
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Nội khoa')">+ Nội khoa</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Ngoại khoa')">+ Ngoại khoa</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Nhi khoa')">+ Nhi khoa</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Sản khoa')">+ Sản khoa</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Hồi sức cấp cứu')">+ HSCC</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Y học cổ truyền')">+ YHCT</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Thông tin liên hệ & Giá -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="fas fa-phone-alt"></i></div>
                        Liên hệ & Chi phí
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-envelope"></i> Thông tin liên hệ <span class="required">*</span></label>
                                <input type="text" name="contact" class="form-control" placeholder="Số điện thoại hoặc email" required value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-map-marker-alt"></i> Khu vực ưu tiên</label>
                                <input type="text" name="area" class="form-control" placeholder="Ví dụ: Quận 1, TP.HCM hoặc link Google Maps" value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>">
                                <div class="form-hint">Nhập tên khu vực hoặc dán trực tiếp link chia sẻ Google Maps để tạo nút định vị bản đồ.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-money-bill-wave"></i> Gợi ý giá (VNĐ)</label>
                                <input type="number" min="0" step="100" name="suggested_price" class="form-control" value="<?php echo htmlspecialchars($_POST['suggested_price'] ?? '22700'); ?>">
                                <div class="form-hint">Mặc định 22.700đ/giờ theo quy định, bạn có thể điều chỉnh</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-id-badge"></i> Ảnh thẻ sinh viên</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <input type="file" name="card_image" accept="image/*" id="cardImageInput">
                                    <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="file-upload-text" id="fileUploadText">Kéo thả hoặc nhấn để chọn ảnh thẻ</div>
                                    <div class="file-upload-hint">JPG, PNG hoặc WEBP (tối đa 3MB)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-file-invoice"></i> Ảnh chứng chỉ / Bằng cấp khác (Tùy chọn)</label>
                                <div class="file-upload-area" id="evidenceUploadArea">
                                    <input type="file" name="evidence_image" accept="image/*" id="evidenceImageInput">
                                    <div class="file-upload-icon"><i class="fas fa-award"></i></div>
                                    <div class="file-upload-text" id="evidenceUploadText">Kéo thả hoặc nhấn để chọn ảnh chứng chỉ</div>
                                    <div class="file-upload-hint">JPG, PNG hoặc WEBP (tối đa 5MB)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Video giới thiệu -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="fas fa-video"></i></div>
                        Video giới thiệu bản thân (Tùy chọn)
                    </div>

                    <div class="video-upload-area" id="videoUploadArea">
                        <input type="file" name="health_video" accept="video/*" id="healthVideoInput">
                        <div class="video-upload-icon"><i class="fas fa-film"></i></div>
                        <div class="video-upload-title">Tải lên video ngắn giới thiệu bản thân</div>
                        <div class="video-upload-text" id="videoUploadText">Kéo thả hoặc nhấn để chọn video</div>
                        <div class="video-upload-hint">MP4, WebM, MOV (tối đa 50MB, khuyến nghị dưới 2 phút)</div>
                        
                        <div class="video-benefits">
                            <span class="video-benefit-tag"><i class="fas fa-check-circle"></i> Bệnh nhân tin tưởng hơn</span>
                            <span class="video-benefit-tag"><i class="fas fa-check-circle"></i> Giới thiệu kỹ năng trực quan</span>
                            <span class="video-benefit-tag"><i class="fas fa-check-circle"></i> Nổi bật hồ sơ ứng tuyển</span>
                        </div>
                    </div>

                    <div class="video-preview-container" id="videoPreviewContainer">
                        <div class="video-file-info" id="videoFileInfo">
                            <div class="video-file-icon"><i class="fas fa-play"></i></div>
                            <div class="video-file-details">
                                <div class="video-file-name" id="videoFileName"></div>
                                <div class="video-file-size" id="videoFileSize"></div>
                            </div>
                            <button type="button" class="btn-remove-video" id="removeVideoBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <video class="video-preview" id="videoPreview" controls></video>
                    </div>

                    <div class="form-hint mt-2">
                        <i class="fas fa-info-circle text-primary"></i> 
                        Video giới thiệu giúp bệnh nhân cảm thấy an tâm hơn khi lựa chọn bạn chăm sóc sức khỏe cho người thân của họ.
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Đăng tin ứng tuyển
                    </button>
                    <?php if ($isEmbed): ?>
                    <a class="btn-cancel" href="javascript:void(0)" onclick="window.top.location.href='dashboard_student.php'">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <?php else: ?>
                    <a class="btn-cancel" href="dashboard_student.php">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup for Card Image
    setupDragDrop('cardImageInput', 'fileUploadArea', 'fileUploadText');
    // Setup for Evidence Image
    setupDragDrop('evidenceImageInput', 'evidenceUploadArea', 'evidenceUploadText');

    // Setup for Video
    const videoInput = document.getElementById('healthVideoInput');
    const uploadArea = document.getElementById('videoUploadArea');
    const uploadText = document.getElementById('videoUploadText');
    const previewContainer = document.getElementById('videoPreviewContainer');
    const videoPreview = document.getElementById('videoPreview');
    const videoFileName = document.getElementById('videoFileName');
    const videoFileSize = document.getElementById('videoFileSize');
    const removeBtn = document.getElementById('removeVideoBtn');

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function handleVideoSelect(file) {
        if (file && file.type.startsWith('video/')) {
            videoFileName.textContent = file.name;
            videoFileSize.textContent = formatFileSize(file.size);
            
            const url = URL.createObjectURL(file);
            videoPreview.src = url;
            
            uploadArea.style.display = 'none';
            previewContainer.classList.add('show');
        }
    }

    function resetVideoUpload() {
        videoInput.value = '';
        videoPreview.src = '';
        uploadArea.style.display = 'block';
        previewContainer.classList.remove('show');
    }

    if (videoInput) {
        videoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                handleVideoSelect(this.files[0]);
            }
        });
    }

    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('video/')) {
                    videoInput.files = e.dataTransfer.files;
                    handleVideoSelect(file);
                }
            }
        });
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', resetVideoUpload);
    }

    function setupDragDrop(inputId, areaId, textId) {
        const input = document.getElementById(inputId);
        const area = document.getElementById(areaId);
        const text = document.getElementById(textId);

        if (input && area) {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    text.textContent = this.files[0].name;
                    area.style.borderColor = '#10b981';
                    area.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                }
            });

            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            area.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });

            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    text.textContent = e.dataTransfer.files[0].name;
                    area.style.borderColor = '#10b981';
                    area.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                }
            });
        }
    }
});

// ========== SUGGESTION FORM HELPERS ==========
function insertTemplate(type) {
    const textarea = document.querySelector('textarea[name="content"]');
    if (!textarea) return;
    
    const templates = {
        nursing: "Tôi là sinh viên ngành Điều dưỡng. Có kỹ năng thực hành lâm sàng tốt, cẩn thận và chu đáo. Đã từng thực tập tại bệnh viện đa khoa.\n\nKỹ năng chính:\n- Đo dấu hiệu sinh tồn, đo huyết áp\n- Chăm sóc và thay băng vết thương sạch/nhiễm trùng\n- Chăm sóc người cao tuổi, hỗ trợ ăn uống, dùng thuốc đúng giờ.",
        general: "Tôi là sinh viên ngành Y đa khoa. Có kiến thức chuyên môn cơ bản vững vàng, trung thực và tận tụy. Có kinh nghiệm hỗ trợ bác sĩ trong bệnh viện.\n\nKỹ năng chính:\n- Đo huyết áp, theo dõi đường huyết, dấu hiệu sinh tồn\n- Hướng dẫn bệnh nhân tập vật lý trị liệu cơ bản\n- Hỗ trợ giải thích toa thuốc, tư vấn chế độ dinh dưỡng.",
        other: "Tôi là sinh viên Y khoa mong muốn hỗ trợ chăm sóc sức khỏe bệnh nhân ngoài giờ học.\n\nKỹ năng chính:\n- Chăm sóc sức khỏe toàn diện tại nhà\n- Hỗ trợ vệ sinh cá nhân, tắm rửa, hỗ trợ đi lại\n- Đưa đón bệnh nhân đi khám bệnh, mua thuốc."
    };
    
    if (textarea.value.trim() && !confirm("Nội dung hiện tại sẽ bị ghi đè bằng mẫu gợi ý. Bạn có muốn tiếp tục không?")) {
        return;
    }
    textarea.value = templates[type] || '';
    textarea.focus();
}

function appendSkill(skill) {
    const input = document.querySelector('input[name="skills"]');
    if (!input) return;
    let val = input.value.trim();
    if (val === '') {
        input.value = skill;
    } else {
        let list = val.split(',').map(s => s.trim()).filter(s => s !== '');
        if (!list.includes(skill)) {
            list.push(skill);
            input.value = list.join(', ');
        }
    }
    input.focus();
}

function appendCategory(cat) {
    const input = document.querySelector('input[name="category"]');
    if (!input) return;
    let val = input.value.trim();
    if (val === '') {
        input.value = cat;
    } else {
        let list = val.split(',').map(s => s.trim()).filter(s => s !== '');
        if (!list.includes(cat)) {
            list.push(cat);
            input.value = list.join(', ');
        }
    }
    input.focus();
}
</script>

<?php if ($isEmbed): ?>
</body>
</html>
<?php else:
    require_once 'footer.php';
endif; ?>
