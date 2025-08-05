<?php
require_once '../includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    try {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email)) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        }
        
        if (empty($password)) {
            $errors[] = 'كلمة المرور مطلوبة';
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && verify_password($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    error_log("Successful login for user: " . $user['email']);
                    
                    if ($user['user_type'] === 'student') {
                        redirect_with_message('../Student.php', 'تم تسجيل الدخول بنجاح! مرحباً بك مرة أخرى', 'success');
                    } else {
                        redirect_with_message('../teacher.php', 'تم تسجيل الدخول بنجاح! مرحباً بك مرة أخرى', 'success');
                    }
                } else {
                    $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
                    error_log("Failed login attempt for email: " . $email);
                }
                
            } catch (PDOException $e) {
                error_log("Database error in login: " . $e->getMessage());
                $errors[] = 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.';
            } catch (Exception $e) {
                error_log("General error in login: " . $e->getMessage());
                $errors[] = 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.';
            }
        }
        
    } catch (Exception $e) {
        error_log("Critical error in login: " . $e->getMessage());
        $errors[] = 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى لاحقاً.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ../login.html');
        exit();
    }
} else {
    header('Location: ../login.html');
    exit();
}
?> 