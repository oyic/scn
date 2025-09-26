<?php
/**
 * Fix Rewrite Rules - Direct Database Approach
 * This bypasses WordPress to directly fix the rewrite rules
 */

// Database connection details from Local
$host = 'localhost';
$socket = '/Users/calberto/Library/Application Support/Local/run/kQdwDS3Kf/mysql/mysqld.sock';
$dbname = 'local';
$username = 'root';
$password = 'root';

try {
    // Connect to database
    $pdo = new PDO("mysql:unix_socket=$socket;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 Fixing Rewrite Rules</h1>";
    echo "<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }</style>";
    
    // Get current rewrite rules
    $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'rewrite_rules'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $current_rules = maybe_unserialize($result['option_value']);
        echo "<h2>Current Rules:</h2>";
        if (is_array($current_rules)) {
            echo "<p>Found " . count($current_rules) . " rules</p>";
        } else {
            echo "<p>Rules are not an array</p>";
        }
    } else {
        echo "<p>No rewrite rules found</p>";
        $current_rules = [];
    }
    
    // Add our authentication rules
    $new_rules = [
        '^member-login/?$' => 'index.php?scn_member_login=1',
        '^member-register/?$' => 'index.php?scn_member_register=1',
        '^member-dashboard/?$' => 'index.php?scn_member_dashboard=1',
        '^member-logout/?$' => 'index.php?scn_member_logout=1',
        '^test-auth/?$' => 'index.php?scn_test_auth=1'
    ];
    
    // Merge with existing rules
    if (is_array($current_rules)) {
        $updated_rules = array_merge($new_rules, $current_rules);
    } else {
        $updated_rules = $new_rules;
    }
    
    // Update the database
    $serialized_rules = serialize($updated_rules);
    $stmt = $pdo->prepare("UPDATE wp_options SET option_value = ? WHERE option_name = 'rewrite_rules'");
    $stmt->execute([$serialized_rules]);
    
    echo "<h2>✅ Rules Updated!</h2>";
    echo "<p>Added " . count($new_rules) . " authentication rules:</p>";
    echo "<ul>";
    foreach ($new_rules as $pattern => $replacement) {
        echo "<li><code>$pattern</code> → <code>$replacement</code></li>";
    }
    echo "</ul>";
    
    // Also update permalink structure to trigger rewrite rule refresh
    $stmt = $pdo->prepare("UPDATE wp_options SET option_value = '/%postname%/' WHERE option_name = 'permalink_structure'");
    $stmt->execute();
    
    echo "<h2>✅ Permalink Structure Updated</h2>";
    echo "<p>Set to: <code>/%postname%/</code></p>";
    
    // Clear any cached rewrite rules
    $stmt = $pdo->prepare("DELETE FROM wp_options WHERE option_name = 'rewrite_rules'");
    $stmt->execute();
    
    echo "<h2>✅ Cache Cleared</h2>";
    echo "<p>Rewrite rules cache cleared. WordPress will regenerate rules on next page load.</p>";
    
    echo "<h2>🧪 Test URLs</h2>";
    echo "<p>Try these URLs:</p>";
    echo "<ul>";
    echo "<li><a href='/member-login/' target='_blank'>/member-login/</a></li>";
    echo "<li><a href='/member-dashboard/' target='_blank'>/member-dashboard/</a></li>";
    echo "<li><a href='/test-auth/' target='_blank'>/test-auth/</a></li>";
    echo "</ul>";
    
    echo "<h2>📝 Next Steps</h2>";
    echo "<ol>";
    echo "<li>Go to WordPress Admin → Settings → Permalinks</li>";
    echo "<li>Click 'Save Changes' (this will regenerate rewrite rules)</li>";
    echo "<li>Test the URLs above</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<h1>❌ Database Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Connection details used:</p>";
    echo "<ul>";
    echo "<li>Socket: $socket</li>";
    echo "<li>Database: $dbname</li>";
    echo "<li>User: $username</li>";
    echo "</ul>";
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
?>
