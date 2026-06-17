<?php
// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config.php';
require_login();

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID không hợp lệ.');
}
$id = (int)$_GET['id'];

$stmt = $pdo->prepare('SELECT p.*, u.school FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) {
    die('Tin không tồn tại.');
}
if ($post['user_id'] != $_SESSION['user_id']) {
    die('Bạn không có quyền sửa tin này.');
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';
$error = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $area = trim($_POST['area'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    
    if ($post['type'] === 'recruitment') {
        $fullname = trim($_POST['fullname'] ?? '');
        $videoPath = $post['video_path'] ?? null;
        $suggestedPrice = isset($_POST['suggested_price']) ? (int)preg_replace('/[^0-9]/', '', $_POST['suggested_price']) : 50000;
        $workTime = trim($_POST['work_time'] ?? '');
        $jobType = trim($_POST['job_type'] ?? 'part_time');
        $isUrgent = isset($_POST['is_urgent']) ? 1 : 0;

        // Ensure columns exist (auto-migrate if needed)
        try {
            $checkWT = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'work_time'");
            $checkWT->execute();
            if ((int)$checkWT->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN work_time VARCHAR(255) NULL AFTER suggested_price");
            }

            $checkJT = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'job_type'");
            $checkJT->execute();
            if ((int)$checkJT->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN job_type VARCHAR(50) NULL AFTER work_time");
            }

            $checkUrgent = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'is_urgent'");
            $checkUrgent->execute();
            if ((int)$checkUrgent->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN is_urgent TINYINT(1) DEFAULT 0 AFTER job_type");
            }

            // Thêm các cột tài liệu y tế đính kèm của bệnh nhân nếu chưa có
            $checkPF = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'prescription_file'");
            $checkPF->execute();
            if ((int)$checkPF->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN prescription_file VARCHAR(255) NULL AFTER evidence_image");
            }

            $checkTRF = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'test_result_file'");
            $checkTRF->execute();
            if ((int)$checkTRF->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE posts ADD COLUMN test_result_file VARCHAR(255) NULL AFTER prescription_file");
            }
        } catch (Exception $e) {
            error_log('Auto-migrate posts table failed in edit: ' . $e->getMessage());
        }

        // Handle video upload
        if (!empty($_FILES['health_video']['name'])) {
            $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
            if ($_FILES['health_video']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải video thất bại. Vui lòng thử lại.';
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
                        if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])) {
                            @unlink(__DIR__ . '/' . $post['video_path']);
                        }
                        $videoPath = 'uploads/health_videos/' . $filename;
                    } else {
                        $error = 'Không thể lưu video. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle evidence image upload (recruitment)
        $evidenceImagePath = $post['evidence_image'] ?? null;
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
                        if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image'])) {
                            @unlink(__DIR__ . '/' . $post['evidence_image']);
                        }
                        $evidenceImagePath = 'uploads/evidence_images/' . $filename;
                    } else {
                        $error = 'Không thể lưu ảnh minh chứng. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle evidence image removal (recruitment)
        if (isset($_POST['remove_evidence_image']) && $_POST['remove_evidence_image'] === '1') {
            if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image'])) {
                @unlink(__DIR__ . '/' . $post['evidence_image']);
            }
            $evidenceImagePath = null;
        }

        // Handle video removal
        if (isset($_POST['remove_video']) && $_POST['remove_video'] === '1') {
            if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])) {
                @unlink(__DIR__ . '/' . $post['video_path']);
            }
            $videoPath = null;
        }

        // Handle prescription file upload (recruitment)
        $prescriptionFilePath = $post['prescription_file'] ?? null;
        if (!empty($_FILES['prescription_file']['name']) && !$error) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
            if ($_FILES['prescription_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải file toa thuốc thất bại. Vui lòng thử lại.';
            } else {
                $detectedType = null;
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($_FILES['prescription_file']['tmp_name']);
                }
                if (!$detectedType && isset($_FILES['prescription_file']['type'])) {
                    $detectedType = $_FILES['prescription_file']['type'];
                }
                if ($detectedType && !in_array($detectedType, $allowedTypes)) {
                    $error = 'File toa thuốc chỉ hỗ trợ định dạng JPEG, PNG, WEBP, hoặc PDF.';
                } elseif ($_FILES['prescription_file']['size'] > 5 * 1024 * 1024) {
                    $error = 'File toa thuốc tối đa 5MB.';
                } else {
                    $targetDir = __DIR__ . '/uploads/prescriptions';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['prescription_file']['name'], PATHINFO_EXTENSION);
                    $filename = 'prescription_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'jpg');
                    $targetPath = $targetDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['prescription_file']['tmp_name'], $targetPath)) {
                        if (!empty($post['prescription_file']) && file_exists(__DIR__ . '/' . $post['prescription_file'])) {
                            @unlink(__DIR__ . '/' . $post['prescription_file']);
                        }
                        $prescriptionFilePath = 'uploads/prescriptions/' . $filename;
                    } else {
                        $error = 'Không thể lưu file toa thuốc. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle prescription file removal
        if (isset($_POST['remove_prescription_file']) && $_POST['remove_prescription_file'] === '1') {
            if (!empty($post['prescription_file']) && file_exists(__DIR__ . '/' . $post['prescription_file'])) {
                @unlink(__DIR__ . '/' . $post['prescription_file']);
            }
            $prescriptionFilePath = null;
        }

        // Handle test result file upload (recruitment)
        $testResultFilePath = $post['test_result_file'] ?? null;
        if (!empty($_FILES['test_result_file']['name']) && !$error) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'application/pdf'];
            if ($_FILES['test_result_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải file kết quả xét nghiệm thất bại. Vui lòng thử lại.';
            } else {
                $detectedType = null;
                if (function_exists('mime_content_type')) {
                    $detectedType = mime_content_type($_FILES['test_result_file']['tmp_name']);
                }
                if (!$detectedType && isset($_FILES['test_result_file']['type'])) {
                    $detectedType = $_FILES['test_result_file']['type'];
                }
                if ($detectedType && !in_array($detectedType, $allowedTypes)) {
                    $error = 'File kết quả xét nghiệm chỉ hỗ trợ định dạng JPEG, PNG, WEBP, hoặc PDF.';
                } elseif ($_FILES['test_result_file']['size'] > 5 * 1024 * 1024) {
                    $error = 'File kết quả xét nghiệm tối đa 5MB.';
                } else {
                    $targetDir = __DIR__ . '/uploads/test_results';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }
                    $ext = pathinfo($_FILES['test_result_file']['name'], PATHINFO_EXTENSION);
                    $filename = 'test_result_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext ?: 'jpg');
                    $targetPath = $targetDir . '/' . $filename;
                    if (move_uploaded_file($_FILES['test_result_file']['tmp_name'], $targetPath)) {
                        if (!empty($post['test_result_file']) && file_exists(__DIR__ . '/' . $post['test_result_file'])) {
                            @unlink(__DIR__ . '/' . $post['test_result_file']);
                        }
                        $testResultFilePath = 'uploads/test_results/' . $filename;
                    } else {
                        $error = 'Không thể lưu file kết quả xét nghiệm. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle test result file removal
        if (isset($_POST['remove_test_result_file']) && $_POST['remove_test_result_file'] === '1') {
            if (!empty($post['test_result_file']) && file_exists(__DIR__ . '/' . $post['test_result_file'])) {
                @unlink(__DIR__ . '/' . $post['test_result_file']);
            }
            $testResultFilePath = null;
        }

        if (!$error) {
            if (!$title || !$content || !$contact || !$fullname) {
                $error = 'Vui lòng điền đủ các trường bắt buộc.';
            } else {
                $u = $pdo->prepare('UPDATE posts SET title=?, content=?, area=?, category=?, contact_info=?, recruiter_fullname=?, video_path=?, evidence_image=?, prescription_file=?, test_result_file=?, suggested_price=?, work_time=?, job_type=?, is_urgent=? WHERE id=?');
                $u->execute([$title, $content, $area, $category, $contact, $fullname, $videoPath, $evidenceImagePath, $prescriptionFilePath, $testResultFilePath, $suggestedPrice, $workTime, $jobType, $isUrgent, $id]);
                
                if ($isEmbed) {
                    echo '<!DOCTYPE html><html><head><script>window.top.location.href = "dashboard_patient.php";</script></head><body></body></html>';
                    exit;
                }
                header('Location: view_post.php?id=' . $id);
                exit;
            }
        }

    } else { // application
        $studentFullname = trim($_POST['student_fullname'] ?? '');
        $studentCode = trim($_POST['student_code'] ?? '');
        $studentClass = trim($_POST['student_class'] ?? '');
        $school = trim($_POST['school'] ?? '');
        $suggestedPrice = isset($_POST['suggested_price']) ? (int)preg_replace('/[^0-9]/', '', $_POST['suggested_price']) : 22700;
        $skills = trim($_POST['skills'] ?? '');
        $cardImagePath = $post['card_image'] ?? null;
        $evidenceImagePath = $post['evidence_image'] ?? null;
        $videoPath = $post['video_path'] ?? null;

        // Combine skills into content
        if ($skills) {
            $content .= "\n\nKỹ năng nổi bật: " . $skills;
        }

        // Handle card image upload
        if (!empty($_FILES['card_image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            if ($_FILES['card_image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải ảnh thất bại. Vui lòng thử lại.';
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
                        if (!empty($post['card_image']) && file_exists(__DIR__ . '/' . $post['card_image'])) {
                            @unlink(__DIR__ . '/' . $post['card_image']);
                        }
                        $cardImagePath = 'uploads/student_cards/' . $filename;
                    } else {
                        $error = 'Không thể lưu ảnh thẻ sinh viên. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle card image removal
        if (isset($_POST['remove_card_image']) && $_POST['remove_card_image'] === '1') {
            if (!empty($post['card_image']) && file_exists(__DIR__ . '/' . $post['card_image'])) {
                @unlink(__DIR__ . '/' . $post['card_image']);
            }
            $cardImagePath = null;
        }

        // Handle evidence image upload (application)
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
                        if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image'])) {
                            @unlink(__DIR__ . '/' . $post['evidence_image']);
                        }
                        $evidenceImagePath = 'uploads/evidence_images/' . $filename;
                    } else {
                        $error = 'Không thể lưu ảnh minh chứng. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle evidence image removal (application)
        if (isset($_POST['remove_evidence_image']) && $_POST['remove_evidence_image'] === '1') {
            if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image'])) {
                @unlink(__DIR__ . '/' . $post['evidence_image']);
            }
            $evidenceImagePath = null;
        }

        // Handle video upload (application)
        if (!empty($_FILES['health_video']['name'])) {
            $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
            if ($_FILES['health_video']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Tải video thất bại. Vui lòng thử lại.';
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
                        if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])) {
                            @unlink(__DIR__ . '/' . $post['video_path']);
                        }
                        $videoPath = 'uploads/health_videos/' . $filename;
                    } else {
                        $error = 'Không thể lưu video. Vui lòng thử lại.';
                    }
                }
            }
        }

        // Handle video removal (application)
        if (isset($_POST['remove_video']) && $_POST['remove_video'] === '1') {
            if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])) {
                @unlink(__DIR__ . '/' . $post['video_path']);
            }
            $videoPath = null;
        }

        if (!$error) {
            if (!$title || !$content || !$contact || !$studentFullname || !$studentCode || !$studentClass) {
                $error = 'Vui lòng điền đủ các trường bắt buộc.';
            } else {
                if (!empty($school)) {
                    try {
                        $updateSchool = $pdo->prepare('UPDATE users SET school = ? WHERE id = ?');
                        $updateSchool->execute([$school, $post['user_id']]);
                    } catch (Throwable $e) {}
                }
                $u = $pdo->prepare('UPDATE posts SET title=?, content=?, area=?, category=?, contact_info=?, student_fullname=?, student_code=?, student_class=?, suggested_price=?, card_image=?, evidence_image=?, video_path=? WHERE id=?');
                $u->execute([$title, $content, $area, $category, $contact, $studentFullname, $studentCode, $studentClass, max(0, $suggestedPrice), $cardImagePath, $evidenceImagePath, $videoPath, $id]);
                
                if ($isEmbed) {
                    echo '<!DOCTYPE html><html><head><script>window.top.location.href = "dashboard_student.php";</script></head><body></body></html>';
                    exit;
                }
                header('Location: view_post.php?id=' . $id);
                exit;
            }
        }
    }
}

