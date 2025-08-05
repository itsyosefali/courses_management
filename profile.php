<?php
require_once 'includes/functions.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is logged in and is a teacher
if (!is_logged_in() || !is_teacher()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كمعلم للوصول لهذه الصفحة', 'error');
}

$user = get_current_user_data();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    // Check if email is already taken by another user
    if (!empty($email) && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'كلمة المرور الحالية مطلوبة لتغيير كلمة المرور';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'كلمة المرور الجديدة غير متطابقة';
        }
    }
    
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                // Update with password change
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
                $success = $stmt->execute([$full_name, $email, $hashed_password, $_SESSION['user_id']]);
            } else {
                // Update without password change
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $success = $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
            }
            
            if ($success) {
                redirect_with_message('profile.php', 'تم تحديث الملف الشخصي بنجاح!', 'success');
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث الملف الشخصي';
            }
        } catch (Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            $errors[] = 'حدث خطأ في النظام';
        }
    }
}

// Get updated user data
$user = get_current_user_data();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="logo">
        <div class="name2" dir="ltr">
            <span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span>
            <span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span>
        </div>
        <div class="name">ARG</div>
    </div>

    <div class="user-menu">
        <span>
            <i class="fas fa-user-circle"></i>
            مرحباً، <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?>
        </span>
        <a href="auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            تسجيل خروج
        </a>
    </div>
</header>

<div class="section">
    <div class="dashboard-container">
        <div class="main-content">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <a href="teacher.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-arrow-right"></i>
                    العودة للوحة التحكم
                </a>
                <h2 style="margin: 0; color: #333;">
                    <i class="fas fa-user-cog"></i>
                    الملف الشخصي
                </h2>
            </div>

            <?php echo display_message(); ?>
            
            <?php if (!empty($errors)): ?>
                <div style="background: #fee; border: 1px solid #fcc; color: #c33; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h4 style="margin: 0 0 0.5rem 0;">أخطاء:</h4>
                    <ul style="margin: 0; padding-right: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <form method="POST" action="">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2.5rem; color: white;">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 style="margin: 0; color: #333;"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h3>
                        <p style="margin: 0.5rem 0 0 0; color: #666;">معلم</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="full_name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-user"></i>
                            الاسم الكامل *
                        </label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-envelope"></i>
                            البريد الإلكتروني *
                        </label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-calendar-alt"></i>
                            تاريخ الانضمام
                        </label>
                        <input type="text" value="<?php echo format_date($user['created_at'] ?? '', 'Y-m-d'); ?>" 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; background: #f9f9f9;" readonly>
                    </div>

                    <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">

                    <h4 style="margin-bottom: 1rem; color: #333;">
                        <i class="fas fa-lock"></i>
                        تغيير كلمة المرور
                    </h4>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="current_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            كلمة المرور الحالية
                        </label>
                        <input type="password" id="current_password" name="current_password" 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="اتركها فارغة إذا لم ترد تغيير كلمة المرور">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="new_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            كلمة المرور الجديدة
                        </label>
                        <input type="password" id="new_password" name="new_password" 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="6 أحرف على الأقل">
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label for="confirm_password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            تأكيد كلمة المرور الجديدة
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="أعد إدخال كلمة المرور الجديدة">
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
                            <i class="fas fa-save"></i>
                            حفظ التغييرات
                        </button>
                        <a href="teacher.php" class="btn btn-secondary" style="padding: 0.75rem 2rem; font-size: 1rem; text-decoration: none;">
                            <i class="fas fa-times"></i>
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html> 