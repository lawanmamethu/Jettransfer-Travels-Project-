<?php
// Place in htdocs/jettransfer/admin/ — run ONCE then DELETE
$conn = new mysqli('localhost','root','','jettransfer');
$conn->set_charset('utf8mb4');
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && !empty($_POST['new_pass'])) {
    $hash = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
    $h = $conn->real_escape_string($hash);
    $conn->query("UPDATE admins SET password_hash='$h' WHERE id=1");
    $msg = "✅ Password updated to: <strong>" . htmlspecialchars($_POST['new_pass']) . "</strong> — delete this file now!";
}
?><!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reset Admin</title>
<style>body{font-family:sans-serif;max-width:400px;margin:3rem auto;padding:1rem}input,button{width:100%;padding:.7rem;margin-top:.5rem;border-radius:8px;border:1px solid #ccc;font-size:1rem}button{background:#0A7EA4;color:white;border:none;cursor:pointer;margin-top:1rem}.msg{background:#d1fae5;padding:1rem;border-radius:8px;margin-top:1rem;color:#065f46}</style>
</head><body>
<h2>Set Admin Password</h2>
<?php if($msg): ?><div class="msg"><?= $msg ?></div><?php else: ?>
<form method="POST"><label>New Password</label><input type="text" name="new_pass" placeholder="e.g. Admin@1234" required><button type="submit">Set Password</button></form>
<?php endif; ?>
</body></html>
