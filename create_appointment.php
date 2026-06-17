<?php
require_once 'config.php';
require_login();

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';

if (($_SESSION['role'] ?? '') !== 'patient') {
    header('Location: index.php');
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$currentUserId = $_SESSION['user_id'];

// Kiểm tra sinh viên có tồn tại không
$stmt = $pdo->prepare('SELECT id, name, school, email FROM users WHERE id = ? AND role = \'student\'');
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['flash_error'] = 'Không tìm thấy thông tin sinh viên y khoa.';
    header('Location: conversations.php');
    exit;
}

// Kiểm tra tình trạng bạn bè (phục vụ hiển thị sidebar giống hệt chat.php)
$areFriends = false;
$friendshipStatus = null;
try {
    $fsStmt = $pdo->prepare('SELECT status FROM friendships WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))');
    $fsStmt->execute([$currentUserId, $studentId, $studentId, $currentUserId]);
    $fsRow = $fsStmt->fetch();
    if ($fsRow) {
        $friendshipStatus = $fsRow['status'];
        $areFriends = ($fsRow['status'] === 'accepted');
    }
} catch (Throwable $e) {
    // Table friendships might not exist
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '08:00';
    $endTime = $_POST['end_time'] ?? '17:00';
    $billingCycle = $_POST['billing_cycle'] ?? 'daily';
    $pricePerDay = (float)($_POST['price_per_day'] ?? 150000.00);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($startDate) || empty($endDate) || empty($startTime) || empty($endTime)) {
        $error = 'Vui lòng chọn đầy đủ ngày bắt đầu, kết thúc và khung giờ.';
    } elseif (strtotime($startDate) > strtotime($endDate)) {
        $error = 'Ngày bắt đầu không được lớn hơn ngày kết thúc.';
    } elseif ($pricePerDay <= 0) {
        $error = 'Mức lương mỗi ngày phải lớn hơn 0.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Tạo lịch hẹn ở trạng thái 'pending'
            $apptSql = 'INSERT INTO appointments (patient_id, student_id, status, notes, billing_cycle, start_date, end_date, start_time, end_time, price_per_day, created_at) 
                        VALUES (?, ?, \'pending\', ?, ?, ?, ?, ?, ?, ?, NOW())';
            $apptStmt = $pdo->prepare($apptSql);
            $apptStmt->execute([$currentUserId, $studentId, $notes, $billingCycle, $startDate, $endDate, $startTime, $endTime, $pricePerDay]);
            $apptId = $pdo->lastInsertId();

            // 2. Tìm hoặc tạo hội thoại chat
            $user1 = min($currentUserId, $studentId);
            $user2 = max($currentUserId, $studentId);
            $convStmt = $pdo->prepare('SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)');
            $convStmt->execute([$user1, $user2, $user2, $user1]);
            $conv = $convStmt->fetch();

            if ($conv) {
                $convId = $conv['id'];
            } else {
                $insConv = $pdo->prepare('INSERT INTO conversations (user1_id, user2_id, last_message_at, created_at) VALUES (?, ?, NOW(), NOW())');
                $insConv->execute([$user1, $user2]);
                $convId = $pdo->lastInsertId();
            }

            // 3. Gửi tin nhắn tự động vào hội thoại báo đề xuất lịch hẹn mới
            $cycleText = $billingCycle === 'daily' ? 'Mỗi ngày' : ($billingCycle === 'weekly' ? 'Hàng tuần' : 'Hàng tháng');
            $msgContent = "📅 **ĐỀ XUẤT LỊCH HẸN CHĂM SÓC MỚI**\n"
                        . "- **Thời hạn**: Từ ngày " . date('d/m/Y', strtotime($startDate)) . " đến " . date('d/m/Y', strtotime($endDate)) . "\n"
                        . "- **Khung giờ**: " . date('H:i', strtotime($startTime)) . " - " . date('H:i', strtotime($endTime)) . "\n"
                        . "- **Chu kỳ thanh toán**: " . $cycleText . "\n"
                        . "- **Lương thỏa thuận**: " . number_format($pricePerDay, 0, ',', '.') . " VNĐ/ngày làm việc.\n"
                        . "- **Ghi chú**: " . ($notes ?: 'Không có') . "\n\n"
                        . "Vui lòng xem chi tiết lịch hẹn tại đây để Xác nhận hoặc Từ chối: [Xem đề xuất lịch hẹn](appointment_details.php?id=" . $apptId . ")";

            $msgStmt = $pdo->prepare('INSERT INTO direct_messages (conversation_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())');
            $msgStmt->execute([$convId, $currentUserId, $studentId, $msgContent]);

            // Cập nhật last_message_at
            $updConv = $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?');
            $updConv->execute([$convId]);

            // 4. Tạo thông báo hệ thống cho sinh viên
            $notifTitle = "Bạn nhận được đề xuất đặt lịch mới!";
            $notifMsg = "Bệnh nhân " . ($_SESSION['name'] ?? 'người dùng') . " đã gửi cho bạn một đề xuất chăm sóc từ " . date('d/m/Y', strtotime($startDate)) . ". Vui lòng phê duyệt.";
            $notifLink = "appointment_details.php?id=" . $apptId;

            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
            $notifStmt->execute([$studentId, $notifTitle, $notifMsg, $notifLink]);

            $pdo->commit();
            $success = 'Gửi đề xuất lịch hẹn thành công! Lịch hẹn đang chờ sinh viên y khoa phê duyệt.';
            
            // Redirect sau 2 giây
            header('Refresh: 2; URL=chat.php?user_id=' . $studentId . ($isEmbed ? '&embed=1' : ''));
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

require_once 'header.php';
if (!$isEmbed) {
    echo '<style>.premium-navbar { display: none !important; } body { padding-top: 0 !important; margin: 0 !important; } .container.py-4 { padding: 0 !important; max-width: 100% !important; margin: 0 !important; }</style>';
}
?>

<style>
/* Layout & Sidebar Styles */
.chat-page { margin: -1.5rem -0.75rem; min-height: calc(100vh - 80px); background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%); }
.chat-container { display: flex; max-width: 1200px; margin: 0 auto; height: calc(100vh - 80px); padding: 1.5rem; gap: 1.5rem; }

/* Sidebar */
.chat-sidebar { width: 280px; flex-shrink: 0; display: flex; flex-direction: column; gap: 1rem; }
.sidebar-header { background: linear-gradient(135deg, #0b3f91 0%, #1e40af 100%); border-radius: 16px; padding: 1.25rem; color: #fff; display: flex; align-items: center; gap: 0.75rem; font-size: 1.1rem; font-weight: 700; box-shadow: 0 4px 15px rgba(11, 63, 145, 0.25); }
.sidebar-nav { background: #fff; border-radius: 16px; padding: 0.75rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.sidebar-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 10px; color: #475569; text-decoration: none; font-weight: 500; transition: all 0.3s; }
.sidebar-link:hover { background: #f1f5f9; color: #3b82f6; transform: translateX(4px); }
.sidebar-link i { font-size: 1.1rem; }

.friend-status-box { background: #fff; border-radius: 12px; padding: 1rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 600; font-size: 0.9rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.friend-status-box.success { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.friend-status-box.warning { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
.friend-status-box.info { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
.friend-status-box a { color: inherit; text-decoration: underline; }

/* Main Content Area */
.chat-main { flex: 1; background: #f8fafc; border-radius: 20px; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.08); overflow: hidden; }

/* Embed style overrides */
<?php if ($isEmbed): ?>
body { background:#f1f5f9; margin:0; padding:0; overflow:hidden; }
.premium-navbar, .navbar, .site-header { display:none!important; }
.chat-page { margin: 0 !important; min-height: 100vh !important; background: #f1f5f9 !important; }
.chat-container { height: 100vh !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 1rem !important; gap: 1rem !important; }
.chat-sidebar { width: 260px !important; }
<?php endif; ?>

/* Responsive */
@media (max-width: 991px) {
    .chat-sidebar { display: none; }
    .chat-container { padding: 0; }
    .chat-main { border-radius: 0; }
}

.proposal-wrapper {
    max-width: 680px;
    margin: 2rem auto;
    padding: 0 1rem;
}
.proposal-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 15px 45px rgba(0,0,0,0.06);
    overflow: hidden;
}
.proposal-card-header {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    padding: 2.25rem 2rem;
    color: #fff;
    position: relative;
}
.proposal-card-header h3 {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.proposal-card-header p {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}
.proposal-card-body {
    padding: 2.5rem 2rem;
}
.form-group-premium {
    margin-bottom: 1.75rem;
}
.form-group-premium label {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.9rem;
}
.form-input-premium {
    border: 2px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    width: 100%;
    transition: all 0.3s;
    background: #f8fafc;
}
.form-input-premium:focus {
    border-color: #3b82f6;
    background: #fff;
    outline: none;
    box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
}
.student-mini-profile {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.student-mini-avatar {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
}
.student-mini-info h5 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #1e3a8a;
}
.student-mini-info p {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0;
}
.btn-submit-proposal {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #fff;
    border: none;
    padding: 0.9rem 1.5rem;
    border-radius: 14px;
    font-weight: 700;
    font-size: 1rem;
    width: 100%;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    cursor: pointer;
}
.btn-submit-proposal:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59,130,246,0.4);
}
</style>

<div class="chat-page">
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <i class="bi bi-chat-heart-fill"></i>
                <span>Tin nhắn</span>
            </div>
            <div class="sidebar-nav">
                <a href="conversations.php<?php echo $isEmbed ? '?embed=1' : ''; ?>" class="sidebar-link">
                    <i class="bi bi-arrow-left"></i> Tất cả cuộc trò chuyện
                </a>
                <a href="conversations.php?view=friends<?php echo $isEmbed ? '&embed=1' : ''; ?>" class="sidebar-link">
                    <i class="bi bi-people"></i> Danh sách bạn bè
                </a>
                <a href="view_profile.php?id=<?php echo $studentId; ?><?php echo $isEmbed ? '&embed=1' : ''; ?>" class="sidebar-link">
                    <i class="bi bi-person"></i> Xem hồ sơ
                </a>
            </div>
            
            <?php if ($areFriends): ?>
            <div class="friend-status-box success">
                <i class="bi bi-person-check-fill"></i>
                <span>Đã là bạn bè</span>
            </div>
            <?php elseif ($friendshipStatus === 'pending'): ?>
            <div class="friend-status-box warning">
                <i class="bi bi-clock"></i>
                <span>Đang chờ kết bạn</span>
            </div>
            <?php else: ?>
            <div class="friend-status-box info">
                <i class="bi bi-person-plus"></i>
                <a href="view_profile.php?id=<?php echo $studentId; ?><?php echo $isEmbed ? '&embed=1' : ''; ?>">Gửi lời mời kết bạn</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Main Content (Form) -->
        <div class="chat-main" style="background: #fff;">
            <!-- Header (Full Width) -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 1.5rem 2rem; color: #fff;">
                <h3 style="margin: 0; font-size: 1.35rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="bi bi-calendar-plus"></i> Đề xuất Lịch hẹn Chăm sóc
                </h3>
                <p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; opacity: 0.9;">Thiết lập điều khoản và gửi đề xuất đàm phán lịch làm việc cho sinh viên y khoa</p>
            </div>

            <!-- Form Body (Scrollable & Full Width) -->
            <div style="flex: 1; overflow-y: auto; padding: 2rem 2.5rem;">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert" style="border-radius: 14px;">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.2rem;"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert" style="border-radius: 14px;">
                        <i class="bi bi-check-circle-fill me-2" style="font-size: 1.2rem;"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php else: ?>
                    <div class="student-mini-profile" style="margin-bottom: 2rem;">
                        <div class="student-mini-avatar">
                            <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                        </div>
                        <div class="student-mini-info">
                            <h5>🧑‍⚕️ Sinh viên: <?php echo htmlspecialchars($student['name']); ?></h5>
                            <p><i class="bi bi-mortarboard"></i> Trường: <?php echo htmlspecialchars($student['school'] ?: 'Trường Đại học Y Dược'); ?></p>
                        </div>
                    </div>

                    <form method="post" action="create_appointment.php?student_id=<?php echo $studentId; ?><?php echo $isEmbed ? '&embed=1' : ''; ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="start_date"><i class="bi bi-calendar-date"></i> Ngày bắt đầu trực</label>
                                    <input type="date" id="start_date" name="start_date" class="form-input-premium" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="end_date"><i class="bi bi-calendar-date"></i> Ngày kết thúc dự kiến</label>
                                    <input type="date" id="end_date" name="end_date" class="form-input-premium" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="start_time"><i class="bi bi-clock"></i> Giờ bắt đầu làm việc</label>
                                    <input type="time" id="start_time" name="start_time" class="form-input-premium" required value="08:00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="end_time"><i class="bi bi-clock"></i> Giờ kết thúc làm việc</label>
                                    <input type="time" id="end_time" name="end_time" class="form-input-premium" required value="17:00">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="billing_cycle"><i class="bi bi-clock-history"></i> Chu kỳ thanh toán</label>
                                    <select id="billing_cycle" name="billing_cycle" class="form-input-premium">
                                        <option value="daily">Theo ngày (Thanh toán sau mỗi ca)</option>
                                        <option value="weekly">Theo tuần (Chốt công hàng tuần)</option>
                                        <option value="monthly">Theo tháng (Chốt công hàng tháng)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-premium">
                                    <label for="price_per_day"><i class="bi bi-cash-stack"></i> Lương thỏa thuận (/ngày làm việc)</label>
                                    <input type="number" id="price_per_day" name="price_per_day" class="form-input-premium" value="150000" step="5000" min="10000" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-premium">
                            <label for="notes"><i class="bi bi-chat-left-text"></i> Ghi chú công việc & Yêu cầu cụ thể</label>
                            <textarea id="notes" name="notes" class="form-input-premium" rows="4" placeholder="VD: Cần hỗ trợ thay băng rửa vết thương lúc 9h sáng, cho uống thuốc và theo dõi huyết áp..."></textarea>
                        </div>

                        <button type="submit" class="btn-submit-proposal">
                            <i class="bi bi-send-fill"></i> Gửi Đề xuất Lịch hẹn
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
