<?php
/**
 * migrate.php - ترحيل قاعدة البيانات وإضافة جداول الأحجام والإضافات
 * Osmanli Menu Project (1854) - Migration for Multiple Sizes & Add-ons
 */

require_once __DIR__ . '/db.php';

try {
    $pdo = getDBConnection();

    // 1. إنشاء جدول الأحجام والأسعار (item_variants)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `item_variants` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `item_id` INT NOT NULL,
      `size_name` VARCHAR(100) NOT NULL,
      `price` DECIMAL(10, 2) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_variant_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. إنشاء جدول الإضافات (item_addons)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `item_addons` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(150) NOT NULL,
      `price` DECIMAL(10, 2) NOT NULL,
      `is_available` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "[1/4] تم إنشاء جداول item_variants و item_addons بنجاح.\n";

    // 3. قراءة بيانات الأحجام التراثية من variants_from_html.json
    $variants_file = __DIR__ . '/variants_from_html.json';
    $known_variants = [];
    if (file_exists($variants_file)) {
        $data = json_decode(file_get_contents($variants_file), true);
        foreach ($data as $entry) {
            $known_variants[$entry['title']] = $entry['prices'];
        }
    }

    // 4. جلب جميع الأصناف الحالية في menu_items
    $items = $pdo->query("SELECT id, name, price FROM menu_items")->fetchAll();
    $inserted_variants = 0;

    $stmt_insert_var = $pdo->prepare("INSERT INTO item_variants (item_id, size_name, price) VALUES (?, ?, ?)");
    $stmt_check_var = $pdo->prepare("SELECT COUNT(*) FROM item_variants WHERE item_id = ?");

    foreach ($items as $item) {
        $stmt_check_var->execute([$item['id']]);
        $existing_count = $stmt_check_var->fetchColumn();

        if ($existing_count == 0) {
            $name = $item['name'];
            if (isset($known_variants[$name]) && !empty($known_variants[$name])) {
                foreach ($known_variants[$name] as $v) {
                    $stmt_insert_var->execute([$item['id'], $v['size'], $v['price']]);
                    $inserted_variants++;
                }
            } else {
                // حجم وسعر موحد افتراضي
                $stmt_insert_var->execute([$item['id'], 'سعر موحد', $item['price']]);
                $inserted_variants++;
            }
        }
    }

    echo "[2/4] تم ترحيل وربط {$inserted_variants} حجماً وسعراً للأصناف الحالية.\n";

    // 5. زرع الإضافات الافتراضية
    $addons_count = $pdo->query("SELECT COUNT(*) FROM item_addons")->fetchColumn();
    if ($addons_count == 0) {
        $default_addons = [
            ['إضافة قطعة قشطة', 2.00],
            ['بوظة فانيلا', 2.00],
            ['بوظة تركية', 5.00],
            ['إضافة نوتيلا', 3.00],
        ];

        $stmt_addon = $pdo->prepare("INSERT INTO item_addons (name, price, is_available) VALUES (?, ?, 1)");
        foreach ($default_addons as $addon) {
            $stmt_addon->execute([$addon[0], $addon[1]]);
        }
        echo "[3/4] تم زرع الإضافات الاختيارية (بوظة، قشطة، نوتيلا).\n";
    } else {
        echo "[3/4] جدول الإضافات يحتوي بالفعل على {$addons_count} إضافات.\n";
    }

    // 6. التحقق النهائي
    $total_variants = $pdo->query("SELECT COUNT(*) FROM item_variants")->fetchColumn();
    $total_addons = $pdo->query("SELECT COUNT(*) FROM item_addons")->fetchColumn();

    echo "[4/4] اكتمل الترحيل بنجاح!\n";
    echo "       - إجمالي الأصناف: " . count($items) . "\n";
    echo "       - إجمالي الأحجام والأسعار: {$total_variants}\n";
    echo "       - إجمالي الإضافات: {$total_addons}\n";

} catch (Exception $e) {
    echo "خطأ أثناء الترحيل: " . $e->getMessage() . "\n";
    exit(1);
}
