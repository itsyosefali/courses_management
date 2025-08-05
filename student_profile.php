<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_student()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كطالب للوصول لهذه الصفحة', 'error');
}

$user = get_current_user_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    if (empty($full_name)) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح';
    }
    
    if (!empty($email) && $email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
    }
    
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
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $success = $stmt->execute([$full_name, $email, $phone, $hashed_password, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $success = $stmt->execute([$full_name, $email, $phone, $_SESSION['user_id']]);
            }
            
            if ($success) {
                redirect_with_message('student_profile.php', 'تم تحديث الملف الشخصي بنجاح!', 'success');
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث الملف الشخصي';
            }
        } catch (Exception $e) {
            error_log("Error updating student profile: " . $e->getMessage());
            $errors[] = 'حدث خطأ في النظام';
        }
    }
}

$user = get_current_user_data();

try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT e.course_id) as total_courses,
            COUNT(e.id) as total_enrollments,
            SUM(c.price) as total_spent
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student_stats = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching student stats: " . $e->getMessage());
    $student_stats = ['total_courses' => 0, 'total_enrollments' => 0, 'total_spent' => 0];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($user['full_name'] ?? 'طالب'); ?></title>
    <link rel="stylesheet" href="Student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .profile-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        .error-message {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .success-message {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
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
            مرحباً، <?php echo htmlspecialchars($user['full_name'] ?? 'طالب'); ?>
        </span>
        <a href="auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            تسجيل خروج
        </a>
    </div>
</header>

<div class="section">
    <div class="profile-container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <a href="Student.php" class="btn btn-secondary" style="text-decoration: none;">
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
            <div class="error-message">
                <h4 style="margin: 0 0 0.5rem 0;">أخطاء:</h4>
                <ul style="margin: 0; padding-right: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $student_stats['total_courses'] ?? 0; ?></div>
                <div class="stat-label">الدورات المسجلة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $student_stats['total_enrollments'] ?? 0; ?></div>
                <div class="stat-label">إجمالي التسجيلات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($user['wallet_balance'] ?? 0, 2); ?> د.ل</div>
                <div class="stat-label">رصيد المحفظة</div>
            </div>
        </div>

        <div class="profile-form">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3 style="margin: 0; color: #333;"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></h3>
                <p style="margin: 0.5rem 0 0 0; color: #666;">طالب</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name" class="form-label">
                        <i class="fas fa-user"></i>
                        الاسم الكامل *
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        البريد الإلكتروني *
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i>
                        رقم الهاتف
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                           class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-calendar-alt"></i>
                        تاريخ الانضمام
                    </label>
                    <input type="text" value="<?php echo format_date($user['created_at'] ?? '', 'Y-m-d'); ?>" 
                           class="form-input" readonly style="background: #f9f9f9;">
                </div>

                <hr style="margin: 2rem 0; border: none; border-top: 1px solid #eee;">

                <h4 style="margin-bottom: 1rem; color: #333;">
                    <i class="fas fa-lock"></i>
                    تغيير كلمة المرور
                </h4>

                <div class="form-group">
                    <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password" 
                           class="form-input"
                           placeholder="اتركها فارغة إذا لم ترد تغيير كلمة المرور">
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="form-input"
                           placeholder="6 أحرف على الأقل">
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           class="form-input"
                           placeholder="أعد إدخال كلمة المرور الجديدة">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        حفظ التغييرات
                    </button>
                    <a href="Student.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html> 