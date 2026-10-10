-- ========================================================
-- قاعدة بيانات مشروع عصملي - أبو سير 1854 (OSMANLI 1854)
-- Database Schema for Osmanli Restaurant Menu Management
-- Includes: Users, Menu Items, Multiple Sizes & Add-ons
-- ========================================================

CREATE DATABASE IF NOT EXISTS `osmanli_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `osmanli_db`;

-- 1. جدول المستخدمين والمشرفين (Users / Admins Table)
DROP TABLE IF EXISTS `item_variants`;
DROP TABLE IF EXISTS `item_addons`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@osmanli.ps', '$2y$10$xaHaRMX7qaCldTPjcJf2ee2PYGN.NI.1FeeDCNykre.k8HQ.28Ta.', 'admin');

-- 2. جدول أصناف المنيو (Menu Items Table)
CREATE TABLE `menu_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `category` VARCHAR(100) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` (`id`, `name`, `description`, `price`, `category`, `image_url`, `is_available`) VALUES
(1, 'كنافة ناعمة على الفحم', 'عجينة ناعمة مخبوزة على الفحم', 7.00, 'كنافة', 'images/knafeh_charcoal.jpg', 1),
(2, 'كنافة محيرة على الفحم / إسطنبولية', '', 8.00, 'كنافة', 'images/knafeh-mohayara-icecream.jpg', 1),
(3, 'كنافة مبرومة بالجبنة', 'كنافة مبرومة محشوة بالجبنة', 42.00, 'كنافة', 'images/knafeh-barma-tray.jpg', 1),
(4, 'كنافة بالقشطة', 'عجينة ناعمة / خشنة + قشطة فاخرة.', 8.00, 'كنافة', 'images/knafeh_charcoal.jpg', 1),
(5, 'كنافة دايت', 'عجينة ناعمة / خشنة خفيفة السكر والدهون.', 10.00, 'كنافة', 'images/knafeh_charcoal.jpg', 1),
(6, 'كنافة البيستاشيو', 'عجينة ناعمة / خشنة + زبدة الفستق الحلبي الفاخرة.', 10.00, 'كنافة', 'images/knafeh-mix-flavours.jpg', 1),
(7, 'كنافة النوتيلا', 'عجينة ناعمة / خشنة + نوتيلا أصلية غنية.', 10.00, 'كنافة', 'images/knafeh-mix-flavours.jpg', 1),
(8, 'كنافة اللوتس', 'عجينة ناعمة / خشنة + زبدة اللوتس.', 10.00, 'كنافة', 'images/knafeh-mix-flavours.jpg', 1),
(9, 'اليوزباشي', 'عجينة ناعمة / خشنة + فستق حلبي مطحون + الجبنة / قشطة / بوظة.', 10.00, 'كنافة', 'images/knafeh_charcoal.jpg', 1),
(10, 'عصملية السلطان', 'عجينة فستق حلبي + قشطة + عجينة ناعمة/خشنة.', 13.00, 'كنافة', 'images/osmaliyeh-sultan-green.jpg', 1),
(11, 'عصملية أبو سير', 'فستق حلبي حب + عجينة ناعمة / خشنة + فستق حلبي مطحون + الجبنة / قشطة / بوظة.', 14.00, 'كنافة', 'images/osmaliyeh-abu-seir-stretch.jpg', 1),
(12, 'فطير بالقشطة (شفايف)', 'رقائق مورقة ذهبية محشوة بالقشطة الطازجة والقطر المعطر.', 7.00, 'حلويات شرقية وتركية', 'images/fateer_shafayef.jpg', 1),
(13, 'فطير بالجبنة', 'فطير مورق محشو بالجبنة', 7.00, 'حلويات شرقية وتركية', 'images/fateer_shafayef.jpg', 1),
(14, 'كلاج بالجبنة', 'رولات الكلاج المقرمشة الشهيرة بحشوة الجبنة الفاخرة.', 7.00, 'حلويات شرقية وتركية', 'images/kellaj_cheese.jpg', 1),
(15, 'بسكوت شهرزاد', 'بسكوت مقرمش خفيف محشو بالقشطة ومغطى بالفستق الحلبي.', 7.00, 'حلويات شرقية وتركية', 'images/shahrazad_biscuit.jpg', 1),
(16, 'بين النارين', 'طبقتان من شعيرية الكنافة المحمصة يتوسطهما قلب غني من القشطة الفاخرة.', 7.00, 'حلويات شرقية وتركية', 'images/knafeh_bain_narin.jpg', 1),
(17, 'خدود الست بالقشطة الطبيعية', 'حبات دائرية مورقة بالسمن البلدي محشوة بقشطة طبيعية وفستق.', 7.00, 'حلويات شرقية وتركية', 'images/khodoud_elsett.jpg', 1),
(18, 'حلاوة الجبن', 'لفائف رقائق الجبن والسميد الطرية المحشوة بقشطة فاخرة', 8.00, 'حلويات شرقية وتركية', 'images/halawet_jibn.jpg', 1),
(19, 'مدلوقة بالقشطة', 'عجينة كنافة ناعمة مطبوخة تعلوها قشطة كثيفة ومكسرات محمصة.', 8.00, 'حلويات شرقية وتركية', 'images/madlouqa-plate.jpg', 1),
(20, 'أصابع الفستق الحلبي', 'مبرومة فاخرة محشوة بالفستق الحلبي الأخضر المحمص بدون أي إضافات.', 7.00, 'حلويات شرقية وتركية', 'images/asabea-fustuq-plate.jpg', 1),
(21, 'أصابع الجوز', 'أصابع بقلاوة مقرمشة محشوة بالجوز البلدي المجروش والمتبل بالقرفة.', 7.00, 'حلويات شرقية وتركية', 'images/asabea-jawz-plate.jpg', 1),
(22, 'أصابع قشطة', '', 7.00, 'حلويات شرقية وتركية', 'images/asabea-ashta-plate.jpg', 1),
(23, 'بقلاوة تركية (مثلثات)', 'مثلثات بقلاوة تركية مورقة غنية بالفستق الحلبي والقشطة.', 12.00, 'حلويات شرقية وتركية', 'images/baklava_turkish.jpg', 1),
(24, 'عش البلبل', 'أعشاش كنافة مقرمشة محشوة بالنوتيلا أو الفستق الحلبي.', 1.00, 'حلويات شرقية وتركية', 'images/osh-el-bolbol-chocolate.jpg', 1),
(25, 'تريليتشيا (كيكة الكراميل التركية)', 'كيكة الحليب والكراميل التركية الإسفنجية المنعشة المشربة بثلاثة أنواع حليب.', 10.00, 'حلويات شرقية وتركية', 'images/trilece-cake.jpg', 1),
(26, 'مشكل حلويات فاخرة', 'صواني وبوكسات ضيافة مشكلة مختارة من أرقى أصناف الحلويات الشرقية والتركية.', 85.00, 'حلويات شرقية وتركية', 'images/baklava_turkish.jpg', 1),
(27, 'هريسة بالسمن البلدي', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 25.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 0),
(28, 'برازق شامية بالسمسم والفستق', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 30.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1),
(29, 'غريبة بالسمن البلدي', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 36.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1),
(30, 'غريبة محشية فستق حلبي', 'ضيافة شامية فاخرة بالسمن البلدي (/ للقطعة)', 5.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1),
(31, 'معمول عجوة المدينة الفاخرة', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 40.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1),
(32, 'معمول جوز', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 55.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1),
(33, 'معمول فستق حلبي', 'ضيافة شامية فاخرة بالسمن البلدي (/ 1 كيلو)', 80.00, 'نثريات ومعمول', 'images/shahrazad_biscuit.jpg', 1);

-- 3. جدول الأحجام والأسعار لكل صنف (Item Variants / Prices Table)
CREATE TABLE `item_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `size_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_variant_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `item_variants` (`item_id`, `size_name`, `price`) VALUES
(1, 'XS', 7.00),
(1, 'S', 12.00),
(1, 'M', 20.00),
(1, 'L', 40.00),
(1, 'XL', 60.00),
(1, '2 كغ', 40.00),
(2, 'XS', 8.00),
(2, 'S', 12.00),
(2, 'M', 22.00),
(2, 'L', 42.00),
(2, 'XL', 60.00),
(2, '2 كغ', 40.00),
(3, 'L', 42.00),
(3, 'XL', 60.00),
(3, '2 كغ', 40.00),
(4, 'XS', 8.00),
(4, 'S', 12.00),
(4, 'M', 20.00),
(4, 'L', 40.00),
(4, 'XL', 60.00),
(5, 'XS', 10.00),
(5, 'S', 15.00),
(6, 'XS', 10.00),
(6, 'S', 14.00),
(6, 'M', 30.00),
(6, 'L', 55.00),
(6, 'XL', 75.00),
(7, 'XS', 10.00),
(7, 'S', 14.00),
(7, 'M', 25.00),
(7, 'L', 50.00),
(7, 'XL', 70.00),
(8, 'XS', 10.00),
(8, 'S', 14.00),
(8, 'M', 25.00),
(8, 'L', 50.00),
(8, 'XL', 70.00),
(9, 'XS', 10.00),
(9, 'S', 15.00),
(9, 'M', 27.00),
(9, 'L', 50.00),
(9, 'XL', 75.00),
(9, '2 كغ', 45.00),
(10, 'XS', 13.00),
(10, 'S', 18.00),
(10, 'M', 32.00),
(10, 'L', 65.00),
(11, 'XS', 14.00),
(11, 'S', 20.00),
(11, 'M', 35.00),
(11, 'L', 70.00),
(11, 'XL', 100.00),
(11, '2 كغ', 65.00),
(12, 'شخصي (4 قطع)', 7.00),
(12, '1 كيلو', 34.00),
(12, 'تواصي', 36.00),
(13, 'شخصي (4 قطع)', 7.00),
(13, '1 كيلو', 36.00),
(13, 'تواصي', 40.00),
(14, 'شخصي (قطعة)', 7.00),
(14, '1 كيلو', 40.00),
(15, 'شخصي (4 حبات)', 7.00),
(15, '1 كيلو', 40.00),
(16, 'شخصي (قطعتين)', 7.00),
(16, '1 كيلو', 36.00),
(17, 'شخصي (قطعتين)', 7.00),
(17, '1 كيلو', 50.00),
(18, 'شخصي (4 حبات)', 8.00),
(18, '1 كيلو', 50.00),
(19, 'شخصي (قطعة)', 8.00),
(19, '1 كيلو', 55.00),
(20, 'شخصي (قطعتين)', 7.00),
(20, '1 كيلو', 120.00),
(21, 'شخصي (4 قطع)', 7.00),
(21, '1 كيلو', 40.00),
(22, 'شخصي (قطعتين)', 7.00),
(22, '1 كيلو', 40.00),
(23, 'شخصي (قطعة)', 12.00),
(24, 'شخصي (قطعة)', 1.00),
(25, 'شخصي (قطعة)', 10.00),
(26, '1 كغ مشكل نواشف', 85.00),
(26, '1 كغ مشكل فستق حلبي', 95.00),
(26, '1 كغ مشكل تركي', 120.00),
(27, 'سعر موحد', 25.00),
(28, 'سعر موحد', 30.00),
(29, 'سعر موحد', 36.00),
(30, 'سعر موحد', 5.00),
(31, 'سعر موحد', 40.00),
(32, 'سعر موحد', 55.00),
(33, 'سعر موحد', 80.00);

-- 4. جدول الإضافات الاختيارية (Item Add-ons Table)
CREATE TABLE `item_addons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `item_addons` (`name`, `price`, `is_available`) VALUES
('إضافة قطعة قشطة', 2.00, 1),
('بوظة فانيلا', 2.00, 1),
('بوظة تركية', 5.00, 1),
('إضافة نوتيلا', 3.00, 1);
