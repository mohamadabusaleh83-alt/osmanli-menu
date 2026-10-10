<?php
/**
 * ====================================================================
 * Project: OSMANLI - ABU SEIR 1854 (عصملي) | Digital Restaurant Menu
 * Developer: Mohamad Abu Saleh
 * Phone / WhatsApp: +970598600230 (https://wa.me/970598600230)
 * Email: mohamadabusaleh83@gmail.com
 * LinkedIn: https://www.linkedin.com/in/mohamad-abu-saleh-598255350
 * ====================================================================
 * 
 * index.php - الواجهة العامة لمنيو عصملي - أبو سير 1854
 */

require_once __DIR__ . '/db.php';

$db_items = [];
$db_variants = [];
$db_addons = [];
$db_load_error = null;

try {
    $pdo = getDBConnection();
    // جلب الأصناف المتوفرة فقط (is_available = 1)
    $stmt = $pdo->query("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category ASC, id ASC");
    $db_items = $stmt->fetchAll();

    // جلب الأحجام والأسعار لكل صنف
    $varStmt = $pdo->query("SELECT * FROM item_variants ORDER BY item_id ASC, price ASC, id ASC");
    $raw_variants = $varStmt->fetchAll();
    foreach ($raw_variants as $v) {
        $db_variants[$v['item_id']][] = $v;
    }

    // جلب الإضافات المتوفرة (is_available = 1)
    $addStmt = $pdo->query("SELECT * FROM item_addons WHERE is_available = 1 ORDER BY id ASC");
    $db_addons = $addStmt->fetchAll();
} catch (Exception $e) {
    $db_load_error = $e->getMessage();
}

// تجميع الأصناف حسب الأقسام
$grouped_menu = [];
foreach ($db_items as $item) {
    $cat = $item['category'];
    if (!isset($grouped_menu[$cat])) {
        $grouped_menu[$cat] = [];
    }
    $grouped_menu[$cat][] = $item;
}

