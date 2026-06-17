<?php
/**
 * appointment_details.php - Trang chi tiết lịch hẹn, điểm danh, nhật ký chăm sóc & in ấn báo cáo
 */

require_once 'config.php';
require_login();

function convert_number_to_words($number) {
    $hyphen      = ' ';
    $conjunction = ' ';
    $separator   = ' ';
    $negative    = 'âm ';
    $decimal     = ' phẩy ';
    $dictionary  = array(
        0                   => 'không',
        1                   => 'một',
        2                   => 'hai',
        3                   => 'ba',
        4                   => 'bốn',
        5                   => 'năm',
        6                   => 'sáu',
        7                   => 'bảy',
        8                   => 'tám',
        9                   => 'chín',
        10                  => 'mười',
        11                  => 'mười một',
        12                  => 'mười hai',
        13                  => 'mười ba',
        14                  => 'mười bốn',
        15                  => 'mười lăm',
        16                  => 'mười sáu',
        17                  => 'mười bảy',
        18                  => 'mười tám',
        19                  => 'mười chín',
        20                  => 'hai mươi',
        30                  => 'ba mươi',
        40                  => 'bốn mươi',
        50                  => 'năm mươi',
        60                  => 'sáu mươi',
        70                  => 'bảy mươi',
        80                  => 'tám mươi',
        90                  => 'chín mươi',
        100                 => 'trăm',
        1000                => 'nghìn',
        1000000             => 'triệu',
        1000000000          => 'tỷ'
    );
    
    if (!is_numeric($number)) {
        return false;
    }
    
    if ($number < 0) {
        return $negative . convert_number_to_words(abs($number));
    }
    
    $string = $fraction = null;
    
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    
    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $ones   = $number % 10;
            $string = $dictionary[$tens];
            if ($ones) {
                $string .= $hyphen . ($ones == 5 ? 'lăm' : ($ones == 1 ? 'mốt' : $dictionary[$ones]));
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[(int)$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . ($remainder < 10 ? 'lẻ ' : '') . convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction . 'lẻ ' : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }
    
    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }
    
    return ucfirst(trim($string));
}

$apptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentUserId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

if (!$apptId) {
    header('Location: assignment_history.php');
    exit;
}

