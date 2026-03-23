<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>✅ PHP is working! Version: " . phpversion() . "</h2>";

// Test 1: DB connection
echo "<h3>Test 1 — Database Connection</h3>";
$conn = @new mysqli('localhost', 'root', '', 'jettransfer_db');
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ DB Error: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>✅ Connected to jettransfer_db!</p>";

    // Test 2: destinations table
    echo "<h3>Test 2 — Destinations Table</h3>";
    $res = $conn->query("SELECT COUNT(*) as total FROM destinations");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "<p style='color:green'>✅ Table exists! Total rows: <strong>" . $row['total'] . "</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Table error: " . $conn->error . "</p>";
        echo "<p>👉 You need to import <strong>jettransfer_destinations.sql</strong> in phpMyAdmin!</p>";
    }

    // Test 3: session
    echo "<h3>Test 3 — Session</h3>";
    session_start();
    echo "<p style='color:green'>✅ Sessions working!</p>";

    $conn->close();
}

echo "<hr><p style='color:gray'>Delete test.php after you're done!</p>";
?>