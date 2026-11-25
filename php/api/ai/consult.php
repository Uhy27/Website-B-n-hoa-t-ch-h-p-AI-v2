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

// Đọc dữ liệu gửi lên (Hỗ trợ cả JSON body và POST form thông thường)
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$occasion = trim((string)($input['occasion'] ?? ''));
$recipient = trim((string)($input['recipient'] ?? ''));
$budget = (int)($input['budget'] ?? 0);
$userMessage = trim((string)($input['message'] ?? ''));
$sessionId = trim((string)($input['session_id'] ?? ('sess_' . bin2hex(random_bytes(8)))));

// 1. LẤY DANH SÁCH HOA THẬT TỪ DATABASE CỦA BLOOMORA
$db = getDBConnection();
$products = [];

if ($db) {
    try {
        $stmt = $db->query("
            SELECT p.id, p.sku, p.name, c.name AS category_name, p.price, p.original_price, p.image, p.badge, p.description 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_available_for_order = 1
            ORDER BY p.price ASC
        ");
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $products = [];
    }
}

// Fallback danh mục sản phẩm nếu DB chưa import
if (empty($products)) {
    $products = [
        [
            'id' => 1, 'sku' => 'WF-KT01', 'name' => 'Kệ Khai Trương Đại Cát Hồng Phát',
            'category_name' => 'Hoa Khai Trương', 'price' => 1850000, 'original_price' => 2200000,
            'image' => 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Bán Chạy Nhất', 'description' => 'Kệ hoa 2 tầng phối hoa hồng Ohara đỏ, hướng dương Đà Lạt'
        ],
        [
            'id' => 2, 'sku' => 'WF-TY01', 'name' => 'Bó Hồng Đỏ Nồng Nàn 99 Bông (Forever Love)',
            'category_name' => 'Hoa Tình Yêu & Hoa Hồng', 'price' => 1250000, 'original_price' => 1500000,
            'image' => 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Gợi Ý Valentine', 'description' => '99 đóa hoa hồng đỏ Ecuador tuyển chọn cành thẳng'
        ],
        [
            'id' => 3, 'sku' => 'WF-HD01', 'name' => 'Chậu Lan Hồ Điệp Hoàng Kim 5 Cành',
            'category_name' => 'Lan Hồ Điệp', 'price' => 2500000, 'original_price' => 2800000,
            'image' => 'https://images.unsplash.com/photo-1525310072745-f49212b5ac6d?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Cao Cấp', 'description' => 'Lan hồ điệp vàng hoàng kim cắm chậu gốm mộc nghệ thuật'
        ],
        [
            'id' => 4, 'sku' => 'WF-SN01', 'name' => 'Giỏ Hoa Pastel Nắng Mai Ấm Áp',
            'category_name' => 'Hoa Sinh Nhật', 'price' => 650000, 'original_price' => 800000,
            'image' => 'https://images.unsplash.com/photo-1567696911980-2eed69a46042?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Yêu Thích', 'description' => 'Giỏ hoa tone kem pastel nhẹ nhàng kết hợp hoa baby trắng'
        ],
        [
            'id' => 5, 'sku' => 'WF-KT02', 'name' => 'Kệ Hoa Khai Trương Tinh Khôi Thịnh Vượng',
            'category_name' => 'Hoa Khai Trương', 'price' => 2150000, 'original_price' => 2500000,
            'image' => 'https://images.unsplash.com/photo-1519378058457-4c29a0a2efac?auto=format&fit=crop&w=600&q=80',
            'badge' => 'VIP', 'description' => 'Thiết kế phong cách tối giản Bắc Âu sang trọng với hoa cẩm tú cầu xanh bơ'
        ],
        [
            'id' => 6, 'sku' => 'WF-TY02', 'name' => 'Hộp Hoa Trái Tim Hồng Phấn Thủy Tinh',
            'category_name' => 'Hoa Tình Yêu & Hoa Hồng', 'price' => 890000, 'original_price' => 1050000,
            'image' => 'https://images.unsplash.com/photo-1591886960571-74d43a9d4166?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Mẫu Mới', 'description' => 'Hộp mica trong suốt xếp hình trái tim với hoa hồng phấn Ecuador'
        ],
        [
            'id' => 7, 'sku' => 'WF-SN02', 'name' => 'Bó Hướng Dương Năng Lượng Tươi Sáng',
            'category_name' => 'Hoa Sinh Nhật', 'price' => 550000, 'original_price' => 650000,
            'image' => 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Best Value', 'description' => '5 cành hướng dương lớn vươn mình rực rỡ mừng tốt nghiệp và sinh nhật'
        ],
        [
            'id' => 8, 'sku' => 'WF-VC01', 'name' => 'Khung Kính Hoa Hồng Vĩnh Cửu 3 Năm',
            'category_name' => 'Hoa Vĩnh Cửu', 'price' => 950000, 'original_price' => 1200000,
            'image' => 'https://images.unsplash.com/photo-1533616688419-b7a58556458e?auto=format&fit=crop&w=600&q=80',
            'badge' => 'Độc Quyền', 'description' => 'Hoa hồng vĩnh cửu công nghệ ướp tươi Nhật Bản đặt trong vòm kính pha lê'
        ]
    ];
}

// 2. LỌC HOA PHÙ HỢP THEO TIÊU CHÍ (NGÂN SÁCH, DỊP, NGƯỜI NHẬN)
$matchedProducts = [];
$targetCat = '';

if (stripos($occasion, 'khai') !== false || stripos($userMessage, 'khai') !== false) {
    $targetCat = 'Hoa Khai Trương';
} elseif (stripos($occasion, 'sinh') !== false || stripos($userMessage, 'sinh') !== false) {
    $targetCat = 'Hoa Sinh Nhật';
} elseif (stripos($occasion, 'tình') !== false || stripos($occasion, 'yêu') !== false || stripos($userMessage, 'yêu') !== false || stripos($recipient, 'yêu') !== false || stripos($recipient, 'vợ') !== false) {
    $targetCat = 'Hoa Tình Yêu & Hoa Hồng';
} elseif (stripos($occasion, 'đối tác') !== false || stripos($recipient, 'sếp') !== false || stripos($recipient, 'đối tác') !== false) {
    $targetCat = 'Lan Hồ Điệp';
}

foreach ($products as $p) {
    $matchScore = 0;
    $pCat = $p['category_name'] ?? '';
    $pPrice = (int)$p['price'];

    if ($targetCat !== '' && $pCat === $targetCat) {
        $matchScore += 3;
    }

    if ($budget > 0) {
        if ($pPrice <= $budget) {
            $matchScore += 2;
        } elseif ($pPrice <= $budget * 1.25) {
            $matchScore += 1;
        }
    } else {
        $matchScore += 1;
    }

    if ($matchScore > 0) {
        $p['match_score'] = $matchScore;
        $matchedProducts[] = $p;
    }
}

// Sắp xếp theo điểm tương thích cao nhất
usort($matchedProducts, fn($a, $b) => ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0));