// 1. Tự động duyệt các log pending quá 24h trước khi truy vấn dữ liệu
try {
    $autoApproveStmt = $pdo->prepare("
        UPDATE attendance_logs 
        SET status = 'approved' 
        WHERE appointment_id = ? 
          AND status = 'pending' 
          AND created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    $autoApproveStmt->execute([$apptId]);
} catch (Exception $e) {
    error_log('Auto approve check failed: ' . $e->getMessage());
}

// 2. Lấy thông tin lịch hẹn
$apptStmt = $pdo->prepare('
    SELECT a.*, 
           up.name AS patient_name, up.email AS patient_email, up.phone AS patient_phone, up.location AS patient_location, up.address AS patient_address,
           us.name AS student_name, us.email AS student_email, us.phone AS student_phone, us.school AS student_school, us.location AS student_location, us.address AS student_address
    FROM appointments a
    JOIN users up ON a.patient_id = up.id
    JOIN users us ON a.student_id = us.id
    WHERE a.id = ?
');
$apptStmt->execute([$apptId]);
$appt = $apptStmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) {
    $_SESSION['flash_error'] = 'Lịch hẹn không tồn tại.';
    header('Location: assignment_history.php');
    exit;
}

// Kiểm tra quyền truy cập (phải là bệnh nhân hoặc sinh viên của lịch hẹn đó)
if ($appt['patient_id'] != $currentUserId && $appt['student_id'] != $currentUserId) {
    $_SESSION['flash_error'] = 'Bạn không có quyền truy cập thông tin lịch hẹn này.';
    header('Location: assignment_history.php');
    exit;
}

// Xác định đối tác trong ca trực
$isPatientView = ($appt['patient_id'] == $currentUserId);
$partnerName = $isPatientView ? $appt['student_name'] : $appt['patient_name'];
$partnerRole = $isPatientView ? 'Sinh viên Y khoa' : 'Bệnh nhân / Người nhà';
$partnerPhone = $isPatientView ? $appt['student_phone'] : $appt['patient_phone'];
$partnerEmail = $isPatientView ? $appt['student_email'] : $appt['patient_email'];

// 3. Lấy danh sách nhật ký điểm danh
$logsStmt = $pdo->prepare('
    SELECT * FROM attendance_logs 
    WHERE appointment_id = ? 
    ORDER BY log_date DESC, created_at DESC
');
$logsStmt->execute([$apptId]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Tính toán thống kê chấm công
$stats = [
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'day_off' => 0
];
foreach ($logs as $log) {
    if (isset($stats[$log['status']])) {
        $stats[$log['status']]++;
    }
}

$pricePerDay = (float)($appt['price_per_day'] ?? 150000.00);
$totalEarnings = $stats['approved'] * $pricePerDay;

// Kiểm tra xem hôm nay sinh viên đã điểm danh hay báo nghỉ chưa
$todayLog = null;
$todayDate = date('Y-m-d');
foreach ($logs as $log) {
    if ($log['log_date'] === $todayDate) {
        $todayLog = $log;
        break;
    }
}

// Kiểm tra xem hôm nay có nằm trong chu kỳ hiệu lực của hợp đồng không
$isEffective = false;
if ($appt['status'] === 'confirmed' && !empty($appt['start_date']) && !empty($appt['end_date'])) {
    $todayTs = strtotime($todayDate);
    $startTs = strtotime($appt['start_date']);
    $endTs = strtotime($appt['end_date']);
    if ($todayTs >= $startTs && $todayTs <= $endTs) {
        $isEffective = true;
    }
}

require_once 'header.php';
?>

<!-- Custom Styles CSS (Bao gồm kiểu in ấn @media print) -->
<style>
.details-page-wrapper {
    padding: 2rem 0;
    background: #f8fafc;
    min-height: 100vh;
}
.premium-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 2rem;
}
.premium-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.premium-card-header h4 {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.premium-card-body {
    padding: 2rem;
}

/* Info Badges */
.appt-status-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.appt-status-badge.confirmed { background: #d1fae5; color: #065f46; }
.appt-status-badge.pending { background: #fef3c7; color: #92400e; }
.appt-status-badge.completed { background: #e0f2fe; color: #0369a1; }
.appt-status-badge.cancelled { background: #fee2e2; color: #991b1b; }

/* Attendance Timeline */
.timeline-list {
    position: relative;
    padding-left: 2.5rem;
    border-left: 3px solid #e2e8f0;
    margin-left: 1rem;
}
.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.25rem;
    transition: all 0.3s;
}
.timeline-item:hover {
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border-color: #cbd5e1;
}
.timeline-dot {
    position: absolute;
    left: -3.2rem;
    top: 1.25rem;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 4px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.timeline-dot.approved { background: #10b981; }
.timeline-dot.pending { background: #f59e0b; }
.timeline-dot.rejected { background: #ef4444; }
.timeline-dot.day_off { background: #64748b; }

.log-status-badge {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 50px;
}
.log-status-badge.approved { background: #d1fae5; color: #065f46; }
.log-status-badge.pending { background: #fef3c7; color: #92400e; }
.log-status-badge.rejected { background: #fee2e2; color: #991b1b; }
.log-status-badge.day_off { background: #f1f5f9; color: #475569; }

.log-image {
    max-width: 120px;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid #e2e8f0;
    transition: all 0.3s;
}
.log-image:hover {
    transform: scale(1.05);
    border-color: #3b82f6;
}

/* Statistics Widgets */
.stat-box {
    text-align: center;
    background: #fff;
    border-radius: 20px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 20px rgba(0,0,0,0.02);
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 0.25rem;
}
.stat-label {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 600;
}

/* PRINT MEDIA CSS: Chỉ chạy khi bấm in báo cáo đối chứng */
@media print {
    /* Ẩn các thành phần không cần thiết */
    .premium-navbar, .site-header, .btn, .no-print, .chat-header-actions, .modal, .modal-backdrop, footer {
        display: none !important;
    }
    body, .details-page-wrapper {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #000 !important;
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Ẩn hoàn toàn các card giao diện web */
    .premium-card {
        display: none !important;
    }
    
    /* Hiện bản in pháp lý */
    .legal-document-print {
        display: block !important;
        font-family: "Times New Roman", Times, serif !important;
        font-size: 11pt !important;
        color: #000 !important;
        line-height: 1.5 !important;
    }
    
    /* Căn lề và phong cách in ấn */
    @page {
        size: A4;
        margin: 1.5cm 1.5cm 1.5cm 1.5cm;
    }
}

/* Ẩn bản in pháp lý khi hiển thị web bình thường */
.legal-document-print {
    display: none;
}

/* Phong cách Dấu xác thực số */
.digital-seal-container {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 15px;
}
.digital-seal {
    border: 3px double #16a34a;
    border-radius: 8px;
    padding: 8px 15px;
    color: #16a34a;
    font-family: "Courier New", Courier, monospace;
    font-size: 0.75rem;
    font-weight: bold;
    text-align: center;
    background-color: #f0fdf4;
    text-transform: uppercase;
    transform: rotate(-3deg);
    max-width: 260px;
    line-height: 1.3;
    display: inline-block;
}
.digital-seal-title {
    font-size: 0.85rem;
    border-bottom: 1px solid #16a34a;
    padding-bottom: 2px;
    margin-bottom: 4px;
}
.digital-seal-meta {
    font-size: 0.7rem;
    font-weight: normal;
}
</style>

<div class="details-page-wrapper">
    <div class="container">
        
        <!-- ==========================================
        BẢN IN PHÁP LÝ (CHỈ HIỂN THỊ KHI IN)
        ========================================== -->
        <div class="legal-document-print">
            <!-- Quốc hiệu & Tiêu ngữ -->
            <div class="legal-print-header">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="width: 40%; text-align: center; vertical-align: top; font-size: 0.95rem;">
                            <strong>NỀN TẢNG KẾT NỐI Y TẾ</strong><br>
                            <span style="font-size: 0.85rem;">Mã hồ sơ: #<?php echo $apptId; ?></span><br>
                            <span>---------------</span>
                        </td>
                        <td style="width: 60%; text-align: center; vertical-align: top; font-size: 0.95rem;">
                            <strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br>
                            <strong style="font-size: 0.9rem;">Độc lập - Tự do - Hạnh phúc</strong><br>
                            <span style="font-size: 0.85rem; font-style: italic;"><?php echo htmlspecialchars($appt['patient_location'] ?: ($appt['student_location'] ?: 'Việt Nam')); ?>, ngày <?php echo date('d'); ?> tháng <?php echo date('m'); ?> năm <?php echo date('Y'); ?></span><br>
                            <span style="letter-spacing: -1px;">--------------------</span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Tiêu đề biên bản -->
            <div class="legal-print-title" style="text-align: center; margin-top: 2.5rem; margin-bottom: 2rem;">
                <h2 style="font-weight: bold; font-size: 1.4rem; text-transform: uppercase; margin-bottom: 5px;">BIÊN BẢN ĐỐI CHIẾU CÔNG VIỆC VÀ XÁC NHẬN CHẤM CÔNG</h2>
                <span style="font-style: italic; font-size: 0.95rem;">(Số: <?php echo sprintf('%03d', $apptId); ?>/BB-KNYT)</span>
            </div>

            <!-- Căn cứ pháp lý -->
            <div class="legal-print-basis" style="font-style: italic; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; text-align: justify;">
                <p style="margin: 3px 0;">- Căn cứ Bộ luật Dân sự nước Cộng hòa Xã hội Chủ nghĩa Việt Nam hiện hành;</p>
                <p style="margin: 3px 0;">- Căn cứ Quy chế hoạt động và thỏa thuận cung cấp dịch vụ trên Nền tảng "Kết nối Y tế";</p>
                <p style="margin: 3px 0;">- Căn cứ Hợp đồng dịch vụ chăm sóc sức khỏe tại nhà số #<?php echo $apptId; ?> được hai Bên thống nhất giao dịch trên hệ thống;</p>
                <p style="margin: 3px 0;">- Căn cứ vào dữ liệu chấm công điểm danh thực tế của Sinh viên Y khoa và lịch sử phê duyệt của Người nhà Bệnh nhân.</p>
            </div>

            <p style="font-size: 0.95rem; margin-bottom: 1rem; line-height: 1.5;">Hôm nay, đại diện hai Bên đã thực hiện đối chiếu, rà soát thông tin dịch vụ và thống nhất ký biên bản xác nhận công việc với các nội dung chi tiết như sau:</p>

            <!-- Thông tin hai bên -->
            <div class="legal-print-parties" style="font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <strong>BÊN A (NGƯỜI THUÊ / NGƯỜI NHÀ BỆNH NHÂN):</strong>
                    <div style="padding-left: 1.5rem;">
                        - Họ và tên: <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong><br>
                        - Địa chỉ: <?php echo htmlspecialchars($appt['patient_address'] ?: ($appt['patient_location'] ?: 'Chưa cập nhật')); ?><br>
                        - Điện thoại liên hệ: <?php echo htmlspecialchars($appt['patient_phone'] ?: 'Chưa cập nhật'); ?><br>
                        - Địa chỉ Email: <?php echo htmlspecialchars($appt['patient_email'] ?: 'Chưa cập nhật'); ?>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <strong>BÊN B (NGƯỜI ĐƯỢC THUÊ / SINH VIÊN Y KHOA THỰC HIỆN):</strong>
                    <div style="padding-left: 1.5rem;">
                        - Họ và tên: <strong><?php echo htmlspecialchars($appt['student_name']); ?></strong><br>
                        - Cơ sở đào tạo: <?php echo htmlspecialchars($appt['student_school'] ?: 'Đại học Y Dược'); ?><br>
                        - Địa chỉ: <?php echo htmlspecialchars($appt['student_address'] ?: ($appt['student_location'] ?: 'Chưa cập nhật')); ?><br>
                        - Điện thoại liên hệ: <?php echo htmlspecialchars($appt['student_phone'] ?: 'Chưa cập nhật'); ?><br>
                        - Địa chỉ Email: <?php echo htmlspecialchars($appt['student_email'] ?: 'Chưa cập nhật'); ?>
                    </div>
                </div>
            </div>

            <!-- Tổng hợp ngày công -->
            <div class="legal-print-section" style="margin-bottom: 1.5rem;">
                <strong>I. BẢNG TỔNG HỢP CA TRỰC VÀ GIÁ TRỊ QUY TOÁN:</strong>
                <p style="font-size: 0.9rem; font-style: italic; margin-top: 5px; margin-bottom: 5px;">(Chu kỳ tính công: Từ ngày <?php echo date('d/m/Y', strtotime($appt['start_date'])); ?> đến ngày <?php echo date('d/m/Y', strtotime($appt['end_date'])); ?>)</p>
                
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; font-size: 0.9rem;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="border: 1px solid #000; padding: 8px; text-align: left;">Hạng mục chấm công</th>
                            <th style="border: 1px solid #000; padding: 8px; text-align: center; width: 20%;">Số lượng ca trực</th>
                            <th style="border: 1px solid #000; padding: 8px; text-align: right; width: 25%;">Đơn giá ca trực (VNĐ)</th>
                            <th style="border: 1px solid #000; padding: 8px; text-align: right; width: 25%;">Thành tiền chốt (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #000; padding: 8px;">Ca làm việc đã duyệt (Approved)</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['approved']; ?> ngày</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;"><?php echo number_format($pricePerDay, 0, ',', '.'); ?></td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right; font-weight: bold;"><?php echo number_format($totalEarnings, 0, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 8px;">Ca làm việc chờ duyệt (Pending)</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['pending']; ?> ngày</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 8px;">Ca làm việc bị từ chối (Rejected)</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['rejected']; ?> ngày</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #000; padding: 8px;">Ngày báo nghỉ phép (Day Off)</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: center;"><?php echo $stats['day_off']; ?> ngày</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right;">-</td>
                        </tr>
                        <tr style="font-weight: bold; background-color: #fafafa;">
                            <td colspan="3" style="border: 1px solid #000; padding: 8px; text-align: right;">TỔNG KINH PHÍ THANH TOÁN (THỰC TẾ):</td>
                            <td style="border: 1px solid #000; padding: 8px; text-align: right; font-size: 1rem; color: #16a34a;"><?php echo number_format($totalEarnings, 0, ',', '.'); ?> VNĐ</td>
                        </tr>
                    </tbody>
                </table>
                <p style="font-style: italic; font-size: 0.9rem; margin-top: 5px;">Bằng chữ: <strong><?php echo convert_number_to_words((int)$totalEarnings); ?> đồng chẵn.</strong></p>
            </div>

            <!-- Chi tiết nhật ký công việc -->
            <div class="legal-print-section" style="margin-bottom: 1.5rem; page-break-before: auto;">
                <strong>II. BẢNG CHI TIẾT NHẬT KÝ VÀ MINH CHỨNG CÔNG VIỆC:</strong>
                
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.8rem; vertical-align: middle;">
                    <thead>
                        <tr style="background-color: #f2f2f2;">
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; width: 5%;">STT</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; width: 15%;">Ngày trực</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; width: 10%;">Giờ vào</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: left; width: 42%;">Báo cáo nhật ký chăm sóc chi tiết</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; width: 12%;">Xác nhận</th>
                            <th style="border: 1px solid #000; padding: 6px; text-align: center; width: 16%;">Ảnh bằng chứng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="border: 1px solid #000; padding: 20px; text-align: center; color: #666;">Chưa có ngày chấm công nào được ghi nhận.</td>
                            </tr>
                        <?php else: 
                            $stt = 1;
                            foreach (array_reverse($logs) as $log): 
                        ?>
                            <tr style="page-break-inside: avoid;">
                                <td style="border: 1px solid #000; padding: 6px; text-align: center;"><?php echo $stt++; ?></td>
                                <td style="border: 1px solid #000; padding: 6px; text-align: center; font-weight: bold;"><?php echo date('d/m/Y', strtotime($log['log_date'])); ?></td>
                                <td style="border: 1px solid #000; padding: 6px; text-align: center;"><?php echo !empty($log['check_in_time']) ? date('H:i', strtotime($log['check_in_time'])) : '-'; ?></td>
                                <td style="border: 1px solid #000; padding: 6px; text-align: left; white-space: pre-wrap; line-height: 1.4;"><?php echo htmlspecialchars($log['daily_notes'] ?: 'Không có ghi nhận.'); ?></td>
                                <td style="border: 1px solid #000; padding: 6px; text-align: center;">
                                    <?php 
                                        if ($log['status'] === 'approved') echo '<span style="color: #16a34a; font-weight: bold;">Đã duyệt</span>';
                                        elseif ($log['status'] === 'pending') echo '<span style="color: #d97706;">Chờ duyệt</span>';
                                        elseif ($log['status'] === 'rejected') echo '<span style="color: #dc2626; font-weight: bold;">Bị từ chối</span>';
                                        else echo '<span style="color: #4b5563;">Nghỉ phép</span>';
                                    ?>
                                </td>
                                <td style="border: 1px solid #000; padding: 4px; text-align: center;">
                                    <?php if ($log['evidence_image']): ?>
                                        <img src="<?php echo htmlspecialchars(public_url_for($log['evidence_image'])); ?>" style="max-height: 50px; max-width: 80px; border: 1px solid #ccc; border-radius: 3px;">
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 0.7rem;">Không có ảnh</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; 
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cam kết và Hiệu lực -->
            <div class="legal-print-section" style="margin-bottom: 2rem; line-height: 1.6; text-align: justify; font-size: 0.9rem;">
                <strong>III. THỎA THUẬN VÀ PHÁP LÝ:</strong>
                <p style="margin: 5px 0;">1. Hai bên xác nhận các dữ liệu chấm công và báo cáo nhật ký trên là đúng sự thật và hoàn toàn tự nguyện đối chiếu.</p>
                <p style="margin: 5px 0;">2. Biên bản này được thiết lập tự động từ lịch sử giao dịch trực tuyến trên Nền tảng Kết nối Y tế, có giá trị đối soát và làm căn cứ pháp lý để thực hiện nghĩa vụ thanh toán chi phí.</p>
                <p style="margin: 5px 0;">3. Bất kỳ khiếu nại nào về ngày công phải được báo cáo và giải quyết thông qua ban quản trị Nền tảng Kết nối Y tế trước khi quyết toán hợp đồng.</p>
            </div>

            <!-- Chữ ký xác nhận của hai bên -->
            <div class="legal-print-signatures" style="display: flex; justify-content: space-between; margin-top: 2.5rem; page-break-inside: avoid;">
                <div style="text-align: center; width: 45%; font-size: 0.95rem;">
                    <strong>ĐẠI DIỆN BÊN A</strong><br>
                    <span>(Người nhà Bệnh nhân / Bên thuê)</span><br>
                    <span style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký và ghi rõ họ tên)</span>
                    
                    <?php if ($appt['patient_signature']): ?>
                        <div style="height: 5rem; display: flex; align-items: center; justify-content: center; margin-top: 10px; margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars(public_url_for($appt['patient_signature'])); ?>" style="max-height: 4.8rem; max-width: 100%;">
                        </div>
                    <?php else: ?>
                        <div style="height: 5rem;"></div> <!-- Khoảng trống ký tay -->
                    <?php endif; ?>
                    
                    <p style="font-weight: bold; margin: 0;"><?php echo htmlspecialchars($appt['patient_name']); ?></p>
                </div>
                
                <div style="text-align: center; width: 45%; font-size: 0.95rem;">
                    <strong>ĐẠI DIỆN BÊN B</strong><br>
                    <span>(Sinh viên Y khoa / Bên được thuê)</span><br>
                    <span style="font-size: 0.8rem; font-style: italic; color: #666;">(Ký và ghi rõ họ tên)</span>
                    
                    <?php if ($appt['student_signature']): ?>
                        <div style="height: 5rem; display: flex; align-items: center; justify-content: center; margin-top: 10px; margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars(public_url_for($appt['student_signature'])); ?>" style="max-height: 4.8rem; max-width: 100%;">
                        </div>
                    <?php else: ?>
                        <div style="height: 5rem;"></div> <!-- Khoảng trống ký tay -->
                    <?php endif; ?>
                    
                    <p style="font-weight: bold; margin: 0;"><?php echo htmlspecialchars($appt['student_name']); ?></p>
                </div>
            </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────
        THÔNG TIN TÓM TẮT HỢP ĐỒNG / LỊCH HẸN
        ───────────────────────────────────────────────────────────── -->
        <div class="premium-card">
            <div class="premium-card-header">
                <h4>
                    <i class="bi bi-file-earmark-check-fill text-primary"></i> 
                    Chi tiết hợp đồng chăm sóc #<?php echo $apptId; ?>
                </h4>
                <div class="no-print">
                    <span class="appt-status-badge <?php echo $appt['status']; ?>">
                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        Trạng thái: <?php 
                            echo $appt['status'] === 'pending' ? 'Chờ xác nhận' : 
                                ($appt['status'] === 'confirmed' ? 'Đang hoạt động' : 
                                ($appt['status'] === 'completed' ? 'Đã hoàn thành' : 'Đã hủy'));
                        ?>
                    </span>
                </div>
            </div>
            <div class="premium-card-body">
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;">VAI TRÒ CỦA BẠN</span>
                                <strong><?php echo $userRole === 'student' ? 'Sinh viên Y khoa' : 'Bệnh nhân (Người thuê)'; ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;">ĐỐI TÁC LIÊN KẾT</span>
                                <strong>
                                    <a href="view_profile.php?id=<?php echo $isPatientView ? $appt['student_id'] : $appt['patient_id']; ?>" target="_blank" style="text-decoration: none; color: inherit; border-bottom: 1px dashed #3b82f6;">
                                        <?php echo htmlspecialchars($partnerName); ?> 
                                        <i class="bi bi-box-arrow-up-right" style="font-size: 0.75rem; color: #3b82f6;"></i>
                                    </a>
                                </strong>
                                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($partnerPhone ?: 'Chưa cập nhật'); ?></span>
                            </div>
                            <div class="col-md-12"><hr class="my-1"></div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;"><i class="bi bi-calendar-event"></i> THỜI HẠN CHĂM SÓC</span>
                                <strong><?php 
                                    $startDateText = !empty($appt['start_date']) ? date('d/m/Y', strtotime($appt['start_date'])) : 'Chưa xác định';
                                    $endDateText = !empty($appt['end_date']) ? date('d/m/Y', strtotime($appt['end_date'])) : 'Chưa xác định';
                                    echo $startDateText . ' ➔ ' . $endDateText;
                                ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;"><i class="bi bi-clock"></i> KHUNG GIỜ LÀM VIỆC</span>
                                <strong class="text-primary"><?php 
                                    $startTimeText = !empty($appt['start_time']) ? date('H:i', strtotime($appt['start_time'])) : '08:00';
                                    $endTimeText = !empty($appt['end_time']) ? date('H:i', strtotime($appt['end_time'])) : '17:00';
                                    echo $startTimeText . ' - ' . $endTimeText;
                                ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;"><i class="bi bi-clock-history"></i> CHU KỲ THANH TOÁN</span>
                                <strong class="text-primary"><?php 
                                    echo $appt['billing_cycle'] === 'daily' ? 'Thanh toán theo ngày' : 
                                        ($appt['billing_cycle'] === 'weekly' ? 'Quy toán theo tuần' : 'Quy toán theo tháng');
                                ?></strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;"><i class="bi bi-cash-coin"></i> LƯƠNG THỎA THUẬN / NGÀY GHI NHẬN</span>
                                <strong class="text-success"><?php echo number_format($pricePerDay, 0, ',', '.'); ?> VNĐ/ngày</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted d-block" style="font-size: 0.85rem; font-weight: 600;"><i class="bi bi-wallet2"></i> TÍCH LŨY CÔNG THỰC TẾ</span>
                                <span class="badge bg-success p-2" style="font-size: 0.95rem; font-weight: 700; border-radius: 8px;">
                                    <?php echo number_format($totalEarnings, 0, ',', '.'); ?> VNĐ
                                </span>
                            </div>
                        </div>

                        <!-- Ghi chú chung -->
                        <div class="mt-4 p-3 bg-light rounded-3" style="border-left: 4px solid #3b82f6;">
                            <span class="text-muted d-block mb-1" style="font-size: 0.8rem; font-weight: 700;">YÊU CẦU BAN ĐẦU:</span>
                            <span class="text-dark" style="font-size: 0.9rem; white-space: pre-line;"><?php echo htmlspecialchars($appt['notes'] ?: 'Không có ghi chú đặc biệt.'); ?></span>
                        </div>
                    </div>

                    <!-- THỐNG KÊ LOGS CHẤM CÔNG -->
                    <div class="col-md-5">
                        <h5 class="mb-3 text-secondary" style="font-size: 0.9rem; font-weight: 700; text-transform: uppercase;">Thống kê ngày công</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stat-box" style="border-left: 4px solid #10b981;">
                                    <div class="stat-value text-success"><?php echo $stats['approved']; ?></div>
                                    <div class="stat-label">Đã duyệt</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box" style="border-left: 4px solid #f59e0b;">
                                    <div class="stat-value text-warning"><?php echo $stats['pending']; ?></div>
                                    <div class="stat-label">Chờ duyệt</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box" style="border-left: 4px solid #ef4444;">
                                    <div class="stat-value text-danger"><?php echo $stats['rejected']; ?></div>
                                    <div class="stat-label">Từ chối</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box" style="border-left: 4px solid #64748b;">
                                    <div class="stat-value text-secondary"><?php echo $stats['day_off']; ?></div>
                                    <div class="stat-label">Nghỉ phép</div>
                                </div>
                            </div>
                        </div>

                        <!-- Cảnh báo trùng lịch cho sinh viên -->
                        <?php 
                        if ($appt['status'] === 'pending' && $appt['student_id'] == $currentUserId) {
                            $checkOverlap = $pdo->prepare("
                                SELECT id, start_date, end_date, start_time, end_time 
                                FROM appointments 
                                WHERE student_id = ? 
                                  AND status = 'confirmed' 
                                  AND id != ?
                                  AND (start_date <= ? AND end_date >= ?)
                                  AND (COALESCE(start_time, '00:00:00') < ? AND COALESCE(end_time, '23:59:59') > ?)
                                LIMIT 1
                            ");
                            $checkOverlap->execute([
                                $currentUserId,
                                $apptId,
                                $appt['end_date'],
                                $appt['start_date'],
                                $appt['end_time'] ?? '23:59:59',
                                $appt['start_time'] ?? '00:00:00'
                            ]);
                            $overlap = $checkOverlap->fetch(PDO::FETCH_ASSOC);
                            
                            if ($overlap) {
                                $overlapStart = date('d/m/Y', strtotime($overlap['start_date']));
                                $overlapEnd = date('d/m/Y', strtotime($overlap['end_date']));
                                $overlapTimeStart = date('H:i', strtotime($overlap['start_time'] ?? '08:00:00'));
                                $overlapTimeEnd = date('H:i', strtotime($overlap['end_time'] ?? '17:00:00'));
                                ?>
                                <div class="alert alert-warning mt-4 p-3 d-flex align-items-start gap-2" style="border-radius: 12px; border-left: 4px solid #f59e0b; text-align: left;">
                                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.25rem; color: #d97706; flex-shrink: 0; margin-top: 2px;"></i>
                                    <div>
                                        <strong style="color: #92400e; display: block; margin-bottom: 0.25rem;">Cảnh báo trùng lịch làm việc!</strong>
                                        <span style="font-size: 0.85rem; color: #78350f;">
                                            Bạn đã có lịch hẹn chăm sóc khác được xác nhận trùng với thời gian này 
                                            (từ ngày <strong><?php echo $overlapStart; ?></strong> đến ngày <strong><?php echo $overlapEnd; ?></strong>, 
                                            khung giờ <strong><?php echo $overlapTimeStart; ?> - <?php echo $overlapTimeEnd; ?></strong>). 
                                            Vui lòng trao đổi lại với bệnh nhân để thay đổi khung giờ hoặc từ chối đề xuất này.
                                        </span>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>

                        <!-- Các nút hành động chính -->
                        <div class="mt-4 d-flex gap-2 no-print">
                            <?php if ($appt['status'] === 'pending'): ?>
                                <?php if ($appt['student_id'] == $currentUserId): ?>
                                    <button onclick="respondAppointment('accept')" class="btn btn-success w-100 p-2" style="border-radius: 12px; font-weight: 700;">
                                        <i class="bi bi-check-circle-fill"></i> Chấp nhận đề xuất
                                    </button>
                                    <button onclick="respondAppointment('reject')" class="btn btn-danger w-100 p-2" style="border-radius: 12px; font-weight: 700;">
                                        <i class="bi bi-x-circle-fill"></i> Từ chối đề xuất
                                    </button>
                                <?php else: ?>
                                    <div class="alert alert-info w-100 m-0 text-center" style="border-radius: 12px;">
                                        <i class="bi bi-hourglass-split"></i> Đang chờ sinh viên y khoa phản hồi đề xuất lịch hẹn này.
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <button onclick="window.print()" class="btn btn-outline-primary w-100 p-2" style="border-radius: 12px; font-weight: 700;">
                                    <i class="bi bi-printer-fill"></i> In bảng đối chứng
                                </button>
                                <?php if ($appt['status'] === 'confirmed'): ?>
                                    <button type="button" class="btn btn-outline-danger w-100 p-2" style="border-radius: 12px; font-weight: 700;" data-bs-toggle="modal" data-bs-target="#terminateModal">
                                        <i class="bi bi-x-octagon-fill"></i> Dừng chăm sóc sớm
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─────────────────────────────────────────────────────────────
        MÀN HÌNH ĐIỂM DANH / XIN NGHỈ (DÀNH CHO SINH VIÊN Y)
        ───────────────────────────────────────────────────────────── -->
        <?php if ($userRole === 'student' && $appt['status'] === 'confirmed'): ?>
            <div class="premium-card no-print">
                <div class="premium-card-header" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-bottom: 1px solid #bfdbfe;">
                    <h4 class="text-primary">
                        <i class="bi bi-calendar-check-fill"></i> 
                        Chấm công điểm danh & Báo cáo ngày hôm nay
                    </h4>
                </div>
                <div class="premium-card-body">
                    <?php if (!$isEffective): ?>
                        <div class="alert alert-warning m-0 rounded-3">
                            <i class="bi bi-exclamation-triangle-fill"></i> Lịch hẹn này chưa đến ngày bắt đầu (<?php echo !empty($appt['start_date']) ? date('d/m/Y', strtotime($appt['start_date'])) : 'Chưa xác định'; ?>) hoặc đã quá hạn kết thúc.
                        </div>
                    <?php elseif ($todayLog): ?>
                        <div class="alert alert-success m-0 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-check-circle-fill me-2"></i> 
                                Bạn đã gửi báo cáo hôm nay (Trạng thái: 
                                <strong><?php 
                                    echo $todayLog['status'] === 'pending' ? 'Chờ duyệt' : 
                                        ($todayLog['status'] === 'approved' ? 'Đã duyệt' : 
                                        ($todayLog['status'] === 'day_off' ? 'Xin nghỉ phép' : 'Bị từ chối')); 
                                ?></strong>)
                            </div>
                            <a href="#log-<?php echo $todayLog['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3">Xem báo cáo</a>
                        </div>
                    <?php else: ?>
                        <!-- Form Điểm danh thực tế -->
                        <div class="row g-4">
                            <div class="col-md-7 border-end">
                                <h5 class="mb-3 text-primary font-weight-bold" style="font-size: 0.95rem;"><i class="bi bi-camera"></i> Điểm danh làm việc</h5>
                                <form id="checkInForm" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="check_in">
                                    <input type="hidden" name="appointment_id" value="<?php echo $apptId; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label font-weight-bold text-dark" style="font-size: 0.85rem;">Ảnh minh chứng (Bắt buộc)</label>
                                        <input type="file" name="evidence_image" class="form-control" accept="image/*" required style="border-radius: 10px;">
                                        <small class="text-muted">Chụp ảnh bạn làm việc chung với bệnh nhân, hoặc ảnh hỗ trợ điều trị để làm bằng chứng.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label font-weight-bold text-dark" style="font-size: 0.85rem;">Nhật ký chăm sóc ngày hôm nay</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Ghi nhận công việc: Đã cho ăn, cho uống thuốc gì, tình trạng vết thương thế nào..." style="border-radius: 10px;"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 p-2" style="border-radius: 12px; font-weight: 700;">
                                        <i class="bi bi-send-check-fill"></i> Gửi Điểm Danh Chấm Công
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Báo nghỉ phép -->
                            <div class="col-md-5">
                                <h5 class="mb-3 text-secondary font-weight-bold" style="font-size: 0.95rem;"><i class="bi bi-calendar-x"></i> Xin nghỉ phép hôm nay</h5>
                                <p class="text-muted" style="font-size: 0.85rem;">Nếu bạn bận học, có lịch thi đột xuất, hoặc lý do sức khỏe không thể đến làm, hãy xin nghỉ phép chính quy tại đây.</p>
                                <form id="dayOffForm">
                                    <input type="hidden" name="action" value="request_day_off">
                                    <input type="hidden" name="appointment_id" value="<?php echo $apptId; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label font-weight-bold text-dark" style="font-size: 0.85rem;">Lý do xin nghỉ</label>
                                        <textarea name="reason" class="form-control" rows="3" required placeholder="Nêu rõ lý do bận để người nhà bệnh nhân nắm thông tin..." style="border-radius: 10px;"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-outline-secondary w-100 p-2" style="border-radius: 12px; font-weight: 700;">
                                        <i class="bi bi-clock-history"></i> Báo cáo Nghỉ phép hôm nay
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ─────────────────────────────────────────────────────────────
        KÝ NHẬN BIÊN BẢN ĐỐI CHIẾU TRỰC TUYẾN
        ───────────────────────────────────────────────────────────── -->
        <?php if ($appt['status'] === 'confirmed' || $appt['status'] === 'completed'): ?>
            <div class="premium-card no-print">
                <div class="premium-card-header" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid #bbf7d0;">
                    <h4 class="text-success">
                        <i class="bi bi-pen-fill"></i> 
                        Ký nhận Biên bản đối chiếu & Thỏa thuận trực tuyến
                    </h4>
                </div>
                <div class="premium-card-body">
                    <p class="text-muted" style="font-size: 0.9rem;">
                        Hai bên thực hiện ký tên trực tuyến dưới đây. 
                        Chữ ký sẽ được hiển thị chính thức trên <strong>Biên bản đối chiếu công việc</strong> khi in ấn báo cáo chấm công.
                    </p>
                    
                    <div class="row g-4 mt-2">
                        <!-- BÊN A - NGƯỜI NHÀ BỆNH NHÂN -->
                        <div class="col-md-6 border-end" id="patient-sig-col">
                            <div class="p-3 rounded-4 bg-light" style="border: 1px dashed #cbd5e1; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h5 class="font-weight-bold text-dark mb-1" style="font-size: 1rem;"><i class="bi bi-person-fill"></i> BÊN A: NGƯỜI NHÀ BỆNH NHÂN</h5>
                                    <p class="text-muted mb-3" style="font-size: 0.8rem;">Đại diện: <strong><?php echo htmlspecialchars($appt['patient_name']); ?></strong></p>
                                </div>
                                
                                <div class="signature-display-area my-3 text-center" style="min-height: 240px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px;">
                                    <?php if ($appt['patient_signature']): ?>
                                        <!-- Đã có chữ ký -->
                                        <div class="sig-img-wrapper position-relative w-100">
                                            <img src="<?php echo htmlspecialchars(public_url_for($appt['patient_signature'])); ?>" style="max-height: 180px; max-width: 90%; background: #fafafa; border-radius: 8px;" alt="Chữ ký Bên A">
                                            <?php if ($userRole === 'patient'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute bottom-0 end-0 m-2" onclick="showSignCanvas('patient')">
                                                    <i class="bi bi-arrow-repeat"></i> Ký lại
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Khung vẽ chữ ký ẩn khi bấm Ký lại -->
                                        <?php if ($userRole === 'patient'): ?>
                                            <div class="sig-canvas-wrapper w-100 d-none">
                                                <canvas id="signature-canvas-patient" width="350" height="200" style="touch-action: none; cursor: crosshair; background: #fafafa; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 100%; height: auto;"></canvas>
                                                <div class="mt-2 d-flex gap-2 justify-content-center">
                                                    <button type="button" id="clear-btn-patient" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i> Xóa</button>
                                                    <button type="button" id="save-btn-patient" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                                                    <button type="button" class="btn btn-sm btn-light" onclick="hideSignCanvas('patient')">Hủy</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Chưa có chữ ký -->
                                        <?php if ($userRole === 'patient'): ?>
                                            <div class="sig-canvas-wrapper w-100">
                                                <canvas id="signature-canvas-patient" width="350" height="200" style="touch-action: none; cursor: crosshair; background: #fafafa; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 100%; height: auto;"></canvas>
                                                <div class="mt-2 d-flex gap-2 justify-content-center">
                                                    <button type="button" id="clear-btn-patient" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i> Xóa</button>
                                                    <button type="button" id="save-btn-patient" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Lưu chữ ký</button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted py-4">
                                                <i class="bi bi-hourglass-split display-6 d-block mb-2 text-warning"></i>
                                                <span>Bên A chưa thực hiện ký tên</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- BÊN B - SINH VIÊN Y KHOA -->
                        <div class="col-md-6" id="student-sig-col">
                            <div class="p-3 rounded-4 bg-light" style="border: 1px dashed #cbd5e1; height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h5 class="font-weight-bold text-dark mb-1" style="font-size: 1rem;"><i class="bi bi-shield-fill-check"></i> BÊN B: SINH VIÊN Y KHOA</h5>
                                    <p class="text-muted mb-3" style="font-size: 0.8rem;">Đại diện: <strong><?php echo htmlspecialchars($appt['student_name']); ?></strong></p>
                                </div>
                                
                                <div class="signature-display-area my-3 text-center" style="min-height: 240px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px;">
                                    <?php if ($appt['student_signature']): ?>
                                        <!-- Đã có chữ ký -->
                                        <div class="sig-img-wrapper position-relative w-100">
                                            <img src="<?php echo htmlspecialchars(public_url_for($appt['student_signature'])); ?>" style="max-height: 180px; max-width: 90%; background: #fafafa; border-radius: 8px;" alt="Chữ ký Bên B">
                                            <?php if ($userRole === 'student'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute bottom-0 end-0 m-2" onclick="showSignCanvas('student')">
                                                    <i class="bi bi-arrow-repeat"></i> Ký lại
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Khung vẽ chữ ký ẩn khi bấm Ký lại -->
                                        <?php if ($userRole === 'student'): ?>
                                            <div class="sig-canvas-wrapper w-100 d-none">
                                                <canvas id="signature-canvas-student" width="350" height="200" style="touch-action: none; cursor: crosshair; background: #fafafa; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 100%; height: auto;"></canvas>
                                                <div class="mt-2 d-flex gap-2 justify-content-center">
                                                    <button type="button" id="clear-btn-student" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i> Xóa</button>
                                                    <button type="button" id="save-btn-student" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Lưu</button>
                                                    <button type="button" class="btn btn-sm btn-light" onclick="hideSignCanvas('student')">Hủy</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Chưa có chữ ký -->
                                        <?php if ($userRole === 'student'): ?>
                                            <div class="sig-canvas-wrapper w-100">
                                                <canvas id="signature-canvas-student" width="350" height="200" style="touch-action: none; cursor: crosshair; background: #fafafa; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 100%; height: auto;"></canvas>
                                                <div class="mt-2 d-flex gap-2 justify-content-center">
                                                    <button type="button" id="clear-btn-student" class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash"></i> Xóa</button>
                                                    <button type="button" id="save-btn-student" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Lưu chữ ký</button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted py-4">
                                                <i class="bi bi-hourglass-split display-6 d-block mb-2 text-warning"></i>
                                                <span>Bên B chưa thực hiện ký tên</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ─────────────────────────────────────────────────────────────
        TIMELINE / NHẬT KÝ CHI TIẾT CÁC NGÀY
        ───────────────────────────────────────────────────────────── -->
        <div class="premium-card">
            <div class="premium-card-header">
                <h4>
                    <i class="bi bi-journal-medical text-primary"></i> 
                    Nhật ký công việc & Điểm danh qua các ngày
                </h4>
            </div>
            <div class="premium-card-body">
                <?php if (empty($logs)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x-fill display-3 mb-3 text-slate-300"></i>
                        <h5>Chưa có ngày chấm công nào</h5>
                        <p>Các ngày điểm danh chấm công của sinh viên sẽ hiển thị tại đây.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline-list">
                        <?php foreach ($logs as $log): ?>
                            <div class="timeline-item" id="log-<?php echo $log['id']; ?>">
                                <div class="timeline-dot <?php echo $log['status']; ?>">
                                    <?php if ($log['status'] === 'approved'): ?>
                                        <i class="bi bi-check-lg text-white" style="font-size: 0.75rem;"></i>
                                    <?php elseif ($log['status'] === 'pending'): ?>
                                        <i class="bi bi-hourglass text-white" style="font-size: 0.75rem;"></i>
                                    <?php elseif ($log['status'] === 'rejected'): ?>
                                        <i class="bi bi-x-lg text-white" style="font-size: 0.75rem;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-calendar-x text-white" style="font-size: 0.75rem;"></i>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <strong style="font-size: 1.1rem; color: #1e293b;">
                                            Ngày <?php echo date('d/m/Y', strtotime($log['log_date'])); ?>
                                        </strong>
                                        <?php if (!empty($log['check_in_time'])): ?>
                                             <span class="text-muted ms-2" style="font-size: 0.8rem;">
                                                 Check-in lúc: <?php echo date('H:i', strtotime($log['check_in_time'])); ?>
                                             </span>
                                         <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="log-status-badge <?php echo $log['status']; ?>">
                                            <?php 
                                                echo $log['status'] === 'approved' ? '✓ Đã duyệt' : 
                                                    ($log['status'] === 'pending' ? '⏳ Chờ duyệt' : 
                                                    ($log['status'] === 'rejected' ? '✗ Từ chối' : '📅 Nghỉ phép'));
                                            ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-sm-8">
                                        <p class="mb-2 text-secondary" style="font-size: 0.95rem; white-space: pre-wrap;"><?php echo htmlspecialchars($log['daily_notes'] ?: 'Không có ghi chú công việc.'); ?></p>
                                        
                                        <!-- Lý do từ chối -->
                                        <?php if ($log['status'] === 'rejected' && !empty($log['rejection_reason'])): ?>
                                            <div class="alert alert-danger py-2 px-3 mt-2 m-0" style="border-radius: 10px; font-size: 0.85rem;">
                                                <strong>Lý do từ chối:</strong> <?php echo htmlspecialchars($log['rejection_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-sm-4 text-sm-end">
                                        <?php if ($log['evidence_image']): ?>
                                            <!-- Minh chứng ảnh -->
                                            <img src="<?php echo htmlspecialchars(public_url_for($log['evidence_image'])); ?>" class="log-image img-thumbnail" alt="Bằng chứng làm việc" onclick="viewFullImage('<?php echo htmlspecialchars(public_url_for($log['evidence_image'])); ?>')">
                                            <div class="text-muted no-print" style="font-size: 0.7rem; margin-top: 0.25rem;">(Bấm để phóng to)</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- BẢNG DUYỆT CHẤM CÔNG DÀNH CHO BỆNH NHÂN -->
                                <?php if ($isPatientView && $log['status'] === 'pending'): ?>
                                    <div class="mt-3 p-3 bg-light rounded-3 d-flex gap-2 justify-content-end no-print">
                                        <button class="btn btn-success btn-sm px-3 rounded-pill" onclick="verifyLog(<?php echo $log['id']; ?>, 'approve')">
                                            <i class="bi bi-check-lg"></i> Duyệt ca trực
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm px-3 rounded-pill" onclick="openRejectModal(<?php echo $log['id']; ?>)">
                                            <i class="bi bi-x-lg"></i> Từ chối duyệt
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>



    </div>
</div>

<!-- ─────────────────────────────────────────────────────────────
MODALS
───────────────────────────────────────────────────────────── -->

<!-- Modal phóng to ảnh -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true" class="no-print">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body p-0 text-center position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="modalFullImage" src="" style="max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            </div>
        </div>
    </div>
</div>

<!-- Modal Bệnh nhân Từ chối và nhập lý do -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true" class="no-print">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-light" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title font-weight-bold text-danger" id="rejectModalLabel"><i class="bi bi-exclamation-octagon-fill"></i> Từ chối duyệt điểm danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="rejectForm">
                    <input type="hidden" id="rejectLogIdInput" name="log_id">
                    <input type="hidden" name="action" value="verify_log">
                    <input type="hidden" name="decision" value="reject">
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label font-weight-bold text-secondary">Lý do từ chối duyệt</label>
                        <textarea class="form-control border-2" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Nhập rõ lý do từ chối (ví dụ: Sinh viên đi trễ, không tải đúng ảnh, hoặc chưa làm việc hôm nay...)" required style="border-radius: 12px;"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Hủy</button>
                        <button type="submit" class="btn btn-danger" style="border-radius: 10px; font-weight: 700;">Gửi từ chối</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dừng hợp đồng sớm -->
<div class="modal fade" id="terminateModal" tabindex="-1" aria-labelledby="terminateModalLabel" aria-hidden="true" class="no-print">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-light" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h5 class="modal-title font-weight-bold text-danger" id="terminateModalLabel"><i class="bi bi-x-octagon-fill"></i> Yêu cầu dừng chăm sóc sớm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted" style="font-size: 0.9rem;">Hệ thống sẽ lập tức khóa cổng điểm danh, hoàn tất lịch hẹn và **chốt công thực tế** tính đến thời điểm hiện tại. Số tiền thừa sẽ hoàn trả lại cho bệnh nhân.</p>
                <form id="terminateForm">
                    <input type="hidden" name="action" value="terminate_early">
                    <input type="hidden" name="appointment_id" value="<?php echo $apptId; ?>">
                    
                    <div class="mb-3">
                        <label for="terminate_reason" class="form-label font-weight-bold text-secondary">Lý do dừng sớm</label>
                        <textarea class="form-control border-2" id="terminate_reason" name="reason" rows="3" placeholder="Nhập lý do kết thúc công việc chăm sóc sớm nửa chừng..." required style="border-radius: 12px;"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius: 10px; font-weight: 600;">Hủy</button>
                        <button type="submit" class="btn btn-danger" style="border-radius: 10px; font-weight: 700;">Xác nhận dừng hợp đồng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ─────────────────────────────────────────────────────────────
AJAX SCRIPTING
───────────────────────────────────────────────────────────── -->
<script>
// 1. Phóng to ảnh minh chứng
function viewFullImage(src) {
    document.getElementById('modalFullImage').src = src;
    var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
    myModal.show();
}

// 2. Mở modal từ chối duyệt
function openRejectModal(logId) {
    document.getElementById('rejectLogIdInput').value = logId;
    var myModal = new bootstrap.Modal(document.getElementById('rejectModal'));
    myModal.show();
}

// 3. Phê duyệt ca chấm công (Approve)
function verifyLog(logId, decision) {
    if (confirm('Bạn xác nhận duyệt ca trực này cho sinh viên?')) {
        const formData = new FormData();
        formData.append('action', 'verify_log');
        formData.append('log_id', logId);
        formData.append('decision', decision);

        fetch('attendance_action.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối hệ thống.');
        });
    }
}

// 3.1. Phản hồi đề xuất lịch hẹn (Chấp nhận / Từ chối)
function respondAppointment(action) {
    const confirmMsg = action === 'accept' 
        ? 'Bạn có chắc chắn muốn CHẤP NHẬN đề xuất lịch hẹn chăm sóc này?' 
        : 'Bạn có chắc chắn muốn TỪ CHỐI đề xuất lịch hẹn chăm sóc này?';
        
    if (!confirm(confirmMsg)) return;
    
    const formData = new FormData();
    formData.append('appointment_id', <?php echo $apptId; ?>);
    formData.append('response', action);
    
    fetch('respond_appointment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối hệ thống.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // 4. Submit Form Điểm danh (Check-in)
    const checkInForm = document.getElementById('checkInForm');
    if (checkInForm) {
        checkInForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('attendance_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi kết nối hệ thống.');
            });
        });
    }

    // 5. Submit Form Báo Nghỉ phép
    const dayOffForm = document.getElementById('dayOffForm');
    if (dayOffForm) {
        dayOffForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('attendance_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi kết nối hệ thống.');
            });
        });
    }

    // 6. Submit Form từ chối duyệt điểm danh
    const rejectForm = document.getElementById('rejectForm');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('attendance_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi kết nối hệ thống.');
            });
        });
    }

    // 7. Submit Form dừng lịch hẹn sớm (Early Termination)
    const terminateForm = document.getElementById('terminateForm');
    if (terminateForm) {
        terminateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('LƯU Ý: Hành động này sẽ khóa toàn bộ hợp đồng, dừng ca trực và chốt lương thực tế. Bạn có chắc chắn muốn tiếp tục?')) {
                const formData = new FormData(this);

                fetch('attendance_action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Lỗi kết nối hệ thống.');
                });
            }
        });
    }
    // 8. Khởi tạo các khung vẽ chữ ký nếu chưa có chữ ký
    <?php if (!$appt['patient_signature'] && $userRole === 'patient'): ?>
    initSignaturePad('signature-canvas-patient', 'clear-btn-patient', 'save-btn-patient', 'patient', <?php echo $apptId; ?>);
    <?php endif; ?>

    <?php if (!$appt['student_signature'] && $userRole === 'student'): ?>
    initSignaturePad('signature-canvas-student', 'clear-btn-student', 'save-btn-student', 'student', <?php echo $apptId; ?>);
    <?php endif; ?>
});

// Khởi tạo pad chữ ký vẽ tay
function initSignaturePad(canvasId, clearBtnId, saveBtnId, role, apptId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    // Thiết lập phong cách vẽ (nét vẽ màu mực xanh đậm truyền thống)
    ctx.strokeStyle = '#1e3a8a';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function getMousePos(canvasDom, touchOrMouseEvent) {
        const rect = canvasDom.getBoundingClientRect();
        const clientX = touchOrMouseEvent.touches ? touchOrMouseEvent.touches[0].clientX : touchOrMouseEvent.clientX;
        const clientY = touchOrMouseEvent.touches ? touchOrMouseEvent.touches[0].clientY : touchOrMouseEvent.clientY;
        
        // Tính toán tỉ lệ co giãn thực tế của canvas so với màn hình để không bị lệch nét vẽ
        return {
            x: (clientX - rect.left) * (canvasDom.width / rect.width),
            y: (clientY - rect.top) * (canvasDom.height / rect.height)
        };
    }

    function startDrawing(e) {
        isDrawing = true;
        const pos = getMousePos(canvas, e);
        lastX = pos.x;
        lastY = pos.y;
        e.preventDefault();
    }

    function draw(e) {
        if (!isDrawing) return;
        const pos = getMousePos(canvas, e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastX = pos.x;
        lastY = pos.y;
        e.preventDefault();
    }

    function stopDrawing(e) {
        isDrawing = false;
        e.preventDefault();
    }

    // Sự kiện vẽ bằng chuột
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);

    // Sự kiện vẽ bằng cảm ứng (Điện thoại / Tablet)
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing, { passive: false });

    // Nút Xóa
    const clearBtn = document.getElementById(clearBtnId);
    if (clearBtn) {
        clearBtn.onclick = function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        };
    }

    // Nút Lưu chữ ký
    const saveBtn = document.getElementById(saveBtnId);
    if (saveBtn) {
        saveBtn.onclick = function() {
            const blank = document.createElement('canvas');
            blank.width = canvas.width;
            blank.height = canvas.height;
            if (canvas.toDataURL() === blank.toDataURL()) {
                alert('Vui lòng vẽ chữ ký của bạn trước khi lưu.');
                return;
            }

            const dataURL = canvas.toDataURL('image/png');
            const formData = new FormData();
            formData.append('action', 'save_signature');
            formData.append('appointment_id', apptId);
            formData.append('role', role);
            formData.append('signature_data', dataURL);

            fetch('attendance_action.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Lỗi kết nối máy chủ.');
            });
        };
    }
}

function showSignCanvas(role) {
    const parentCol = document.getElementById(`${role}-sig-col`);
    if (!parentCol) return;
    const imgWrapper = parentCol.querySelector('.sig-img-wrapper');
    if (imgWrapper) imgWrapper.classList.add('d-none');
    
    const canvasWrapper = parentCol.querySelector('.sig-canvas-wrapper');
    if (canvasWrapper) {
        canvasWrapper.classList.remove('d-none');
        // Khởi tạo pad vẽ
        initSignaturePad(`signature-canvas-${role}`, `clear-btn-${role}`, `save-btn-${role}`, role, <?php echo $apptId; ?>);
    }
}

function hideSignCanvas(role) {
    const parentCol = document.getElementById(`${role}-sig-col`);
    if (!parentCol) return;
    const imgWrapper = parentCol.querySelector('.sig-img-wrapper');
    if (imgWrapper) imgWrapper.classList.remove('d-none');
    
    const canvasWrapper = parentCol.querySelector('.sig-canvas-wrapper');
    if (canvasWrapper) canvasWrapper.classList.add('d-none');
}
</script>

<?php require_once 'footer.php'; ?>
