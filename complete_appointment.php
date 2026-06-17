<?php
/**
 * complete_appointment.php - Báo cáo hoàn thành ca trực
 * Sinh viên báo cáo đã hoàn thành ca trực của lịch hẹn chăm sóc
 */

require_once 'config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$appointment_id = (int)($_POST['appointment_id'] ?? 0);

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin lịch hẹn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Lấy thông tin lịch hẹn
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$appointment_id]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Chỉ có sinh viên được gán mới có quyền báo cáo hoàn thành
    if ($appt['student_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền báo cáo hoàn thành cho lịch hẹn này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Chỉ cho phép hoàn thành khi lịch hẹn ở trạng thái confirmed
    if ($appt['status'] !== 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Lịch hẹn phải ở trạng thái đã xác nhận mới có thể báo cáo hoàn thành.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Cập nhật trạng thái lịch hẹn sang completed
    $updateStmt = $pdo->prepare("UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$appointment_id]);

    // Lấy thông tin sinh viên phục vụ thông báo
    $studentStmt = $pdo->prepare('SELECT name FROM users WHERE id = ?');
    $studentStmt->execute([$_SESSION['user_id']]);
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);

    // Tạo thông báo đẩy cho bệnh nhân
    $notifTitle = "Sinh viên đã báo cáo hoàn thành ca trực!";
    $notifMsg = "Sinh viên Y khoa " . ($student['name'] ?? 'chăm sóc') . " đã báo cáo hoàn thành ca trực hỗ trợ sức khỏe. Vui lòng bấm vào đây để xác nhận và gửi đánh giá cho sinh viên.";
    $notifLink = "appointment_details.php?id=" . $appointment_id; // Chuyển thẳng đến trang Chi tiết lịch hẹn

    $insertNotifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) VALUES (?, \'appointment\', ?, ?, ?, 0, NOW())');
    $insertNotifStmt->execute([$appt['patient_id'], $notifTitle, $notifMsg, $notifLink]);

    echo json_encode([
        'success' => true,
        'message' => 'Báo cáo hoàn thành ca trực thành công! Bệnh nhân đã nhận được thông báo kiểm tra.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Complete appointment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi hệ thống xảy ra: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
