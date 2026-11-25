<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/ai_config.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$occasion = trim((string)($input['occasion'] ?? 'Khai Trương'));
$tone = trim((string)($input['tone'] ?? 'trang_trong')); // trang_trong, hai_huoc, ngot_ngao, kinh_can
$recipient = trim((string)($input['recipient'] ?? ''));
$sender = trim((string)($input['sender'] ?? ''));
$format = trim((string)($input['format'] ?? 'ribbon')); // ribbon: dưới 35 ký tự, card: thiệp viết tay
$sessionId = trim((string)($input['session_id'] ?? ('sess_wishes_' . bin2hex(random_bytes(6)))));

// KHO MẪU LỜI CHÚC CHUYÊN GIA ĐIỆN HOA (Chuẩn niêm luật độ dài dải ruy-băng vi tính)
$wishTemplates = [
    'khai_truong' => [
        'trang_trong' => [
            'KHAI TRƯƠNG HỒNG PHÁT - TÀI LỘC DỒỒI DÀO',
            'CHÚC MỪNG KHAI TRƯƠNG - MUA MAY BÁN ĐẮT',
            'ĐẠI CÁT ĐẠI LỢI - VẠN SỰ HANH THÔNG'
        ],
        'hai_huoc' => [
            'KHAI TRƯƠNG HỒNG PHÁT - ĐẾM TIỀN MỎI TAY',
            'TIỆM MỚI TƯƠI MÁT - TIỀN VÀO NHƯ NƯỚC',
            'CHÚC BẠN YÊU MỞ BÁT - THÀNH CÔNG RỰC RỠ'
        ],
        'ngot_ngao' => [
            'CHÚC ƯỚC MƠ CỦA BẠN BAY CAO TỎA SÁNG',
            'LUÔN TỰ HÀO VÀ ĐỒNG HÀNH CÙNG BẠN',
            'CHÚC KHỞI ĐẦU MỚI THUẬN BUỒM XUÔI GIÓ'
        ]
    ],
    'sinh_nhat' => [
        'trang_trong' => [
            'CHÚC MỪNG SINH NHẬT - VẠN SỰ NHƯ Ý',
            'TUỔI MỚI NHIỀU THÀNH CÔNG VÀ MAY MẮN',
            'CHÚC BẠN LUÔN BÌNH AN VÀ HẠNH PHÚC'
        ],
        'ngot_ngao' => [
            'CHÚC EM YÊU TUỔI MỚI MÃI RẠNG NGỜI',
            'CẢM ƠN VÌ EM ĐÃ ĐẾN BÊN CUỘC ĐỜI ANH',
            'HAPPY BIRTHDAY TO MY SUNSHINE'
        ],
        'hai_huoc' => [
            'CHÚC TUỔI MỚI BỚT KHÙNG VÀ NHANH GIÀU',
            'HAPPY BIRTHDAY - MÃI MÃI TUỔI 18',
            'THÊM MỘT TUỔI - ĐẲNG CẤP MỚI'
        ]
    ],
    'tinh_yeu' => [
        'ngot_ngao' => [
            'YÊU EM HÔM NAY VÀ MÃI MÃI VỀ SAU',
            'EM LÀ ĐIỀU TUYỆT VỜI NHẤT TRONG ANH',
            'FOREVER IN LOVE - YÊU VỢ NHẤT TRÊN ĐỜI'
        ],
        'trang_trong' => [
            'KỶ NIỆM NGÀY CHUNG ĐÔI - TRỌN VẸN YÊU THƯƠNG',
            'CHÚC TÌNH YÊU CHÚNG TA MÃI BỀN LÂU',
            'HẠNH PHÚC ĐƠN GIẢN LÀ CÓ EM MỖI NGÀY'
        ]
    ],
    'chia_buon' => [
        'kinh_can' => [
            'THÀNH KÍNH PHÂN ƯU CÙNG TANG QUYẾN',
            'VÔ CÙNG THƯƠNG TIẾC - NGUYỆN HƯƠNG HỒN AN NGHỈ',
            'XIN CHIA BUỒN SÂU SẮC CÙNG GIA QUYẾN'
        ],
        'trang_trong' => [
            'KÍNH VIẾNG HƯƠNG HỒN NGƯỜI QUÁ CỐ',
            'THÀNH KÍNH TIỄN BIỆT BÁC VỀ CÕI VĨNH HẰNG',
            'XIN GỬI NỖI NIỀM TIẾC THƯƠNG VÔ HẠN'
        ]
    ]
];

// Xác định nhóm dịp
$occasionKey = 'khai_truong';
if (stripos($occasion, 'sinh') !== false) {
    $occasionKey = 'sinh_nhat';
} elseif (stripos($occasion, 'tình') !== false || stripos($occasion, 'yêu') !== false || stripos($occasion, 'kỷ niệm') !== false) {
    $occasionKey = 'tinh_yeu';
} elseif (stripos($occasion, 'chia') !== false || stripos($occasion, 'viếng') !== false || stripos($occasion, 'tang') !== false) {
    $occasionKey = 'chia_buon';
    if ($tone === 'hai_huoc') $tone = 'kinh_can'; // Tránh phản cảm khi chia buồn
}

$toneKey = $tone;
if (!isset($wishTemplates[$occasionKey][$toneKey])) {
    $toneKey = array_key_first($wishTemplates[$occasionKey]);
}

$rawWishes = $wishTemplates[$occasionKey][$toneKey] ?? [
    'CHÚC MỪNG NGÀY ĐẶC BIỆT',
    'VẠN SỰ NHƯ Ý - PHÁT TÀI PHÁT LỘC',
    'CHÚC MỌI ĐIỀU TỐT ĐẸP NHẤT'
];

// Nếu có tên người gửi, định dạng chuẩn câu băng rôn: "NGƯỜI GỬI - LỜI CHÚC"
$formattedWishes = [];
foreach ($rawWishes as $idx => $w) {
    $item = $w;
    if ($sender !== '' && $format === 'ribbon') {
        $prefix = mb_strtoupper($sender, 'UTF-8') . " CHÚC MỪNG: ";
        // Kiểm soát không quá 45 ký tự trên băng rôn
        if (mb_strlen($prefix . $item, 'UTF-8') <= 45) {
            $item = $prefix . $item;
        }
    }
    $formattedWishes[] = $item;
}

// Lưu log vào DB
$db = getDBConnection();
if ($db) {
    try {
        $stmt = $db->prepare("
            INSERT INTO ai_conversations (session_id, feature_type, occasion, prompt_input, ai_response, ip_address)
            VALUES (?, 'ribbon_wishes', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sessionId,
            $occasion,
            "Dịp: $occasion, Giọng điệu: $tone, Người nhận: $recipient, Định dạng: $format",
            implode("\n", $formattedWishes),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (PDOException $e) {
        // Log safe
    }
}

echo json_encode([
    'success' => true,
    'session_id' => $sessionId,
    'occasion' => $occasion,
    'tone' => $tone,
    'format' => $format,
    'wishes' => $formattedWishes
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