// Chọn top 2-3 sản phẩm tối ưu
$recommended = array_slice($matchedProducts ?: $products, 0, 3);

// 3. TẠO LỜI TƯ VẤN CHUYÊN GIA HOA BLOOMORA
$occasionLabel = $occasion ?: 'dịp đặc biệt';
$recipientLabel = $recipient ?: 'người thân yêu';
$budgetLabel = $budget > 0 ? (number_format($budget, 0, ',', '.') . '₫') : 'phù hợp';

$topProduct = $recommended[0];
$topPriceFormatted = number_format((int)$topProduct['price'], 0, ',', '.') . '₫';

$consultAdvice = "Chào bạn! Với mong muốn tặng **{$recipientLabel}** nhân dịp **{$occasionLabel}** (ngân sách tầm {$budgetLabel}), Bloomora gợi ý mẫu **{$topProduct['name']}** (giá niêm yết {$topPriceFormatted}).\n\n"
               . "✨ **Lý do chọn:** {$topProduct['description']}. Mẫu này được florist tại Buôn Ma Thuột tuyển chọn hoa tươi mới trong ngày, cam kết chụp ảnh hoa thật (Photo Proof) gửi bạn duyệt trước khi giao hàng trong 60-90 phút!";

// 4. LƯU LỊCH SỬ VÀO BẢNG AI_CONVERSATIONS
if ($db) {
    try {
        $suggestedIds = implode(',', array_column($recommended, 'id'));
        $stmtLog = $db->prepare("
            INSERT INTO ai_conversations (session_id, feature_type, occasion, recipient, budget, prompt_input, ai_response, suggested_product_ids, ip_address)
            VALUES (?, 'chatbot_consult', ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtLog->execute([
            $sessionId,
            $occasion,
            $recipient,
            $budget ?: null,
            $userMessage ?: "Dịp: $occasion, Người nhận: $recipient, Ngân sách: $budget",
            $consultAdvice,
            $suggestedIds,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (PDOException $e) {
        // Ghi log lỗi nhẹ nhàng không làm crash response
    }
}

// 5. TRẢ KẾT QUẢ JSON CHO CLIENT
echo json_encode([
    'success' => true,
    'session_id' => $sessionId,
    'message' => $consultAdvice,
    'products' => $recommended,
    'criteria' => [
        'occasion' => $occasion,
        'recipient' => $recipient,
        'budget' => $budget
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