// Re-fetch post data if needed to show correctly
$stmt = $pdo->prepare('SELECT p.*, u.school FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

// Extract skills from student application content
$rawContent = $post['content'];
$skills = '';
if ($post['type'] === 'application') {
    if (preg_match('/Kỹ năng nổi bật:\s*(.*)(?:\r?\n|$)/iu', $rawContent, $matches)) {
        $skills = trim($matches[1]);
        $rawContent = trim(preg_replace('/Kỹ năng nổi bật:\s*.*(?:\r?\n|$)/iu', '', $rawContent));
    }
}

if ($isEmbed): ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài đăng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; margin: 0; padding: 0; }
        .edit-page-container { padding: 0 !important; }
        .edit-card { max-width: 100% !important; margin: 0 !important; border-radius: 0 !important; box-shadow: none !important; }
    </style>
</head>
<body>
<?php else:
    require_once 'header.php';
endif; ?>

<?php if ($post['type'] === 'recruitment'): ?>
<!-- LAYOUT CHỈNH SỬA TIN TUYỂN DỤNG (BỆNH NHÂN) -->
<style>
.edit-page-container {
    min-height: auto;
    padding: 1rem;
}
.edit-card {
    max-width: 700px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 15px 50px rgba(11, 63, 145, 0.1);
    overflow: hidden;
    animation: cardFadeIn 0.4s ease-out;
}
@keyframes cardFadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
.edit-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    padding: 1.25rem 1.5rem;
    position: relative;
    overflow: hidden;
}
.edit-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    pointer-events: none;
}
.edit-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.edit-icon {
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
.edit-header h1 {
    color: #fff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}
.edit-header p {
    color: rgba(255,255,255,0.9);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.4;
}
.edit-body {
    padding: 1.5rem;
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
    gap: 0.6rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1.25rem;
}
.form-section-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e40af;
    font-size: 0.95rem;
}
.form-floating-custom {
    position: relative;
    margin-bottom: 0.875rem;
}
.form-floating-custom .form-control,
.form-floating-custom .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    font-size: 0.9rem;
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
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
}
.form-floating-custom label .required {
    color: #ef4444;
    font-weight: 700;
}
.form-floating-custom .form-hint {
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 0.3rem;
}
.form-floating-custom textarea.form-control {
    min-height: 120px;
    resize: vertical;
}
.video-upload-area {
    border: 2px dashed #3b82f6;
    border-radius: 14px;
    padding: 1.25rem;
    text-align: center;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}
