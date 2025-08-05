<?php
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!is_logged_in() || !is_student()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كطالب للوصول لهذه الصفحة', 'error');
}

try {
    $user = get_current_user_data();
    if (!$user) {
        session_destroy();
        redirect_with_message('login.html', 'حدث خطأ في الجلسة. يرجى تسجيل الدخول مرة أخرى', 'error');
    }
    
    $enrolled_courses = get_student_courses($_SESSION['user_id']);
    $recent_announcements = get_recent_announcements(3);
    
} catch (Exception $e) {
    error_log("Error in Student.php: " . $e->getMessage());
    $enrolled_courses = [];
    $recent_announcements = [];
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الطالب - <?php echo htmlspecialchars($user['full_name'] ?? 'طالب'); ?></title>
  <link rel="stylesheet" type="text/css" href="Student.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Doto:wght@100..900&family=Major+Mono+Display&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<section class="section" dir="rtl">
 <header dir="rtl"> 
      <div class="logo">
        <div class="name2" dir="ltr"><span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span><span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span></div>    
        <div class="name">ARG</div>
      </div>
      
      <div class="InputContainer"> 
        <input placeholder="بحث..." id="input" class="input" name="text" type="text" dir="rtl">
      </div>

      <div class="user-menu">
        <span><i class="fas fa-user-circle"></i> مرحباً، <?php echo htmlspecialchars($user['full_name'] ?? 'طالب'); ?></span>
        <a href="auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> تسجيل خروج</a>
      </div>
      
  </header>

 <div class="container">
    <?php echo display_message(); ?>
    
    <div class="welcome-section">
        <h2><i class="fas fa-graduation-cap"></i> مرحباً بك في أكاديمية ARG</h2>
        <p>استمر في رحلة التعلم الخاصة بك واكتشف عالم المعرفة</p>
    </div>

    <?php if (!empty($enrolled_courses)): ?>
        <div class="courses-section">
            <h3><i class="fas fa-book-open"></i> دوراتي المسجلة</h3>
            <div class="courses-grid">
                <?php foreach ($enrolled_courses as $course): ?>
                    <div class="course-card">
                        <h4><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($course['title'] ?? 'دورة غير محددة'); ?></h4>
                        <p><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $course['progress_percentage'] ?? 0; ?>%"></div>
                        </div>
                        <span class="progress-text"><i class="fas fa-chart-line"></i> <?php echo $course['progress_percentage'] ?? 0; ?>% مكتمل</span>
                        <a href="course_lessons.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-play"></i> استمر في التعلم
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="no-courses">
            <h3><i class="fas fa-search"></i> لم تسجل في أي دورة بعد</h3>
            <p>استكشف الدورات المتاحة وابدأ رحلة التعلم المثيرة</p>
            <a href="courses.php" class="btn btn-primary">
                <i class="fas fa-rocket"></i> استكشف الدورات
            </a>
        </div>
    <?php endif; ?>

    <?php if (!empty($recent_announcements)): ?>
        <div class="announcements-section">
            <h3><i class="fas fa-bullhorn"></i> آخر الإعلانات</h3>
            <?php foreach ($recent_announcements as $announcement): ?>
                <div class="announcement-card">
                    <h4><i class="fas fa-newspaper"></i> <?php echo htmlspecialchars($announcement['title'] ?? ''); ?></h4>
                    <p><?php echo htmlspecialchars($announcement['content'] ?? ''); ?></p>
                    <small><i class="fas fa-user"></i> بواسطة: <?php echo htmlspecialchars($announcement['author_name'] ?? 'الإدارة'); ?> - <i class="fas fa-calendar"></i> <?php echo format_date($announcement['created_at'] ?? '', 'Y-m-d'); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> إجراءات سريعة</h3>
        <div class="actions-grid">
            <a href="courses.php" class="action-card">
                <i class="fas fa-book"></i>
                <span>استكشف الدورات</span>
            </a>
            <a href="wallet.php" class="action-card">
                <i class="fas fa-wallet"></i>
                <span>المحفظة (<?php echo number_format($user['wallet_balance'] ?? 0, 2); ?> دينار ليبي)</span>
            </a>
            <a href="profile.php" class="action-card">
                <i class="fas fa-user-cog"></i>
                <span>الملف الشخصي</span>
            </a>
            <a href="help.php" class="action-card">
                <i class="fas fa-question-circle"></i>
                <span>المساعدة</span>
            </a>
        </div>
    </div>
</div>

<div class="sideBar" dir="rtl">
  <ul class="sideBar-content">
    <li>
      <a href="index.html" class="l">
        <i class="fa-solid fa-house icons"></i>
        <span class="text">الصفحة الرئيسية</span>
      </a>
    </li>
    <li>
      <a href="courses.php" class="l">
        <i class="fa-solid fa-book icons"></i>
        <span class="text">الدورات</span>
      </a>
    </li>
    <li>
      <a href="wallet.php" class="l">
        <i class="fa-solid fa-wallet icons"></i>
        <span class="text">المحفظة</span>
      </a>
    </li>
    <li>
      <a href="profile.php" class="l">
        <i class="fa-solid fa-user-cog icons"></i>
        <span class="text">الملف الشخصي</span>
      </a>
    </li>
    <li>
      <a href="help.php" class="l">
        <i class="fa-solid fa-question-circle icons"></i>
        <span class="text">المساعدة</span>
      </a>
    </li>
  </ul>
</div>

</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 