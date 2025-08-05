<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_teacher()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كمعلم للوصول لهذه الصفحة', 'error');
}

try {
    $user = get_current_user_data();
    if (!$user) {
        session_destroy();
        redirect_with_message('login.html', 'حدث خطأ في الجلسة. يرجى تسجيل الدخول مرة أخرى', 'error');
    }
    
    $teacher_courses = get_teacher_courses($_SESSION['user_id']);

    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.full_name, u.email, u.created_at
        FROM users u
        JOIN enrollments e ON u.id = e.student_id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND u.user_type = 'student'
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $students = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as author_name
        FROM announcements a 
        LEFT JOIN users u ON a.author_id = u.id 
        WHERE a.author_id = ? 
        ORDER BY a.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_announcements = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error in teacher.php: " . $e->getMessage());
    $teacher_courses = [];
    $students = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المعلم - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
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

    <div class="InputContainer">
        <input placeholder="بحث..." id="input" class="input" name="text" type="text" dir="rtl">
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
            <?php echo display_message(); ?>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <a href="create_course.php" class="btn btn-primary" style="text-decoration: none;">
                    <i class="fas fa-plus"></i>
                    إنشاء دورة جديدة
                </a>
                <a href="announcements.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-bullhorn"></i>
                    إدارة الإعلانات
                </a>
                <a href="manage_courses.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-book"></i>
                    إدارة الدورات
                </a>
                <a href="manage_students.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-users"></i>
                    إدارة الطلاب
                </a>
                <a href="analytics.php" class="btn btn-secondary" style="text-decoration: none;">
                    <i class="fas fa-chart-bar"></i>
                    التقارير والإحصائيات
                </a>
            </div>
            
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($teacher_courses); ?></div>
                        <div class="stat-label">الدورات</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($students); ?></div>
                        <div class="stat-label">الطلاب</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count(array_filter($teacher_courses, function($course) { return $course['is_active'] ?? false; })); ?></div>
                        <div class="stat-label">النشطة</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php 
                            $total_revenue = array_sum(array_map(function($course) { 
                                return ($course['price'] ?? 0) * ($course['enrolled_students'] ?? 0); 
                            }, $teacher_courses));
                            echo number_format($total_revenue, 0);
                        ?></div>
                        <div class="stat-label">د.ل الإيرادات</div>
                    </div>
                </div>
            </div>

            <div class="courses-section">
                <h3>دوراتي</h3>
                <?php if (!empty($teacher_courses)): ?>
                    <div class="courses-grid">
                        <?php foreach ($teacher_courses as $course): ?>
                            <div class="course-card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <h4><?php echo htmlspecialchars($course['title'] ?? ''); ?></h4>
                                    <span style="background: <?php echo ($course['is_active'] ?? false) ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                                        <?php echo ($course['is_active'] ?? false) ? 'نشط' : 'غير نشط'; ?>
                                    </span>
                                </div>
                                <p><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                                <div class="course-stats">
                                    <span>
                                        <i class="fas fa-users"></i>
                                        <?php echo $course['enrolled_students'] ?? 0; ?> طالب
                                    </span>
                                    <span>
                                        <i class="fas fa-money-bill-wave"></i>
                                        <?php echo number_format($course['price'] ?? 0, 0); ?> د.ل
                                    </span>
                                </div>
                                <div class="course-actions">
                                    <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-edit"></i>
                                        تعديل
                                    </a>
                                    <a href="course_students.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i>
                                        عرض الطلاب
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-courses">
                                        <div style="font-size: 3rem; margin-bottom: 0.5rem;">📚</div>
                    <h3>لم تنشئ أي دورات بعد</h3>
                    <p>ابدأ بإنشاء دورتك الأولى</p>
                        <a href="create_course.php" class="btn btn-primary" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i>
                            إنشاء دورة جديدة
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="right-sidebar">
        <div class="card">
            <div class="id">
                <img src="image/Teacher.png" alt="Teacher Profile" onerror="this.src='https://via.placeholder.com/80x80/667eea/ffffff?text=👨‍🏫'">
                <h4>معلوماتي الشخصية</h4>

                <div class="InputContainer2">
                    <label class="input2">
                        <i class="fas fa-user"></i>
                        الاسم: <?php echo htmlspecialchars($user['full_name'] ?? ''); ?>
                    </label>
                </div>
                <div class="InputContainer2">
                    <label class="input2">
                        <i class="fas fa-envelope"></i>
                        البريد الإلكتروني: <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                    </label>
                </div>
                <div class="InputContainer2">
                    <label class="input2">
                        <i class="fas fa-calendar-alt"></i>
                        تاريخ الانضمام: <?php echo format_date($user['created_at'] ?? '', 'Y-m-d'); ?>
                    </label>
                </div>
                <div class="InputContainer2">
                    <label class="input2">
                        <i class="fas fa-users"></i>
                        عدد الطلبة: <?php echo count($students); ?>
                    </label>
                </div>
            </div>
            <button class="cart-button" onclick="window.location.href='profile.php'">
                <i class="fas fa-edit"></i>
                <span>تعديل الملف الشخصي</span>
            </button>
        </div>

        <div class="card">
            <h2>
                <i class="fas fa-bullhorn"></i>
                الإعلانات الأخيرة
            </h2>
            <?php if (!empty($recent_announcements)): ?>
                <?php foreach ($recent_announcements as $announcement): ?>
                    <div class="student" style="margin-bottom: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                            <div style="flex: 1;">
                                <div style="font-weight: 500; color: #333; margin-bottom: 0.25rem;">
                                    <?php if ($announcement['is_important'] ?? false): ?>
                                        <i class="fas fa-exclamation-triangle" style="color: #ffc107; margin-left: 0.25rem;"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($announcement['title'] ?? ''); ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #666; line-height: 1.3;">
                                    <?php echo htmlspecialchars(substr($announcement['content'] ?? '', 0, 60)) . (strlen($announcement['content'] ?? '') > 60 ? '...' : ''); ?>
                                </div>
                                <div style="font-size: 0.6rem; color: #999; margin-top: 0.25rem;">
                                    <?php echo format_date($announcement['created_at'] ?? '', 'Y-m-d'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button class="cart-button" onclick="window.location.href='announcements.php'">
                    <i class="fas fa-eye"></i>
                    <span>عرض جميع الإعلانات</span>
                </button>
            <?php else: ?>
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">📢</div>
                    <p style="color: #666; margin-bottom: 0.5rem;">لا توجد إعلانات</p>
                    <p style="color: #999; font-size: 0.8rem;">ابدأ بإنشاء إعلانك الأول</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>
                <i class="fas fa-user-graduate"></i>
                طلابي
            </h2>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $student): ?>
                    <div class="student">
                        <i class="fas fa-user-circle"></i> 
                        <span><?php echo htmlspecialchars($student['full_name'] ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>
                <button class="cart-button" onclick="window.location.href='manage_students.php'">
                    <i class="fas fa-eye"></i>
                    <span>عرض جميع الطلاب</span>
                </button>
            <?php else: ?>
                <div style="text-align: center; padding: 1rem;">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
                    <p style="color: #666; margin-bottom: 0.5rem;">لا يوجد طلاب مسجلين</p>
                    <p style="color: #999; font-size: 0.8rem;">ابدأ بإنشاء دورات</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.sideBar-content a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage || 
            (currentPage === '' && link.getAttribute('href') === 'index.html')) {
            link.classList.add('active');
        }
    });
    
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    const courseCards = document.querySelectorAll('.course-card');
    courseCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});

document.getElementById('input').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const courseCards = document.querySelectorAll('.course-card');
    
    courseCards.forEach(card => {
        const title = card.querySelector('h4').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

</body>
</html> 