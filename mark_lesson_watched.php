<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !is_student()) {
    http_response_code(403);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$lesson_id = $input['lesson_id'] ?? null;

if (!$lesson_id) {
    http_response_code(400);
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
        exit();
    }
    
    // Insert or update lesson progress (just mark as watched, not completed)
    $stmt = $pdo->prepare("
        INSERT INTO lesson_progress (student_id, lesson_id, is_completed, watched_duration)
        VALUES (?, ?, 0, 1)
        ON DUPLICATE KEY UPDATE 
        watched_duration = watched_duration + 1
    ");
    $stmt->execute([$_SESSION['user_id'], $lesson_id]);
    
    http_response_code(200);
    
} catch (Exception $e) {
    http_response_code(500);
}
?> 