<?php
echo "=== Database Connection Test ===\n";

try {
    require_once 'config/database.php';
    echo "✓ Database connection successful!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✓ Database query successful! Users count: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure MySQL is running\n";
    echo "2. Check database credentials in config/database.php\n";
    echo "3. Ensure database 'arg_academy' exists\n";
    echo "4. Import the schema from database/schema.sql\n";
}

echo "\n=== Test Complete ===\n";
?> 