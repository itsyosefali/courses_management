<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_student()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كطالب للانضمام للدورات', 'error');
}

try {
    $course_id = $_GET['course_id'] ?? null;
    if (!$course_id) {
        redirect_with_message('courses.php', 'معرف الدورة غير صحيح', 'error');
    }

    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if (!$course) {
        redirect_with_message('courses.php', 'الدورة غير موجودة', 'error');
    }

    if (is_enrolled($_SESSION['user_id'], $course_id)) {
        redirect_with_message('courses.php', 'أنت مسجل بالفعل في هذه الدورة', 'info');
    }

    $course_price = get_course_price_with_discount($course_id);
    $user_balance = get_wallet_balance($_SESSION['user_id']);

    if ($user_balance < $course_price) {
        redirect_with_message('wallet.php', 'رصيدك غير كافي. يرجى شحن المحفظة أولاً', 'error');
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO enrollments (student_id, course_id, enrollment_date, progress_percentage)
            VALUES (?, ?, NOW(), 0)
        ");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        
        $deduction_amount = -$course_price;
        if (!update_wallet_balance($_SESSION['user_id'], $deduction_amount, 'purchase', "شراء دورة: " . $course['title'], true)) {
            throw new Exception('فشل في تحديث رصيد المحفظة');
        }
        
        $pdo->commit();
        
        redirect_with_message('course_lessons.php?course_id=' . $course_id, 'تم التسجيل في الدورة بنجاح!', 'success');
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error in course enrollment: " . $e->getMessage());
        redirect_with_message('courses.php', 'حدث خطأ أثناء التسجيل في الدورة', 'error');
    }
    
} catch (Exception $e) {
    error_log("Critical error in enroll_course.php: " . $e->getMessage());
    redirect_with_message('courses.php', 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى', 'error');
}
?> 