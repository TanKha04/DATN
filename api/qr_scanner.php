<?php
/**
 * QR Scanner API
 * Handles QR code scanning operations and history
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'save_scan':
            saveScanResult();
            break;
            
        case 'get_history':
            getScanHistory();
            break;
            
        case 'delete_scan':
            deleteScanResult();
            break;
            
        case 'clear_history':
            clearScanHistory();
            break;
            
        case 'analyze_qr':
            analyzeQRContent();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function saveScanResult() {
    global $pdo, $user_id;
    
    $content = $_POST['content'] ?? '';
    $method = $_POST['method'] ?? 'camera'; // camera or upload
    $metadata = $_POST['metadata'] ?? '{}';
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required']);
        return;
    }
    
    // Create qr_scans table if not exists
    createQRScansTable();
    
    $stmt = $pdo->prepare("
        INSERT INTO qr_scans (user_id, content, scan_method, metadata, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([$user_id, $content, $method, $metadata]);
    
    if ($result) {
        $scan_id = $pdo->lastInsertId();
        
        // Get the saved scan
        $stmt = $pdo->prepare("SELECT * FROM qr_scans WHERE id = ?");
        $stmt->execute([$scan_id]);
        $scan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'scan' => formatScanResult($scan)
        ]);
    } else {
        throw new Exception('Failed to save scan result');
    }
}

function getScanHistory() {
    global $pdo, $user_id;
    
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Create table if not exists
    createQRScansTable();
    
    $stmt = $pdo->prepare("
        SELECT * FROM qr_scans 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$user_id, $limit, $offset]);
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM qr_scans WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'scans' => array_map('formatScanResult', $scans),
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function deleteScanResult() {
    global $pdo, $user_id;
    
    $scan_id = $_POST['scan_id'] ?? '';
    
    if (empty($scan_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Scan ID is required']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM qr_scans WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$scan_id, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to delete scan result');
    }
}

function clearScanHistory() {
    global $pdo, $user_id;
    
    $stmt = $pdo->prepare("DELETE FROM qr_scans WHERE user_id = ?");
    $result = $stmt->execute([$user_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'deleted_count' => $stmt->rowCount()
        ]);
    } else {
        throw new Exception('Failed to clear scan history');
    }
}

function analyzeQRContent() {
    $content = $_POST['content'] ?? '';
    
    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['error' => 'Content is required']);
        return;
    }
    
    $analysis = [
        'type' => 'text',
        'is_url' => false,
        'is_email' => false,
        'is_phone' => false,
        'is_wifi' => false,
        'is_vcard' => false,
        'metadata' => []
    ];
    
    // Check if URL
    if (filter_var($content, FILTER_VALIDATE_URL)) {
        $analysis['type'] = 'url';
        $analysis['is_url'] = true;
        $analysis['metadata']['domain'] = parse_url($content, PHP_URL_HOST);
        $analysis['metadata']['scheme'] = parse_url($content, PHP_URL_SCHEME);
    }
    // Check if email
    elseif (filter_var($content, FILTER_VALIDATE_EMAIL)) {
        $analysis['type'] = 'email';
        $analysis['is_email'] = true;
        $analysis['metadata']['domain'] = substr(strrchr($content, "@"), 1);
    }
    // Check if phone number
    elseif (preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $content)) {
        $analysis['type'] = 'phone';
        $analysis['is_phone'] = true;
        $analysis['metadata']['formatted'] = preg_replace('/[^\d\+]/', '', $content);
    }
    // Check if WiFi QR
    elseif (strpos($content, 'WIFI:') === 0) {
        $analysis['type'] = 'wifi';
        $analysis['is_wifi'] = true;
        
        // Parse WiFi QR format: WIFI:T:WPA;S:MyNetwork;P:MyPassword;H:false;;
        if (preg_match('/WIFI:T:([^;]*);S:([^;]*);P:([^;]*);/', $content, $matches)) {
            $analysis['metadata'] = [
                'security' => $matches[1],
                'ssid' => $matches[2],
                'password' => $matches[3]
            ];
        }
    }
    // Check if vCard
    elseif (strpos($content, 'BEGIN:VCARD') !== false) {
        $analysis['type'] = 'vcard';
        $analysis['is_vcard'] = true;
        
        // Parse basic vCard info
        if (preg_match('/FN:([^\r\n]+)/', $content, $matches)) {
            $analysis['metadata']['name'] = $matches[1];
        }
        if (preg_match('/TEL:([^\r\n]+)/', $content, $matches)) {
            $analysis['metadata']['phone'] = $matches[1];
        }
        if (preg_match('/EMAIL:([^\r\n]+)/', $content, $matches)) {
            $analysis['metadata']['email'] = $matches[1];
        }
    }
    
    // Additional metadata
    $analysis['metadata']['length'] = strlen($content);
    $analysis['metadata']['word_count'] = str_word_count($content);
    
    echo json_encode([
        'success' => true,
        'analysis' => $analysis
    ]);
}

function createQRScansTable() {
    global $pdo;
    
    $sql = "
        CREATE TABLE IF NOT EXISTS qr_scans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            scan_method ENUM('camera', 'upload') DEFAULT 'camera',
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_created (user_id, created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql);
}

function formatScanResult($scan) {
    if (!$scan) return null;
    
    $formatted = [
        'id' => (int)$scan['id'],
        'content' => $scan['content'],
        'method' => $scan['scan_method'],
        'created_at' => $scan['created_at'],
        'metadata' => json_decode($scan['metadata'] ?? '{}', true)
    ];
    
    // Add analysis
    $formatted['is_url'] = filter_var($scan['content'], FILTER_VALIDATE_URL) !== false;
    $formatted['is_email'] = filter_var($scan['content'], FILTER_VALIDATE_EMAIL) !== false;
    $formatted['is_phone'] = preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $scan['content']);
    
    // Format timestamp
    $formatted['formatted_date'] = date('d/m/Y H:i', strtotime($scan['created_at']));
    $formatted['relative_time'] = getRelativeTime($scan['created_at']);
    
    return $formatted;
}

function getRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Vừa xong';
    if ($time < 3600) return floor($time/60) . ' phút trước';
    if ($time < 86400) return floor($time/3600) . ' giờ trước';
    if ($time < 2592000) return floor($time/86400) . ' ngày trước';
    if ($time < 31536000) return floor($time/2592000) . ' tháng trước';
    
    return floor($time/31536000) . ' năm trước';
}
?>