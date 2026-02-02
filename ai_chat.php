<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['message'] ?? '';
    // Lấy API key từ biến môi trường hoặc config
    $apiKey = getenv('HUGGINGFACE_API_KEY') ?: (defined('HUGGINGFACE_API_KEY') ? HUGGINGFACE_API_KEY : '');

    $data = [
        "inputs" => $question
    ];

    $ch = curl_init('https://router.huggingface.co/models/facebook/blenderbot-400M-distill');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo 'Lỗi hệ thống: ' . $curlError;
        exit;
    }

    $response = json_decode($result, true);
    // Nếu trả về mảng có trường 'generated_text'
    if (isset($response['generated_text'])) {
        echo $response['generated_text'];
    }
    // Nếu trả về mảng 0-indexed (thường là [{"generated_text": ...}])
    elseif (is_array($response) && isset($response[0]['generated_text'])) {
        echo $response[0]['generated_text'];
    }
    // Nếu trả về lỗi
    elseif (isset($response['error'])) {
        echo 'Lỗi AI: ' . htmlspecialchars($response['error']);
    }
    else {
        echo 'Xin lỗi, tôi chưa hiểu câu hỏi của bạn.';
    }
}
?>
