<?php
require_once '../includes/functions.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    try {
        // Get and sanitize form data
        $full_name = sanitize_input($_POST['full-name'] ?? '');
        $username = sanitize_input($_POST['user-name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm-password'] ?? '';
        $subject_specialization = sanitize_input($_POST['subject-specialization'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $id_number = sanitize_input($_POST['ID'] ?? '');
        $grade = sanitize_input($_POST['grade'] ?? '');
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'الاسم الكامل مطلوب';
        }
        
        if (empty($username)) {
            $errors[] = 'اسم المستخدم مطلوب';
        } elseif (strlen($username) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }
        
        if (empty($email)) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        } elseif (!validate_email($email)) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }
        
        if (empty($password)) {
            $errors[] = 'كلمة المرور مطلوبة';
        } elseif (strlen($password) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'كلمة المرور غير متطابقة';
        }
        
        if (empty($subject_specialization)) {
            $errors[] = 'التخصص الدراسي مطلوب';
        }
        
        if (empty($phone)) {
            $errors[] = 'رقم الهاتف مطلوب';
        } elseif (!validate_phone($phone)) {
            $errors[] = 'رقم الهاتف غير صحيح';
        }
        
        if (empty($id_number)) {
            $errors[] = 'رقم الهوية مطلوب';
        } elseif (!validate_saudi_id($id_number)) {
            $errors[] = 'رقم الهوية غير صحيح';
        }
        
        if (empty($grade)) {
            $errors[] = 'المستوى الدراسي مطلوب';
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'اسم المستخدم موجود مسبقاً';
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'البريد الإلكتروني موجود مسبقاً';
        }
        
        // If no errors, proceed with registration
        if (empty($errors)) {
            try {
                $hashed_password = hash_password($password);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, username, email, password, phone, id_number, 
                                     user_type, subject_specialization, grade_level, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'student', ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $full_name, $username, $email, $hashed_password, $phone, $id_number,
                    $subject_specialization, $grade
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                if ($user_id) {
                    // Set session
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    
                    redirect_with_message('../Student.php', 'تم إنشاء الحساب بنجاح! مرحباً بك في أكاديمية ARG', 'success');
                } else {
                    throw new Exception('فشل في إنشاء الحساب');
                }
                
            } catch (PDOException $e) {
                error_log("Database error in student registration: " . $e->getMessage());
                $errors[] = 'حدث خطأ أثناء إنشاء الحساب. يرجى المحاولة مرة أخرى.';
            } catch (Exception $e) {
                error_log("General error in student registration: " . $e->getMessage());
                $errors[] = 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.';
            }
        }
        
    } catch (Exception $e) {
        error_log("Critical error in student registration: " . $e->getMessage());
        $errors[] = 'حدث خطأ في النظام. يرجى المحاولة مرة أخرى لاحقاً.';
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ../signUpS.php');
        exit();
    }
} else {
    header('Location: ../signUpS.php');
    exit();
}
?> 