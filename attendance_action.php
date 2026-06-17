<?php
/**
 * attendance_action.php - Xử lý điểm danh, xin nghỉ phép, duyệt chấm công và dừng chăm sóc sớm
 */

require_once 'config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$currentUserId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? '';

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Hành động không xác định.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Hàm tự động duyệt điểm danh pending quá 24h cho một lịch hẹn
 */
function auto_approve_pending_logs($pdo, $appointmentId) {
    try {
        // Cập nhật các log đang pending quá 24 giờ thành approved
        $stmt = $pdo->prepare("
            UPDATE attendance_logs 
            SET status = 'approved' 
            WHERE appointment_id = ? 
              AND status = 'pending' 
              AND created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([$appointmentId]);
    } catch (Exception $e) {
        error_log('Auto approve error: ' . $e->getMessage());
    }
}

try {
    switch ($action) {
        // ─────────────────────────────────────────────────────────────────
        // 1. SINH VIÊN ĐIỂM DANH (CHECK-IN)
        // ─────────────────────────────────────────────────────────────────
        case 'check_in':
            if ($userRole !== 'student') {
                echo json_encode(['success' => false, 'message' => 'Chỉ sinh viên mới có quyền điểm danh.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $apptId = (int)($_POST['appointment_id'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $logDate = date('Y-m-d'); // Điểm danh ngày hôm nay

            if (!$apptId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin lịch hẹn.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Kiểm tra tính hợp lệ của lịch hẹn
            $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
            $stmt->execute([$apptId]);
            $appt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appt || $appt['student_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không hợp lệ hoặc bạn không được gán.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($appt['status'] !== 'confirmed') {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn chưa được kích hoạt hoặc đã kết thúc.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Kiểm tra xem hôm nay đã điểm danh hoặc xin nghỉ chưa
            $chkStmt = $pdo->prepare('SELECT id, status FROM attendance_logs WHERE appointment_id = ? AND log_date = ?');
            $chkStmt->execute([$apptId, $logDate]);
            $existingLog = $chkStmt->fetch();

            if ($existingLog) {
                $statusText = $existingLog['status'] === 'day_off' ? 'đã xin nghỉ phép' : 'đã được ghi nhận điểm danh';
                echo json_encode(['success' => false, 'message' => "Hôm nay bạn $statusText rồi, không thể điểm danh tiếp."], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Xử lý upload ảnh bằng chứng
            $evidencePath = null;
            if (isset($_FILES['evidence_image']) && $_FILES['evidence_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['evidence_image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận các định dạng ảnh (jpg, png, webp).'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'Dung lượng ảnh tối đa là 5MB.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Kiểm tra và tự động tạo thư mục uploads/attendance nếu chưa có
                $uploadDir = __DIR__ . '/uploads/attendance';
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0777, true);
                    @chmod($uploadDir, 0777);
                }
                if (!is_writable($uploadDir)) {
                    echo json_encode(['success' => false, 'message' => 'Thư mục tải lên ảnh bằng chứng không có quyền ghi. Vui lòng liên hệ quản trị viên hoặc cấp quyền ghi (chmod 777) cho thư mục uploads/attendance.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Đổi tên file ngẫu nhiên để bảo mật
                $fileName = 'att_' . $apptId . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = $uploadDir . '/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $evidencePath = 'attendance/' . $fileName;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không thể lưu ảnh bằng chứng lên máy chủ.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Bạn bắt buộc phải tải lên ảnh bằng chứng làm việc để điểm danh.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Ghi nhận điểm danh
            $insStmt = $pdo->prepare('INSERT INTO attendance_logs (appointment_id, student_id, log_date, check_in_time, status, daily_notes, evidence_image) VALUES (?, ?, ?, NOW(), \'pending\', ?, ?)');
            $insStmt->execute([$apptId, $currentUserId, $logDate, $notes, $evidencePath]);

            // Gửi thông báo cho bệnh nhân
            $notifTitle = "Sinh viên đã điểm danh hôm nay!";
            $notifMsg = "Sinh viên " . ($_SESSION['name'] ?? 'chăm sóc') . " đã điểm danh & ghi nhật ký ngày " . date('d/m/Y') . ". Hãy bấm vào đây để duyệt ca trực.";
            $notifLink = "appointment_details.php?id=" . $apptId;

            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
            $notifStmt->execute([$appt['patient_id'], $notifTitle, $notifMsg, $notifLink]);

            echo json_encode(['success' => true, 'message' => 'Điểm danh ngày hôm nay thành công! Lịch hẹn đang chờ người nhà bệnh nhân phê duyệt.'], JSON_UNESCAPED_UNICODE);
            break;

        // ─────────────────────────────────────────────────────────────────
        // 2. SINH VIÊN XIN NGHỈ PHÉP (DAY-OFF REQUEST)
        // ─────────────────────────────────────────────────────────────────
        case 'request_day_off':
            if ($userRole !== 'student') {
                echo json_encode(['success' => false, 'message' => 'Chỉ sinh viên mới có quyền xin nghỉ phép.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $apptId = (int)($_POST['appointment_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            $logDate = date('Y-m-d');

            if (!$apptId || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp lý do xin nghỉ phép.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
            $stmt->execute([$apptId]);
            $appt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appt || $appt['student_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($appt['status'] !== 'confirmed') {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không trong trạng thái hoạt động.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $chkStmt = $pdo->prepare('SELECT id FROM attendance_logs WHERE appointment_id = ? AND log_date = ?');
            $chkStmt->execute([$apptId, $logDate]);
            if ($chkStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Hôm nay ngày này đã ghi nhận trạng thái rồi.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Ghi nhận nghỉ phép status = day_off
            $insStmt = $pdo->prepare('INSERT INTO attendance_logs (appointment_id, student_id, log_date, check_in_time, status, daily_notes) VALUES (?, ?, ?, NOW(), \'day_off\', ?)');
            $insStmt->execute([$apptId, $currentUserId, $logDate, 'XIN NGHỈ PHÉP: ' . $reason]);

            // Gửi thông báo cho bệnh nhân
            $notifTitle = "Sinh viên báo nghỉ phép hôm nay!";
            $notifMsg = "Sinh viên " . ($_SESSION['name'] ?? 'chăm sóc') . " đã báo nghỉ phép ngày " . date('d/m/Y') . " với lý do: " . $reason;
            $notifLink = "appointment_details.php?id=" . $apptId;

            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
            $notifStmt->execute([$appt['patient_id'], $notifTitle, $notifMsg, $notifLink]);

            echo json_encode(['success' => true, 'message' => 'Đã báo nghỉ phép ngày hôm nay thành công!'], JSON_UNESCAPED_UNICODE);
            break;

        // ─────────────────────────────────────────────────────────────────
        // 3. BỆNH NHÂN PHÊ DUYỆT ĐIỂM DANH (APPROVE / REJECT)
        // ─────────────────────────────────────────────────────────────────
        case 'verify_log':
            if ($userRole !== 'patient') {
                echo json_encode(['success' => false, 'message' => 'Chỉ bệnh nhân mới có quyền duyệt điểm danh.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $logId = (int)($_POST['log_id'] ?? 0);
            $decision = trim($_POST['decision'] ?? ''); // approve | reject
            $reason = trim($_POST['rejection_reason'] ?? '');

            if (!$logId || !in_array($decision, ['approve', 'reject'])) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($decision === 'reject' && empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do từ chối.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Lấy thông tin log
            $logStmt = $pdo->prepare('SELECT al.*, a.patient_id FROM attendance_logs al JOIN appointments a ON al.appointment_id = a.id WHERE al.id = ?');
            $logStmt->execute([$logId]);
            $log = $logStmt->fetch(PDO::FETCH_ASSOC);

            if (!$log) {
                echo json_encode(['success' => false, 'message' => 'Nhật ký điểm danh không tồn tại.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($log['patient_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'message' => 'Bạn không sở hữu lịch hẹn này.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($log['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Nhật ký này đã được xử lý trước đó.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $pdo->beginTransaction();

            if ($decision === 'approve') {
                $updStmt = $pdo->prepare('UPDATE attendance_logs SET status = \'approved\' WHERE id = ?');
                $updStmt->execute([$logId]);

                $notifTitle = "Điểm danh của bạn đã được duyệt!";
                $notifMsg = "Bệnh nhân đã duyệt điểm danh ngày " . date('d/m/Y', strtotime($log['log_date'])) . " cho bạn.";
            } else {
                $updStmt = $pdo->prepare('UPDATE attendance_logs SET status = \'rejected\', rejection_reason = ? WHERE id = ?');
                $updStmt->execute([$reason, $logId]);

                $notifTitle = "Điểm danh của bạn bị từ chối!";
                $notifMsg = "Bệnh nhân từ chối duyệt điểm danh ngày " . date('d/m/Y', strtotime($log['log_date'])) . ". Lý do: " . $reason;
            }

            $notifLink = "appointment_details.php?id=" . $log['appointment_id'];
            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
            $notifStmt->execute([$log['student_id'], $notifTitle, $notifMsg, $notifLink]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => $decision === 'approve' ? 'Đã phê duyệt ca trực thành công!' : 'Đã từ chối duyệt điểm danh.'], JSON_UNESCAPED_UNICODE);
            break;

        // ─────────────────────────────────────────────────────────────────
        // 4. DỪNG CHĂM SÓC SỚM (EARLY TERMINATION)
        // ─────────────────────────────────────────────────────────────────
        case 'terminate_early':
            $apptId = (int)($_POST['appointment_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');

            if (!$apptId || empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng cung cấp đầy đủ lý do dừng hợp đồng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
            $stmt->execute([$apptId]);
            $appt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appt) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Người dùng phải là Sinh viên hoặc Bệnh nhân trong lịch hẹn
            if ($appt['patient_id'] != $currentUserId && $appt['student_id'] != $currentUserId) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền dừng lịch hẹn này.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($appt['status'] !== 'confirmed') {
                echo json_encode(['success' => false, 'message' => 'Chỉ có thể dừng lịch hẹn đang hoạt động.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $pdo->beginTransaction();

            // Cập nhật trạng thái lịch hẹn sang completed
            $newNotes = $appt['notes'] . "\n[KẾT THÚC SỚM] Dừng chăm sóc vào ngày " . date('d/m/Y') . ". Lý do: " . $reason;
            $updAppt = $pdo->prepare('UPDATE appointments SET status = \'completed\', notes = ?, updated_at = NOW() WHERE id = ?');
            $updAppt->execute([$newNotes, $apptId]);

            // Cập nhật bài đăng gốc sang trạng thái completed
            // (Đồng bộ hóa để chốt công việc)
            $updPost = $pdo->prepare('UPDATE posts SET status = \'completed\' WHERE assigned_to = ? AND user_id = ? AND status = \'taken\'');
            $updPost->execute([$appt['student_id'], $appt['patient_id']]);

            // Gửi tin nhắn vào hội thoại chat
            $user1 = min($appt['patient_id'], $appt['student_id']);
            $user2 = max($appt['patient_id'], $appt['student_id']);
            $convStmt = $pdo->prepare('SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)');
            $convStmt->execute([$user1, $user2, $user2, $user1]);
            $conv = $convStmt->fetch();

            $initiatorName = $_SESSION['name'] ?? 'Thành viên';
            $msgText = "⚠️ **KẾT THÚC CHĂM SÓC SỚM**\n"
                     . "$initiatorName đã yêu cầu dừng lịch hẹn chăm sóc sớm nửa chừng.\n"
                     . "- **Lý do**: $reason\n"
                     . "- **Ngày dừng**: " . date('d/m/Y') . "\n"
                     . "Hợp đồng đã được đóng lại. Tiền lương sẽ chốt tính trên số ngày công điểm danh thực tế thành công: [Chi tiết lịch chốt công](appointment_details.php?id=" . $apptId . ")";

            if ($conv) {
                $convId = $conv['id'];
                $msgStmt = $pdo->prepare('INSERT INTO direct_messages (conversation_id, sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, ?, NOW())');
                $msgStmt->execute([$convId, $currentUserId, $currentUserId == $appt['patient_id'] ? $appt['student_id'] : $appt['patient_id'], $msgText]);

                $updConv = $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?');
                $updConv->execute([$convId]);
            }

            // Gửi thông báo cho bên kia
            $targetUserId = ($currentUserId == $appt['patient_id']) ? $appt['student_id'] : $appt['patient_id'];
            $notifTitle = "Lịch hẹn đã kết thúc sớm!";
            $notifMsg = "$initiatorName đã bấm dừng chăm sóc sớm vào ngày " . date('d/m/Y') . ". Vui lòng xem hóa đơn chốt.";
            $notifLink = "appointment_details.php?id=" . $apptId;

            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
            $notifStmt->execute([$targetUserId, $notifTitle, $notifMsg, $notifLink]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Lịch hẹn đã được dừng thành công! Đã gửi thông báo chốt công cho cả hai bên.'], JSON_UNESCAPED_UNICODE);
            break;

        // ─────────────────────────────────────────────────────────────────
        // 5. LƯU CHỮ KÝ ĐIỆN TỬ (SAVE DIGITAL SIGNATURE)
        // ─────────────────────────────────────────────────────────────────
        case 'save_signature':
            $apptId = (int)($_POST['appointment_id'] ?? 0);
            $role = trim($_POST['role'] ?? '');
            $sigData = trim($_POST['signature_data'] ?? '');

            if (!$apptId || !in_array($role, ['patient', 'student']) || empty($sigData)) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu ký không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Kiểm tra quyền: Bên ký phải khớp với user đăng nhập
            $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ?');
            $stmt->execute([$apptId]);
            $appt = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$appt) {
                echo json_encode(['success' => false, 'message' => 'Lịch hẹn không tồn tại.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($role === 'patient' && ($appt['patient_id'] != $currentUserId || $userRole !== 'patient')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền ký với tư cách Bệnh nhân.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($role === 'student' && ($appt['student_id'] != $currentUserId || $userRole !== 'student')) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền ký với tư cách Sinh viên.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Decode base64 image
            if (preg_match('/^data:image\/(\w+);base64,/', $sigData, $type)) {
                $data = substr($sigData, strpos($sigData, ',') + 1);
                $type = strtolower($type[1]); // png

                if (!in_array($type, ['png'])) {
                    echo json_encode(['success' => false, 'message' => 'Định dạng chữ ký không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $data = base64_decode($data);
                if ($data === false) {
                    echo json_encode(['success' => false, 'message' => 'Giải mã dữ liệu chữ ký thất bại.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu ảnh không đúng định dạng.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Tạo thư mục lưu chữ ký nếu chưa có
            $sigDir = __DIR__ . '/uploads/signatures';
            if (!file_exists($sigDir)) {
                @mkdir($sigDir, 0777, true);
                @chmod($sigDir, 0777);
            }

            if (!is_writable($sigDir)) {
                echo json_encode(['success' => false, 'message' => 'Thư mục chữ ký không có quyền ghi.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $fileName = $role . '_sig_' . $apptId . '_' . time() . '.png';
            $filePath = 'signatures/' . $fileName;
            $fullPath = $sigDir . '/' . $fileName;

            if (file_put_contents($fullPath, $data)) {
                // Lấy chữ ký cũ để xóa sau khi lưu thành công
                $oldSig = ($role === 'patient') ? $appt['patient_signature'] : $appt['student_signature'];

                // Cập nhật database
                $col = ($role === 'patient') ? 'patient_signature' : 'student_signature';
                $updStmt = $pdo->prepare("UPDATE appointments SET $col = ? WHERE id = ?");
                $updStmt->execute([$filePath, $apptId]);

                // Xóa file chữ ký cũ nếu có để tránh rác
                if ($oldSig && file_exists(__DIR__ . '/uploads/' . $oldSig)) {
                    @unlink(__DIR__ . '/uploads/' . $oldSig);
                }

                // Gửi thông báo cho đối tác
                $partnerId = ($role === 'patient') ? $appt['student_id'] : $appt['patient_id'];
                $signerRoleText = ($role === 'patient') ? 'Bệnh nhân' : 'Sinh viên Y khoa';
                $notifTitle = "Biên bản đã có chữ ký mới!";
                $notifMsg = "$signerRoleText " . ($_SESSION['name'] ?? '') . " đã ký tên vào biên bản lịch hẹn #$apptId.";
                $notifLink = "appointment_details.php?id=" . $apptId;

                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, \'appointment\', ?, ?, ?, NOW())');
                $notifStmt->execute([$partnerId, $notifTitle, $notifMsg, $notifLink]);

                echo json_encode(['success' => true, 'message' => 'Đã lưu chữ ký điện tử của bạn thành công!'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể lưu file chữ ký lên máy chủ.'], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống xảy ra: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
