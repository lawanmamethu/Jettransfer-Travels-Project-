<?php
// ============================================================
//  login_debug.php — Place in htdocs/jettransfer/
//  Open: http://localhost/jettransfer/login_debug.php
//  DELETE after fixing!
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h2 style='font-family:sans-serif'>🔍 Login Debug</h2><hr>";

// ── Test 1: DB connection
echo "<h3>1. Database</h3>";
$conn = new mysqli('localhost', 'root', '', 'jettransfer');
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ DB failed: " . $conn->connect_error . "</p>";
    exit;
}
$conn->set_charset('utf8mb4');
echo "<p style='color:green'>✅ Connected</p>";

// ── Test 2: Users table exists
echo "<h3>2. Users table</h3>";
$r = $conn->query("SELECT COUNT(*) as total FROM users");
if (!$r) {
    echo "<p style='color:red'>❌ Table missing: " . $conn->error . "</p>";
    echo "<p>Run: <code>CREATE TABLE users ...</code> — import users.sql again</p>";
} else {
    $row = $r->fetch_assoc();
    echo "<p style='color:green'>✅ Table exists — <strong>" . $row['total'] . " users</strong> registered</p>";
}

// ── Test 3: Show all users
echo "<h3>3. Registered users</h3>";
$r = $conn->query("SELECT id, first_name, last_name, email, is_active, created_at FROM users ORDER BY id DESC");
if ($r && $r->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-family:sans-serif;font-size:.9rem'>";
    echo "<tr style='background:#f0f0f0'><th>ID</th><th>Name</th><th>Email</th><th>Active</th><th>Registered</th></tr>";
    while ($u = $r->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $u['id'] . "</td>";
        echo "<td>" . htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($u['email']) . "</td>";
        echo "<td>" . ($u['is_active'] ? '✅' : '❌') . "</td>";
        echo "<td>" . $u['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange'>⚠️ No users found — have you registered yet?</p>";
    echo "<p>Go to <a href='register.php'>register.php</a> and create an account first.</p>";
}

// ── Test 4: Simulate login POST
echo "<h3>4. Test login form</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    echo "<p>Email entered: <strong>" . htmlspecialchars($email) . "</strong></p>";
    echo "<p>Password entered: <strong>" . (empty($pass) ? '(empty!)' : '(filled)') . "</strong></p>";

    if ($email && $pass) {
        $e = $conn->real_escape_string($email);
        $r = $conn->query("SELECT id, first_name, last_name, email, password_hash, is_active FROM users WHERE email='$e' LIMIT 1");

        if ($r && $r->num_rows > 0) {
            $user = $r->fetch_assoc();
            echo "<p style='color:green'>✅ Email found in database</p>";
            echo "<p>is_active = <strong>" . $user['is_active'] . "</strong></p>";

            if (!$user['is_active']) {
                echo "<p style='color:red'>❌ Account is DEACTIVATED — set is_active=1 in database</p>";
            } else {
                $match = password_verify($pass, $user['password_hash']);
                echo "<p>Password match: <strong>" . ($match ? '✅ YES' : '❌ NO — wrong password or hash issue') . "</strong></p>";

                if ($match) {
                    echo "<p style='color:green'>✅ LOGIN WOULD SUCCEED → redirect to profile.php</p>";
                    echo "<p><a href='profile.php' style='background:#0A7EA4;color:white;padding:.5rem 1rem;border-radius:8px;text-decoration:none'>Go to Profile →</a></p>";
                } else {
                    echo "<p style='color:red'>❌ Password is wrong. Did you register with a different password?</p>";
                    // Check if password is stored as plain text accidentally
                    if ($user['password_hash'] === $pass) {
                        echo "<p style='color:orange'>⚠️ Password stored as PLAIN TEXT — re-register to fix</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:red'>❌ Email NOT found — did you register with this email?</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Empty email or password submitted</p>";
    }
} else {
    echo "<p>Submit the form below to test:</p>";
    echo "
    <form method='POST' style='font-family:sans-serif;max-width:400px'>
        <div style='margin-bottom:1rem'>
            <label style='display:block;margin-bottom:.3rem;font-weight:600'>Email</label>
            <input type='email' name='email' placeholder='your@email.com' required
                   style='width:100%;padding:.6rem;border:1.5px solid #ccc;border-radius:8px;font-size:.95rem'>
        </div>
        <div style='margin-bottom:1rem'>
            <label style='display:block;margin-bottom:.3rem;font-weight:600'>Password</label>
            <input type='password' name='password' placeholder='••••••••' required
                   style='width:100%;padding:.6rem;border:1.5px solid #ccc;border-radius:8px;font-size:.95rem'>
        </div>
        <button type='submit'
                style='background:#0A7EA4;color:white;border:none;padding:.7rem 2rem;border-radius:8px;font-size:1rem;cursor:pointer'>
            Test Login
        </button>
    </form>";
}

// ── Test 5: Check session
echo "<h3>5. Current session</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// ── Test 6: Check login.php exists and form action
echo "<h3>6. login.php file check</h3>";
if (file_exists('login.php')) {
    $content = file_get_contents('login.php');
    // Check form has method POST
    if (strpos($content, 'method="POST"') !== false || strpos($content, "method='POST'") !== false) {
        echo "<p style='color:green'>✅ login.php has method=POST</p>";
    } else {
        echo "<p style='color:red'>❌ login.php form is MISSING method='POST' — form won't submit!</p>";
    }
    // Check it has name attributes
    if (strpos($content, 'name="email"') !== false || strpos($content, "name='email'") !== false) {
        echo "<p style='color:green'>✅ Email input has name='email'</p>";
    } else {
        echo "<p style='color:red'>❌ Email input missing name='email' — value won't be sent!</p>";
    }
    if (strpos($content, 'name="password"') !== false || strpos($content, "name='password'") !== false) {
        echo "<p style='color:green'>✅ Password input has name='password'</p>";
    } else {
        echo "<p style='color:red'>❌ Password input missing name='password' — value won't be sent!</p>";
    }
    // Check session_start
    if (strpos($content, 'session_start') !== false) {
        echo "<p style='color:green'>✅ session_start() found</p>";
    } else {
        echo "<p style='color:red'>❌ session_start() MISSING in login.php!</p>";
    }
} else {
    echo "<p style='color:red'>❌ login.php not found in this folder!</p>";
}

$conn->close();
echo "<hr><p style='color:gray;font-size:.8rem'>Delete login_debug.php after fixing!</p>";
?>
