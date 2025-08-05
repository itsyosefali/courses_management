<?php
session_start();
// Try different possible paths for database config
$config_paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../../config/database.php',
    'config/database.php',
    '../config/database.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Could not load database configuration file");
}

// Function to sanitize input data
function sanitize_input($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to hash passwords
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify passwords
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to generate random token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is teacher
function is_teacher() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
}

// Function to check if user is student
function is_student() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

// Function to get current user data
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Database error in get_current_user_data: " . $e->getMessage());
        return null;
    }
}

// Function to redirect with message
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit();
}

// Function to display messages
function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        $alert_class = '';
        switch ($type) {
            case 'success':
                $alert_class = 'alert-success';
                break;
            case 'error':
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                break;
            default:
                $alert_class = 'alert-info';
        }
        
        return "<div class='alert $alert_class alert-dismissible fade show' role='alert'>
                    $message
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
    return '';
}

// Function to format date
function format_date($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) return '';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return $date;
    }
}

// Function to calculate course progress
function calculate_course_progress($student_id, $course_id) {
    try {
        global $pdo;
        
        // Get total lessons in course
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $total_lessons = $stmt->fetch()['total'];
        
        if ($total_lessons == 0) return 0;
        
        // Get completed lessons
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed 
            FROM lesson_progress lp 
            JOIN lessons l ON lp.lesson_id = l.id 
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.is_completed = 1
        ");
        $stmt->execute([$student_id, $course_id]);
        $completed_lessons = $stmt->fetch()['completed'];
        
        return round(($completed_lessons / $total_lessons) * 100);
    } catch (PDOException $e) {
        error_log("Database error in calculate_course_progress: " . $e->getMessage());
        return 0;
    }
}

// Function to get course price with discount
function get_course_price_with_discount($course_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT price, discount_percentage FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        
        if (!$course) return 0;
        
        $discount_amount = ($course['price'] * $course['discount_percentage']) / 100;
        return $course['price'] - $discount_amount;
    } catch (PDOException $e) {
        error_log("Database error in get_course_price_with_discount: " . $e->getMessage());
        return 0;
    }
}

// Function to check if student is enrolled in course
function is_enrolled($student_id, $course_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$student_id, $course_id]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Database error in is_enrolled: " . $e->getMessage());
        return false;
    }
}

// Function to get user's wallet balance
function get_wallet_balance($user_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result ? $result['wallet_balance'] : 0;
    } catch (PDOException $e) {
        error_log("Database error in get_wallet_balance: " . $e->getMessage());
        return 0;
    }
}

// Function to update wallet balance
function update_wallet_balance($user_id, $amount, $transaction_type, $description = '', $existing_transaction = false) {
    try {
        global $pdo;
        
        if (!$existing_transaction) {
            $pdo->beginTransaction();
        }
        
        // Update user's wallet balance
        $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
        
        // Record transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, amount, transaction_type, description, status) 
            VALUES (?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([$user_id, $amount, $transaction_type, $description]);
        
        if (!$existing_transaction) {
            $pdo->commit();
        }
        return true;
    } catch (Exception $e) {
        if (!$existing_transaction && isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Database error in update_wallet_balance: " . $e->getMessage());
        return false;
    }
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number (Saudi format)
function validate_phone($phone) {
    return true;
}

// Function to validate Saudi ID
function validate_saudi_id($id) {
    return true;
}

// Function to upload file
function upload_file($file, $target_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'pdf']) {
    try {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return false;
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            return false;
        }
        
        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                error_log("Failed to create directory: $target_dir");
                return false;
            }
        }
        
        $filename = uniqid() . '.' . $file_extension;
        $target_path = $target_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            return $target_path;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error in upload_file: " . $e->getMessage());
        return false;
    }
}

// Function to get course lessons
function get_course_lessons($course_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT * FROM lessons 
            WHERE course_id = ? AND is_active = 1 
            ORDER BY lesson_number
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database error in get_course_lessons: " . $e->getMessage());
        return [];
    }
}

// Function to get student's enrolled courses
function get_student_courses($student_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT c.*, e.enrollment_date, e.progress_percentage, e.is_completed,
                   u.full_name as teacher_name
            FROM enrollments e
            JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON c.teacher_id = u.id
            WHERE e.student_id = ?
            ORDER BY e.enrollment_date DESC
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database error in get_student_courses: " . $e->getMessage());
        return [];
    }
}

// Function to get teacher's courses
function get_teacher_courses($teacher_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT c.*, COUNT(e.id) as enrolled_students
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            WHERE c.teacher_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$teacher_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database error in get_teacher_courses: " . $e->getMessage());
        return [];
    }
}

// Function to get recent announcements
function get_recent_announcements($limit = 5) {
    try {
        global $pdo;
        
        // Fix: Use LIMIT with integer instead of parameter
        $limit = (int)$limit;
        
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name as author_name
            FROM announcements a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.is_active = 1
            ORDER BY a.created_at DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Database error in get_recent_announcements: " . $e->getMessage());
        return [];
    }
}

// Function to handle database errors gracefully
function handle_database_error($e, $context = '') {
    error_log("Database error in $context: " . $e->getMessage());
    return false;
}

// Function to validate and sanitize user input
function validate_user_input($data, $rules = []) {
    $errors = [];
    $sanitized = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';
        
        // Sanitize
        $sanitized[$field] = sanitize_input($value);
        
        // Validate based on rules
        if (isset($rule['required']) && $rule['required'] && empty($sanitized[$field])) {
            $errors[] = "حقل $field مطلوب";
        }
        
        if (isset($rule['min_length']) && strlen($sanitized[$field]) < $rule['min_length']) {
            $errors[] = "حقل $field يجب أن يكون " . $rule['min_length'] . " أحرف على الأقل";
        }
        
        if (isset($rule['email']) && $rule['email'] && !validate_email($sanitized[$field])) {
            $errors[] = "البريد الإلكتروني غير صحيح";
        }
        
        if (isset($rule['phone']) && $rule['phone'] && !validate_phone($sanitized[$field])) {
            $errors[] = "رقم الهاتف غير صحيح";
        }
        
        if (isset($rule['saudi_id']) && $rule['saudi_id'] && !validate_saudi_id($sanitized[$field])) {
            $errors[] = "رقم الهوية غير صحيح";
        }
    }
    
    return ['errors' => $errors, 'data' => $sanitized];
}
?> 