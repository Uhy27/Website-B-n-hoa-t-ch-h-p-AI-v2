<?php
/**
 * Fix ALL double-encoded UTF-8 in ALL tables using MySQL's native BINARY conversion.
 * This is more reliable than PHP iconv for Vietnamese characters.
 * 
 * Strategy: Use CONVERT(BINARY CONVERT(col USING latin1) USING utf8mb4)
 * This undoes double-encoding by first treating the UTF-8 bytes as latin1, 
 * then re-interpreting as UTF-8.
 */
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Found " . count($tables) . " tables.\n\n";

$totalFixed = 0;

foreach ($tables as $table) {
    $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $textCols = [];
    $pkCol = null;
    
    foreach ($cols as $col) {
        $type = strtolower($col['Type']);
        if ($col['Key'] === 'PRI') {
            $pkCol = $col['Field'];
        }
        if (strpos($type, 'varchar') !== false || strpos($type, 'text') !== false || 
            strpos($type, 'char(') !== false) {
            $textCols[] = $col['Field'];
        }
    }
    
    if (empty($textCols) || $pkCol === null) continue;

    // For each text column, run up to 3 passes of double-encoding fix
    $tableFixed = 0;
    
    for ($pass = 1; $pass <= 3; $pass++) {
        $passFixed = 0;
        
        foreach ($textCols as $col) {
            // Check if any rows have double-encoded data in this column
            // Double-encoded UTF-8 Vietnamese always contains these byte sequences
            // Ã¡, Ã , Ã©, Ãª, etc. which are the mojibake signatures
            $checkSql = "SELECT COUNT(*) FROM `$table` WHERE `$col` LIKE '%Ã%' OR `$col` LIKE '%Æ°%' OR `$col` LIKE '%áº%' OR `$col` LIKE '%Ã³%' OR `$col` LIKE '%Ä%'";
            
            try {
                $count = (int)$pdo->query($checkSql)->fetchColumn();
            } catch (Throwable $e) {
                continue;
            }
            
            if ($count === 0) continue;
            
            // Use MySQL native conversion to fix double-encoding
            // CONVERT(BINARY CONVERT(col USING latin1) USING utf8mb4)
            $updateSql = "UPDATE `$table` SET `$col` = CONVERT(BINARY CONVERT(`$col` USING latin1) USING utf8mb4) WHERE `$col` LIKE '%Ã%' OR `$col` LIKE '%Æ°%' OR `$col` LIKE '%áº%' OR `$col` LIKE '%Ã³%' OR `$col` LIKE '%Ä%'";
            
            try {
                $affected = $pdo->exec($updateSql);
                if ($affected > 0) {
                    $passFixed += $affected;
                }
            } catch (Throwable $e) {
                echo "  ⚠️ Error fixing `{$table}`.`{$col}`: " . $e->getMessage() . "\n";
            }
        }
        
        if ($passFixed > 0) {
            echo "  Pass {$pass}: Fixed {$passFixed} cells in `{$table}`\n";
            $tableFixed += $passFixed;
        } else {
            break; // No more fixes needed
        }
    }
    
    if ($tableFixed > 0) {
        echo "✅ Total fixed in `{$table}`: {$tableFixed}\n\n";
        $totalFixed += $tableFixed;
    }
}

echo "\n=== GRAND TOTAL: Fixed {$totalFixed} cells across all tables ===\n";

// === COMPREHENSIVE VERIFICATION ===
echo "\n\n========== VERIFICATION ==========\n";

echo "\n--- categories ---\n";
$r = $pdo->query("SELECT id, name, badge FROM categories ORDER BY id");
foreach ($r->fetchAll() as $row) {
    echo "  [{$row['id']}] {$row['name']} | badge: {$row['badge']}\n";
}

echo "\n--- users (staff) ---\n";
$r = $pdo->query("SELECT id, full_name, email, phone FROM users ORDER BY id");
foreach ($r->fetchAll() as $row) {
    echo "  [{$row['id']}] {$row['full_name']} | {$row['email']}\n";
}

echo "\n--- store_settings ---\n";
$r = $pdo->query("SELECT key_name, key_value, description FROM store_settings ORDER BY key_name");
foreach ($r->fetchAll() as $row) {
    echo "  {$row['key_name']}: {$row['key_value']}\n    desc: {$row['description']}\n";
}

echo "\n--- reviews (sample) ---\n";
try {
    // Get column names first
    $cols = $pdo->query("SHOW COLUMNS FROM reviews")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Columns: " . implode(', ', $cols) . "\n";
    $r = $pdo->query("SELECT * FROM reviews ORDER BY id LIMIT 3");
    foreach ($r->fetchAll() as $row) {
        // Find text content columns
        foreach ($row as $k => $v) {
            if (is_string($v) && mb_strlen($v) > 20 && $k !== 'password' && $k !== 'password_hash') {
                echo "  [{$row['id']}] {$k}: " . mb_substr($v, 0, 100, 'UTF-8') . "\n";
            }
        }
    }
} catch (Throwable $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}

echo "\n--- products (sample 5) ---\n";
$r = $pdo->query("SELECT id, name, primary_flower, color_name FROM products ORDER BY id LIMIT 5");
foreach ($r->fetchAll() as $row) {
    echo "  [{$row['id']}] {$row['name']} | {$row['primary_flower']} | {$row['color_name']}\n";
}

echo "\n--- orders (sample) ---\n";
try {
    $r = $pdo->query("SELECT * FROM orders ORDER BY id LIMIT 3");
    foreach ($r->fetchAll() as $row) {
        $addr = $row['shipping_address'] ?? $row['address'] ?? '?';
        $name = $row['customer_name'] ?? $row['recipient_name'] ?? '?';
        echo "  [{$row['id']}] {$name} | {$addr}\n";
    }
} catch (Throwable $e) {
    echo "  Error: " . $e->getMessage() . "\n";
}
