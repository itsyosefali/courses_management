<?php
// Setup script for ARG Academy
echo "=== ARG Academy Setup ===\n";

// Create necessary directories
$directories = [
    'uploads',
    'uploads/certificates',
    'uploads/profile_pictures',
    'config',
    'includes',
    'auth',
    'database'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ Created directory: $dir\n";
        } else {
            echo "✗ Failed to create directory: $dir\n";
        }
    } else {
        echo "✓ Directory already exists: $dir\n";
    }
}

// Database setup instructions
echo "\n=== Database Setup ===\n";
echo "1. Create a MySQL database named 'arg_academy'\n";
echo "2. Import the schema from 'database/schema.sql'\n";
echo "3. Update database credentials in 'config/database.php'\n";
echo "4. Make sure PHP has PDO and MySQL extensions enabled\n";

// Check PHP extensions
echo "\n=== PHP Extensions Check ===\n";
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ $ext extension is loaded\n";
    } else {
        echo "✗ $ext extension is NOT loaded\n";
    }
}

// Check file permissions
echo "\n=== File Permissions Check ===\n";
$files_to_check = [
    'config/database.php',
    'includes/functions.php',
    'auth/login.php',
    'auth/register_student.php',
    'auth/register_teacher.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file is missing\n";
    }
}

echo "\n=== Setup Complete ===\n";
echo "Next steps:\n";
echo "1. Configure your web server to point to this directory\n";
echo "2. Set up the database using the schema file\n";
echo "3. Update database credentials in config/database.php\n";
echo "4. Test the application by visiting index.html\n";
?> 