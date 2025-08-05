<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_teacher()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كمعلم للوصول لهذه الصفحة', 'error');
}

$user = get_current_user_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $course_id = $_POST['course_id'] ?? null;
        $is_important = isset($_POST['is_important']) ? 1 : 0;
        
        $errors = [];
        if (empty($title)) {
            $errors[] = 'عنوان الإعلان مطلوب';
        }
        if (empty($content)) {
            $errors[] = 'محتوى الإعلان مطلوب';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO announcements (title, content, course_id, teacher_id, is_important, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                if ($stmt->execute([$title, $content, $course_id, $_SESSION['user_id'], $is_important])) {
                    redirect_with_message('announcements.php', 'تم إنشاء الإعلان بنجاح!', 'success');
                } else {
                    $errors[] = 'حدث خطأ أثناء إنشاء الإعلان';
                }
            } catch (Exception $e) {
                error_log("Error creating announcement: " . $e->getMessage());
                $errors[] = 'حدث خطأ في النظام';
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['announcement_id'])) {
        $announcement_id = $_POST['announcement_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND teacher_id = ?");
            if ($stmt->execute([$announcement_id, $_SESSION['user_id']])) {
                redirect_with_message('announcements.php', 'تم حذف الإعلان بنجاح!', 'success');
            }
        } catch (Exception $e) {
            error_log("Error deleting announcement: " . $e->getMessage());
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, c.title as course_title 
        FROM announcements a 
        LEFT JOIN courses c ON a.course_id = c.id 
        WHERE a.teacher_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching announcements: " . $e->getMessage());
    $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإعلانات - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
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
                    <i class="fas fa-bullhorn"></i>
                    إدارة الإعلانات
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

            <div class="card" style="margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; color: #333;">
                    <i class="fas fa-plus-circle"></i>
                    إنشاء إعلان جديد
                </h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    
                    <div style="margin-bottom: 1rem;">
                        <label for="title" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            عنوان الإعلان *
                        </label>
                        <input type="text" id="title" name="title" required 
                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;"
                               placeholder="أدخل عنوان الإعلان">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label for="content" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            محتوى الإعلان *
                        </label>
                        <textarea id="content" name="content" required rows="4"
                                  style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; resize: vertical;"
                                  placeholder="أدخل محتوى الإعلان"></textarea>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label for="course_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">
                            الدورة (اختياري)
                        </label>
                        <select id="course_id" name="course_id" 
                                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                            <option value="">إعلان عام</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; color: #333;">
                            <input type="checkbox" name="is_important" value="1" style="width: 18px; height: 18px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            إعلان مهم
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        نشر الإعلان
                    </button>
                </form>
            </div>

            <div class="card">
                <h3 style="margin: 0 0 1rem 0; color: #333;">
                    <i class="fas fa-list"></i>
                    الإعلانات المنشورة
                </h3>

                <?php if (!empty($announcements)): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($announcements as $announcement): ?>
                            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem; background: <?php echo ($announcement['is_important'] ?? false) ? '#fff3cd' : '#fff'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                    <h4 style="margin: 0; color: #333; font-size: 1.1rem;">
                                        <?php if ($announcement['is_important'] ?? false): ?>
                                            <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-left: 0.5rem;"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($announcement['title'] ?? ''); ?>
                                    </h4>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                        <button type="submit" onclick="return confirm('هل أنت متأكد من حذف هذا الإعلان؟')" 
                                                style="background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                
                                <p style="margin: 0 0 0.5rem 0; color: #666; line-height: 1.5;">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'] ?? '')); ?>
                                </p>
                                
                                <div style="display: flex; gap: 1rem; font-size: 0.8rem; color: #999;">
                                    <span>
                                        <i class="fas fa-calendar"></i>
                                        <?php echo format_date($announcement['created_at'] ?? '', 'Y-m-d H:i'); ?>
                                    </span>
                                    <?php if ($announcement['course_title']): ?>
                                        <span>
                                            <i class="fas fa-book"></i>
                                            <?php echo htmlspecialchars($announcement['course_title']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span>
                                            <i class="fas fa-globe"></i>
                                            إعلان عام
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📢</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">لا توجد إعلانات</h4>
                        <p style="margin: 0; color: #999;">ابدأ بإنشاء إعلانك الأول</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html> 