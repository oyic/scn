<?php
/**
 * Fix Authentication URLs
 * This script will connect to the database and flush rewrite rules
 */

// Database connection settings
$host = 'localhost';
$socket = '/Users/calberto/Library/Application Support/Local/run/kQdwDS3Kf/mysql/mysqld.sock';
$database = 'local';
$username = 'root';
$password = 'root';

echo "=== SCN Authentication URL Fix ===\n\n";

// Connect to database
try {
    $dsn = "mysql:unix_socket=$socket;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Database connection successful\n";
    echo "Database: $database\n";
    echo "Socket: $socket\n\n";
    
    // Get current rewrite rules
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'rewrite_rules'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        $rules = maybe_unserialize($result['option_value']);
        echo "Current rewrite rules found: " . count($rules) . " rules\n";
        
        // Check for our auth rules
        $auth_rules = [
            '^member-login/?$' => 'index.php?member_login=1',
            '^member-register/?$' => 'index.php?member_register=1',
            '^member-dashboard/?$' => 'index.php?member_dashboard=1',
            '^member-logout/?$' => 'index.php?member_logout=1',
            '^test-auth/?$' => 'index.php?scn_test_auth=1'
        ];
        
        echo "\nChecking for SCN authentication rules:\n";
        $missing_rules = [];
        
        foreach ($auth_rules as $pattern => $replacement) {
            if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
                echo "✓ $pattern\n";
            } else {
                echo "✗ Missing: $pattern\n";
                $missing_rules[$pattern] = $replacement;
            }
        }
        
        if (!empty($missing_rules)) {
            echo "\nAdding missing rewrite rules...\n";
            
            // Add missing rules
            foreach ($missing_rules as $pattern => $replacement) {
                $rules[$pattern] = $replacement;
                echo "Added: $pattern -> $replacement\n";
            }
            
            // Save updated rules
            $serialized_rules = maybe_serialize($rules);
            $stmt = $pdo->prepare("UPDATE wp_options SET option_value = ? WHERE option_name = 'rewrite_rules'");
            $stmt->execute([$serialized_rules]);
            
            echo "\n✓ Rewrite rules updated successfully!\n";
        } else {
            echo "\n✓ All authentication rules are present!\n";
        }
        
        // Clear rewrite rules cache
        $stmt = $pdo->prepare("DELETE FROM wp_options WHERE option_name = 'rewrite_rules'");
        $stmt->execute();
        
        echo "✓ Rewrite rules cache cleared\n";
        
    } else {
        echo "✗ No rewrite rules found in database\n";
    }
    
    // Check if plugin is active
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'active_plugins'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        $active_plugins = maybe_unserialize($result['option_value']);
        $scn_active = false;
        
        foreach ($active_plugins as $plugin) {
            if (strpos($plugin, 'scn-membership') !== false) {
                $scn_active = true;
                echo "\n✓ SCN Membership plugin is active: $plugin\n";
                break;
            }
        }
        
        if (!$scn_active) {
            echo "\n⚠ SCN Membership plugin may not be active\n";
        }
    }
    
    echo "\n=== Test URLs ===\n";
    echo "Try accessing these URLs:\n";
    echo "- http://scn.local/member-login/\n";
    echo "- http://scn.local/member-register/\n";
    echo "- http://scn.local/member-dashboard/\n";
    echo "- http://scn.local/test-auth/\n";
    
    echo "\n=== Next Steps ===\n";
    echo "1. Visit one of the URLs above to test\n";
    echo "2. If still not working, go to WordPress Admin > Settings > Permalinks and click 'Save Changes'\n";
    echo "3. Or deactivate and reactivate the SCN Membership plugin\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. MySQL is running\n";
    echo "2. Socket path is correct\n";
    echo "3. Database credentials are correct\n";
}

echo "\nDone!\n";
?>
