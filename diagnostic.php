<?php
/**
 * Event Manager Diagnostic Tool
 * Tests all paths and connections
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Event Manager Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;}.error{color:red;font-weight:bold;}";
echo ".info{color:blue;}.section{background:white;padding:15px;margin:10px 0;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}";
echo "h2{margin-top:0;border-bottom:2px solid #333;padding-bottom:5px;}</style></head><body>";

echo "<h1>🔍 Event Manager Diagnostic Report</h1>";

// Section 1: Environment Info
echo "<div class='section'><h2>1. Environment Information</h2>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "<strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "<br>";
echo "<strong>Current Script:</strong> " . __FILE__ . "<br>";
echo "<strong>Current Dir:</strong> " . __DIR__ . "<br>";
echo "<strong>Script Filename:</strong> " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not set') . "<br>";
echo "</div>";

// Section 2: Path Detection
echo "<div class='section'><h2>2. Database Connection Path Detection</h2>";

$dbPaths = [
    __DIR__ . '/../../includes/db_connect.php',
    __DIR__ . '/../../../includes/db_connect.php',
    __DIR__ . '/../../../../includes/db_connect.php',
    $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php',
    $_SERVER['DOCUMENT_ROOT'] . '/public_html/includes/db_connect.php',
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>Path</th><th>Exists?</th><th>Readable?</th></tr>";

$foundPath = null;
foreach ($dbPaths as $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($path) . "</code></td>";
    echo "<td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ YES' : '✗ NO') . "</td>";
    echo "<td class='" . ($readable ? 'success' : 'error') . "'>" . ($readable ? '✓ YES' : '✗ NO') . "</td>";
    echo "</tr>";
    
    if ($exists && $readable && !$foundPath) {
        $foundPath = $path;
    }
}
echo "</table>";

if ($foundPath) {
    echo "<p class='success'>✓ Found database connection at: <code>$foundPath</code></p>";
} else {
    echo "<p class='error'>✗ No database connection file found!</p>";
}
echo "</div>";

// Section 3: Directory Traversal Test
echo "<div class='section'><h2>3. Directory Traversal Test</h2>";
$currentDir = __DIR__;
echo "<ol>";
for ($i = 0; $i < 5; $i++) {
    $testPath = $currentDir . '/includes/db_connect.php';
    $exists = file_exists($testPath);
    echo "<li>Level $i: <code>" . htmlspecialchars($testPath) . "</code> - ";
    echo "<span class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ EXISTS' : '✗ NOT FOUND') . "</span></li>";
    if ($exists) {
        $foundPath = $testPath;
        break;
    }
    $currentDir = dirname($currentDir);
}
echo "</ol></div>";

// Section 4: Try to Load Database Connection
echo "<div class='section'><h2>4. Database Connection Test</h2>";

if ($foundPath) {
    try {
        require_once $foundPath;
        echo "<p class='success'>✓ Database connection file loaded successfully!</p>";
        
        // Test if PDO connection exists
        if (isset($pdo) && $pdo instanceof PDO) {
            echo "<p class='success'>✓ PDO connection object exists!</p>";
            
            // Test database query
            try {
                $stmt = $pdo->query("SELECT DATABASE() as db_name");
                $result = $stmt->fetch();
                echo "<p class='success'>✓ Database connected: <strong>" . htmlspecialchars($result['db_name']) . "</strong></p>";
            } catch (Exception $e) {
                echo "<p class='error'>✗ Database query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p class='error'>✗ PDO connection object not found!</p>";
        }
        
        // Test if mysqli connection exists
        if (isset($conn) && $conn instanceof mysqli) {
            echo "<p class='success'>✓ MySQLi connection object exists!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Failed to load database connection: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot test database connection - file not found!</p>";
}
echo "</div>";

// Section 5: Event Manager Files Check
echo "<div class='section'><h2>5. Event Manager Files Check</h2>";
$emFiles = [
    'includes/em_auth.php',
    'includes/em_db.php',
    'includes/em_functions.php',
    'includes/em_header.php',
    'pages/dashboard.php',
    'index.php',
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;width:100%;'>";
echo "<tr><th>File</th><th>Exists?</th><th>Readable?</th><th>Size</th></tr>";

foreach ($emFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    $readable = $exists ? is_readable($fullPath) : false;
    $size = $exists ? filesize($fullPath) : 0;
    
    echo "<tr>";
    echo "<td><code>" . htmlspecialchars($file) . "</code></td>";
    echo "<td class='" . ($exists ? 'success' : 'error') . "'>" . ($exists ? '✓ YES' : '✗ NO') . "</td>";
    echo "<td class='" . ($readable ? 'success' : 'error') . "'>" . ($readable ? '✓ YES' : '✗ NO') . "</td>";
    echo "<td>" . number_format($size) . " bytes</td>";
    echo "</tr>";
}
echo "</table></div>";

// Section 6: Recommendations
echo "<div class='section'><h2>6. Recommendations</h2>";
if (!$foundPath) {
    echo "<p class='error'><strong>CRITICAL:</strong> Database connection file not found!</p>";
    echo "<p>Possible solutions:</p>";
    echo "<ol>";
    echo "<li>Verify that <code>includes/db_connect.php</code> exists in your web root</li>";
    echo "<li>Check file permissions (should be readable by web server)</li>";
    echo "<li>Verify the directory structure matches expectations</li>";
    echo "</ol>";
} else {
    echo "<p class='success'>✓ All critical files found!</p>";
    echo "<p>Event Manager should be working. If you still see errors:</p>";
    echo "<ol>";
    echo "<li>Check PHP error logs for detailed error messages</li>";
    echo "<li>Verify database credentials in db_connect.php</li>";
    echo "<li>Run database migrations if not done yet</li>";
    echo "</ol>";
}
echo "</div>";

echo "<div class='section'><h2>7. Next Steps</h2>";
echo "<p>If database connection was found, try accessing:</p>";
echo "<ul>";
echo "<li><a href='/event-manager/'>Event Manager Home</a></li>";
echo "<li><a href='/event-manager/migrations/install.php'>Run Database Migrations</a></li>";
echo "<li><a href='/event-manager/test_setup.php'>Setup Test</a></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
