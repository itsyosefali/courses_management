<?php
require_once 'includes/functions.php';

if (!is_logged_in() || !is_teacher()) {
    die("Please log in as a teacher first");
}

echo "<h2>Database Debug Information</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'courses'");
    $table_exists = $stmt->rowCount() > 0;
    echo "<p><strong>Courses table exists:</strong> " . ($table_exists ? "Yes" : "No") . "</p>";
    
    if ($table_exists) {
        $stmt = $pdo->query("DESCRIBE courses");
        $columns = $stmt->fetchAll();
        echo "<p><strong>Courses table structure:</strong></p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}</li>";
        }
        echo "</ul>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
        $total = $stmt->fetch()['total'];
        echo "<p><strong>Total courses in database:</strong> $total</p>";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as teacher_courses FROM courses WHERE teacher_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $teacher_courses = $stmt->fetch()['teacher_courses'];
        echo "<p><strong>Courses for current teacher (ID: {$_SESSION['user_id']}):</strong> $teacher_courses</p>";
        
        $stmt = $pdo->query("SELECT id, title, teacher_id, created_at FROM courses LIMIT 10");
        $all_courses = $stmt->fetchAll();
        echo "<p><strong>Sample courses:</strong></p>";
        echo "<ul>";
        foreach ($all_courses as $course) {
            echo "<li>ID: {$course['id']}, Title: {$course['title']}, Teacher ID: {$course['teacher_id']}, Created: {$course['created_at']}</li>";
        }
        echo "</ul>";
        
        $teacher_courses_result = get_teacher_courses($_SESSION['user_id']);
        echo "<p><strong>get_teacher_courses() result:</strong> " . count($teacher_courses_result) . " courses</p>";
        
        if (!empty($teacher_courses_result)) {
            echo "<ul>";
            foreach ($teacher_courses_result as $course) {
                echo "<li>ID: {$course['id']}, Title: {$course['title']}, Students: {$course['enrolled_students']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<p><a href='teacher.php'>Back to Teacher Dashboard</a></p>";
?> 