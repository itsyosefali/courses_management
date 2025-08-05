<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!is_logged_in() || !is_student()) {
    redirect_with_message('login.html', 'يجب تسجيل الدخول كطالب لعرض الدروس', 'error');
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    redirect_with_message('Student.php', 'معرف الدورة غير صحيح', 'error');
}

// Check if student is enrolled in this course
if (!is_enrolled($_SESSION['user_id'], $course_id)) {
    redirect_with_message('courses.php', 'يجب التسجيل في الدورة أولاً', 'error');
}

// Get course details
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    redirect_with_message('Student.php', 'الدورة غير موجودة', 'error');
}

// Get course lessons
$lessons = get_course_lessons($course_id);

// Get student's progress for this course
$progress = calculate_course_progress($_SESSION['user_id'], $course_id);

// Update course progress
$stmt = $pdo->prepare("UPDATE enrollments SET progress_percentage = ? WHERE student_id = ? AND course_id = ?");
$stmt->execute([$progress, $_SESSION['user_id'], $course_id]);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - ARG Academy</title>
    <link rel="stylesheet" href="course_lessons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header>
    <div class="logo">
        <div class="name2" dir="ltr">
            <span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span>
            <span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span>
        </div>    
        <div class="name">ARG</div>
    </div>
    
    <div class="InputContainer"> 
        <input placeholder="بحث..." id="input" class="input" name="text" type="text" dir="rtl">
    </div>

    <div class="user-menu">
        <a href="Student.php" class="back-btn">
            <i class="fas fa-arrow-right"></i>
            العودة للوحة التحكم
        </a>
        <a href="auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            تسجيل خروج
        </a>
    </div>
</header>

<div class="container">
    <?php echo display_message(); ?>
    
    <!-- Course Header -->
    <div class="course-header">
        <h2><?php echo htmlspecialchars($course['title']); ?></h2>
        <div class="course-progress">
            <div class="progress">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <span class="progress-text"><?php echo $progress; ?>% مكتمل</span>
        </div>
    </div>

    <!-- Lessons Section -->
    <div class="lessons-section">
        <h2>قائمة الدروس</h2>
        
        <?php if (!empty($lessons)): ?>
            <div class="lessons">
                <?php foreach ($lessons as $index => $lesson): ?>
                    <?php
                    // Check if lesson is completed
                    $stmt = $pdo->prepare("SELECT is_completed FROM lesson_progress WHERE student_id = ? AND lesson_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $lesson['id']]);
                    $lesson_progress = $stmt->fetch();
                    $is_completed = $lesson_progress && $lesson_progress['is_completed'];
                    ?>
                    
                    <div class="lesson-card <?php echo $is_completed ? 'completed' : ''; ?>" 
                         onclick="loadLesson(<?php echo $lesson['id']; ?>, '<?php echo htmlspecialchars($lesson['video_url']); ?>', '<?php echo htmlspecialchars($lesson['title']); ?>', '<?php echo htmlspecialchars($lesson['description']); ?>')">
                        <h3>درس رقم <?php echo $lesson['lesson_number']; ?></h3>
                        <p><?php echo htmlspecialchars($lesson['title']); ?></p>
                        <div class="lesson-meta">
                            <span class="duration">
                                <i class="fas fa-clock"></i> 
                                <?php echo $lesson['duration_minutes']; ?> دقيقة
                            </span>
                            <?php if ($is_completed): ?>
                                <span class="completed-badge">
                                    <i class="fas fa-check"></i> مكتمل
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-lessons">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                <h3>لا توجد دروس متاحة لهذه الدورة</h3>
                <p>سيتم إضافة الدروس قريباً</p>
                <div style="margin-top: 2rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Video Section -->
    <div class="video-section">
        <div class="lesson-info" id="lessonInfo">
            <div class="lesson-content" id="lessonContent">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🎓</div>
                <h2>اختر درساً لبدء التعلم</h2>
                <p>اضغط على أي درس من القائمة على اليسار لبدء المشاهدة والتعلم</p>
                <div style="margin-top: 2rem; opacity: 0.7;">
                    <i class="fas fa-play-circle" style="font-size: 3rem; color: #667eea;"></i>
                </div>
            </div>
        </div>
        
        <div class="video-container">
            <video id="videoFrame" controls>
                <source src="" type="video/mp4">
                متصفحك لا يدعم تشغيل الفيديو.
            </video>
        </div>
    </div>
</div>

<script>
function loadLesson(lessonId, videoUrl, title, description) {
    // Show loading state
    const content = document.getElementById('lessonContent');
    content.innerHTML = `
        <div class="loading" style="margin: 2rem auto;"></div>
        <p>جاري تحميل الدرس...</p>
    `;
    
    // Update video source
    const video = document.getElementById('videoFrame');
    video.src = videoUrl;
    
    // Update lesson content after a short delay for smooth transition
    setTimeout(() => {
        content.innerHTML = `
            <h2>${title}</h2>
            <p>${description}</p>
            <button class="btn btn-success" onclick="markAsCompleted(${lessonId})">
                <i class="fas fa-check"></i>
                تحديد كمكتمل
            </button>
        `;
        
        // Add success animation
        content.classList.add('success-animation');
        setTimeout(() => content.classList.remove('success-animation'), 600);
    }, 500);
    
    // Mark lesson as watched
    markLessonAsWatched(lessonId);
    
    // Scroll to video on mobile
    if (window.innerWidth <= 768) {
        document.querySelector('.video-section').scrollIntoView({ 
            behavior: 'smooth' 
        });
    }
}

function markLessonAsWatched(lessonId) {
    fetch('mark_lesson_watched.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            lesson_id: lessonId
        })
    }).catch(error => {
        console.error('Error marking lesson as watched:', error);
    });
}

function markAsCompleted(lessonId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<div class="loading"></div> تحديث...';
    button.disabled = true;
    
    fetch('mark_lesson_completed.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            lesson_id: lessonId
        })
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show completed
            const lessonCard = document.querySelector(`[onclick*="${lessonId}"]`);
            lessonCard.classList.add('completed');
            
            // Update button
            button.innerHTML = '<i class="fas fa-check"></i> تم التحديد';
            button.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
            
            // Show success message
            showNotification('تم تحديد الدرس كمكتمل بنجاح!', 'success');
            
            // Update progress after a delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء تحديث الدرس');
        }
    }).catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalText;
        button.disabled = false;
        showNotification('حدث خطأ أثناء تحديث الدرس', 'error');
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        background: ${type === 'success' ? 'linear-gradient(45deg, #28a745, #20c997)' : 'linear-gradient(45deg, #dc3545, #c82333)'};
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add smooth scrolling for lesson cards
document.addEventListener('DOMContentLoaded', function() {
    const lessonCards = document.querySelectorAll('.lesson-card');
    lessonCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

</body>
</html> 