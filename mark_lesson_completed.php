<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !is_student()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$lesson_id = $input['lesson_id'] ?? null;

if (!$lesson_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'معرف الدرس مطلوب']);
    exit();
}

try {
    // Check if lesson exists and student is enrolled in the course
    $stmt = $pdo->prepare("
        SELECT l.id, l.course_id 
        FROM lessons l
        JOIN enrollments e ON l.course_id = e.course_id
        WHERE l.id = ? AND e.student_id = ?
    ");
    $stmt->execute([$lesson_id, $_SESSION['user_id']]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'الدرس غير موجود أو غير مسجل في الدورة']);
        exit();
    }
    
    // Insert or update lesson progress
    $stmt = $pdo->prepare("
        INSERT INTO lesson_progress (student_id, lesson_id, is_completed, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
        is_completed = 1, completed_at = NOW()
    ");
    $stmt->execute([$_SESSION['user_id'], $lesson_id]);
    
    // Update course progress
    $progress = calculate_course_progress($_SESSION['user_id'], $lesson['course_id']);
    $stmt = $pdo->prepare("UPDATE enrollments SET progress_percentage = ? WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$progress, $_SESSION['user_id'], $lesson['course_id']]);
    
    echo json_encode(['success' => true, 'message' => 'تم تحديد الدرس كمكتمل بنجاح']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث التقدم']);
}
?> 