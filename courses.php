<?php
require_once 'includes/functions.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Get all active courses
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as teacher_name, COUNT(e.id) as enrolled_students
        FROM courses c
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll();

    // Check if user is logged in
    $is_logged_in = is_logged_in();
    $user_id = $is_logged_in ? $_SESSION['user_id'] : null;
    
} catch (Exception $e) {
    error_log("Error in courses.php: " . $e->getMessage());
    $courses = [];
    $is_logged_in = false;
    $user_id = null;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الدورات المميزة | ARG Academy</title>
  <link rel="stylesheet" href="Home.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Major+Mono+Display&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
  <header>
    <div class="logo">
      <div class="name2" dir="ltr">
        <span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span><span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span>
      </div>
      <div class="name">ARG</div>
    </div>
    <nav class="links">
      <a href="index.html" class="link">الصفحة الرئيسية</a>
      <a href="courses.php" class="link active">الدورات</a>
      <a href="wallet.php" class="link">المحفظة</a>
      <?php if ($is_logged_in): ?>
        <a href="<?php echo $_SESSION['user_type'] === 'student' ? 'Student.php' : 'teacher.php'; ?>" class="link">لوحة التحكم</a>
        <a href="auth/logout.php" class="link">تسجيل خروج</a>
      <?php else: ?>
        <a href="login.html" class="link">تسجيل دخول</a>
      <?php endif; ?>
    </nav>
    <div class="InputContainer">
      <input placeholder="ابحث عن مدرسك المفضل" id="input" class="input" name="text" type="text" dir="rtl">
    </div>
  </header>

  <main>
    <h2 style="text-align: center; font-family: NotoKufiArabic-Light; margin: 30px 0; color: #0b2b6d;">الدورات المميزة</h2>
    
    <?php echo display_message(); ?>
    
    <section class="cards">
      <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $course): ?>
          <div class="cardHTML">
            <div class="discount">خصم <?php echo $course['discount_percentage'] ?? 0; ?>%</div>
            <h1><?php echo htmlspecialchars($course['title'] ?? ''); ?></h1>
            <p class="p-html"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
            <div class="course-info">
              <p><strong>المعلم:</strong> <?php echo htmlspecialchars($course['teacher_name'] ?? 'غير محدد'); ?></p>
              <p><strong>المستوى:</strong> <?php echo htmlspecialchars($course['level'] ?? 'beginner'); ?></p>
              <p><strong>الطلاب المسجلين:</strong> <?php echo $course['enrolled_students'] ?? 0; ?></p>
              <p><strong>السعر:</strong> 
                <span class="original-price"><?php echo number_format($course['price'] ?? 0, 2); ?> دينار ليبي</span>
<span class="discounted-price"><?php echo number_format(get_course_price_with_discount($course['id'] ?? 0), 2); ?> دينار ليبي</span>
              </p>
            </div>
            
            <div class="course-actions">
              <?php if ($is_logged_in && is_student()): ?>
                <?php if (is_enrolled($user_id, $course['id'])): ?>
                  <a href="course_lessons.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">استمر في التعلم</a>
                <?php else: ?>
                  <a href="enroll_course.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">سجل في الدورة</a>
                <?php endif; ?>
              <?php elseif ($is_logged_in && is_teacher()): ?>
                <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-info">عرض التفاصيل</a>
              <?php else: ?>
                <a href="login.html" class="btn btn-primary">سجل دخول للانضمام</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-courses">
          <h3>لا توجد دورات متاحة حالياً</h3>
          <p>يرجى العودة لاحقاً</p>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <div class="footer">
    <p>
      ARG Academy is optimized for learning and training. Examples might be simplified to improve reading and learning.
      Tutorials, references, and examples are constantly reviewed to avoid errors, but we cannot warrant full correctness
      of all content.<br> While using ARG Academy, you agree to have read and accepted our <a href="#">terms of use</a>,
      <a href="#">cookie</a> and <a href="#">privacy policy</a>.
    </p>
    <p>
      Copyright 2025 by ARG Academy<br> All Rights Reserved.
    </p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 