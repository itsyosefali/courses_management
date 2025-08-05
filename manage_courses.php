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

// Handle course actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status' && isset($_POST['course_id'])) {
        $course_id = $_POST['course_id'];
        try {
            $stmt = $pdo->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ? AND teacher_id = ?");
            if ($stmt->execute([$course_id, $_SESSION['user_id']])) {
                redirect_with_message('manage_courses.php', 'تم تحديث حالة الدورة بنجاح!', 'success');
            }
        } catch (Exception $e) {
            error_log("Error toggling course status: " . $e->getMessage());
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['course_id'])) {
        $course_id = $_POST['course_id'];
        try {
            // Check if course has enrollments
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $enrollment_count = $stmt->fetchColumn();
            
            if ($enrollment_count > 0) {
                redirect_with_message('manage_courses.php', 'لا يمكن حذف دورة بها طلاب مسجلين', 'error');
            } else {
                $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
                if ($stmt->execute([$course_id, $_SESSION['user_id']])) {
                    redirect_with_message('manage_courses.php', 'تم حذف الدورة بنجاح!', 'success');
                }
            }
        } catch (Exception $e) {
            error_log("Error deleting course: " . $e->getMessage());
        }
    }
}

// Get teacher's courses with detailed information
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT e.student_id) as enrolled_students,
            SUM(c.price) as total_revenue,
            COUNT(DISTINCT a.id) as announcements_count
        FROM courses c
        LEFT JOIN enrollments e ON c.id = e.course_id
        LEFT JOIN announcements a ON c.id = a.course_id
        WHERE c.teacher_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

// Get statistics
$total_courses = count($courses);
$active_courses = count(array_filter($courses, function($course) { return $course['is_active'] ?? false; }));
$total_students = array_sum(array_column($courses, 'enrolled_students'));
$total_revenue = array_sum(array_column($courses, 'total_revenue'));
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الدورات - <?php echo htmlspecialchars($user['full_name'] ?? 'معلم'); ?></title>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <a href="teacher.php" class="btn btn-secondary" style="text-decoration: none;">
                        <i class="fas fa-arrow-right"></i>
                        العودة للوحة التحكم
                    </a>
                    <h2 style="margin: 0; color: #333;">
                        <i class="fas fa-book"></i>
                        إدارة الدورات
                    </h2>
                </div>
                <a href="create_course.php" class="btn btn-primary" style="text-decoration: none;">
                    <i class="fas fa-plus"></i>
                    إنشاء دورة جديدة
                </a>
            </div>

            <?php echo display_message(); ?>

            <!-- Statistics -->
            <div class="stats-container" style="margin-bottom: 2rem;">
                <div class="stat-item">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_courses; ?></div>
                        <div class="stat-label">إجمالي الدورات</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $active_courses; ?></div>
                        <div class="stat-label">الدورات النشطة</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_students; ?></div>
                        <div class="stat-label">إجمالي الطلاب</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo number_format($total_revenue, 0); ?></div>
                        <div class="stat-label">د.ل الإيرادات</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <input type="text" id="searchInput" placeholder="بحث في الدورات..." 
                           style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                    <select id="statusFilter" style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="">جميع الحالات</option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
            </div>

            <!-- Courses List -->
            <div class="card">
                <h3 style="margin: 0 0 1rem 0; color: #333;">
                    <i class="fas fa-list"></i>
                    قائمة الدورات
                </h3>

                <?php if (!empty($courses)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                                    <th style="padding: 0.75rem; text-align: right; font-weight: 600; color: #333;">الدورة</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">الحالة</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">الطلاب</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">السعر</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">الإيرادات</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">تاريخ الإنشاء</th>
                                    <th style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <?php foreach ($courses as $course): ?>
                                    <tr class="course-row" data-status="<?php echo ($course['is_active'] ?? false) ? 'active' : 'inactive'; ?>" style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 0.75rem;">
                                            <div style="font-weight: 600; color: #333; margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars($course['title'] ?? ''); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #666; line-height: 1.3;">
                                                <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)) . (strlen($course['description'] ?? '') > 100 ? '...' : ''); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <span style="background: <?php echo ($course['is_active'] ?? false) ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;">
                                                <?php echo ($course['is_active'] ?? false) ? 'نشط' : 'غير نشط'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center; font-weight: 600; color: #333;">
                                            <?php echo $course['enrolled_students'] ?? 0; ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center; color: #666;">
                                            <?php echo number_format($course['price'] ?? 0, 0); ?> د.ل
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center; font-weight: 600; color: #28a745;">
                                            <?php echo number_format($course['total_revenue'] ?? 0, 0); ?> د.ل
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center; color: #666; font-size: 0.9rem;">
                                            <?php echo format_date($course['created_at'] ?? '', 'Y-m-d'); ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <a href="edit_course.php?id=<?php echo $course['id']; ?>" 
                                                   style="background: #007bff; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; text-decoration: none; font-size: 0.8rem;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="course_students.php?id=<?php echo $course['id']; ?>" 
                                                   style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; text-decoration: none; font-size: 0.8rem;">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <button type="submit" 
                                                            style="background: #ffc107; color: #333; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                                        <i class="fas fa-toggle-on"></i>
                                                    </button>
                                                </form>
                                                <?php if (($course['enrolled_students'] ?? 0) == 0): ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                        <button type="submit" onclick="return confirm('هل أنت متأكد من حذف هذه الدورة؟')" 
                                                                style="background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                        <h4 style="margin: 0 0 0.5rem 0; color: #333;">لا توجد دورات</h4>
                        <p style="margin: 0; color: #999;">ابدأ بإنشاء دورتك الأولى</p>
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
    const rows = document.querySelectorAll('.course-row');
    
    rows.forEach(row => {
        const title = row.querySelector('td:first-child').textContent.toLowerCase();
        const description = row.querySelector('td:first-child div:last-child').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('.course-row');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        
        if (filterValue === '' || status === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

</body>
</html> 