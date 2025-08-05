<?php
require_once 'includes/functions.php';

// Get some dynamic content for the about page
try {
    // Get total students and teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'");
    $stmt->execute();
    $total_students = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'teacher'");
    $stmt->execute();
    $total_teachers = $stmt->fetch()['count'];
    
    // Get total courses
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM courses WHERE is_active = 1");
    $stmt->execute();
    $total_courses = $stmt->fetch()['count'];
    
    // Get total enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM enrollments");
    $stmt->execute();
    $total_enrollments = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log("Error fetching about page stats: " . $e->getMessage());
    $total_students = 0;
    $total_teachers = 0;
    $total_courses = 0;
    $total_enrollments = 0;
}

// Check if user is logged in
$is_logged_in = is_logged_in();
$user = null;
if ($is_logged_in) {
    $user = get_current_user_data();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - أكاديمية ARG</title>
    <link rel="stylesheet" href="who.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .header-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: #333;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
        }
        
        @media (max-width: 768px) {
            .header-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                padding: 0 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <header class="header-nav">
        <a href="index.html" class="logo">
            <div class="name2" dir="ltr">
                <span class="letter-a">A</span><span class="letter-c">c</span><span class="letter-a1">a</span>
                <span class="letter-d2">d</span><span class="letter-e">e</span><span class="letter-m1">m</span><span class="letter-y1">y</span>
            </div>
            <div class="name">ARG</div>
        </a>
        
        <nav class="nav-links">
            <a href="index.html">الرئيسية</a>
            <a href="courses.php">الدورات</a>
            <a href="about.php">من نحن</a>
            <?php if ($is_logged_in): ?>
                <div class="user-menu">
                    <span><i class="fas fa-user-circle"></i> مرحباً، <?php echo htmlspecialchars($user['full_name'] ?? 'مستخدم'); ?></span>
                    <?php if (is_teacher()): ?>
                        <a href="teacher.php" class="btn btn-primary">لوحة التحكم</a>
                    <?php elseif (is_student()): ?>
                        <a href="Student.php" class="btn btn-primary">لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-secondary">تسجيل خروج</a>
                </div>
            <?php else: ?>
                <a href="login.html" class="btn btn-primary">تسجيل الدخول</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container" dir="rtl">
        <h2>ARG Academy</h2>
        
        <div class="main">
            <div class="label" dir="rtl">من نحن؟</div>
            <div class="content">
                <p>مرحبًا بكم في <span class="highlight">أكاديمية ARG</span>، وجهتكم الأولى لاكتساب المهارات الأساسية والمتقدمة في تطوير الويب! نحن مؤسسة تعليمية متخصصة في تقديم دورات تدريبية عالية الجودة في <strong>HTML، CSS، وJavaScript</strong>، لمساعدتك على بناء أساس قوي في عالم البرمجة وتصميم المواقع.</p>
            </div>
            <div class="blank"></div>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">طالب مسجل</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_teachers; ?></div>
                    <div class="stat-label">معلم محترف</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_courses; ?></div>
                    <div class="stat-label">دورة متاحة</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_enrollments; ?></div>
                    <div class="stat-label">تسجيل في الدورات</div>
                </div>
            </div>
        </div>

        <div class="heading">
            <div class="label">مهمتنا</div>
            <div class="content">
                <p>نهدف إلى تمكين المبتدئين والمحترفين من تطوير مهاراتهم البرمجية من خلال منهجيات حديثة وتدريب عملي مكثف، مما يتيح لهم الانطلاق بثقة في سوق العمل.</p>
            </div>
            <div class="blank"></div>
        </div>

        <div class="heading">
            <div class="label">لماذا تختار أكاديمية ARG؟</div>
            <div class="content">
                <ul class="features">
                    <li><strong>مناهج متكاملة:</strong> تغطي أساسيات وتقنيات متقدمة في HTML، CSS، وJavaScript.</li>
                    <li><strong>تدريب عملي:</strong> تطبيقات واقعية لتعزيز فهم المفاهيم البرمجية.</li>
                    <li><strong>مدربون محترفون:</strong> بخبرة واسعة في مجال تطوير الويب.</li>
                    <li><strong>دعم مستمر:</strong> مجتمع نشط من المتعلمين لمشاركة الخبرات والاستفسارات.</li>
                    <li><strong>منصة تفاعلية:</strong> نظام تعليمي متطور مع تتبع التقدم والإنجازات.</li>
                    <li><strong>شهادات معتمدة:</strong> شهادات إتمام الدورات معترف بها في سوق العمل.</li>
                </ul>
            </div>
        </div>

        <div class="heading">
            <div class="label">خدماتنا</div>
            <div class="content">
                <ul class="features">
                    <li><strong>دورات HTML:</strong> تعلم أساسيات بناء هيكل صفحات الويب</li>
                    <li><strong>دورات CSS:</strong> إتقان تصميم وتنسيق المواقع الإلكترونية</li>
                    <li><strong>دورات JavaScript:</strong> إضافة التفاعل والوظائف المتقدمة</li>
                    <li><strong>مشاريع عملية:</strong> تطبيق المعرفة على مشاريع حقيقية</li>
                    <li><strong>دعم فني:</strong> مساعدة مستمرة من المدربين والمجتمع</li>
                </ul>
            </div>
        </div>

        <div class="heading">
            <h3>انضم إلينا</h3>
            <div class="content">
                <p class="cta">ابدأ رحلتك في عالم البرمجة مع <span class="highlight">أكاديمية ARG</span> اليوم!</p>
                <div style="text-align: center; margin-top: 2rem;">
                    <?php if (!$is_logged_in): ?>
                        <a href="signUpS.html" class="btn btn-primary" style="margin: 0 1rem;">
                            <i class="fas fa-user-plus"></i> سجل كطالب
                        </a>
                        <a href="signUpT.html" class="btn btn-secondary" style="margin: 0 1rem;">
                            <i class="fas fa-chalkboard-teacher"></i> سجل كمعلم
                        </a>
                    <?php else: ?>
                        <a href="courses.php" class="btn btn-primary">
                            <i class="fas fa-rocket"></i> استكشف الدورات
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="blank"></div>
        </div>

        <button type="submit" onclick="window.location.href='index.html';" class="btn btn-secondary">
            <i class="fas fa-home"></i> العودة للرئيسية
        </button>
    </div>

    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Animate statistics on scroll
            const stats = document.querySelectorAll('.stat-number');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const target = entry.target;
                        const finalValue = parseInt(target.textContent);
                        let currentValue = 0;
                        const increment = finalValue / 50;
                        
                        const timer = setInterval(() => {
                            currentValue += increment;
                            if (currentValue >= finalValue) {
                                target.textContent = finalValue;
                                clearInterval(timer);
                            } else {
                                target.textContent = Math.floor(currentValue);
                            }
                        }, 50);
                        
                        observer.unobserve(target);
                    }
                });
            });
            
            stats.forEach(stat => observer.observe(stat));
        });
    </script>
</body>
</html> 