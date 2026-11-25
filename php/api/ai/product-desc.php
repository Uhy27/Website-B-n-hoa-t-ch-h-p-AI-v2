<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?: $_POST;

$name = trim((string)($input['name'] ?? ''));
$category = trim((string)($input['category'] ?? 'Hoa Khai Trương'));
$price = (int)($input['price'] ?? 0);

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Tên mẫu hoa không được để trống!']);
    exit;
}

// Heuristic Generator cho mô tả hoa nghệ nhân Bloomora
$desc = "Mẫu hoa **{$name}** thuộc phân loại **{$category}**, được các nghệ nhân florist tại tiệm Bloomora Buôn Ma Thuột thiết kế tỉ mỉ. "
      . "Sản phẩm tuyển chọn những cành hoa tươi mới trong ngày nhập từ Đà Lạt và hoa nhập khẩu cao cấp, kết hợp giấy gói nghệ thuật và ruy-băng vi tính. "
      . "Bloomora cam kết chụp ảnh hoa thật (Photo Proof) gửi khách duyệt trước khi giao hàng trong 60-90 phút.";

echo json_encode([
    'success' => true,
    'description' => $desc
], JSON_UNESCAPED_UNICODE);