.video-upload-area:hover {
    border-color: #1e40af;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    transform: translateY(-1px);
}
.video-upload-area.dragover {
    border-color: #1e40af;
    background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
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
    background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
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
    color: #1e40af;
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
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 18px rgba(59, 130, 246, 0.3);
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}
.btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.8rem 1.5rem;
    background: #f1f5f9;
    color: #475569;
    border: 2px solid #e2e8f0;
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
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 14px;
    margin-bottom: 1.5rem;
    animation: alertSlide 0.4s ease-out;
}
@keyframes alertSlide {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}
.alert-custom.error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.alert-custom .alert-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.alert-custom.error .alert-icon {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
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
.current-video-box {
    background: #f0fdf4;
    border: 2px solid #bbf7d0;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.current-video-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.current-video-title {
    font-weight: 700;
    color: #166534;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.btn-delete-current-video {
    background: #fee2e2;
    color: #991b1b;
    border: none;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
}
.btn-delete-current-video:hover {
    background: #fecaca;
}
@media (max-width: 768px) {
    .edit-header { padding: 2rem 1.5rem; }
    .edit-body { padding: 1.5rem; }
    .edit-header-content { flex-direction: column; text-align: center; }
    .edit-icon { width: 70px; height: 70px; font-size: 1.8rem; }
    .edit-header h1 { font-size: 1.5rem; }
    .form-actions { flex-direction: column; }
    .video-benefits { flex-direction: column; align-items: center; }
}
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
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}
.vp-suggestion-chip.template:hover {
    background: #dbeafe;
    border-color: #3b82f6;
}
</style>

