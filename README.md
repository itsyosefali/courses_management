# ARG Academy - Educational Platform

A comprehensive Arabic educational platform for web development courses, built with HTML, CSS, JavaScript, and PHP.

## Features

### For Students
- User registration and authentication
- Course browsing and enrollment
- Video lesson viewing with progress tracking
- Wallet system for course purchases
- Progress tracking and completion certificates
- Responsive design for all devices

### For Teachers
- Teacher registration with certificate upload
- Course creation and management
- Student progress monitoring
- Announcement system
- Analytics and reporting

### Technical Features
- Secure user authentication
- Database-driven content management
- File upload system
- Transaction management
- Responsive Arabic UI
- Session management

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Additional**: Bootstrap 5, Font Awesome

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PHP extensions: PDO, PDO_MySQL, JSON, MBString

### Setup Instructions

1. **Clone or download the project**
   ```bash
   git clone <repository-url>
   cd arg-academy
   ```

2. **Run the setup script**
   ```bash
   php setup.php
   ```

3. **Database Setup**
   - Create a MySQL database named `arg_academy`
   - Import the schema from `database/schema.sql`
   - Update database credentials in `config/database.php`

4. **Configure Web Server**
   - Point your web server to the project directory
   - Ensure PHP is properly configured
   - Set appropriate file permissions

5. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/certificates/
   chmod 755 uploads/profile_pictures/
   ```

## Database Schema

The application uses the following main tables:

- **users**: Student and teacher accounts
- **courses**: Course information and pricing
- **lessons**: Individual lesson content
- **enrollments**: Student course registrations
- **lesson_progress**: Student progress tracking
- **transactions**: Wallet transactions
- **announcements**: System announcements
- **quizzes**: Course assessments

## File Structure

```
arg-academy/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── functions.php         # Common functions
├── auth/
│   ├── login.php            # Login handler
│   ├── register_student.php # Student registration
│   ├── register_teacher.php # Teacher registration
│   └── logout.php           # Logout handler
├── database/
│   └── schema.sql           # Database schema
├── uploads/
│   ├── certificates/        # Teacher certificates
│   └── profile_pictures/    # User profile pictures
├── image/                   # Static images
├── video/                   # Course videos
├── fonts/                   # Custom fonts
├── *.html                   # Static HTML pages
├── *.css                    # Stylesheets
├── *.php                    # Dynamic PHP pages
├── setup.php               # Setup script
└── README.md               # This file
```

## Usage

### For Students
1. Register a new student account
2. Browse available courses
3. Purchase courses using wallet balance
4. Watch video lessons and track progress
5. Complete courses and earn certificates

### For Teachers
1. Register a teacher account with certificate
2. Create and manage courses
3. Upload video lessons
4. Monitor student progress
5. Post announcements

## Security Features

- Password hashing using PHP's built-in `password_hash()`
- SQL injection prevention with prepared statements
- Input sanitization and validation
- Session-based authentication
- File upload security measures

## Customization

### Styling
- Modify CSS files in the root directory
- Update color schemes and fonts
- Customize responsive breakpoints

### Content
- Add new courses in the database
- Upload video files to the `video/` directory
- Update course descriptions and pricing

### Features
- Extend functionality by adding new PHP files
- Modify database schema for additional features
- Add new user roles or permissions

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Issues**
   - Check file permissions on upload directories
   - Verify PHP upload settings in `php.ini`
   - Ensure sufficient disk space

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies

4. **Video Playback Issues**
   - Ensure video files are in supported formats (MP4 recommended)
   - Check video file permissions
   - Verify web server supports video streaming

## Support

For technical support or questions:
- Check the troubleshooting section above
- Review PHP error logs
- Ensure all prerequisites are met

## License

This project is created for educational purposes. Please ensure compliance with any third-party licenses for included libraries and resources.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note**: This is a student project created for educational purposes. The deadline for this project is tomorrow, so all core functionality has been implemented with a focus on meeting the requirements. # courses_management
