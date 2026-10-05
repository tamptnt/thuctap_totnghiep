<?php
// File chẩn đoán tạm thời - XÓA SAU KHI DÙNG XONG
header('Content-Type: application/json; charset=utf-8');
$s = $_GET['s'] ?? '1';
if ($s === '1') {                       // POST thuần, không include gì
    echo json_encode(['step' => 1, 'ok' => true, 'method' => $_SERVER['REQUEST_METHOD'], 'post' => $_POST]);
} elseif ($s === '2') {                 // include config.php (DB, session)
    require_once 'config.php';
    echo json_encode(['step' => 2, 'ok' => true, 'db' => 'ket noi duoc']);
} elseif ($s === '3') {                 // gọi Gemini thật
    require_once 'config.php';
    require_once 'includes/gemini.php';
    $r = gemini_text($conn, 'Trả lời đúng một từ: OK', '', '', false, 256);
    echo json_encode(['step' => 3, 'ok' => $r['ok'], 'text' => $r['text'] ?? ($r['error'] ?? '')], JSON_UNESCAPED_UNICODE);
} elseif ($s === '4') {                 // PHP chủ động trả mã 422
    http_response_code(422);
    echo json_encode(['step' => 4, 'note' => 'PHP tra ve 422']);
}