<div class="edit-page-container">
    <div class="edit-card">
        <!-- Header -->
        <div class="edit-header">
            <div class="edit-header-content">
                <div class="edit-icon">📝</div>
                <div>
                    <h1>Chỉnh Sửa Tin Tuyển Dụng</h1>
                    <p>Cập nhật nội dung yêu cầu chăm sóc sức khỏe tại nhà</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="edit-body">
            <?php if ($error): ?>
                <div class="alert-custom error">
                    <div class="alert-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="<?php echo $isEmbed ? 'edit_post.php?id='.$id.'&embed=1' : 'edit_post.php?id='.$id; ?>">
                <input type="hidden" name="remove_video" id="removeVideoFlag" value="0">

                <!-- Section: Thông tin người đăng -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="bi bi-person-fill"></i></div>
                        Thông tin người đăng
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Họ và tên người liên hệ <span class="required">*</span></label>
                                <input type="text" name="fullname" class="form-control" placeholder="Nhập họ tên đầy đủ" required value="<?php echo htmlspecialchars($_POST['fullname'] ?? $post['recruiter_fullname'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Thông tin liên hệ <span class="required">*</span></label>
                                <input type="text" name="contact" class="form-control" placeholder="Số điện thoại hoặc email" required value="<?php echo htmlspecialchars($_POST['contact'] ?? $post['contact_info'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Nội dung tin tuyển -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                        Nội dung tin tuyển
                    </div>

                    <div class="form-floating-custom">
                        <label>Tiêu đề bài đăng <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ví dụ: Cần sinh viên Y chăm sóc người cao tuổi tại Quận 1" required value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title'] ?? ''); ?>">
                    </div>

                    <div class="form-floating-custom">
                        <label>Mô tả chi tiết <span class="required">*</span></label>
                        <textarea name="content" class="form-control" placeholder="Mô tả chi tiết..." required><?php echo htmlspecialchars($_POST['content'] ?? $post['content'] ?? ''); ?></textarea>
                        <div class="vp-suggestion-container">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600; margin-right:0.25rem;">Gợi ý mẫu nhanh:</span>
                            <span class="vp-suggestion-chip template" onclick="insertRecruitmentTemplate('elderly')"><i class="bi bi-person-heart"></i> Chăm sóc người cao tuổi</span>
                            <span class="vp-suggestion-chip template" onclick="insertRecruitmentTemplate('post_op')"><i class="bi bi-heart-pulse-fill"></i> Chăm sóc hậu phẫu</span>
                            <span class="vp-suggestion-chip template" onclick="insertRecruitmentTemplate('monitoring')"><i class="bi bi-activity"></i> Theo dõi trực đêm</span>
                        </div>
                        <div class="form-hint">Mô tả càng chi tiết, sinh viên càng dễ chuẩn bị tốt cho công việc.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Chuyên khoa / Loại chăm sóc</label>
                                <input type="text" name="category" class="form-control" placeholder="Ví dụ: Chăm sóc người cao tuổi" value="<?php echo htmlspecialchars($_POST['category'] ?? $post['category'] ?? ''); ?>">
                                <div class="vp-suggestion-container">
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Chăm sóc người cao tuổi')">+ Người cao tuổi</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Phục hồi chức năng')">+ PHCN</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Chăm sóc vết thương')">+ Vết thương</span>
                                    <span class="vp-suggestion-chip" onclick="appendCategory('Theo dõi sức khỏe')">+ Theo dõi</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Địa chỉ / Khu vực</label>
                                <input type="text" name="area" class="form-control" placeholder="Địa chỉ cụ thể hoặc khu vực" value="<?php echo htmlspecialchars($_POST['area'] ?? $post['area'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Mức lương đề xuất (VNĐ/giờ) <span class="required">*</span></label>
                                <input type="number" min="0" step="1000" name="suggested_price" class="form-control" placeholder="Ví dụ: 60000" required value="<?php echo htmlspecialchars($_POST['suggested_price'] ?? $post['suggested_price'] ?? '50000'); ?>">
                                <div class="form-hint">Mức chi phí chi trả cho mỗi giờ hỗ trợ chăm sóc</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Thời gian làm việc cụ thể</label>
                                <input type="text" name="work_time" class="form-control" placeholder="Ví dụ: Thứ 2 - Thứ 6 (18h-21h) hoặc Linh hoạt" value="<?php echo htmlspecialchars($_POST['work_time'] ?? $post['work_time'] ?? ''); ?>">
                                <div class="form-hint">Thông tin lịch biểu làm việc cụ thể để sinh viên sắp xếp</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label>Hình thức làm việc</label>
                                <select name="job_type" class="form-select">
                                    <?php $currentJobType = $_POST['job_type'] ?? $post['job_type'] ?? 'part_time'; ?>
                                    <option value="part_time" <?php echo ($currentJobType === 'part_time') ? 'selected' : ''; ?>>Bán thời gian (Part-time)</option>
                                    <option value="full_time" <?php echo ($currentJobType === 'full_time') ? 'selected' : ''; ?>>Toàn thời gian (Full-time)</option>
                                    <option value="night_shift" <?php echo ($currentJobType === 'night_shift') ? 'selected' : ''; ?>>Trực đêm</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6" style="display: flex; align-items: center; padding-top: 1.8rem;">
                            <div class="form-check form-switch">
                                <?php $isUrgentChecked = isset($_POST['is_urgent']) ? !empty($_POST['is_urgent']) : !empty($post['is_urgent']); ?>
                                <input class="form-check-input" type="checkbox" name="is_urgent" id="isUrgentInput" value="1" <?php echo $isUrgentChecked ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold text-danger" for="isUrgentInput">
                                    🔥 Cần tuyển gấp (Hiển thị nhãn Cần gấp trên bài đăng)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Video tình trạng sức khỏe -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="bi bi-camera-video-fill"></i></div>
                        Video tình trạng sức khỏe (Tùy chọn)
                    </div>

                    <?php if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])): ?>
                    <div class="current-video-box" id="currentVideoBox">
                        <div class="current-video-header">
                            <span class="current-video-title"><i class="bi bi-play-circle-fill"></i> Video hiện tại</span>
                            <button type="button" class="btn-delete-current-video" onclick="removeCurrentVideo()">Xóa video hiện tại</button>
                        </div>
                        <video src="<?php echo htmlspecialchars($post['video_path']); ?>" controls style="max-width:100%; max-height:200px; border-radius:8px;"></video>
                    </div>
                    <?php endif; ?>

                    <div class="video-upload-area" id="videoUploadArea">
                        <input type="file" name="health_video" accept="video/*" id="healthVideoInput">
                        <div class="video-upload-icon"><i class="bi bi-film"></i></div>
                        <div class="video-upload-title">Thay đổi video tình trạng sức khỏe</div>
                        <div class="video-upload-text" id="videoUploadText">Kéo thả hoặc nhấn để chọn video mới</div>
                        <div class="video-upload-hint">MP4, WebM, MOV (tối đa 50MB)</div>
                    </div>

                    <div class="video-preview-container" id="videoPreviewContainer">
                        <div class="video-file-info" id="videoFileInfo">
                            <div class="video-file-icon"><i class="bi bi-play-fill"></i></div>
                            <div class="video-file-details">
                                <div class="video-file-name" id="videoFileName"></div>
                                <div class="video-file-size" id="videoFileSize"></div>
                            </div>
                            <button type="button" class="btn-remove-video" id="removeVideoBtn"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <video class="video-preview" id="videoPreview" controls></video>
                    </div>
                </div>

                <!-- Section: Toa thuốc -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);"><i class="bi bi-file-earmark-text-fill"></i></div>
                        Toa thuốc (Tùy chọn)
                    </div>

                    <input type="hidden" name="remove_prescription_file" id="removePrescriptionFileFlag" value="0">

                    <?php if (!empty($post['prescription_file']) && file_exists(__DIR__ . '/' . $post['prescription_file'])): ?>
                    <div class="current-video-box" id="currentPrescriptionBox" style="background:#f0fdfa; border:2px solid #99f6e4; margin-bottom: 15px; padding: 15px; border-radius: 8px;">
                        <div class="current-video-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="current-video-title" style="color:#0f766e; font-weight: 600;"><i class="bi bi-file-earmark-text"></i> Toa thuốc hiện tại</span>
                            <button type="button" class="btn-delete-current-video" style="background: #fee2e2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 500;" onclick="removeCurrentPrescriptionFile()">Xóa toa thuốc hiện tại</button>
                        </div>
                        <?php 
                        $isPrescriptionPdf = strtolower(pathinfo($post['prescription_file'], PATHINFO_EXTENSION)) === 'pdf';
                        if ($isPrescriptionPdf): ?>
                            <a href="<?php echo htmlspecialchars($post['prescription_file']); ?>" target="_blank" class="btn btn-sm btn-outline-info" style="display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                <i class="bi bi-file-pdf-fill" style="color: #ef4444; font-size: 1.2rem;"></i> Xem file PDF toa thuốc
                            </a>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($post['prescription_file']); ?>" style="max-width:100%; max-height:200px; border-radius:8px; display:block;">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="video-upload-area" id="prescriptionUploadArea" style="border: 2px dashed #0d9488; background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);">
                        <input type="file" name="prescription_file" accept="image/*,application/pdf" id="prescriptionFileInput">
                        <div class="video-upload-icon" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="video-upload-title" style="color: #115e59;">Thay đổi file toa thuốc</div>
                        <div class="video-upload-text" id="prescriptionUploadText" style="color: #0d9488;">Kéo thả hoặc nhấn để chọn file mới</div>
                        <div class="video-upload-hint">JPG, PNG, WEBP hoặc PDF (tối đa 5MB)</div>
                    </div>
                </div>

                <!-- Section: Hồ sơ bệnh án -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="bi bi-file-earmark-medical-fill"></i></div>
                        Hồ sơ bệnh án (Tùy chọn)
                    </div>

                    <input type="hidden" name="remove_evidence_image" id="removeEvidenceImageFlag" value="0">

                    <?php if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image']) && $post['type'] === 'recruitment'): ?>
                    <div class="current-video-box" id="currentEvidenceImageBox" style="background:#f0fdfa; border:2px solid #bbf7d0; margin-bottom: 15px; padding: 15px; border-radius: 8px;">
                        <div class="current-video-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="current-video-title" style="color:#166534; font-weight: 600;"><i class="bi bi-image"></i> Hồ sơ bệnh án hiện tại</span>
                            <button type="button" class="btn-delete-current-video" style="background: #fee2e2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 500;" onclick="removeCurrentEvidenceImage()">Xóa hồ sơ hiện tại</button>
                        </div>
                        <img src="<?php echo htmlspecialchars($post['evidence_image']); ?>" style="max-width:100%; max-height:200px; border-radius:8px; display:block;">
                    </div>
                    <?php endif; ?>

                    <div class="video-upload-area" id="evidenceUploadArea" style="border: 2px dashed #10b981; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);">
                        <input type="file" name="evidence_image" accept="image/*" id="evidenceImageInput">
                        <div class="video-upload-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"><i class="bi bi-file-earmark-medical"></i></div>
                        <div class="video-upload-title" style="color: #065f46;">Thay đổi ảnh minh chứng hoặc hồ sơ bệnh án</div>
                        <div class="video-upload-text" id="evidenceUploadText" style="color: #059669;">Kéo thả hoặc nhấn để chọn ảnh mới</div>
                        <div class="video-upload-hint">JPG, PNG hoặc WEBP (tối đa 5MB)</div>
                    </div>
                </div>

                <!-- Section: Kết quả xét nghiệm -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);"><i class="bi bi-file-earmark-ruled-fill"></i></div>
                        Kết quả xét nghiệm (Tùy chọn)
                    </div>

                    <input type="hidden" name="remove_test_result_file" id="removeTestResultFileFlag" value="0">

                    <?php if (!empty($post['test_result_file']) && file_exists(__DIR__ . '/' . $post['test_result_file'])): ?>
                    <div class="current-video-box" id="currentTestResultBox" style="background:#f0fdfa; border:2px solid #bae6fd; margin-bottom: 15px; padding: 15px; border-radius: 8px;">
                        <div class="current-video-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span class="current-video-title" style="color:#0369a1; font-weight: 600;"><i class="bi bi-file-earmark-ruled"></i> Kết quả xét nghiệm hiện tại</span>
                            <button type="button" class="btn-delete-current-video" style="background: #fee2e2; color: #dc2626; border: none; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 500;" onclick="removeCurrentTestResultFile()">Xóa kết quả hiện tại</button>
                        </div>
                        <?php 
                        $isTestResultPdf = strtolower(pathinfo($post['test_result_file'], PATHINFO_EXTENSION)) === 'pdf';
                        if ($isTestResultPdf): ?>
                            <a href="<?php echo htmlspecialchars($post['test_result_file']); ?>" target="_blank" class="btn btn-sm btn-outline-info" style="display: inline-flex; align-items: center; gap: 5px; text-decoration: none;">
                                <i class="bi bi-file-pdf-fill" style="color: #ef4444; font-size: 1.2rem;"></i> Xem file PDF kết quả xét nghiệm
                            </a>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($post['test_result_file']); ?>" style="max-width:100%; max-height:200px; border-radius:8px; display:block;">
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="video-upload-area" id="testResultUploadArea" style="border: 2px dashed #0284c7; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                        <input type="file" name="test_result_file" accept="image/*,application/pdf" id="testResultFileInput">
                        <div class="video-upload-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);"><i class="bi bi-file-earmark-ruled"></i></div>
                        <div class="video-upload-title" style="color: #075985;">Thay đổi file kết quả xét nghiệm</div>
                        <div class="video-upload-text" id="testResultUploadText" style="color: #0284c7;">Kéo thả hoặc nhấn để chọn file mới</div>
                        <div class="video-upload-hint">JPG, PNG, WEBP hoặc PDF (tối đa 5MB)</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-save-fill"></i> Lưu thay đổi
                    </button>
                    <?php if ($isEmbed): ?>
                    <a class="btn-cancel" href="javascript:void(0)" onclick="window.top.location.href='dashboard_patient.php'">Hủy</a>
                    <?php else: ?>
                    <a class="btn-cancel" href="view_post.php?id=<?php echo $id; ?>">Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function removeCurrentVideo() {
    if (confirm('Bạn có chắc chắn muốn xóa video hiện tại không?')) {
        document.getElementById('removeVideoFlag').value = '1';
        document.getElementById('currentVideoBox').style.display = 'none';
    }
}

function removeCurrentEvidenceImage() {
    if (confirm('Bạn có chắc chắn muốn xóa ảnh minh chứng hiện tại không?')) {
        document.getElementById('removeEvidenceImageFlag').value = '1';
        document.getElementById('currentEvidenceImageBox').style.display = 'none';
    }
}

function removeCurrentPrescriptionFile() {
    if (confirm('Bạn có chắc chắn muốn xóa toa thuốc hiện tại không?')) {
        document.getElementById('removePrescriptionFileFlag').value = '1';
        document.getElementById('currentPrescriptionBox').style.display = 'none';
    }
}

function removeCurrentTestResultFile() {
    if (confirm('Bạn có chắc chắn muốn xóa kết quả xét nghiệm hiện tại không?')) {
        document.getElementById('removeTestResultFileFlag').value = '1';
        document.getElementById('currentTestResultBox').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
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
            document.getElementById('removeVideoFlag').value = '0'; // reset removal
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

    // Setup for Evidence Image
    const imgInput = document.getElementById('evidenceImageInput');
    const imgArea = document.getElementById('evidenceUploadArea');
    const imgText = document.getElementById('evidenceUploadText');

    if (imgInput && imgArea) {
        imgInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                imgText.textContent = this.files[0].name;
                imgArea.style.borderColor = '#10b981';
                imgArea.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                document.getElementById('removeEvidenceImageFlag').value = '0';
            }
        });

        imgArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        imgArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        imgArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/')) {
                    imgInput.files = e.dataTransfer.files;
                    imgText.textContent = file.name;
                    imgArea.style.borderColor = '#10b981';
                    imgArea.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                    document.getElementById('removeEvidenceImageFlag').value = '0';
                }
            }
        });
    }
    // Setup for Prescription File
    const prescriptionInput = document.getElementById('prescriptionFileInput');
    const prescriptionArea = document.getElementById('prescriptionUploadArea');
    const prescriptionText = document.getElementById('prescriptionUploadText');

    if (prescriptionInput && prescriptionArea) {
        prescriptionInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                prescriptionText.textContent = this.files[0].name;
                prescriptionArea.style.borderColor = '#0d9488';
                prescriptionArea.style.background = 'linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%)';
                document.getElementById('removePrescriptionFileFlag').value = '0';
            }
        });

        prescriptionArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        prescriptionArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        prescriptionArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/') || file.type === 'application/pdf') {
                    prescriptionInput.files = e.dataTransfer.files;
                    prescriptionText.textContent = file.name;
                    prescriptionArea.style.borderColor = '#0d9488';
                    prescriptionArea.style.background = 'linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%)';
                    document.getElementById('removePrescriptionFileFlag').value = '0';
                }
            }
        });
    }

    // Setup for Test Result File
    const testResultInput = document.getElementById('testResultFileInput');
    const testResultArea = document.getElementById('testResultUploadArea');
    const testResultText = document.getElementById('testResultUploadText');

    if (testResultInput && testResultArea) {
        testResultInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                testResultText.textContent = this.files[0].name;
                testResultArea.style.borderColor = '#0284c7';
                testResultArea.style.background = 'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)';
                document.getElementById('removeTestResultFileFlag').value = '0';
            }
        });

        testResultArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        testResultArea.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });

        testResultArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/') || file.type === 'application/pdf') {
                    testResultInput.files = e.dataTransfer.files;
                    testResultText.textContent = file.name;
                    testResultArea.style.borderColor = '#0284c7';
                    testResultArea.style.background = 'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)';
                    document.getElementById('removeTestResultFileFlag').value = '0';
                }
            }
        });
    }
});

function insertRecruitmentTemplate(type) {
    const textarea = document.querySelector('textarea[name="content"]');
    if (!textarea) return;
    
    const templates = {
        elderly: "Cần tìm sinh viên Y khoa hỗ trợ chăm sóc người cao tuổi tại nhà.\n\n- Tình trạng sức khỏe: Cụ ông 78 tuổi, đi lại hơi yếu, tỉnh táo nhưng cần người theo dõi huyết áp và hỗ trợ sinh hoạt hàng ngày.\n- Công việc chính: Đo huyết áp, theo dõi dùng thuốc theo toa bác sĩ kê, hỗ trợ dìu đi lại nhẹ nhàng và chuẩn bị bữa ăn nhẹ.\n- Yêu cầu mong muốn: Sinh viên Y/Điều dưỡng cẩn thận, lễ phép, có thái độ ân cần chu đáo.",
        post_op: "Cần tìm sinh viên Y/Điều dưỡng hỗ trợ chăm sóc sau phẫu thuật tại nhà.\n\n- Tình trạng sức khỏe: Bệnh nhân nam 45 tuổi mới phẫu thuật chấn thương chỉnh hình khớp gối, đang tập phục hồi chức năng cơ bản tại nhà.\n- Công việc chính: Hỗ trợ thay băng rửa vết thương sạch hàng ngày, hướng dẫn và giúp bệnh nhân thực hiện các bài tập vật lý trị liệu cơ bản theo đúng chỉ định của bác sĩ điều trị.\n- Yêu cầu mong muốn: Sinh viên có kỹ năng thay băng tốt, nắm vững lý thuyết và thực hành PHCN cơ bản.",
        monitoring: "Cần tìm sinh viên Y trực đêm theo dõi sức khỏe cho bệnh nhân.\n\n- Tình trạng sức khỏe: Bệnh nhân suy tim độ 2, hay mất ngủ về đêm, cần người có chuyên môn y tế ở cạnh đề phòng tình huống khẩn cấp hoặc diễn biến xấu đột ngột.\n- Công việc chính: Theo dõi các dấu hiệu sinh tồn cơ bản (nhịp tim, huyết áp, thở) định kỳ theo giờ đêm, hỗ trợ cho uống thuốc tối và xử trí ban đầu, gọi cấp cứu nếu có biến cố.\n- Yêu cầu mong muốn: Ưu tiên sinh viên Y hệ bác sĩ đa khoa từ năm 3 trở lên, bình tĩnh và xử lý tình huống khẩn cấp tốt."
    };
    
    if (textarea.value.trim() && !confirm("Nội dung hiện tại sẽ bị ghi đè bằng mẫu gợi ý. Bạn có muốn tiếp tục không?")) {
        return;
    }
    textarea.value = templates[type] || '';
    textarea.focus();
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

<?php else: ?>
<!-- LAYOUT CHỈNH SỬA TIN ỨNG TUYỂN (SINH VIÊN) -->
<style>
.edit-page-container {
    min-height: auto;
    padding: 1rem 0.5rem;
}
.edit-card {
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
.edit-header {
    background: linear-gradient(135deg, #0b3f91 0%, #1e40af 50%, #3b82f6 100%);
    padding: 1.25rem 1.5rem;
    position: relative;
    overflow: hidden;
}
.edit-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    pointer-events: none;
}
.edit-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.edit-icon {
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
.edit-header h1 {
    color: #fff;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}
.edit-header p {
    color: rgba(255,255,255,0.85);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.4;
}
.edit-body {
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
.current-image-box {
    background: #eff6ff;
    border: 2px solid #bfdbfe;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1rem;
}
.current-image-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}
.current-image-title {
    font-weight: 700;
    color: #1e40af;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.btn-delete-current-image {
    background: #fee2e2;
    color: #991b1b;
    border: none;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
}
.btn-delete-current-image:hover {
    background: #fecaca;
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
    .edit-header { padding: 1rem; }
    .edit-body { padding: 1rem; }
    .edit-header-content { flex-direction: column; text-align: center; }
    .edit-icon { width: 45px; height: 45px; font-size: 1.3rem; }
    .edit-header h1 { font-size: 1.1rem; }
    .form-actions { flex-direction: column; }
    .video-benefits { flex-direction: column; align-items: center; }
}
</style>

<div class="edit-page-container">
    <div class="edit-card">
        <!-- Header -->
        <div class="edit-header">
            <div class="edit-header-content">
                <div class="edit-icon">🩺</div>
                <div>
                    <h1>Chỉnh Sửa Tin Ứng Tuyển</h1>
                    <p>Cập nhật kinh nghiệm và nguyện vọng thực hành lâm sàng của bạn</p>
                </div>
            </div>
        </div>

        <!-- Body -->
        <div class="edit-body">
            <?php if ($error): ?>
                <div class="alert-custom error">
                    <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" action="<?php echo $isEmbed ? 'edit_post.php?id='.$id.'&embed=1' : 'edit_post.php?id='.$id; ?>">
                <input type="hidden" name="remove_card_image" id="removeCardImageFlag" value="0">
                <input type="hidden" name="remove_video" id="removeVideoFlag" value="0">

                <!-- Section: Thông tin sinh viên -->
                <div class="form-section">
                    <div class="form-section-title">
                        <div class="form-section-icon"><i class="fas fa-user-graduate"></i></div>
                        Thông tin sinh viên
                    </div>
                    
                    <div class="form-floating-custom">
                        <label><i class="fas fa-heading"></i> Tiêu đề bài đăng <span class="required">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ví dụ: Sinh viên Y năm 4 tìm cơ hội thực hành" required value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title'] ?? ''); ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-user"></i> Họ và tên <span class="required">*</span></label>
                                <input type="text" name="student_fullname" class="form-control" placeholder="Nhập họ tên đầy đủ" required value="<?php echo htmlspecialchars($_POST['student_fullname'] ?? $post['student_fullname'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-id-card"></i> Mã số sinh viên <span class="required">*</span></label>
                                <input type="text" name="student_code" class="form-control" placeholder="Mã số sinh viên" required value="<?php echo htmlspecialchars($_POST['student_code'] ?? $post['student_code'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-users"></i> Mã lớp <span class="required">*</span></label>
                                <input type="text" name="student_class" class="form-control" placeholder="Ví dụ: DHY4A" required value="<?php echo htmlspecialchars($_POST['student_class'] ?? $post['student_class'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-school"></i> Trường đại học <span class="required">*</span></label>
                                <input type="text" name="school" class="form-control" placeholder="Ví dụ: Đại học Trà Vinh" required value="<?php echo htmlspecialchars($_POST['school'] ?? $post['school'] ?? $post['school'] ?? ''); ?>">
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
                        <textarea name="content" class="form-control" placeholder="Tóm tắt kỹ năng, kinh nghiệm thực tập..." required><?php echo htmlspecialchars($_POST['content'] ?? $rawContent ?? ''); ?></textarea>
                        <div class="vp-suggestion-container">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600; margin-right:0.25rem;">Gợi ý mẫu nhanh:</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('nursing')"><i class="fas fa-file-prescription"></i> Sinh viên Điều dưỡng</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('general')"><i class="fas fa-user-md"></i> Sinh viên Đa khoa</span>
                            <span class="vp-suggestion-chip template" onclick="insertTemplate('other')"><i class="fas fa-hands-helping"></i> Hỗ trợ khác</span>
                        </div>
                        <div class="form-hint">Mô tả chi tiết giúp bệnh nhân hiểu rõ hơn về năng lực của bạn.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-star"></i> Kỹ năng nổi bật</label>
                                <input type="text" name="skills" class="form-control" placeholder="Ví dụ: Chăm sóc bệnh mãn tính, đo huyết áp" value="<?php echo htmlspecialchars($_POST['skills'] ?? $skills ?? ''); ?>">
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
                                <input type="text" name="category" class="form-control" placeholder="Ví dụ: Nội khoa, Nhi..." value="<?php echo htmlspecialchars($_POST['category'] ?? $post['category'] ?? ''); ?>">
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
                                <input type="text" name="contact" class="form-control" placeholder="Số điện thoại hoặc email" required value="<?php echo htmlspecialchars($_POST['contact'] ?? $post['contact_info'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-map-marker-alt"></i> Khu vực ưu tiên</label>
                                <input type="text" name="area" class="form-control" placeholder="Ví dụ: Quận 1, TP.HCM hoặc link Google Maps" value="<?php echo htmlspecialchars($_POST['area'] ?? $post['area'] ?? ''); ?>">
                                <div class="form-hint">Nhập tên khu vực hoặc dán trực tiếp link chia sẻ Google Maps để tạo nút định vị bản đồ.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-money-bill-wave"></i> Gợi ý giá (VNĐ)</label>
                                <input type="number" min="0" step="100" name="suggested_price" class="form-control" value="<?php echo htmlspecialchars($_POST['suggested_price'] ?? $post['suggested_price'] ?? '22700'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-id-badge"></i> Ảnh thẻ sinh viên</label>
                                
                                <?php if (!empty($post['card_image']) && file_exists(__DIR__ . '/' . $post['card_image'])): ?>
                                <div class="current-image-box" id="currentImageBox">
                                    <div class="current-image-header">
                                        <span class="current-image-title"><i class="bi bi-image"></i> Ảnh thẻ hiện tại</span>
                                        <button type="button" class="btn-delete-current-image" onclick="removeCurrentImage()">Xóa ảnh hiện tại</button>
                                    </div>
                                    <img src="<?php echo htmlspecialchars($post['card_image']); ?>" alt="Ảnh thẻ sinh viên" style="max-width:100%; max-height:150px; border-radius:8px; display:block;">
                                </div>
                                <?php endif; ?>

                                <div class="file-upload-area" id="fileUploadArea">
                                    <input type="file" name="card_image" accept="image/*" id="cardImageInput">
                                    <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <div class="file-upload-text" id="fileUploadText">Kéo thả hoặc nhấn để chọn ảnh mới</div>
                                    <div class="file-upload-hint">JPG, PNG hoặc WEBP (tối đa 3MB)</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating-custom">
                                <label><i class="fas fa-file-invoice"></i> Ảnh chứng chỉ / Bằng cấp bổ sung (Tùy chọn)</label>
                                <input type="hidden" name="remove_evidence_image" id="removeEvidenceImageFlag" value="0">

                                <?php if (!empty($post['evidence_image']) && file_exists(__DIR__ . '/' . $post['evidence_image'])): ?>
                                <div class="current-image-box" id="currentEvidenceImageBox">
                                    <div class="current-image-header">
                                        <span class="current-image-title"><i class="bi bi-image"></i> Ảnh chứng chỉ hiện tại</span>
                                        <button type="button" class="btn-delete-current-image" onclick="removeCurrentEvidenceImage()">Xóa ảnh hiện tại</button>
                                    </div>
                                    <img src="<?php echo htmlspecialchars($post['evidence_image']); ?>" alt="Ảnh chứng chỉ" style="max-width:100%; max-height:150px; border-radius:8px; display:block;">
                                </div>
                                <?php endif; ?>

                                <div class="file-upload-area" id="evidenceUploadArea">
                                    <input type="file" name="evidence_image" accept="image/*" id="evidenceImageInput">
                                    <div class="file-upload-icon"><i class="fas fa-award"></i></div>
                                    <div class="file-upload-text" id="evidenceUploadText">Kéo thả hoặc nhấn để chọn ảnh mới</div>
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

                    <?php if (!empty($post['video_path']) && file_exists(__DIR__ . '/' . $post['video_path'])): ?>
                    <div class="current-image-box" id="currentVideoBox">
                        <div class="current-image-header">
                            <span class="current-image-title"><i class="fas fa-play-circle"></i> Video hiện tại</span>
                            <button type="button" class="btn-delete-current-image" onclick="removeCurrentVideo()">Xóa video hiện tại</button>
                        </div>
                        <video src="<?php echo htmlspecialchars($post['video_path']); ?>" controls style="max-width:100%; max-height:200px; border-radius:8px;"></video>
                    </div>
                    <?php endif; ?>

                    <div class="video-upload-area" id="videoUploadArea">
                        <input type="file" name="health_video" accept="video/*" id="healthVideoInput">
                        <div class="video-upload-icon"><i class="fas fa-film"></i></div>
                        <div class="video-upload-title">Thay đổi video giới thiệu bản thân</div>
                        <div class="video-upload-text" id="videoUploadText">Kéo thả hoặc nhấn để chọn video mới</div>
                        <div class="video-upload-hint">MP4, WebM, MOV (tối đa 50MB)</div>
                    </div>

                    <div class="video-preview-container" id="videoPreviewContainer">
                        <div class="video-file-info" id="videoFileInfo">
                            <div class="video-file-icon"><i class="fas fa-play"></i></div>
                            <div class="video-file-details">
                                <div class="video-file-name" id="videoFileName"></div>
                                <div class="video-file-size" id="videoFileSize"></div>
                            </div>
                            <button type="button" class="btn-remove-video" id="removeVideoBtn"><i class="fas fa-times"></i></button>
                        </div>
                        <video class="video-preview" id="videoPreview" controls></video>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <?php if ($isEmbed): ?>
                    <a class="btn-cancel" href="javascript:void(0)" onclick="window.top.location.href='dashboard_student.php'">Hủy</a>
                    <?php else: ?>
                    <a class="btn-cancel" href="view_post.php?id=<?php echo $id; ?>">Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function removeCurrentImage() {
    if (confirm('Bạn có chắc chắn muốn xóa ảnh thẻ hiện tại không?')) {
        document.getElementById('removeCardImageFlag').value = '1';
        document.getElementById('currentImageBox').style.display = 'none';
    }
}

function removeCurrentEvidenceImage() {
    if (confirm('Bạn có chắc chắn muốn xóa ảnh chứng chỉ hiện tại không?')) {
        document.getElementById('removeEvidenceImageFlag').value = '1';
        document.getElementById('currentEvidenceImageBox').style.display = 'none';
    }
}

function removeCurrentVideo() {
    if (confirm('Bạn có chắc chắn muốn xóa video hiện tại không?')) {
        document.getElementById('removeVideoFlag').value = '1';
        document.getElementById('currentVideoBox').style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupDragDrop('cardImageInput', 'fileUploadArea', 'fileUploadText', 'removeCardImageFlag');
    setupDragDrop('evidenceImageInput', 'evidenceUploadArea', 'evidenceUploadText', 'removeEvidenceImageFlag');

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
            document.getElementById('removeVideoFlag').value = '0';
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

    function setupDragDrop(inputId, areaId, textId, flagId) {
        const input = document.getElementById(inputId);
        const area = document.getElementById(areaId);
        const text = document.getElementById(textId);

        if (input && area) {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    text.textContent = this.files[0].name;
                    area.style.borderColor = '#10b981';
                    area.style.background = 'linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%)';
                    if (flagId) document.getElementById(flagId).value = '0';
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
                    if (flagId) document.getElementById(flagId).value = '0';
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
<?php endif; ?>

<?php if ($isEmbed): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else:
    require_once 'footer.php';
endif; ?>
