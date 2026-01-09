<?php
// Set the content type to HTML for better formatting
header('Content-Type: text/html; charset=utf-8');

echo "<h1>PHP Path & File System Debugger</h1>";

// --- Basic Path Information ---
echo "<h2>Core Path Information</h2>";
echo "<p><strong>Current Working Directory (getcwd):</strong> <code>" . getcwd() . "</code></p>";
echo "<p><strong>This File's Directory (__DIR__):</strong> <code>" . __DIR__ . "</code></p>";
echo "<p><strong>This File's Full Path (__FILE__):</strong> <code>" . __FILE__ . "</code></p>";

// --- Check for 'includes' directory and 'db.php' ---
echo "<h2>File & Directory Existence Check</h2>";

// Define the paths we expect to exist
$htdocs_path = 'C:\\xampp\\htdocs';
$includes_dir_path = $htdocs_path . '\\includes';
$db_file_path = $includes_dir_path . '\\db.php';

// Check for the 'includes' directory
echo "<p>Checking for directory: <code>" . $includes_dir_path . "</code></p>";
if (is_dir($includes_dir_path)) {
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: The 'includes' directory was found!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>ERROR: The 'includes' directory was NOT found at this path.</p>";
}

// Check for the 'db.php' file
echo "<p>Checking for file: <code>" . $db_file_path . "</code></p>";
if (file_exists($db_file_path)) {
    echo "<p style='color:green; font-weight:bold;'>SUCCESS: The 'db.php' file was found!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>ERROR: The 'db.php' file was NOT found at this path.</p>";
}

// --- List all files/folders in htdocs ---
echo "<h2>Directory Listing for <code>" . $htdocs_path . "</code></h2>";
echo "<p>This shows everything PHP can see inside your htdocs folder.</p>";
echo "<pre style='background-color:#f5f5f5; border:1px solid #ccc; padding:10px; border-radius:4px;'>";

// Use scandir to get the contents
$htdocs_contents = scandir($htdocs_path);

// Print the array in a readable format
print_r($htdocs_contents);

echo "</pre>";

?>