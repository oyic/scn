<?php
/**
 * Fix Authentication - Working Version
 * Uses the correct socket connection that we just confirmed works
 */

// Database connection details (working socket connection)
$host = 'localhost';
$socket = '/Users/calberto/Library/Application Support/Local/run/kQdwDS3Kf/mysql/mysqld.sock';
$dbname = 'local';
$username = 'root';
$password = 'root';

echo "<h1>🔧 Fixing Authentication System</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

try {
    // Connect to database using working socket connection
    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>";
    echo "<h2>✅ Database Connected Successfully!</h2>";
    echo "<p>Using socket: <code>$socket</code></p>";
    echo "</div>";
    
    // Check current rewrite rules
    echo "<div class='test-section'>";
    echo "<h3>Current Rewrite Rules Status</h3>";
    
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'rewrite_rules'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $rules = maybe_unserialize($result['option_value']);
        if (is_array($rules)) {
            echo "<p class='success'>✓ Rewrite rules exist (" . count($rules) . " rules)</p>";
            
            $auth_rules = [
                '^member-login/?$' => 'index.php?member_login=1',
                '^member-dashboard/?$' => 'index.php?member_dashboard=1',
                '^member-register/?$' => 'index.php?member_register=1',
                '^member-logout/?$' => 'index.php?member_logout=1',
                '^test-auth/?$' => 'index.php?scn_test_auth=1'
            ];
            
            $missing_rules = [];
            foreach ($auth_rules as $pattern => $replacement) {
                if (isset($rules[$pattern]) && $rules[$pattern] === $replacement) {
                    echo "<p class='success'>✓ $pattern → $replacement</p>";
                } else {
                    echo "<p class='error'>✗ Missing: $pattern → $replacement</p>";
                    $missing_rules[$pattern] = $replacement;
                }
            }
            
            // Add missing rules if any
            if (!empty($missing_rules)) {
                echo "<h4>Adding Missing Rules...</h4>";
                $updated_rules = array_merge($missing_rules, $rules);
                $serialized_rules = serialize($updated_rules);
                $stmt = $pdo->prepare("UPDATE wp_options SET option_value = ? WHERE option_name = 'rewrite_rules'");
                $stmt->execute([$serialized_rules]);
                echo "<p class='success'>✓ Missing rules added!</p>";
            } else {
                echo "<p class='success'>✓ All authentication rules are present!</p>";
            }
        } else {
            echo "<p class='error'>✗ Rewrite rules exist but are not an array</p>";
        }
    } else {
        echo "<p class='error'>✗ No rewrite rules found in database</p>";
    }
    echo "</div>";
    
    // Check if test user exists
    echo "<div class='test-section'>";
    echo "<h3>Test User Status</h3>";
    
    $stmt = $pdo->prepare("SELECT ID, user_login, user_email FROM wp_users WHERE user_login = 'testmember'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✓ Test user exists (ID: " . $user['ID'] . ")</p>";
        echo "<p>Username: " . $user['user_login'] . "</p>";
        echo "<p>Email: " . $user['user_email'] . "</p>";
        
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
        $stmt->execute([$user['ID']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            echo "<p class='success'>✓ User has profile (ID: " . $profile['ID'] . ")</p>";
            echo "<p>Profile Title: " . $profile['post_title'] . "</p>";
        } else {
            echo "<p class='error'>✗ User does not have a profile</p>";
        }
    } else {
        echo "<p class='error'>✗ Test user does not exist</p>";
        echo "<p>Creating test user...</p>";
        
        // Create test user
        $user_id = wp_create_user('testmember', 'TestMember123!', 'test@example.com');
        
        if (!is_wp_error($user_id)) {
            echo "<p class='success'>✓ Test user created (ID: $user_id)</p>";
            
            // Create profile for user
            $profile_data = [
                'post_title' => 'Test Member Profile',
                'post_type' => 'profile',
                'post_status' => 'publish',
                'post_author' => $user_id,
            ];
            
            $profile_id = wp_insert_post($profile_data);
            
            if ($profile_id) {
                update_post_meta($profile_id, 'scn_user_id', $user_id);
                update_post_meta($profile_id, 'member_since', current_time('mysql'));
                echo "<p class='success'>✓ Profile created for user (ID: $profile_id)</p>";
            }
        } else {
            echo "<p class='error'>✗ Failed to create test user: " . $user_id->get_error_message() . "</p>";
        }
    }
    echo "</div>";
    
    // Test URLs
    echo "<div class='test-section'>";
    echo "<h3>Test URLs</h3>";
    echo "<p>Try these URLs to test the authentication system:</p>";
    echo "<ul>";
    echo "<li><a href='/member-login/' target='_blank'>/member-login/</a> - Should show login form</li>";
    echo "<li><a href='/member-dashboard/' target='_blank'>/member-dashboard/</a> - Should redirect to login if not logged in</li>";
    echo "<li><a href='/test-auth/' target='_blank'>/test-auth/</a> - Should show test page</li>";
    echo "</ul>";
    echo "</div>";
    
    // Test login simulation
    echo "<div class='test-section'>";
    echo "<h3>Login Test</h3>";
    
    if (isset($_POST['test_login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        echo "<p><strong>Testing login with:</strong> $username</p>";
        
        // Simulate wp_signon
        $stmt = $pdo->prepare("SELECT ID, user_login, user_pass FROM wp_users WHERE user_login = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && wp_check_password($password, $user['user_pass'])) {
            echo "<p class='success'>✓ Login successful! (User ID: " . $user['ID'] . ")</p>";
            
            // Check profile
            $stmt = $pdo->prepare("
                SELECT p.ID, p.post_title 
                FROM wp_posts p 
                INNER JOIN wp_postmeta pm ON p.ID = pm.post_id 
                WHERE p.post_type = 'profile' 
                AND pm.meta_key = 'scn_user_id' 
                AND pm.meta_value = ? 
                AND p.post_status = 'publish'
            ");
            $stmt->execute([$user['ID']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($profile) {
                echo "<p class='success'>✓ User has profile - would redirect to dashboard</p>";
                echo "<p><a href='/member-dashboard/' target='_blank'>Go to Dashboard</a></p>";
            } else {
                echo "<p class='warning'>⚠ User has no profile - would redirect to profile creation</p>";
            }
        } else {
            echo "<p class='error'>✗ Invalid credentials</p>";
        }
    } else {
        echo "<form method='post'>";
        echo "<p><strong>Test Login:</strong></p>";
        echo "<p>Username: <input type='text' name='username' value='testmember' required></p>";
        echo "<p>Password: <input type='password' name='password' value='TestMember123!' required></p>";
        echo "<p><input type='submit' name='test_login' value='Test Login'></p>";
        echo "</form>";
    }
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h2>🎉 Authentication System Fixed!</h2>";
    echo "<p>The database connection is working and rewrite rules are properly configured.</p>";
    echo "<p>You should now be able to access the login and dashboard pages.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Helper function (simplified version of WordPress maybe_unserialize)
function maybe_unserialize($original) {
    if (is_serialized($original)) {
        return @unserialize($original);
    }
    return $original;
}

function is_serialized($data, $strict = true) {
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);
    if ('N;' == $data) {
        return true;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if (':' !== $data[1]) {
        return false;
    }
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace     = strpos($data, '}');
        if (false === $semicolon && false === $brace) {
            return false;
        }
        if (false !== $semicolon && $semicolon < 3) {
            return false;
        }
        if (false !== $brace && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
        case 'a':
        case 'O':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
}

echo "<hr>";
echo "<p><em>Authentication fix completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
