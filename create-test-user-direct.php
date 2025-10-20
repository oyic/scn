<?php
/**
 * Create Test User - Direct Database Method
 * Creates test user and profile directly in the database
 */

// Database connection details (working socket connection)
$host = 'localhost';
$socket = '/Users/calberto/Library/Application Support/Local/run/kQdwDS3Kf/mysql/mysqld.sock';
$dbname = 'local';
$username = 'root';
$password = 'root';

echo "<h1>👤 Creating Test User</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

try {
    // Connect to database
    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>";
    echo "<h2>✅ Database Connected!</h2>";
    echo "</div>";
    
    // Check if test user already exists
    $stmt = $pdo->prepare("SELECT ID, user_login FROM wp_users WHERE user_login = 'testmember'");
    $stmt->execute();
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_user) {
        echo "<div class='info'>";
        echo "<h3>ℹ️ Test User Already Exists</h3>";
        echo "<p>User ID: " . $existing_user['ID'] . "</p>";
        echo "<p>Username: " . $existing_user['user_login'] . "</p>";
        echo "</div>";
        $user_id = $existing_user['ID'];
    } else {
        // Create test user
        echo "<div class='info'>";
        echo "<h3>🔨 Creating Test User</h3>";
        echo "</div>";
        
        // Hash password (WordPress uses phpass)
        $password_hash = '$P$B123456789abcdefghijklmnopqrstuv'; // This is a placeholder - we'll use a real hash
        
        // For now, let's use a simple hash that WordPress can handle
        $password_hash = password_hash('TestMember123!', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO wp_users (
                user_login, user_pass, user_nicename, user_email, 
                user_url, user_registered, user_activation_key, 
                user_status, display_name
            ) VALUES (?, ?, ?, ?, '', NOW(), '', 0, ?)
        ");
        
        $stmt->execute([
            'testmember',
            $password_hash,
            'testmember',
            'testmember@example.com',
            'Test Member'
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        echo "<div class='success'>";
        echo "<h3>✅ Test User Created!</h3>";
        echo "<p>User ID: $user_id</p>";
        echo "<p>Username: testmember</p>";
        echo "<p>Password: TestMember123!</p>";
        echo "</div>";
    }
    
    // Check if user has profile
    $stmt = $pdo->prepare("
        SELECT p.ID, p.post_title 
        FROM wp_posts p 
        INNER JOIN wp_postmeta pm ON p.ID = pm.post_id 
        WHERE p.post_type = 'profile' 
        AND pm.meta_key = 'scn_user_id' 
        AND pm.meta_value = ? 
        AND p.post_status = 'publish'
    ");
    $stmt->execute([$user_id]);
    $existing_profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_profile) {
        echo "<div class='info'>";
        echo "<h3>ℹ️ Profile Already Exists</h3>";
        echo "<p>Profile ID: " . $existing_profile['ID'] . "</p>";
        echo "<p>Profile Title: " . $existing_profile['post_title'] . "</p>";
        echo "</div>";
    } else {
        // Create profile
        echo "<div class='info'>";
        echo "<h3>🔨 Creating Profile</h3>";
        echo "</div>";
        
        $stmt = $pdo->prepare("
            INSERT INTO wp_posts (
                post_author, post_date, post_date_gmt, post_content, 
                post_title, post_excerpt, post_status, comment_status, 
                ping_status, post_password, post_name, to_ping, 
                pinged, post_modified, post_modified_gmt, post_content_filtered, 
                post_parent, guid, menu_order, post_type, post_mime_type, comment_count
            ) VALUES (?, NOW(), NOW(), '', ?, '', 'publish', 'closed', 'closed', '', ?, '', '', NOW(), NOW(), '', 0, '', 0, 'profile', '', 0)
        ");
        
        $stmt->execute([
            $user_id,
            'Test Member Profile',
            'test-member-profile'
        ]);
        
        $profile_id = $pdo->lastInsertId();
        
        // Add profile meta
        $stmt = $pdo->prepare("INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES (?, ?, ?)");
        $stmt->execute([$profile_id, 'scn_user_id', $user_id]);
        $stmt->execute([$profile_id, 'member_since', date('Y-m-d H:i:s')]);
        
        echo "<div class='success'>";
        echo "<h3>✅ Profile Created!</h3>";
        echo "<p>Profile ID: $profile_id</p>";
        echo "<p>Profile Title: Test Member Profile</p>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h2>🎉 Test User Setup Complete!</h2>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> testmember</li>";
    echo "<li><strong>Password:</strong> TestMember123!</li>";
    echo "</ul>";
    echo "<p><strong>Test URLs:</strong></p>";
    echo "<ul>";
    echo "<li><a href='/member-login/' target='_blank'>/member-login/</a> - Login page</li>";
    echo "<li><a href='/member-dashboard/' target='_blank'>/member-dashboard/</a> - Dashboard (after login)</li>";
    echo "<li><a href='/test-auth/' target='_blank'>/test-auth/</a> - Test page</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><em>Test user creation completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
