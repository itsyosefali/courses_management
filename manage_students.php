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

// Get all students enrolled in teacher's courses
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            u.id, 
            u.full_name, 
            u.email, 
            u.created_at,
            COUNT(DISTINCT e.course_id) as enrolled_courses,
            MAX(e.enrolled_at) as last_enrollment
        FROM users u
        JOIN enrollments e ON u.id = e.student_id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND u.user_type = 'student'
        GROUP BY u.id, u.full_name, u.email, u.created_at
        ORDER BY last_enrollment DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $students = [];
}

// Get teacher's courses for filtering
try {
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلاب - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
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
                    <i class="fas fa-users-cog"></i>
                    إدارة الطلاب
                </h2>
            </div>

            <?php echo display_message(); ?>

            <!-- Statistics -->
            <div class="stats-container" style="margin-bottom: 1.5rem;">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($students); ?></div>
                        <div class="stat-label">إجمالي الطلاب</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($courses); ?></div>
                        <div class="stat-label">الدورات</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                                $total_enrollments = array_sum(array_column($students, 'enrolled_courses'));
                                echo $total_enrollments;
                            ?>
                        </div>
                        <div class="stat-label">إجمالي التسجيلات</div>
                    </div>
                </div>
            </div>

            <!-- Students List -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; color: #333;">
                        <i class="fas fa-user-graduate"></i>
                        قائمة الطلاب
                    </h3>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="searchInput" placeholder="بحث في الطلاب..." 
                               style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem;">
                    </div>
                </div>

                <?php if (!empty($students)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">#</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">الاسم</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">البريد الإلكتروني</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">الدورات المسجلة</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">تاريخ التسجيل</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">آخر انضمام</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <?php foreach ($students as $index => $student): ?>
                                    <tr class="student-row" style="border-bottom: 1px solid #dee2e6; transition: background-color 0.2s;">
                                        <td style="padding: 0.75rem; text-align: center; color: #666;"><?php echo $index + 1; ?></td>
                                        <td style="padding: 0.75rem; font-weight: 500; color: #333;">
                                            <i class="fas fa-user-circle" style="margin-left: 0.5rem; color: #667eea;"></i>
                                            <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <i class="fas fa-envelope" style="margin-left: 0.5rem; color: #28a745;"></i>
                                            <?php echo htmlspecialchars($student['email'] ?? ''); ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <span style="background: #667eea; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                                <?php echo $student['enrolled_courses'] ?? 0; ?> دورة
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <?php echo format_date($student['created_at'] ?? '', 'Y-m-d'); ?>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <?php echo format_date($student['last_enrollment'] ?? '', 'Y-m-d'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">لا يوجد طلاب مسجلين</h4>
                        <p style="margin: 0; color: #999;">لم يسجل أي طالب في دوراتك بعد</p>
                        <a href="create_course.php" class="btn btn-primary" style="margin-top: 1rem; text-decoration: none;">
                            <i class="fas fa-plus"></i>
                            إنشاء دورة جديدة
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.student-row');
    
    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

</body>
</html> 