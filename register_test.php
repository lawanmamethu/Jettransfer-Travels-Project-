<?php
// ============================================================
//  register_test.php — Quick test registration
//  Place in htdocs/jettransfer/ — DELETE after fixing!
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$conn = new mysqli('localhost', 'root', '', 'jettransfer');
$conn->set_charset('utf8mb4');
$message = ''; $success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn    = trim($_POST['firstname'] ?? '');
    $ln    = trim($_POST['lastname']  ?? '');
    $email = trim($_POST['email']     ?? '');
    $pass  = $_POST['password']       ?? '';

    if (!$fn || !$ln || !$email || !$pass) {
        $message = '❌ All fields required';
    } elseif (strlen($pass) < 6) {
        $message = '❌ Password min 6 characters';
    } else {
        // Check if email exists
        $e = $conn->real_escape_string($email);
        $r = $conn->query("SELECT id FROM users WHERE email='$e' LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $message = '❌ Email already registered — go to login.php';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $f  = $conn->real_escape_string($fn);
            $l  = $conn->real_escape_string($ln);
            $h  = $conn->real_escape_string($hash);
            $ph = $conn->real_escape_string(trim($_POST['phone'] ?? ''));

            $sql = "INSERT INTO users(first_name,last_name,email,phone,password_hash,is_active)
                    VALUES('$f','$l','$e','$ph','$h',1)";

            if ($conn->query($sql)) {
                $success = true;
                $uid = $conn->insert_id;
                $message = "✅ Registered! User ID: $uid — now go to login.php";
            } else {
                $message = '❌ DB Error: ' . $conn->error;
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register Test</title>
<style>
body{font-family:sans-serif;max-width:480px;margin:3rem auto;padding:0 1rem;background:#F8FAFC}
h2{color:#0A7EA4}
.card{background:white;border-radius:16px;padding:2rem;box-shadow:0 4px 20px rgba(0,0,0,.1)}
label{display:block;font-weight:600;font-size:.9rem;margin-bottom:.3rem;margin-top:1rem}
input{width:100%;padding:.7rem 1rem;border:2px solid #E2E8F0;border-radius:8px;font-size:.95rem;box-sizing:border-box;outline:none}
input:focus{border-color:#0A7EA4}
button{width:100%;margin-top:1.2rem;padding:.8rem;background:#0A7EA4;color:white;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer}
button:hover{background:#065A7A}
.msg{padding:.8rem 1rem;border-radius:8px;margin-bottom:1rem;font-weight:600;font-size:.9rem}
.msg.ok{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.msg.err{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
.links{margin-top:1rem;text-align:center;font-size:.9rem}
.links a{color:#0A7EA4;font-weight:600}
</style>
</head>
<body>
<h2>🔧 Register Test</h2>
<div class="card">
<?php if ($message): ?>
<div class="msg <?= $success?'ok':'err' ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (!$success): ?>
<form method="POST">
    <label>First Name *</label>
    <input type="text" name="firstname" placeholder="John" required value="<?= htmlspecialchars($_POST['firstname']??'') ?>">
    <label>Last Name *</label>
    <input type="text" name="lastname" placeholder="Doe" required value="<?= htmlspecialchars($_POST['lastname']??'') ?>">
    <label>Email *</label>
    <input type="email" name="email" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
    <label>Phone</label>
    <input type="tel" name="phone" placeholder="+94 77 123 4567" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
    <label>Password * (min 6 chars)</label>
    <input type="password" name="password" placeholder="••••••••" required>
    <button type="submit">Create Account</button>
</form>
<?php endif; ?>
<div class="links">
    <?php if($success): ?>
    <a href="login.php">→ Go to Login</a>
    <?php else: ?>
    <a href="login.php">Already registered? Login</a>
    <?php endif; ?>
</div>
</div>
</body>
</html>
