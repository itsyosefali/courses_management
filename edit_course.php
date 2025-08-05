<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_teacher()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كمعلم للوصول لهذه الصفحة', 'error');
}

$user = get_current_user_data();
$course_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch();
    
    if (!$course) {
        redirect_with_message('teacher.php', 'الدورة غير موجودة أو لا تملك صلاحية تعديلها', 'error');
    }
} catch (Exception $e) {
    error_log("Error fetching course: " . $e->getMessage());
    redirect_with_message('teacher.php', 'حدث خطأ في النظام', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $errors = [];
    if (empty($title)) {
        $errors[] = 'عنوان الدورة مطلوب';
    }
    if (empty($description)) {
        $errors[] = 'وصف الدورة مطلوب';
    }
    if ($price < 0) {
        $errors[] = 'السعر يجب أن يكون رقم موجب';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE courses 
                SET title = ?, description = ?, price = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND teacher_id = ?
            ");
            
            if ($stmt->execute([$title, $description, $price, $is_active, $course_id, $_SESSION['user_id']])) {
                redirect_with_message('teacher.php', 'تم تحديث الدورة بنجاح!', 'success');
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث الدورة';
            }
        } catch (Exception $e) {
            error_log("Error updating course: " . $e->getMessage());
            $errors[] = 'حدث خطأ في النظام';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل الدورة - <?php echo htmlspecialchars($course['title'] ?? ''); ?></title>
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
                    <i class="fas fa-edit"></i>
                    تعديل الدورة
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
                    <div style="margin-bottom: 1.5rem;">
                        <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-book"></i>
                            عنوان الدورة *
                        </label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="description" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-align-left"></i>
                            وصف الدورة *
                        </label>
                        <textarea id="description" name="description" required rows="4"
                                  style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; resize: vertical;"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="price" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            <i class="fas fa-money-bill-wave"></i>
                            سعر الدورة (د.ل)
                        </label>
                        <input type="number" id="price" name="price" min="0" step="0.01"
                               value="<?php echo htmlspecialchars($course['price'] ?? '0'); ?>"
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: #333;">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?php echo ($course['is_active'] ?? false) ? 'checked' : ''; ?>
                                   style="width: 18px; height: 18px;">
                            <i class="fas fa-toggle-on"></i>
                            تفعيل الدورة
                        </label>
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