// أيقونات الأقسام الافتراضية
$category_icons = [
    'كنافة' => 'fa-fire-burner',
    'حلويات شرقية وتركية' => 'fa-gem',
    'نثريات ومعمول' => 'fa-box-archive',
    'مشروبات' => 'fa-bottle-water',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>عصملي - OSMANLI (ABU SEIR 1854) | منيو الحلويات والكنافة - نابلس</title>
  
  <!-- Meta & Branding -->
  <meta name="description" content="عصملي - OSMANLI (ABU SEIR 1854) - منيو الكنافة والحلويات الشامية والتراثية - نابلس - رفيديا - مقابل الأكاديمية">
  <meta name="theme-color" content="#0e2a22">

  <!-- Google Fonts: Cairo, Amiri, Cinzel -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,700;1,400&family=Cairo:wght@300;400;600;700;800;900&family=Cinzel:wght@700;800;900&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <link rel="stylesheet" href="style.css">
  <style>
    .admin-access-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(212, 175, 55, 0.15);
      border: 1px solid rgba(212, 175, 55, 0.4);
      color: var(--color-gold-bright);
      padding: 5px 12px;
      border-radius: 20px;
      text-decoration: none;
      font-size: 0.82rem;
      transition: all 0.2s;
    }
    .admin-access-badge:hover {
      background: var(--color-gold);
      color: #071712;
    }
    .db-warning-banner {
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid #dc3545;
      color: #ff8b94;
      padding: 15px;
      border-radius: 12px;
      margin: 20px auto;
      max-width: 900px;
      text-align: center;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

  <!-- Top Announcement Bar -->
  <aside class="top-announcement" aria-label="معلومات المحل والدوام">
    <div class="container announcement-content">
      <div class="announcement-left">
        <span><i class="fa-solid fa-crown gold-icon"></i> <strong>عصملي - OSMANLI (ABU SEIR 1854)</strong></span>
        <span class="location-badge"><i class="fa-solid fa-location-dot"></i> نابلس - رفيديا - مقابل الأكاديمية</span>
      </div>
      <div class="announcement-right">
        <span class="hours-badge"><i class="fa-regular fa-clock"></i> 11:00 صباحاً – 12:00 منتصف الليل</span>
        <a href="tel:0598001608" class="contact-pill" title="اتصال هاتفي مباشر"><i class="fa-solid fa-phone"></i> <span dir="ltr">0598001608</span></a>
        <a href="https://wa.me/970598001608" target="_blank" rel="noopener noreferrer" class="contact-pill whatsapp-pill" title="محادثة واتساب"><i class="fa-brands fa-whatsapp"></i> واتساب</a>
      </div>
    </div>
  </aside>

  <!-- Main Header -->
  <header class="main-header" role="banner">
    <div class="container header-container">
      <div class="brand">
        <div class="brand-logo-icon">
          <i class="fa-solid fa-crown"></i>
        </div>
        <div class="brand-text">
          <div class="brand-title-wrap">
            <h1 class="brand-title">OSMANLI</h1>
            <span class="brand-arabic-badge">عَصْمَلِي</span>
          </div>
          <span class="brand-since">ABU SEIR 1854</span>
          <span class="brand-subtitle"><i class="fa-solid fa-location-dot gold-icon"></i> نابلس - رفيديا - مقابل الأكاديمية</span>
        </div>
      </div>

      <div class="header-actions">
        <a href="https://www.instagram.com/osmanlipal/" target="_blank" rel="noopener noreferrer" class="btn-insta-header" title="تابعنا على إنستغرام @osmanlipal">
          <i class="fa-brands fa-instagram"></i>
          <span class="btn-text-desktop">@osmanlipal</span>
        </a>
        <a href="tel:0598001608" class="btn-call-header" title="اتصال هاتفي">
          <i class="fa-solid fa-phone"></i>
          <span class="btn-text-desktop">اتصال</span>
        </a>
      </div>
    </div>
  </header>

  <!-- Section 2: Heritage Hero Banner (Owner Spotlight) -->
  <section class="owner-spotlight-hero" aria-label="خبرة تتناقلها الأجيال">
    <div class="container">
      <div class="spotlight-frame">
        <div class="spotlight-image-container">
          <img src="images/owner_hero.jpg" alt="عصملي أبو سير 1854 - خبرة تتناقلها الأجيال" class="spotlight-img">
        </div>
        <div class="spotlight-content">
          <span class="spotlight-badge"><i class="fa-solid fa-crown gold-icon"></i> أصالة تمتد لأكثر من 170 عاماً</span>
          <h2 class="spotlight-title">خبرة تتناقلها الأجيال</h2>
          <p class="spotlight-quote">"من 1854 والحكاية مكملة... وصفات حافظت على أصالتها"</p>
          <div class="spotlight-meta">
            <span><i class="fa-solid fa-location-dot gold-icon"></i> نابلس - رفيديا - مقابل الأكاديمية</span>
            <span class="dot">•</span>
            <span><i class="fa-regular fa-clock gold-icon"></i> 11:00 ص – 12:00 منتصف الليل</span>
            <span class="dot">•</span>
            <span><i class="fa-solid fa-phone gold-icon"></i> <a href="tel:0598001608" dir="ltr">0598001608</a></span>
          </div>
        </div>
      </div>

      <!-- Heritage Story Parchment Card -->
      <div class="heritage-parchment-card" id="heritage-story">
        <div class="parchment-inner">
          <div class="parchment-header">
            <i class="fa-solid fa-scroll parchment-icon"></i>
            <div>
              <span class="parchment-tag">وثيقة تاريخية عريقة • نابلس 1854</span>
              <h3 class="parchment-title">في يوم التراث الفلسطيني... تاريخ الكنافة النابلسية من 1854</h3>
            </div>
          </div>
          <div class="parchment-body">
            <p>
              تعود حكاية الكنافة وصناعة الحلويات لدى <strong>عائلة أبو سير</strong> إلى عام <strong>1854م</strong> في حارات نابلس القديمة (جبل النار)، حيث تناقلت الأجيال أسرار الصنعة على جمر الفحم البلدي، واستخدام السمن البلدي الطبيعي والجبنة النابلسية المعتقة وشعيرية العصملي الذهبية.
            </p>
            <p class="parchment-highlight">
              واليوم في <strong>"عصملي - أبو سير 1854" (رفيديا - مقابل الأكاديمية)</strong>، تستمر الحكاية بذات الوصفات التراثية التي حافظت على نكهتها الأصلية دون أي مساومة على الجودة.
            </p>
          </div>
          <div class="parchment-footer">
            <span><i class="fa-solid fa-award gold-icon"></i> توارث الحرفة أباً عن جد منذ أكثر من قرن ونصف</span>
            <span class="parchment-signature">عصملي - أبو سير 1854</span>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- تنبيه في حال تعذر الاتصال بالقاعدة -->
  <?php if ($db_load_error): ?>
    <div class="container">
      <div class="db-warning-banner">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <strong>تنبيه:</strong> تعذر جلب أحدث بيانات المنيو من قاعدة البيانات.<br>
        <small><?= htmlspecialchars($db_load_error) ?></small><br>
        <a href="setup.php" style="color: #fff; text-decoration: underline; margin-top: 5px; display: inline-block;">انقر هنا لتشغيل سكريبت التهيئة setup.php</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Sticky Categories Navigation -->
  <?php if (!empty($grouped_menu)): ?>
    <nav class="categories-nav-wrapper" id="sticky-categories-nav" aria-label="أقسام المنيو">
      <div class="container">
        <ul class="categories-list">
          <?php $idx = 0; foreach ($grouped_menu as $category_name => $cat_items): $idx++; ?>
            <?php 
              $icon = $category_icons[$category_name] ?? 'fa-utensils';
              $section_id = 'cat-section-' . md5($category_name);
            ?>
            <li>
              <a href="#<?= $section_id ?>" class="category-nav-link <?= $idx === 1 ? 'active' : '' ?>">
                <i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($category_name) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </nav>
  <?php endif; ?>

  <!-- Main Menu Showcase (Dynamic from MySQL) -->
  <main class="menu-main-content">
    <div class="container">

      <?php if (empty($grouped_menu) && !$db_load_error): ?>
        <div style="text-align: center; padding: 60px 20px; color: #b3c2be;">
          <i class="fa-solid fa-store-slash" style="font-size: 3rem; color: var(--color-gold); margin-bottom: 15px;"></i>
          <h3>المنيو قيد التحديث حالياً</h3>
          <p>يرجى مراجعة إدارة المطعم لتفعيل الأصناف.</p>
        </div>
      <?php else: ?>

        <?php foreach ($grouped_menu as $category_name => $items): ?>
          <?php 
            $icon = $category_icons[$category_name] ?? 'fa-utensils';
            $section_id = 'cat-section-' . md5($category_name);
            $count = count($items);
          ?>
          <section id="<?= $section_id ?>" class="menu-section">
            <div class="section-header">
              <div class="section-title-wrap">
                <i class="fa-solid <?= $icon ?> section-icon"></i>
                <div>
                  <h2 class="section-title"><?= htmlspecialchars($category_name) ?></h2>
                  <span class="section-subtitle">تشكيلة عصملي أبو سير 1854 الفاخرة</span>
                </div>
              </div>
              <span class="section-counter"><?= $count ?> صنفاً</span>
            </div>

            <div class="menu-grid">
              <?php foreach ($items as $item): ?>
                <?php $variants = $db_variants[$item['id']] ?? []; ?>
                <article class="menu-card">
                  <div class="card-media-wrapper">
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="card-img" loading="lazy" onerror="this.src='images/knafeh_charcoal.jpg'">
                    <span class="card-badge"><i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($category_name) ?></span>
                  </div>
                  <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($item['name']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars($item['description'] ?: 'محضر طازجاً على الأصول التراثية بالسمن البلدي والقطر المعطر.') ?></p>
                    
                    <?php if (count($variants) > 1): ?>
                      <!-- متعدد الأحجام والأسعار (Multiple Sizes & Dynamic Pricing) -->
                      <div class="dynamic-card-pricing" data-item-id="<?= $item['id'] ?>">
                        <div class="pricing-top-row">
                          <span class="pricing-label"><i class="fa-solid fa-tags"></i> السعر:</span>
                          <div class="selected-price-display" title="السعر الحالي للحجم المحدد">
                            <span class="price-val" id="price-display-<?= $item['id'] ?>"><?= number_format($variants[0]['price'], 0) ?></span>
                            <span class="price-curr">₪</span>
                          </div>
                        </div>
                        <div class="size-chips-wrapper" role="radiogroup" aria-label="أحجام <?= htmlspecialchars($item['name']) ?>">
                          <?php foreach ($variants as $v_idx => $v): ?>
                            <button type="button" 
                                    class="size-choice-chip <?= $v_idx === 0 ? 'active' : '' ?>" 
                                    data-price="<?= number_format($v['price'], 0) ?>" 
                                    data-target="price-display-<?= $item['id'] ?>"
                                    title="<?= htmlspecialchars($v['size_name']) ?>: <?= number_format($v['price'], 0) ?> ₪"
                                    aria-pressed="<?= $v_idx === 0 ? 'true' : 'false' ?>">
                              <span class="chip-size-title"><?= htmlspecialchars($v['size_name']) ?></span>
                              <span class="chip-size-cost"><?= number_format($v['price'], 0) ?> ₪</span>
                            </button>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php elseif (count($variants) === 1): ?>
                      <!-- حجم وسعر واحد محدد (Single Variant) -->
                      <div class="pricing-container">
                        <span class="pricing-label"><i class="fa-solid fa-tag"></i> السعر:</span>
                        <div class="pricing-grid">
                          <div class="price-pill">
                            <span class="size"><?= htmlspecialchars($variants[0]['size_name']) ?></span>
                            <span class="price"><?= number_format($variants[0]['price'], 0) ?> ₪</span>
                          </div>
                        </div>
                      </div>
                    <?php else: ?>
                      <!-- احتياطي: السعر الافتراضي من الصنف -->
                      <div class="pricing-container">
                        <span class="pricing-label"><i class="fa-solid fa-tag"></i> السعر:</span>
                        <div class="pricing-grid">
                          <div class="price-pill">
                            <span class="size">شخصي / طلب</span>
                            <span class="price"><?= number_format($item['price'], 0) ?> ₪</span>
                          </div>
                        </div>
                      </div>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <?php if (mb_strpos($category_name, 'كنافة') !== false && !empty($db_addons)): ?>
              <?php $addons_rendered = true; ?>
              <!-- بطاقة إضافات الكنافة والحلويات الاختيارية -->
              <div class="addons-card" id="addons-section">
                <div class="addons-title">
                  <i class="fa-solid fa-ice-cream gold-icon" style="font-size: 1.4rem;"></i>
                  <h3>إضافات الكنافة والحلويات (اختيارية)</h3>
                </div>
                <div class="addons-content">
                  <p>أضف لمستك الخاصة لمذاق الكنافة الأصيل مع إضافاتنا الطازجة والفاخرة:</p>
                  <div class="addons-grid">
                    <?php foreach ($db_addons as $addon): ?>
                      <div class="addon-chip">
                        <span class="addon-name"><i class="fa-solid fa-plus gold-icon"></i> <?= htmlspecialchars($addon['name']) ?></span>
                        <span class="addon-price"><?= number_format($addon['price'], 0) ?> ₪</span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (empty($addons_rendered) && !empty($db_addons)): ?>
        <!-- بطاقة إضافات الكنافة والحلويات الاختيارية -->
        <div class="addons-card" id="addons-section" style="margin-top: 2rem;">
          <div class="addons-title">
            <i class="fa-solid fa-ice-cream gold-icon" style="font-size: 1.4rem;"></i>
            <h3>إضافات الكنافة والحلويات (اختيارية)</h3>
          </div>
          <div class="addons-content">
            <p>أضف لمستك الخاصة لمذاق الكنافة الأصيل مع إضافاتنا الطازجة والفاخرة:</p>
            <div class="addons-grid">
              <?php foreach ($db_addons as $addon): ?>
                <div class="addon-chip">
                  <span class="addon-name"><i class="fa-solid fa-plus gold-icon"></i> <?= htmlspecialchars($addon['name']) ?></span>
                  <span class="addon-price"><?= number_format($addon['price'], 0) ?> ₪</span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- Instagram & Social Showcase Banner -->
  <section class="instagram-banner" id="instagram-section" aria-label="متابعة إنستغرام">
    <div class="container">
      <div class="instagram-card">
        <div class="instagram-info">
          <div class="insta-badge">
            <i class="fa-brands fa-instagram"></i>
            <span>حسابنا الرسمي الموثق</span>
          </div>
          <h2 class="instagram-title">تابع أحدث صواني الكنافة والحلويات على إنستغرام</h2>
          <p class="instagram-desc">
            فيديوهات سحب الجبنة الساخنة، صواني الفحم المباشرة، ويوميات عصملي أبو سير 1854 في رفيديا. شاركنا لحظاتك الحلوة عبر وسم <strong>#عصملي_1854</strong>
          </p>
          <div class="instagram-actions">
            <a href="https://www.instagram.com/osmanlipal/" target="_blank" rel="noopener noreferrer" class="btn-instagram-cta">
              <i class="fa-brands fa-instagram"></i>
              <span>تابعنا على إنستغرام (@osmanlipal)</span>
            </a>
            <a href="https://wa.me/970598001608" target="_blank" rel="noopener noreferrer" class="btn-whatsapp-cta">
              <i class="fa-brands fa-whatsapp"></i>
              <span>تواصل عبر الواتساب (<span dir="ltr">0598001608</span>)</span>
            </a>
          </div>
        </div>

        <div class="instagram-qr-block">
          <div class="insta-qr-frame">
            <div class="insta-qr-header">
              <i class="fa-brands fa-instagram"></i>
              <span>امسح بكاميرا هاتفك</span>
            </div>
            <div class="insta-qr-image-wrapper">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=https://www.instagram.com/osmanlipal/" onerror="this.src='insta_qr.png'" alt="رمز QR لحساب إنستغرام @osmanlipal" class="insta-qr-img" width="160" height="160" loading="lazy">
            </div>
            <div class="insta-qr-footer">
              <strong>@osmanlipal</strong>
              <small>نابلس - رفيديا - مقابل الأكاديمية</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="main-footer">
    <div class="container footer-content">
      <div class="footer-brand">
        <div class="footer-brand-header">
          <h3>OSMANLI</h3>
          <span class="footer-brand-badge">عَصْمَلِي</span>
        </div>
        <p class="footer-since">ABU SEIR 1854 • نابلس</p>
        <p class="footer-quote">"خبرة تتناقلها الأجيال... من 1854 والحكاية مكملة"</p>
        <p class="footer-location"><i class="fa-solid fa-location-dot gold-icon"></i> <strong>نابلس - رفيديا - مقابل الأكاديمية</strong></p>
        <div style="margin-top: 15px;">
          <a href="login.php" class="admin-access-badge" title="بوابة إدارة المنيو للمشرفين">
            <i class="fa-solid fa-lock"></i>
            <span>لوحة تحكم المشرفين</span>
          </a>
        </div>
      </div>

      <div class="footer-contact">
        <h4>الدوام ومعلومات التواصل</h4>
        <p><i class="fa-regular fa-clock gold-icon"></i> <strong>ساعات الدوام:</strong> 11:00 صباحاً – 12:00 منتصف الليل <span dir="ltr">(11:00 AM - 12:00 AM)</span></p>
        <p><i class="fa-solid fa-phone gold-icon"></i> <strong>الاتصال المباشر:</strong> <a href="tel:0598001608" class="footer-link" dir="ltr">0598001608</a></p>
        <p><i class="fa-brands fa-whatsapp gold-icon"></i> <strong>الواتساب:</strong> <a href="https://wa.me/970598001608" target="_blank" rel="noopener noreferrer" class="footer-link" dir="ltr">0598001608</a></p>
        <p><i class="fa-brands fa-instagram gold-icon"></i> <strong>إنستغرام:</strong> <a href="https://www.instagram.com/osmanlipal/" target="_blank" rel="noopener noreferrer" class="footer-link" dir="ltr">@osmanlipal</a></p>
      </div>

      <div class="footer-qr-block">
        <h4>امسح الرمز لتصفح المنيو من هاتفك</h4>
        <div class="menu-qr-card">
          <div class="menu-qr-img-wrapper">
            <a href="https://osmanli-abu-seir-1854.surge.sh" target="_blank" rel="noopener noreferrer" title="فتح منيو عصملي على هاتفك">
              <img src="images/menu_qr_code.png" alt="رمز QR لمنيو عصملي - أبو سير 1854" class="menu-qr-img" width="140" height="140" loading="lazy">
            </a>
          </div>
          <div class="menu-qr-info">
            <span class="menu-qr-tag"><i class="fa-solid fa-mobile-screen"></i> منيو رقمي سريع</span>
            <a href="https://osmanli-abu-seir-1854.surge.sh" target="_blank" rel="noopener noreferrer" class="menu-qr-url" dir="ltr">osmanli-abu-seir-1854.surge.sh</a>
            <p class="menu-qr-sub"><i class="fa-solid fa-qrcode gold-icon"></i> مخصص لطاولات المطعم (Table Tents)</p>
            <a href="images/menu_qr_code.png" download="osmanli_menu_qr.png" class="btn-download-qr" title="تحميل صورة الباركود بدقة عالية لطباعة الاستاندات">
              <i class="fa-solid fa-download"></i> تحميل الرمز للطباعة
            </a>
          </div>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <p>© 2026 جميع الحقوق محفوظة لـ <strong>عصملي - OSMANLI (ABU SEIR 1854)</strong> • نابلس - رفيديا - مقابل الأكاديمية</p>
        <div class="developer-signature">
          <span class="dev-text">
            تم التطوير بواسطة 
            <a href="https://www.linkedin.com/in/mohamad-abu-saleh-598255350" target="_blank" rel="noopener noreferrer" class="dev-link" title="حساب LinkedIn للمطور محمد أبو صالح">
              <i class="fa-brands fa-linkedin"></i> محمد أبو صالح
            </a>
            <span class="dev-sep">|</span>
            Developed by 
            <a href="https://www.linkedin.com/in/mohamad-abu-saleh-598255350" target="_blank" rel="noopener noreferrer" class="dev-link" title="LinkedIn Profile: Mohamad Abu Saleh">
              Mohamad Abu Saleh
            </a>
          </span>
          <a href="https://wa.me/970598600230" target="_blank" rel="noopener noreferrer" class="dev-whatsapp-btn" title="تواصل مباشر عبر واتساب مع المطور">
            <i class="fa-brands fa-whatsapp"></i>
            <span>واتساب</span>
          </a>
        </div>
      </div>
    </div>
  </footer>

  <script src="app.js"></script>
</body>
</html>
