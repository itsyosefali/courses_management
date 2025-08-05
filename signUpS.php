<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="signUpS.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
   
    <title>انشاء حساب طالب</title>
</head>
<body>

  
  
  <div class="container">
     
    <div dir="rtl" class="heading"> <img  height="120px" src="image/signUp1.png" alt=""><p>انشاء حساب</p></div>
    
    <?php
    session_start();
    if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])) {
        echo '<div class="alert alert-danger" role="alert">';
        foreach ($_SESSION['errors'] as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
        unset($_SESSION['errors']);
    }
    ?>
    
    <form dir="rtl" action="auth/register_student.php" method="POST" class="form">

   <div class="group-Input">
      <input dir="rtl"  required="" class="input" type="text" name="full-name" id="full-name" placeholder="الاسم كامل" value="<?php echo isset($_SESSION['form_data']['full-name']) ? htmlspecialchars($_SESSION['form_data']['full-name']) : ''; ?>">
      <input dir="rtl"  required="" class="input" type="text" name="user-name" id="user-name" placeholder="اسم المستخدم" value="<?php echo isset($_SESSION['form_data']['user-name']) ? htmlspecialchars($_SESSION['form_data']['user-name']) : ''; ?>">

    </div>
      <div class="group-Input">
      <input dir="rtl" required="" class="input" type="email" name="email" id="E-mail" placeholder="البريد الاكتروني" value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
      <input dir="rtl" required="" class="input" type="password" name="password" id="password" placeholder="كلمة المرور">
    </div>
    
    
    
    <div class="group-Input">
      <input dir="rtl" required="" class="input" type="password" name="confirm-password" id="confirm-password" placeholder="تأكيد كلمة المرور">
      <input dir="rtl" required="" class="input" type="text" name="subject-specialization" id="subject" placeholder="التخصص الدراسي" value="<?php echo isset($_SESSION['form_data']['subject-specialization']) ? htmlspecialchars($_SESSION['form_data']['subject-specialization']) : ''; ?>">
    </div>
    
    
      <div class="group-Input">
        <input dir="rtl" required="" class="input" type="tel" name="phone" id="phone" placeholder="رقم الهاتف" value="<?php echo isset($_SESSION['form_data']['phone']) ? htmlspecialchars($_SESSION['form_data']['phone']) : ''; ?>">
        <input dir="rtl" required="" class="input" type="text" name="ID" id="id" placeholder="رقم الهوية" value="<?php echo isset($_SESSION['form_data']['ID']) ? htmlspecialchars($_SESSION['form_data']['ID']) : ''; ?>">
  
      </div>
    




   
 <!-- المستوى الدراسي  -->
 
   <div class="group-selection">
    <label for="grade">المستوى الدراسي:</label>
    <select id="grade" name="grade" required>
        <option value="" disabled selected>اختر مستواك الدراسي</option>
        <option value="middle" <?php echo (isset($_SESSION['form_data']['grade']) && $_SESSION['form_data']['grade'] == 'middle') ? 'selected' : ''; ?>>متوسط</option>
        <option value="high" <?php echo (isset($_SESSION['form_data']['grade']) && $_SESSION['form_data']['grade'] == 'high') ? 'selected' : ''; ?>>ثانوي</option>
        <option value="university" <?php echo (isset($_SESSION['form_data']['grade']) && $_SESSION['form_data']['grade'] == 'university') ? 'selected' : ''; ?>>جامعي</option>
    </select>
  </div>
   
    <?php unset($_SESSION['form_data']); ?>

    
      <input class="signup-button" type="submit" value="انشاء حساب">
      

      
    </form>
   

  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



















