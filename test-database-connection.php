<?php
/**
 * Test Database Connection
 * This will test different database connection methods to find the right one
 */

echo "<h1>🔍 Database Connection Test</h1>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.success { color: green; background: #efe; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: red; background: #fee; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: blue; background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
</style>";

// Database connection details from wp-config.php
$db_name = 'local';
$db_user = 'root';
$db_pass = 'root';

// Different connection methods to try
$connection_methods = [
    [
        'name' => 'Localhost (from wp-config.php)',
        'host' => 'localhost',
        'port' => null,
        'socket' => null
    ],
    [
        'name' => 'Socket (Local by Flywheel)',
        'host' => 'localhost',
        'port' => null,
        'socket' => '/Users/calberto/Library/Application Support/Local/run/kQdwDS3Kf/mysql/mysqld.sock'
    ],
    [
        'name' => 'Localhost with Port 3306',
        'host' => 'localhost',
        'port' => 3306,
        'socket' => null
    ],
    [
        'name' => '127.0.0.1',
        'host' => '127.0.0.1',
        'port' => null,
        'socket' => null
    ]
];

echo "<div class='info'>";
echo "<h2>Database Configuration from wp-config.php</h2>";
echo "<p><strong>Database Name:</strong> $db_name</p>";
echo "<p><strong>Username:</strong> $db_user</p>";
echo "<p><strong>Password:</strong> $db_pass</p>";
echo "<p><strong>Host (from wp-config):</strong> localhost</p>";
echo "</div>";

foreach ($connection_methods as $method) {
    echo "<div class='test-section'>";
    echo "<h3>Testing: " . $method['name'] . "</h3>";
    
    try {
        // Build DSN based on connection method
        if ($method['socket']) {
            $dsn = "mysql:unix_socket={$method['socket']};dbname=$db_name";
        } elseif ($method['port']) {
            $dsn = "mysql:host={$method['host']};port={$method['port']};dbname=$db_name";
        } else {
            $dsn = "mysql:host={$method['host']};dbname=$db_name";
        }
        
        echo "<p><strong>DSN:</strong> <code>$dsn</code></p>";
        
        // Attempt connection
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<div class='success'>";
        echo "<h4>✅ Connection Successful!</h4>";
        
        // Test basic query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch();
        echo "<p><strong>MySQL Version:</strong> " . $version['version'] . "</p>";
        
        // Test WordPress tables
        $stmt = $pdo->query("SHOW TABLES LIKE 'wp_%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>WordPress Tables Found:</strong> " . count($tables) . "</p>";
        
        if (count($tables) > 0) {
            echo "<p><strong>Sample Tables:</strong> " . implode(', ', array_slice($tables, 0, 5));
            if (count($tables) > 5) {
                echo " and " . (count($tables) - 5) . " more...";
            }
            echo "</p>";
        }
        
        // Test rewrite rules
        $stmt = $pdo->prepare("SELECT option_value FROM wp_options WHERE option_name = 'rewrite_rules'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $rules = maybe_unserialize($result['option_value']);
            if (is_array($rules)) {
                echo "<p><strong>Rewrite Rules:</strong> " . count($rules) . " rules found</p>";
                
                // Check for our auth rules
                $auth_rules = [
                    '^member-login/?$',
                    '^member-dashboard/?$'
                ];
                
                foreach ($auth_rules as $pattern) {
                    if (isset($rules[$pattern])) {
                        echo "<p class='success'>✓ Found: <code>$pattern</code> → <code>" . $rules[$pattern] . "</code></p>";
                    } else {
                        echo "<p class='error'>✗ Missing: <code>$pattern</code></p>";
                    }
                }
            } else {
                echo "<p><strong>Rewrite Rules:</strong> Found but not an array</p>";
            }
        } else {
            echo "<p><strong>Rewrite Rules:</strong> Not found in database</p>";
        }
        
        echo "</div>";
        
        // If we get here, this connection method works
        echo "<div class='success'>";
        echo "<h4>🎉 This Connection Method Works!</h4>";
        echo "<p>You can use this configuration for database operations.</p>";
        echo "</div>";
        
        break; // Stop testing once we find a working method
        
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo "<h4>❌ Connection Failed</h4>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Test WordPress loading
echo "<div class='test-section'>";
echo "<h3>Testing WordPress Loading</h3>";

try {
    // Try to load WordPress
    if (file_exists('../../../wp-config.php')) {
        echo "<p>wp-config.php found</p>";
        
        // Try to include WordPress
        ob_start();
        $wp_loaded = false;
        
        try {
            require_once('../../../wp-config.php');
            $wp_loaded = true;
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            echo "<div class='error'>";
            echo "<p><strong>WordPress Loading Error:</strong> " . $e->getMessage() . "</p>";
            echo "</div>";
        }
        
        if ($wp_loaded) {
            echo "<div class='success'>";
            echo "<h4>✅ WordPress Loaded Successfully!</h4>";
            echo "<p><strong>WordPress Version:</strong> " . get_bloginfo('version') . "</p>";
            echo "<p><strong>Site URL:</strong> " . get_site_url() . "</p>";
            echo "<p><strong>Home URL:</strong> " . get_home_url() . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<p>wp-config.php not found at expected location</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";

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
echo "<p><em>Database connection test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
