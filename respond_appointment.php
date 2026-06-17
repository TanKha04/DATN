<?php
/**
 * respond_appointment.php - API xử lý phản hồi đề xuất lịch hẹn của sinh viên
 */

require_once 'config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Chỉ có sinh viên y khoa mới có quyền phản hồi đề xuất lịch hẹn.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$apptId = (int)($_POST['appointment_id'] ?? 0);
$action = trim($_POST['response'] ?? ''); // accept | reject
$currentUserId = $_SESSION['user_id'];

if (!$apptId || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. Kiểm tra lịch hẹn
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$apptId]);
    $appt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appt) {
        echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($appt['student_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($appt['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Lịch hẹn đã được phản hồi trước đó.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'accept') {
        // Kiểm tra trùng lịch hẹn đã xác nhận khác của sinh viên (cả Ngày và Giờ)
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
            echo json_encode([
                'success' => false,
                'message' => "Không thể đồng ý. Bạn đã có lịch hẹn chăm sóc khác được xác nhận trong khoảng thời gian này (từ ngày {$overlapStart} đến {$overlapEnd}, khung giờ {$overlapTimeStart} - {$overlapTimeEnd})."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $pdo->beginTransaction();

    if ($action === 'accept') {
        // Chấp nhận đề xuất -> chuyển sang confirmed
        $updateAppt = $pdo->prepare('UPDATE appointments SET status = \'confirmed\', updated_at = NOW() WHERE id = ?');
        $updateAppt->execute([$apptId]);

        // Gửi tin nhắn thông báo vào khung chat
        $msgText = "✅ **ĐỒNG Ý ĐỀ XUẤT LỊCH HẸN**\n"
                 . "Sinh viên Y khoa " . ($_SESSION['name'] ?? 'chăm sóc') . " đã CHẤP NHẬN đề xuất lịch hẹn từ " . date('d/m/Y', strtotime($appt['start_date'])) . " đến " . date('d/m/Y', strtotime($appt['end_date'])) . ".\n"
                 . "Lịch hẹn chính thức được kích hoạt. Hãy truy cập quản lý điểm danh tại đây: [Chi tiết lịch hẹn](appointment_details.php?id=" . $apptId . ")";

        // Gửi thông báo hệ thống cho bệnh nhân
        $notifTitle = "Sinh viên đã chấp nhận lịch hẹn!";
        $notifMsg = "Sinh viên " . ($_SESSION['name'] ?? 'chăm sóc') . " đã đồng ý lịch hẹn đề xuất từ ngày " . date('d/m/Y', strtotime($appt['start_date'])) . ". Hãy click vào đây để xem chi tiết.";
        $notifLink = "appointment_details.php?id=" . $apptId;

    } else {
        // Từ chối đề xuất -> chuyển sang cancelled
        $updateAppt = $pdo->prepare('UPDATE appointments SET status = \'cancelled\', updated_at = NOW() WHERE id = ?');
        $updateAppt->execute([$apptId]);

        $msgText = "❌ **TỪ CHỐI ĐỀ XUẤT LỊCH HẸN**\n"
                 . "Sinh viên Y khoa " . ($_SESSION['name'] ?? 'chăm sóc') . " đã TỪ CHỐI đề xuất lịch hẹn chăm sóc của bạn.";

        $notifTitle = "Sinh viên từ chối đề xuất lịch hẹn!";
        $notifMsg = "Sinh viên " . ($_SESSION['name'] ?? 'chăm sóc') . " đã từ chối đề xuất lịch hẹn của bạn.";
        $notifLink = "appointment_details.php?id=" . $apptId;
    }

    // Gửi tin nhắn chat
    $user1 = min($currentUserId, $appt['patient_id']);
    $user2 = max($currentUserId, $appt['patient_id']);
    $convStmt = $pdo->prepare('SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)');
    $convStmt->execute([$user1, $user2, $user2, $user1]);
    $conv = $convStmt->fetch();

    if ($conv) {
        $convId = $conv['id'];
        $msgStmt = $pdo->prepare('INSERT INTO direct_messages (conversation_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())');
        $msgStmt->execute([$convId, $currentUserId, $appt['patient_id'], $msgText]);

        // Cập nhật last_message_at
        $updConv = $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?');
        $updConv->execute([$convId]);
    }

    // Tạo thông báo hệ thống cho bệnh nhân
    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
    $notifStmt->execute([$appt['patient_id'], $notifTitle, $notifMsg, $notifLink]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $action === 'accept' ? 'Đã đồng ý lịch hẹn thành công!' : 'Đã từ chối đề xuất lịch hẹn.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
