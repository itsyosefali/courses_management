<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_teacher()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كمعلم للوصول لهذه الصفحة', 'error');
}

$user = get_current_user_data();

try {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_courses,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_courses,
            SUM(price) as total_potential_revenue
        FROM courses 
        WHERE teacher_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $course_stats = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_students,
            COUNT(e.id) as total_enrollments,
            SUM(c.price) as total_revenue
        FROM users u
        JOIN enrollments e ON u.id = e.student_id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND u.user_type = 'student'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student_stats = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(e.enrolled_at, '%Y-%m') as month,
            COUNT(*) as enrollments
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? 
        AND e.enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(e.enrolled_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $monthly_enrollments = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT 
            c.title,
            c.price,
            COUNT(e.student_id) as enrolled_students,
            SUM(c.price) as revenue
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE c.teacher_id = ?
        GROUP BY c.id, c.title, c.price
        ORDER BY enrolled_students DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $top_courses = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT 
            'enrollment' as type,
            u.full_name as student_name,
            c.title as course_title,
            e.enrolled_at as date
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND u.user_type = 'student'
        ORDER BY e.enrolled_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_activity = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Error fetching analytics: " . $e->getMessage());
    $course_stats = ['total_courses' => 0, 'active_courses' => 0, 'total_potential_revenue' => 0];
    $student_stats = ['total_students' => 0, 'total_enrollments' => 0, 'total_revenue' => 0];
    $monthly_enrollments = [];
    $top_courses = [];
    $recent_activity = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقارير والإحصائيات - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
    <link rel="stylesheet" href="teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <i class="fas fa-chart-bar"></i>
                    التقارير والإحصائيات
                </h2>
            </div>

            <?php echo display_message(); ?>

            <div class="stats-container" style="margin-bottom: 2rem;">
                <div class="stat-item">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $course_stats['total_courses'] ?? 0; ?></div>
                        <div class="stat-label">إجمالي الدورات</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $course_stats['active_courses'] ?? 0; ?></div>
                        <div class="stat-label">الدورات النشطة</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $student_stats['total_students'] ?? 0; ?></div>
                        <div class="stat-label">إجمالي الطلاب</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($student_stats['total_revenue'] ?? 0, 0); ?></div>
                        <div class="stat-label">د.ل الإيرادات</div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card">
                    <h3 style="margin: 0 0 1rem 0; color: #333;">
                        <i class="fas fa-chart-line"></i>
                        التسجيلات الشهرية
                    </h3>
                    <canvas id="enrollmentsChart" width="400" height="200"></canvas>
                </div>

                <div class="card">
                    <h3 style="margin: 0 0 1rem 0; color: #333;">
                        <i class="fas fa-chart-pie"></i>
                        أداء الدورات
                    </h3>
                    <canvas id="coursesChart" width="400" height="200"></canvas>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <h3 style="margin: 0 0 1rem 0; color: #333;">
                        <i class="fas fa-trophy"></i>
                        أفضل الدورات أداءً
                    </h3>
                    
                    <?php if (!empty($top_courses)): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($top_courses as $index => $course): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8f9fa; border-radius: 6px;">
                                    <div>
                                        <div style="font-weight: 600; color: #333;">
                                            <?php echo ($index + 1) . '. ' . htmlspecialchars($course['title'] ?? ''); ?>
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666;">
                                            <?php echo $course['enrolled_students'] ?? 0; ?> طالب
                                        </div>
                                    </div>
                                    <div style="text-align: left;">
                                        <div style="font-weight: 600; color: #28a745;">
                                            <?php echo number_format($course['revenue'] ?? 0, 0); ?> د.ل
                                        </div>
                                        <div style="font-size: 0.8rem; color: #666;">
                                            <?php echo number_format($course['price'] ?? 0, 0); ?> د.ل
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
                            <p style="margin: 0;">لا توجد بيانات متاحة</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3 style="margin: 0 0 1rem 0; color: #333;">
                        <i class="fas fa-clock"></i>
                        النشاط الأخير
                    </h3>
                    
                    <?php if (!empty($recent_activity)): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div style="padding: 0.75rem; background: #f8f9fa; border-radius: 6px;">
                                    <div style="font-weight: 500; color: #333; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($activity['student_name'] ?? ''); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #666;">
                                        انضم إلى <?php echo htmlspecialchars($activity['course_title'] ?? ''); ?>
                                    </div>
                                    <div style="font-size: 0.7rem; color: #999; margin-top: 0.25rem;">
                                        <?php echo format_date($activity['date'] ?? '', 'Y-m-d H:i'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏰</div>
                            <p style="margin: 0;">لا يوجد نشاط حديث</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const enrollmentsCtx = document.getElementById('enrollmentsChart').getContext('2d');
const enrollmentsData = <?php echo json_encode($monthly_enrollments); ?>;

new Chart(enrollmentsCtx, {
    type: 'line',
    data: {
        labels: enrollmentsData.map(item => item.month).reverse(),
        datasets: [{
            label: 'التسجيلات',
            data: enrollmentsData.map(item => item.enrollments).reverse(),
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

const coursesCtx = document.getElementById('coursesChart').getContext('2d');
const coursesData = <?php echo json_encode($top_courses); ?>;

new Chart(coursesCtx, {
    type: 'doughnut',
    data: {
        labels: coursesData.map(course => course.title),
        datasets: [{
            data: coursesData.map(course => course.enrolled_students),
            backgroundColor: [
                '#667eea',
                '#764ba2',
                '#f093fb',
                '#f5576c',
                '#4facfe'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: {
                        size: 10
                    }
                }
            }
        }
    }
});
</script>

</body>
</html> 