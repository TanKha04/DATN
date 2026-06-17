<?php
require_once 'config.php';
require_login();

if (is_admin_user()) {
    header('Location: admin.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'patient';
$isStudent = ($userRole === 'student');
$dashboardLink = $isStudent ? 'dashboard_student.php' : 'dashboard_patient.php';
$likePattern = 'Bạn đã được chọn nhận việc%';

$historyRows = [];
$errorMessage = null;
try {
    if ($isStudent) {
        $sql = "SELECT m.created_at AS accepted_at, p.id AS post_id, p.title, u.name AS counterparty_name, u.email AS counterparty_email, p.area "
            . "FROM messages m "
            . "JOIN posts p ON p.id = m.post_id "
            . "JOIN users u ON u.id = p.user_id "
            . "WHERE m.receiver_id = ? "
            . "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) "
            . "AND m.message LIKE ? "
            . "ORDER BY m.created_at DESC";
    } else {
        if ($userRole !== 'patient') {
            header('Location: index.php');
            exit;
        }
        $sql = "SELECT m.created_at AS accepted_at, p.id AS post_id, p.title, u.name AS counterparty_name, u.email AS counterparty_email, u.phone AS counterparty_phone "
            . "FROM messages m "
            . "JOIN posts p ON p.id = m.post_id "
            . "JOIN users u ON u.id = m.receiver_id "
            . "WHERE m.sender_id = ? "
            . "AND m.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) "
            . "AND m.message LIKE ? "
            . "ORDER BY m.created_at DESC";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $likePattern]);
    $historyRows = $stmt->fetchAll();
} catch (Throwable $e) {
    $errorMessage = 'Không thể tải lịch sử nhận việc. Vui lòng thử lại sau.';
    error_log('assignment_history load failed: ' . $e->getMessage());
}
$totalCount = count($historyRows);

$appointments = [];
try {
    if ($isStudent) {
        $apptSql = "
            SELECT a.*, u.name AS counterparty_name, u.email AS counterparty_email, u.phone AS counterparty_phone 
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            WHERE a.student_id = ?
            ORDER BY a.start_date DESC, a.created_at DESC
        ";
    } else {
        $apptSql = "
            SELECT a.*, u.name AS counterparty_name, u.email AS counterparty_email, u.phone AS counterparty_phone 
            FROM appointments a
            JOIN users u ON a.student_id = u.id
            WHERE a.patient_id = ?
            ORDER BY a.start_date DESC, a.created_at DESC
        ";
    }
    $apptStmt = $pdo->prepare($apptSql);
    $apptStmt->execute([$userId]);
    $appointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Load appointments failed in history: ' . $e->getMessage());
}

// 5. Tính toán thông tin cho Lịch biểu Tháng
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}
$calYear = (int)substr($selectedMonth, 0, 4);
$calMonth = (int)substr($selectedMonth, 5, 2);

$firstDayTime = mktime(0, 0, 0, $calMonth, 1, $calYear);
$daysInMonth = (int)date('t', $firstDayTime);
$firstDayWday = (int)date('w', $firstDayTime); // 0 (Sun) to 6 (Sat)
// Chuyển sang bắt đầu từ Thứ Hai: Thứ Hai là 0, Thứ Ba là 1, ..., Chủ Nhật là 6
$startOffset = ($firstDayWday === 0) ? 6 : $firstDayWday - 1;

// Đường dẫn tháng trước/sau
$prevMonth = date('Y-m', mktime(0, 0, 0, $calMonth - 1, 1, $calYear));
$nextMonth = date('Y-m', mktime(0, 0, 0, $calMonth + 1, 1, $calYear));

// Lấy tất cả lịch hẹn đã xác nhận (confirmed) của sinh viên/bệnh nhân trong tháng đang chọn
$monthStartStr = "$calYear-" . sprintf('%02d', $calMonth) . "-01";
$monthEndStr = "$calYear-" . sprintf('%02d', $calMonth) . "-$daysInMonth";

