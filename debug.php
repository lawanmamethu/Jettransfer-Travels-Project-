<?php
// ============================================================
//  debug.php — Place in htdocs/jettransfer/
//  Open: http://localhost/jettransfer/debug.php
//  DELETE after fixing!
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Report</h2>";
echo "<hr>";

// ── Test 1: DB Connection
echo "<h3>1. Database Connection</h3>";
$conn = new mysqli('localhost', 'root', '', 'jettransfer');
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ FAILED: " . $conn->connect_error . "</p>";
    exit;
}
echo "<p style='color:green'>✅ Connected to 'jettransfer'</p>";

// ── Test 2: Count all destinations
echo "<h3>2. Total rows in destinations table</h3>";
$r = $conn->query("SELECT COUNT(*) as total FROM destinations");
$row = $r->fetch_assoc();
echo "<p>Total rows (including hidden): <strong>" . $row['total'] . "</strong></p>";

// ── Test 3: Count ACTIVE destinations
echo "<h3>3. Active destinations (is_active = 1)</h3>";
$r = $conn->query("SELECT COUNT(*) as total FROM destinations WHERE is_active=1");
$row = $r->fetch_assoc();
echo "<p>Active rows: <strong>" . $row['total'] . "</strong></p>";

// ── Test 4: Show ALL destinations with their is_active status
echo "<h3>4. All destinations in database</h3>";
$r = $conn->query("SELECT id, name, category, is_active FROM destinations ORDER BY name ASC");
if ($r->num_rows === 0) {
    echo "<p style='color:red'>❌ No destinations found in database at all!</p>";
} else {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-family:sans-serif;font-size:.9rem'>";
    echo "<tr style='background:#f0f0f0'><th>ID</th><th>Name</th><th>Category</th><th>is_active</th><th>Visible on site?</th></tr>";
    while ($row = $r->fetch_assoc()) {
        $visible = $row['is_active'] == 1 ? "<span style='color:green'>✅ YES</span>" : "<span style='color:red'>❌ NO (hidden)</span>";
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . $row['is_active'] . "</td>";
        echo "<td>" . $visible . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// ── Test 5: Which file is destination.php using?
echo "<h3>5. destination.php file path</h3>";
echo "<p>Full path: <code>" . realpath('destination.php') . "</code></p>";

// ── Test 6: Check if destination.php has hardcoded fallback
echo "<h3>6. Check destination.php for hardcoded fallback</h3>";
$content = file_get_contents('destination.php');
if (strpos($content, 'FALLBACK') !== false || strpos($content, 'hardcoded') !== false) {
    echo "<p style='color:red'>❌ PROBLEM FOUND: destination.php still has hardcoded fallback data!</p>";
    echo "<p>You need to replace destination.php with the new version.</p>";
} else {
    echo "<p style='color:green'>✅ No hardcoded fallback found</p>";
}

// ── Test 7: Simulate what destination.php does
echo "<h3>7. Simulating destination.php query</h3>";
$destinations = [];
$r = $conn->query("SELECT * FROM destinations WHERE is_active=1 ORDER BY name ASC");
if ($r) while ($row = $r->fetch_assoc()) $destinations[] = $row;
echo "<p>Query returned: <strong>" . count($destinations) . " destinations</strong></p>";
if (count($destinations) > 0) {
    echo "<p style='color:green'>✅ These should show on website:</p><ul>";
    foreach ($destinations as $d) {
        echo "<li>" . htmlspecialchars($d['name']) . " (" . htmlspecialchars($d['category']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ 0 destinations returned — nothing will show on website!</p>";
}

$conn->close();
echo "<hr><p style='color:gray;font-size:.8rem'>Delete debug.php after you're done!</p>";
?>
