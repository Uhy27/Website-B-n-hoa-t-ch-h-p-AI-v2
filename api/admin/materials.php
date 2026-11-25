<?php
declare(strict_types=1);

/**
 * BLOOMORA ADMIN 2.0 - QUẢN LÝ KHO NGUYÊN VẬT LIỆU HOA TƯƠI & PHỤ LIỆU (MATERIALS API)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
require_admin_auth();

try {
    $pdo = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $category = trim((string)($_GET['category'] ?? ''));
        $search = trim((string)($_GET['search'] ?? ''));
        $lowStockOnly = ($_GET['low_stock_only'] ?? '0') === '1';

        $where = [];
        $params = [];

        if ($category !== '' && $category !== 'all') {
            $where[] = 'category = :cat';
            $params[':cat'] = $category;
        }

        if ($lowStockOnly) {
            $where[] = 'stock_quantity <= min_stock_alert';
        }

        if ($search !== '') {
            $where[] = '(name LIKE :s1 OR sku LIKE :s2 OR supplier LIKE :s3)';
            $params[':s1'] = "%$search%";
            $params[':s2'] = "%$search%";
            $params[':s3'] = "%$search%";
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Tóm tắt tồn kho nguyên liệu
        $summary = $pdo->query("
            SELECT 
                COUNT(*) AS total_items,
                SUM(CASE WHEN stock_quantity <= min_stock_alert THEN 1 ELSE 0 END) AS low_stock_count,
                COALESCE(SUM(stock_quantity * cost_per_unit), 0) AS total_valuation
            FROM materials
        ")->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM materials $whereSql ORDER BY (stock_quantity <= min_stock_alert) DESC, category ASC, id DESC");
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success' => true,
            'summary' => $summary,
            'data' => $items
        ]);
    }

    if ($method === 'POST') {
        $in = get_json_input();
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        foreach (['name', 'unit', 'cost_per_unit'] as $f) {
            if (!isset($in[$f]) || trim((string)$in[$f]) === '') {
                json_response(['success' => false, 'message' => "Trường '$f' là bắt buộc."], 400);
            }
        }

        $sku = trim((string)($in['sku'] ?? ''));
        if ($sku === '') {
            $sku = 'MAT-' . strtoupper(substr(uniqid(), -6));
        }

        // Kiểm tra trùng SKU
        $chk = $pdo->prepare('SELECT COUNT(*) FROM materials WHERE sku = :sku');
        $chk->execute([':sku' => $sku]);
        if ((int)$chk->fetchColumn() > 0) {
            json_response(['success' => false, 'message' => 'Mã SKU nguyên liệu đã tồn tại.'], 409);
        }

        $qty = max(0, (int)($in['stock_quantity'] ?? 0));
        $minAlert = max(1, (int)($in['min_stock_alert'] ?? 10));
        $status = $qty <= 0 ? 'out_of_stock' : ($qty <= $minAlert ? 'low_stock' : 'in_stock');

        $stmt = $pdo->prepare("
            INSERT INTO materials (sku, name, category, unit, stock_quantity, min_stock_alert, cost_per_unit, supplier, status)
            VALUES (:sku, :name, :cat, :unit, :qty, :min, :cost, :supp, :st)
        ");
        $stmt->execute([
            ':sku' => $sku,
            ':name' => trim((string)$in['name']),
            ':cat' => $in['category'] ?? 'flower_fresh',
            ':unit' => trim((string)$in['unit']),
            ':qty' => $qty,
            ':min' => $minAlert,
            ':cost' => (float)$in['cost_per_unit'],
            ':supp' => trim((string)($in['supplier'] ?? '')),
            ':st' => $status
        ]);
        $newId = (int)$pdo->lastInsertId();

        log_activity($adminId, 'create_material', 'materials', $newId, "Thêm mới nguyên vật liệu: {$in['name']} (SKU: $sku, SL: $qty {$in['unit']})");

        json_response(['success' => true, 'message' => 'Thêm nguyên vật liệu mới thành công.', 'data' => ['id' => $newId]], 201);
    }

    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID nguyên vật liệu.'], 400);
        }

        $chk = $pdo->prepare('SELECT * FROM materials WHERE id = :id');
        $chk->execute([':id' => $id]);
        $mat = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$mat) {
            json_response(['success' => false, 'message' => 'Nguyên vật liệu không tồn tại.'], 404);
        }

        $in = get_json_input();
        $adminId = (int)($_SESSION['user_id'] ?? 0);

        // Trường hợp nhập thêm kho hoặc xuất hỏng hao hụt
        if (isset($in['adjust_quantity'])) {
            $adj = (int)$in['adjust_quantity'];
            $reason = trim((string)($in['reason'] ?? 'Điều chỉnh tồn kho'));
            $newQty = max(0, (int)$mat['stock_quantity'] + $adj);
            $newStatus = $newQty <= 0 ? 'out_of_stock' : ($newQty <= (int)$mat['min_stock_alert'] ? 'low_stock' : 'in_stock');

            $pdo->prepare("UPDATE materials SET stock_quantity = :q, status = :st WHERE id = :id")->execute([
                ':q' => $newQty,
                ':st' => $newStatus,
                ':id' => $id
            ]);

            log_activity($adminId, 'adjust_material_stock', 'materials', $id, "Điều chỉnh SL vật tư {$mat['name']}: " . ($adj > 0 ? "+$adj" : "$adj") . " {$mat['unit']} (Mới: $newQty). Lý do: $reason");

            json_response(['success' => true, 'message' => "Đã cập nhật tồn kho vật tư {$mat['name']} thành $newQty {$mat['unit']}."]);
        }

        // Cập nhật thông tin chi tiết
        $allowed = ['name', 'category', 'unit', 'stock_quantity', 'min_stock_alert', 'cost_per_unit', 'supplier'];
        $sets = [];
        $params = [':id' => $id];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $in)) {
                $sets[] = "`$f` = :$f";
                $params[":$f"] = $in[$f];
            }
        }

        if (empty($sets)) {
            json_response(['success' => false, 'message' => 'Không có dữ liệu hợp lệ cần cập nhật.'], 400);
        }

        // Tự động tính lại status
        $targetQty = isset($in['stock_quantity']) ? (int)$in['stock_quantity'] : (int)$mat['stock_quantity'];
        $targetMin = isset($in['min_stock_alert']) ? (int)$in['min_stock_alert'] : (int)$mat['min_stock_alert'];
        $newStatus = $targetQty <= 0 ? 'out_of_stock' : ($targetQty <= $targetMin ? 'low_stock' : 'in_stock');
        $sets[] = "`status` = :auto_status";
        $params[':auto_status'] = $newStatus;

        $pdo->prepare('UPDATE materials SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);

        log_activity($adminId, 'update_material', 'materials', $id, "Cập nhật thông tin vật liệu: {$mat['name']}");

        json_response(['success' => true, 'message' => 'Cập nhật nguyên vật liệu thành công.']);
    }

    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['success' => false, 'message' => 'Thiếu ID nguyên vật liệu.'], 400);
        }
        $pdo->prepare('DELETE FROM materials WHERE id = :id')->execute([':id' => $id]);
        json_response(['success' => true, 'message' => 'Đã xóa nguyên vật liệu.']);
    }

    json_response(['success' => false, 'message' => 'Phương thức HTTP không được hỗ trợ.'], 405);
} catch (Throwable $e) {
    error_log('[Admin Materials Error] ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Lỗi hệ thống quản lý nguyên vật liệu.'], 500);
}