$confirmedAppts = [];
try {
    if ($isStudent) {
        $calSql = "
            SELECT a.*, u.name AS counterparty_name 
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            WHERE a.student_id = ?
              AND a.status = 'confirmed'
              AND a.start_date <= ?
              AND a.end_date >= ?
        ";
    } else {
        $calSql = "
            SELECT a.*, u.name AS counterparty_name 
            FROM appointments a
            JOIN users u ON a.student_id = u.id
            WHERE a.patient_id = ?
              AND a.status = 'confirmed'
              AND a.start_date <= ?
              AND a.end_date >= ?
        ";
    }
    $calStmt = $pdo->prepare($calSql);
    $calStmt->execute([$userId, $monthEndStr, $monthStartStr]);
    $confirmedAppts = $calStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Load calendar appointments failed: ' . $e->getMessage());
}

$isEmbed = isset($_GET['embed']) && $_GET['embed'] == '1';
if (!$isEmbed) {
    require_once 'header.php';
} else {
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Lịch sử giao việc</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">';
    echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">';
    echo '<link rel="stylesheet" href="assets/css/style.css?v=' . time() . '">';
    echo '<style>body{background:#f1f5f9;margin:0;padding:0;}</style>';
    echo '</head><body>';
}
?>

<div class="assignment-history-page">
    <style>
    /* Premium Calendar CSS */
    .calendar-container {
        background: #fff;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        border: 1px solid rgba(226, 232, 240, 0.8);
        margin-bottom: 2rem;
    }
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .calendar-header h3 {
        font-size: 1.25rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }
    .calendar-nav-btn {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #475569;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .calendar-nav-btn:hover {
        background: #f1f5f9;
        color: #3b82f6;
        border-color: #3b82f6;
    }
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background: #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .calendar-day-header {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-align: center;
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
        border-bottom: 2px solid #cbd5e1;
    }
    .calendar-day-header.weekend {
        color: #ef4444;
    }
    .calendar-cell {
        background: #fff;
        min-height: 110px;
        padding: 0.5rem;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
        transition: background-color 0.2s;
    }
    .calendar-cell:hover {
        background-color: #f8fafc;
    }
    .calendar-cell.other-month {
        background: #f8fafc;
        color: #cbd5e1;
    }
    .calendar-cell.today {
        background-color: #eff6ff;
    }
    .calendar-cell.today .day-number {
        background: #3b82f6;
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
    }
    .day-number {
        align-self: flex-end;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .calendar-cell.other-month .day-number {
        color: #cbd5e1;
    }
    .calendar-events {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        width: 100%;
    }
    .calendar-event-pill {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-left: 3px solid #2563eb;
        color: #1e3a8a;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        transition: transform 0.2s;
    }
    .calendar-event-pill:hover {
        transform: translateX(2px);
        color: #1d4ed8;
    }
    .calendar-cell-empty-text {
        font-size: 0.7rem;
        color: #94a3b8;
        font-style: italic;
        margin-top: 0.25rem;
    }
    .calendar-legend {
        display: flex;
        gap: 1.5rem;
        margin-top: 1.5rem;
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
        justify-content: center;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .legend-dot {
        width: 14px;
        height: 14px;
        border-radius: 4px;
    }
    .legend-dot.busy {
        background: #bfdbfe;
        border: 1px solid #2563eb;
    }
    .legend-dot.free {
        background: #fff;
        border: 1px solid #cbd5e1;
    }
    .legend-dot.today {
        background: #eff6ff;
        border: 1px solid #3b82f6;
    }

    /* Mobile responsive calendar list */
    .day-details-panel {
        margin-top: 1.5rem;
        padding: 1.25rem;
        background: #f8fafc;
        border-radius: 16px;
        border: 1px dashed #cbd5e1;
        display: none;
    }
    .day-details-panel.active {
        display: block;
    }

    @media (max-width: 768px) {
        .calendar-cell {
            min-height: 65px;
        }
        .calendar-event-pill {
            font-size: 0;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            padding: 0;
            margin: 0.1rem auto 0;
            display: inline-block;
            border: none;
        }
        .calendar-events {
            flex-direction: row;
            justify-content: center;
            gap: 0.2rem;
        }
        .calendar-cell-empty-text {
            display: none;
        }
    }
    </style>
    <!-- Header Section -->
    <div class="history-header">
        <div class="history-header-content">
            <div class="history-header-left">
                <span class="history-badge">
                    <i class="bi bi-calendar3"></i>
                    30 ngày gần nhất
                </span>
                <h1 class="history-title">Lịch sử nhận việc</h1>
                <p class="history-subtitle">
                    <span class="history-count"><?php echo $totalCount; ?></span> lượt được ghi nhận
                </p>
            </div>
            <div class="history-header-actions">
                <a href="<?php echo htmlspecialchars($dashboardLink); ?>" class="btn-history-outline">
                    <i class="bi bi-arrow-left"></i>
                    Bảng điều khiển
                </a>
                <?php if ($isStudent): ?>
                    <a href="index.php?type=recruitment#posts" class="btn-history-primary">
                        <i class="bi bi-search"></i>
                        Tìm bài tuyển mới
                    </a>
                <?php else: ?>
                    <a href="index.php?type=application#posts" class="btn-history-primary">
                        <i class="bi bi-person-check"></i>
                        Duyệt hồ sơ sinh viên
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($errorMessage): ?>
        <div class="history-alert history-alert-danger">
            <i class="bi bi-exclamation-triangle"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php else: ?>
        <!-- Navigation Pills Tabs -->
        <ul class="nav nav-pills mb-4 no-print gap-2 justify-content-center" id="historyTabs" role="tablist" style="background: #fff; padding: 0.75rem; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid rgba(226,232,240,0.8);">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4 py-2" id="appointments-tab" data-bs-toggle="pill" data-bs-target="#appointments-pane" type="button" role="tab" aria-controls="appointments-pane" aria-selected="true" style="border-radius: 10px;">
                    <i class="bi bi-calendar-check-fill"></i> Lịch hẹn Y tế (<?php echo count($appointments); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 py-2" id="calendar-tab" data-bs-toggle="pill" data-bs-target="#calendar-pane" type="button" role="tab" aria-controls="calendar-pane" aria-selected="false" style="border-radius: 10px;">
                    <i class="bi bi-calendar3"></i> Lịch biểu Tháng
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4 py-2" id="messages-tab" data-bs-toggle="pill" data-bs-target="#messages-pane" type="button" role="tab" aria-controls="messages-pane" aria-selected="false" style="border-radius: 10px;">
                    <i class="bi bi-chat-left-text-fill"></i> Nhận việc từ Tin nhắn (<?php echo $totalCount; ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="historyTabsContent">
            <!-- TAB 1: LICH HEN Y TE -->
            <div class="tab-pane fade show active" id="appointments-pane" role="tabpanel" aria-labelledby="appointments-tab" tabindex="0">
                <?php if (!$appointments): ?>
                    <div class="history-empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar2-x"></i>
                        </div>
                        <h3 class="empty-state-title">Chưa có lịch hẹn y tế nào</h3>
                        <p class="empty-state-desc">
                            Các lịch hẹn chăm sóc sức khỏe được bệnh nhân đề xuất và xác nhận sẽ được lưu trữ và hiển thị tại đây.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="history-list-container">
                        <div class="history-list">
                            <?php foreach ($appointments as $index => $appt): ?>
                                <div class="history-item" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <!-- Col 1: Date & Time -->
                                    <div class="history-item-date">
                                        <div class="date-icon" style="background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);">
                                            <i class="bi bi-clock-fill" style="color: #0284c7;"></i>
                                        </div>
                                        <div class="date-info">
                                            <span class="date-day" style="font-size: 0.85rem; color: #0369a1;">
                                                <?php echo date('d/m/Y', strtotime($appt['start_date'])) . ' - ' . date('d/m/Y', strtotime($appt['end_date'])); ?>
                                            </span>
                                            <span class="date-time" style="font-weight: 700; color: #1e293b; margin-top: 0.15rem;">
                                                🕒 <?php echo date('H:i', strtotime($appt['start_time'] ?? '08:00:00')) . ' - ' . date('H:i', strtotime($appt['end_time'] ?? '17:00:00')); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Col 2: Partner Info -->
                                    <div class="history-item-person">
                                        <div class="person-avatar">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div class="person-info">
                                            <span class="person-label"><?php echo $isStudent ? 'Bệnh nhân' : 'Sinh viên'; ?></span>
                                            <span class="person-name"><?php echo htmlspecialchars($appt['counterparty_name'] ?? ''); ?></span>
                                            <span class="person-email"><?php echo htmlspecialchars($appt['counterparty_email'] ?? ''); ?></span>
                                            <?php if (!empty($appt['counterparty_phone'])): ?>
                                                <span class="person-phone">
                                                    <i class="bi bi-telephone-fill"></i>
                                                    <?php echo htmlspecialchars($appt['counterparty_phone']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Col 3: Details & Status -->
                                    <div class="history-item-details">
                                        <div class="details-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                                            <i class="bi bi-card-text" style="color: #d97706;"></i>
                                        </div>
                                        <div class="details-info">
                                            <span class="details-label">Thông tin lịch</span>
                                            <span class="details-title" style="font-size: 0.85rem; color: #475569;">
                                                Chu kỳ: <?php echo $appt['billing_cycle'] === 'daily' ? 'Mỗi ngày' : ($appt['billing_cycle'] === 'weekly' ? 'Hàng tuần' : 'Hàng tháng'); ?>
                                            </span>
                                            <!-- Status badge -->
                                            <span class="badge mt-1" style="
                                                display: inline-block;
                                                width: fit-content;
                                                font-size: 0.75rem; 
                                                padding: 0.35rem 0.65rem; 
                                                border-radius: 8px;
                                                font-weight: 700;
                                                <?php 
                                                if ($appt['status'] === 'confirmed') echo 'background-color: #d1fae5; color: #065f46;';
                                                elseif ($appt['status'] === 'pending') echo 'background-color: #fef3c7; color: #92400e;';
                                                elseif ($appt['status'] === 'completed') echo 'background-color: #e0f2fe; color: #0369a1;';
                                                else echo 'background-color: #fee2e2; color: #991b1b;';
                                                ?>
                                            ">
                                                <?php 
                                                if ($appt['status'] === 'pending') echo 'Chờ xác nhận';
                                                elseif ($appt['status'] === 'confirmed') echo 'Đang hoạt động';
                                                elseif ($appt['status'] === 'completed') echo 'Đã hoàn thành';
                                                else echo 'Đã hủy';
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Col 4: Action button -->
                                    <div class="history-item-action">
                                        <a class="btn-view-post" href="appointment_details.php?id=<?php echo (int)$appt['id']; ?>" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff;">
                                            <span>Chi tiết</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2: NHAN VIEC TU TIN NHAN -->
            <div class="tab-pane fade" id="messages-pane" role="tabpanel" aria-labelledby="messages-tab" tabindex="0">
                <?php if (!$historyRows): ?>
                    <!-- Empty State -->
                    <div class="history-empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <h3 class="empty-state-title">Chưa có dữ liệu trong 30 ngày qua</h3>
                        <p class="empty-state-desc">
                            Mỗi khi bạn <?php echo $isStudent ? 'được chọn hỗ trợ' : 'chọn một sinh viên'; ?>, hệ thống sẽ ghi lại tại đây.
                        </p>
                        <?php if ($isStudent): ?>
                            <a href="index.php?type=recruitment#posts" class="btn-history-primary btn-lg">
                                <i class="bi bi-compass"></i>
                                Khám phá bài tuyển
                            </a>
                        <?php else: ?>
                            <a href="index.php?type=application#posts" class="btn-history-primary btn-lg">
                                <i class="bi bi-people"></i>
                                Tìm sinh viên phù hợp
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- History List -->
                    <div class="history-list-container">
                        <div class="history-list">
                            <?php foreach ($historyRows as $index => $row): ?>
                                <div class="history-item" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                    <div class="history-item-date">
                                        <div class="date-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <div class="date-info">
                                            <span class="date-day"><?php echo date('d/m/Y', strtotime($row['accepted_at'])); ?></span>
                                            <span class="date-time"><?php echo date('H:i', strtotime($row['accepted_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="history-item-person">
                                        <div class="person-avatar">
                                            <i class="bi bi-person"></i>
                                        </div>
                                        <div class="person-info">
                                            <span class="person-label"><?php echo $isStudent ? 'Người đăng tuyển' : 'Sinh viên'; ?></span>
                                            <span class="person-name"><?php echo htmlspecialchars($row['counterparty_name'] ?? ''); ?></span>
                                            <span class="person-email"><?php echo htmlspecialchars($row['counterparty_email'] ?? ''); ?></span>
                                            <?php if (!$isStudent && !empty($row['counterparty_phone'])): ?>
                                                <span class="person-phone">
                                                    <i class="bi bi-telephone"></i>
                                                    <?php echo htmlspecialchars($row['counterparty_phone']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="history-item-details">
                                        <div class="details-icon">
                                            <i class="bi bi-file-text"></i>
                                        </div>
                                        <div class="details-info">
                                            <span class="details-label">Chi tiết công việc</span>
                                            <span class="details-title"><?php echo htmlspecialchars($row['title'] ?? ''); ?></span>
                                            <?php if ($isStudent && !empty($row['area'])): ?>
                                                <span class="details-area">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?php echo htmlspecialchars($row['area']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="history-item-action">
                                        <a class="btn-view-post" href="view_post.php?id=<?php echo (int)$row['post_id']; ?>">
                                            <span>Xem bài</span>
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 3: LICH BIEU THANG -->
            <div class="tab-pane fade" id="calendar-pane" role="tabpanel" aria-labelledby="calendar-tab" tabindex="0">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <a href="assignment_history.php?tab=calendar&month=<?php echo $prevMonth; ?><?php echo $isEmbed ? '&embed=1' : ''; ?>" class="calendar-nav-btn">
                            <i class="bi bi-chevron-left"></i> Tháng trước
                        </a>
                        <h3 class="text-center font-weight-bold text-primary">
                            <i class="bi bi-calendar-event"></i> <?php echo "Tháng " . $calMonth . " năm " . $calYear; ?>
                        </h3>
                        <a href="assignment_history.php?tab=calendar&month=<?php echo $nextMonth; ?><?php echo $isEmbed ? '&embed=1' : ''; ?>" class="calendar-nav-btn">
                            Tháng sau <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>

                    <div class="calendar-grid">
                        <!-- Days of week headers -->
                        <div class="calendar-day-header">Thứ 2</div>
                        <div class="calendar-day-header">Thứ 3</div>
                        <div class="calendar-day-header">Thứ 4</div>
                        <div class="calendar-day-header">Thứ 5</div>
                        <div class="calendar-day-header">Thứ 6</div>
                        <div class="calendar-day-header">Thứ 7</div>
                        <div class="calendar-day-header weekend">Chủ nhật</div>

                        <?php
                        // Previous Month Days (Offset)
                        $prevMonthTime = mktime(0, 0, 0, $calMonth - 1, 1, $calYear);
                        $daysInPrevMonth = (int)date('t', $prevMonthTime);
                        for ($i = 0; $i < $startOffset; $i++) {
                            $dayNum = $daysInPrevMonth - $startOffset + $i + 1;
                            echo '<div class="calendar-cell other-month"><span class="day-number">' . $dayNum . '</span></div>';
                        }

                        // Current Month Days
                        for ($dayNum = 1; $dayNum <= $daysInMonth; $dayNum++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $calYear, $calMonth, $dayNum);
                            $isToday = ($dateStr === date('Y-m-d'));
                            
                            $dayAppts = [];
                            foreach ($confirmedAppts as $ca) {
                                if ($dateStr >= $ca['start_date'] && $dateStr <= $ca['end_date']) {
                                    $dayAppts[] = $ca;
                                }
                            }
                            
                            $cellClass = 'calendar-cell' . ($isToday ? ' today' : '');
                            
                            $eventsData = [];
                            foreach ($dayAppts as $da) {
                                $eventsData[] = [
                                    'id' => $da['id'],
                                    'time' => date('H:i', strtotime($da['start_time'] ?? '08:00:00')) . ' - ' . date('H:i', strtotime($da['end_time'] ?? '17:00:00')),
                                    'partner' => htmlspecialchars($da['counterparty_name'] ?? '')
                                ];
                            }
                            $eventsJson = rawurlencode(json_encode($eventsData));
                            
                            echo '<div class="' . $cellClass . '" onclick="showDayDetails(\'' . $dateStr . '\', ' . $dayNum . ', \'' . $eventsJson . '\')" style="cursor: pointer;">';
                            echo '<span class="day-number">' . $dayNum . '</span>';
                            
                            if (!empty($dayAppts)) {
                                echo '<div class="calendar-events">';
                                foreach ($dayAppts as $da) {
                                    $timeText = date('H:i', strtotime($da['start_time'] ?? '08:00:00')) . '-' . date('H:i', strtotime($da['end_time'] ?? '17:00:00'));
                                    echo '<a href="appointment_details.php?id=' . $da['id'] . '" class="calendar-event-pill" title="Lịch hẹn với ' . htmlspecialchars($da['counterparty_name']) . '" onclick="event.stopPropagation();">';
                                    echo $timeText . ' ' . htmlspecialchars($da['counterparty_name']);
                                    echo '</a>';
                                }
                                echo '</div>';
                            } else {
                                echo '<span class="calendar-cell-empty-text">Rảnh (Trống)</span>';
                            }
                            
                            echo '</div>';
                        }

                        // Next Month Days (Offset to complete grid)
                        $totalCells = $startOffset + $daysInMonth;
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                        for ($dayNum = 1; $dayNum <= $remainingCells; $dayNum++) {
                            echo '<div class="calendar-cell other-month"><span class="day-number">' . $dayNum . '</span></div>';
                        }
                        ?>
                    </div>

                    <div class="calendar-legend">
                        <div class="legend-item">
                            <span class="legend-dot busy"></span>
                            <span>Bận (Có lịch hẹn confirmed)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot free"></span>
                            <span>Rảnh (Trống lịch)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot today" style="background-color: #eff6ff; border-color: #3b82f6;"></span>
                            <span>Hôm nay</span>
                        </div>
                    </div>
                </div>

                <!-- Panel hiển thị chi tiết lịch trong ngày khi được click -->
                <div class="day-details-panel" id="day-details-panel">
                    <h5 class="fw-bold mb-3" id="day-details-title"></h5>
                    <div id="day-details-content"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function showDayDetails(dateStr, dayNum, eventsJson) {
    const panel = document.getElementById('day-details-panel');
    const title = document.getElementById('day-details-title');
    const content = document.getElementById('day-details-content');
    
    if (!panel || !title || !content) return;
    
    title.innerHTML = `<i class="bi bi-calendar-event-fill text-primary"></i> Chi tiết lịch hẹn ngày ${dayNum} Tháng <?php echo $calMonth; ?> / <?php echo $calYear; ?>`;
    
    const events = JSON.parse(decodeURIComponent(eventsJson));
    if (events.length === 0) {
        content.innerHTML = `
            <div class="alert alert-success m-0 d-flex align-items-center gap-2" style="border-radius: 12px; border: 1px solid #a7f3d0;">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <div>
                    <strong class="text-success" style="display:block; margin-bottom: 2px;">Trống lịch (Rảnh cả ngày)</strong>
                    <span style="font-size: 0.85rem; color: #065f46;">Sinh viên hoàn toàn rảnh trong ngày này để nhận lịch hẹn hoặc trao đổi thêm.</span>
                </div>
            </div>
        `;
    } else {
        let html = '<div class="d-flex flex-column gap-2">';
        events.forEach(evt => {
            html += `
                <div class="p-3 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center" style="border-radius: 12px !important;">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                            🧑‍⚕️ ${evt.partner}
                        </div>
                        <div class="text-primary fw-bold mt-1" style="font-size: 0.85rem;">
                            🕒 Khung giờ: ${evt.time}
                        </div>
                    </div>
                    <a href="appointment_details.php?id=${evt.id}" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 8px; padding: 0.35rem 0.75rem;">
                        Xem chi tiết <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            `;
        });
        html += '</div>';
        content.innerHTML = html;
    }
    
    panel.classList.add('active');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Giữ active tab Lịch biểu khi bấm chuyển tháng
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab === 'calendar') {
        const triggerEl = document.querySelector('#calendar-tab');
        if (triggerEl) {
            const tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>

<?php if ($isEmbed): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php else: ?>
<?php require_once 'footer.php'; ?>
<?php endif; ?>
