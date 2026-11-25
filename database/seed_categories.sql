-- ===================================================================
-- BLOOMORA 8 CHUẨN DANH MỤC HOA TƯƠI
-- ===================================================================

USE `bloomora_db`;

-- Cập nhật 6 danh mục sẵn có và thêm 2 danh mục mới chuẩn mực
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `image`, `badge`, `display_order`, `is_active`) VALUES
(1, 'Hoa Khai Trương', 'khai-truong', 'Kệ hoa 2 tầng, lẵng hoa nghệ thuật và giỏ hoa để bàn chúc mừng khởi sắc hồng phát, thuận buồm xuôi gió và đại cát đại lợi.', 'ph-trend-up', 'https://images.unsplash.com/photo-1561181286-d3fee7d55364?auto=format&fit=crop&w=800&q=80', 'Tài Lộc', 3, 1),
(2, 'Hoa Sinh Nhật', 'sinh-nhat', 'Những bó hoa và giỏ hoa sinh nhật rực rỡ, tinh tế gửi gắm lời chúc tuổi mới luôn tràn đầy niềm vui, sức sống và may mắn.', 'ph-cake', 'https://images.unsplash.com/photo-1597848212624-a19eb35e2651?auto=format&fit=crop&w=800&q=80', 'Bán Chạy Nhất', 1, 1),
(3, 'Hoa Tình Yêu & Lãng Mạn', 'tinh-yeu', 'Bộ sưu tập hoa hồng đỏ Ecuador, hoa baby và cẩm tú cầu biểu trưng cho tình yêu chân thành, ngọt ngào và bền chặt.', 'ph-heart-straight', 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=800&q=80', 'Lãng Mạn', 2, 1),
(4, 'Giỏ Hoa, Hộp Hoa & Thiết Kế Đặc Biệt', 'thiet-ke-dac-biet', 'Các tác phẩm giỏ gỗ vintage, hộp hoa trái tim kèm quà tặng, chậu lan hồ điệp hoàng gia được các nghệ nhân cắm hoa thủ công.', 'ph-gift', 'https://images.unsplash.com/photo-1582794543139-8ac9cb0f7b11?auto=format&fit=crop&w=800&q=80', 'Thiết Kế VIP', 8, 1),
(5, 'Hoa Tốt Nghiệp & Tri Ân', 'tot-nghiep', 'Các mẫu bó hoa hướng dương, cúc họa mi tươi tắn chúc mừng cột mốc rạng ngời và mở ra tương lai xán lạn.', 'ph-student', 'https://images.unsplash.com/photo-1508615039623-a25605d2b022?auto=format&fit=crop&w=800&q=80', 'Tươi Sáng', 4, 1),
(6, 'Hoa Tang Lễ & Chia Buồn', 'chia-buon', 'Vòng hoa và kệ hoa chia buồn trang nghiêm, trang trọng gửi lời tiễn biệt và chia sẻ nỗi mất mát cùng gia quyến.', 'ph-flower-lotus', 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80', 'Thành Kính', 6, 1),
(7, 'Hoa Kỷ Niệm & Ngày Hạnh Phúc', 'ky-niem', 'Kỷ niệm ngày cưới, ngày hẹn hò hay dấu mốc trọng đại với những đóa hoa tinh khôi lưu giữ ký ức yêu thương.', 'ph-calendar-heart', 'https://images.unsplash.com/photo-1533616688419-b7a585564566?auto=format&fit=crop&w=800&q=80', 'Ý Nghĩa', 5, 1),
(8, 'Hoa Theo Mùa & Dịp Lễ Hội', 'hoa-theo-mua', 'Hoa cúc họa mi Hà Nội đầu đông, tulip mùa xuân, hoa mẫu đơn nhập khẩu và những thiết kế giới hạn theo mùa.', 'ph-sun-dim', 'https://images.unsplash.com/photo-1526047932273-341f2a7631f9?auto=format&fit=crop&w=800&q=80', 'Mới Về', 7, 1)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `slug` = VALUES(`slug`),
    `description` = VALUES(`description`),
    `icon` = VALUES(`icon`),
    `image` = VALUES(`image`),
    `badge` = VALUES(`badge`),
    `display_order` = VALUES(`display_order`),
    `is_active` = VALUES(`is_active`);
