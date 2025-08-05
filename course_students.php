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
        redirect_with_message('teacher.php', 'الدورة غير موجودة أو لا تملك صلاحية الوصول إليها', 'error');
    }
} catch (Exception $e) {
    error_log("Error fetching course: " . $e->getMessage());
    redirect_with_message('teacher.php', 'حدث خطأ في النظام', 'error');
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.created_at, e.enrolled_at
        FROM users u
        JOIN enrollments e ON u.id = e.student_id
        WHERE e.course_id = ? AND u.user_type = 'student'
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
    $students = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلاب الدورة - <?php echo htmlspecialchars($course['title'] ?? ''); ?></title>
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
                    <i class="fas fa-users"></i>
                    طلاب الدورة
                </h2>
            </div>

            <?php echo display_message(); ?>

            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="margin: 0; color: #333;">
                        <i class="fas fa-book"></i>
                        <?php echo htmlspecialchars($course['title'] ?? ''); ?>
                    </h3>
                    <span style="background: <?php echo ($course['is_active'] ?? false) ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">
                        <?php echo ($course['is_active'] ?? false) ? 'نشط' : 'غير نشط'; ?>
                    </span>
                </div>
                <p style="margin: 0 0 1rem 0; color: #666;"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                <div style="display: flex; gap: 2rem; color: #666; font-size: 0.9rem;">
                    <span>
                        <i class="fas fa-users"></i>
                        <?php echo count($students); ?> طالب مسجل
                    </span>
                    <span>
                        <i class="fas fa-money-bill-wave"></i>
                        <?php echo number_format($course['price'] ?? 0, 0); ?> د.ل
                    </span>
                </div>
            </div>

            <div class="card">
                <h3 style="margin: 0 0 1rem 0; color: #333;">
                    <i class="fas fa-user-graduate"></i>
                    قائمة الطلاب المسجلين
                </h3>

                <?php if (!empty($students)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">#</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">الاسم</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">البريد الإلكتروني</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">تاريخ التسجيل</th>
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">تاريخ الانضمام للدورة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6; transition: background-color 0.2s;">
                                        <td style="padding: 0.75rem; text-align: center; color: #666;"><?php echo $index + 1; ?></td>
                                        <td style="padding: 0.75rem; font-weight: 500; color: #333;">
                                            <i class="fas fa-user-circle" style="margin-left: 0.5rem; color: #667eea;"></i>
                                            <?php echo htmlspecialchars($student['full_name'] ?? ''); ?>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <i class="fas fa-envelope" style="margin-left: 0.5rem; color: #28a745;"></i>
                                            <?php echo htmlspecialchars($student['email'] ?? ''); ?>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <?php echo format_date($student['created_at'] ?? '', 'Y-m-d'); ?>
                                        </td>
                                        <td style="padding: 0.75rem; color: #666;">
                                            <?php echo format_date($student['enrolled_at'] ?? '', 'Y-m-d'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">لا يوجد طلاب مسجلين</h4>
                        <p style="margin: 0; color: #999;">لم يسجل أي طالب في هذه الدورة بعد</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html> 