-- ARG Academy Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS arg_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arg_academy;

-- Users table (for both students and teachers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    id_number VARCHAR(50),
    user_type ENUM('student', 'teacher') NOT NULL,
    subject_specialization VARCHAR(255),
    grade_level ENUM('middle', 'high', 'university') NULL,
    profile_picture VARCHAR(255),
    certificate_file VARCHAR(255),
    wallet_balance DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0.00,
    discount_percentage INT DEFAULT 0,
    teacher_id INT,
    category VARCHAR(100),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Lessons table
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    video_url VARCHAR(255),
    lesson_number INT,
    duration_minutes INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Enrollments table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    progress_percentage INT DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Lesson progress table
CREATE TABLE lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lesson_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    watched_duration INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (student_id, lesson_id)
);

-- Announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT,
    target_audience ENUM('all', 'students', 'teachers') DEFAULT 'all',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Transactions table (for wallet)
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('deposit', 'withdrawal', 'purchase', 'refund') NOT NULL,
    description TEXT,
    reference_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Quizzes table
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    total_questions INT DEFAULT 0,
    time_limit_minutes INT DEFAULT 30,
    passing_score INT DEFAULT 70,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Quiz questions table
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'text') DEFAULT 'multiple_choice',
    points INT DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz answers table
CREATE TABLE quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Quiz attempts table
CREATE TABLE quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    time_taken_minutes INT DEFAULT 0,
    is_passed BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Insert default courses
INSERT INTO courses (title, description, price, discount_percentage, category, level) VALUES
('HTML (HyperText Markup Language)', 'لغة ترميز تستخدم لإنشاء هيكل صفحات الويب، حيث تحدد العناصر مثل العناوين، الفقرات، الصور، والروابط وتمثل حجر الأساس للموقع.', 99.00, 20, 'Web Development', 'beginner'),
('CSS (Cascading Style Sheets)', 'لغة تنسيق تُستخدم لتصميم صفحات الويب، حيث تتحكم في الألوان، الخطوط، التخطيط، والأنماط لجعل المواقع أكثر جاذبية وتناسقًا.', 89.00, 15, 'Web Development', 'beginner'),
('JavaScript', 'لغة برمجة ديناميكية تُستخدم لإضافة التفاعل والوظائف إلى صفحات الويب، مثل الاستجابات للأحداث، التحقق من البيانات، وتحريك العناصر.', 129.00, 10, 'Web Development', 'intermediate');

-- Insert default lessons for HTML course
INSERT INTO lessons (course_id, title, description, video_url, lesson_number, duration_minutes) VALUES
(1, 'مقدمة إلى HTML', 'هل تريد تعلم كيفية إنشاء صفحات ويب بسهولة؟ في هذا الفيديو، ستتعرف على أساسيات لغة HTML، اللغة الأساسية المستخدمة في بناء مواقع الإنترنت.', 'video/intro.mp4', 1, 15),
(1, 'التعامل مع العناوين &lt;h1&gt;...', 'هل تريد معرفة كيفية تنظيم المحتوى داخل صفحات الويب؟ في هذا الفيديو، سنتعرف على وسوم العناوين (Headings) في HTML.', 'video/h.mp4', 2, 12),
(1, 'التعامل مع النصوص &lt;p&gt;', 'هل تساءلت يومًا كيف يتم تنسيق النصوص داخل صفحات الويب؟ في هذا الفيديو، سنستكشف كيفية استخدام وسم الفقرة &lt;p&gt; في HTML.', 'video/p.mp4', 3, 10),
(1, 'التعامل مع الصور &lt;img&gt;', 'كيف يمكنك إضافة الصور إلى موقعك الإلكتروني؟ في هذا الفيديو، سنتعرف على وسم &lt;img&gt; في HTML.', 'video/image.mp4', 4, 8),
(1, 'التعامل مع الروابط &lt;a&gt;', 'تعلم كيفية إضافة الروابط إلى صفحات الويب الخاصة بك باستخدام وسم &lt;a&gt; في HTML.', 'video/link.mp4', 5, 10); 