<?php
// Bloomora Florist - Điểm truy cập cho XAMPP Apache / PHP Server
// Tự động load giao diện chính index.html với header UTF-8 chuẩn
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
include_once __DIR__ . '/index.html';
?>

