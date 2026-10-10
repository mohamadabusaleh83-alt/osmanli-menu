<?php
/**
 * login.php - صفحة تسجيل دخول المشرفين
 * Osmanli Menu Project (1854) - Admin Authentication Page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إذا كان المستخدم مسجلاً بالفعل كأدمن، يتم تحويله مباشرة للوحة التحكم
if (isset($_SESSION['user_id']) && !empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin_menu.php");
    exit;
}

require_once __DIR__ . '/db.php';

$error = '';
$info = '';

// التحقق من الرسائل القادمة في الرابط
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'auth_required') {
        $error = 'يرجى تسجيل الدخول أولاً بصلاحية مسؤول للوصول إلى لوحة التحكم.';
    } elseif ($_GET['msg'] === 'logged_out') {
        $info = 'تم تسجيل الخروج بنجاح. أهلاً بك مجدداً!';
    }
}

// معالجة إرسال النموذج (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // البحث عن المستخدم بواسطة اسم المستخدم أو البريد الإلكتروني
            $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE (username = :u1 OR email = :u2) LIMIT 1");
            $stmt->execute([':u1' => $login_input, ':u2' => $login_input]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['role'] === 'admin') {
                    // تجديد معرّف الجلسة لمنع هجمات Session Fixation
                    session_regenerate_id(true);

                    // تخزين بيانات الجلسة كما هو مطلوب
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in_time'] = time();

                    // التوجيه إلى الصفحة المطلوبة سابقاً أو لوحة التحكم
                    $redirect = $_SESSION['redirect_after_login'] ?? 'admin_menu.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    header("Location: " . $redirect);
                    exit;
                } else {
                    $error = 'الحساب لا يمتلك صلاحيات المشرفين (Admin).';
                }
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة. يرجى المحاولة مرة أخرى.';
            }
        } catch (Exception $e) {
            $error = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل دخول المشرفين | عَصْمَلِي - أبو سير 1854</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: radial-gradient(circle at center, #0e2a22 0%, #071712 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Tajawal', sans-serif;
      margin: 0;
      padding: 20px;
      color: #fff;
    }
    .login-container {
      width: 100%;
      max-width: 440px;
      background: linear-gradient(165deg, rgba(14, 42, 34, 0.95), rgba(7, 23, 18, 0.98));
      border: 2px solid var(--color-gold, #d4af37);
      border-radius: 20px;
      padding: 35px 30px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 25px rgba(212, 175, 55, 0.2);
      backdrop-filter: blur(10px);
      position: relative;
      overflow: hidden;
    }
    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, transparent, #d4af37, #f7e7be, #d4af37, transparent);
    }
    .brand-logo-area {
      text-align: center;
      margin-bottom: 25px;
    }
    .brand-badge {
      display: inline-block;
      background: #d4af37;
      color: #071712;
      font-weight: 800;
      font-size: 0.9rem;
      padding: 3px 12px;
      border-radius: 20px;
      margin-bottom: 8px;
    }
    .brand-title {
      font-family: 'Cinzel', serif;
      font-size: 2rem;
      color: #f7e7be;
      margin: 0;
      letter-spacing: 2px;
    }
    .brand-subtitle {
      font-family: 'Amiri', serif;
      color: #d4af37;
      font-size: 1.15rem;
      margin-top: 3px;
    }
    .login-heading {
      text-align: center;
      font-size: 1.1rem;
      color: #b2c2bc;
      margin-bottom: 25px;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(212, 175, 55, 0.2);
    }
    .form-group {
      margin-bottom: 20px;
      text-align: right;
    }
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-size: 0.92rem;
      color: #f7e7be;
      font-weight: 600;
    }
    .input-wrapper {
      position: relative;
    }
    .input-icon {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #d4af37;
      font-size: 1rem;
    }
    .form-control {
      width: 100%;
      background: rgba(4, 13, 10, 0.8);
      border: 1px solid rgba(212, 175, 55, 0.4);
      color: #fff;
      padding: 12px 42px 12px 40px;
      border-radius: 10px;
      font-size: 0.95rem;
      font-family: inherit;
      box-sizing: border-box;
      transition: all 0.3s ease;
    }
    .form-control:focus {
      outline: none;
      border-color: #d4af37;
      box-shadow: 0 0 12px rgba(212, 175, 55, 0.35);
      background: rgba(4, 13, 10, 0.95);
    }
    .password-toggle {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #8c9e97;
      cursor: pointer;
      font-size: 0.95rem;
      background: none;
      border: none;
      padding: 0;
    }
    .password-toggle:hover {
      color: #d4af37;
    }
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #d4af37 0%, #c69214 100%);
      color: #071712;
      border: none;
      padding: 14px;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 800;
      cursor: pointer;
      font-family: inherit;
      transition: all 0.3s ease;
      box-shadow: 0 6px 20px rgba(198, 146, 20, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 10px;
    }
    .btn-submit:hover {
      background: linear-gradient(135deg, #f0c952 0%, #d4af37 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
    }
    .alert {
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 0.92rem;
      display: flex;
      align-items: center;
      gap: 10px;
      line-height: 1.4;
    }
    .alert-danger {
      background: rgba(220, 53, 69, 0.2);
      border: 1px solid rgba(220, 53, 69, 0.6);
      color: #ff8b94;
    }
    .alert-info {
      background: rgba(25, 135, 84, 0.2);
      border: 1px solid rgba(25, 135, 84, 0.6);
      color: #8ce3b3;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 25px;
      color: #d4af37;
      text-decoration: none;
      font-size: 0.92rem;
      transition: color 0.2s;
    }
    .back-link:hover {
      color: #f7e7be;
      text-decoration: underline;
    }
    .default-creds-hint {
      margin-top: 20px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px dashed rgba(212, 175, 55, 0.3);
      padding: 10px;
      border-radius: 8px;
      font-size: 0.8rem;
      color: #8c9e97;
      text-align: center;
    }
    .default-creds-hint strong {
      color: #d4af37;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="brand-logo-area">
      <span class="brand-badge"><i class="fa-solid fa-crown"></i> الإدارة العليا</span>
      <h1 class="brand-title">OSMANLI</h1>
      <div class="brand-subtitle">عَصْمَلِي • أبو سير 1854</div>
    </div>

    <div class="login-heading">
      <i class="fa-solid fa-lock" style="color: #d4af37; margin-left: 6px;"></i>
      بوابة تسجيل الدخول للمشرفين
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
      <div class="alert alert-info">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($info) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
      <div class="form-group">
        <label class="form-label" for="username">اسم المستخدم أو البريد الإلكتروني</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-user input-icon"></i>
          <input type="text" id="username" name="username" class="form-control" placeholder="admin أو admin@osmanli.ps" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">كلمة المرور</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-key input-icon"></i>
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
          <button type="button" class="password-toggle" id="togglePassword" title="إظهار/إخفاء كلمة المرور">
            <i class="fa-regular fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <span>دخول إلى لوحة التحكم</span>
        <i class="fa-solid fa-arrow-left"></i>
      </button>
    </form>

    <div class="default-creds-hint">
      🔑 <strong>الحساب الافتراضي للمسؤول:</strong><br>
      اسم المستخدم: <code>admin</code> | كلمة المرور: <code>admin123</code>
    </div>

    <a href="index.php" class="back-link">
      <i class="fa-solid fa-store" style="margin-left: 5px;"></i>
      العودة إلى المنيو العام للزبائن
    </a>
  </div>

  <script>
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    if (toggleBtn && pwdInput) {
      toggleBtn.addEventListener('click', function() {
        const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
        pwdInput.setAttribute('type', type);
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
      });
    }
  </script>
</body>
</html>
