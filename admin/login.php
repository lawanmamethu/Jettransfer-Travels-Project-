<?php
session_start();
if (isset($_SESSION['jt_admin'])) { header('Location: index.php'); exit; }

define('ADMIN_PASS', 'Admin@1234');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === ADMIN_PASS) {
        $_SESSION['jt_admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Wrong password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – Jettransfer</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--accent:#10B981}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0F172A 0%,#0a2a3a 50%,#0F172A 100%);position:relative;overflow:hidden}
body::before{content:'';position:fixed;top:-20%;left:-20%;width:60%;height:60%;background:radial-gradient(circle,rgba(10,126,164,0.25) 0%,transparent 70%);animation:drift1 12s ease-in-out infinite;pointer-events:none}
body::after{content:'';position:fixed;bottom:-20%;right:-10%;width:50%;height:50%;background:radial-gradient(circle,rgba(16,185,129,0.2) 0%,transparent 70%);animation:drift2 15s ease-in-out infinite;pointer-events:none}
@keyframes drift1{0%,100%{transform:translate(0,0)}50%{transform:translate(5%,8%)}}
@keyframes drift2{0%,100%{transform:translate(0,0)}50%{transform:translate(-6%,-5%)}}
.grid-bg{position:fixed;inset:0;background-image:linear-gradient(rgba(10,126,164,0.06) 1px,transparent 1px),linear-gradient(90deg,rgba(10,126,164,0.06) 1px,transparent 1px);background-size:60px 60px;pointer-events:none}
.wrap{position:relative;z-index:10;width:100%;max-width:440px;padding:2rem;animation:slideUp 0.7s cubic-bezier(0.22,1,0.36,1) both}
@keyframes slideUp{from{opacity:0;transform:translateY(40px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.card{background:rgba(255,255,255,0.04);backdrop-filter:blur(30px);border:1px solid rgba(255,255,255,0.1);border-radius:28px;padding:3rem 2.5rem;box-shadow:0 30px 80px rgba(0,0,0,0.4),inset 0 1px 0 rgba(255,255,255,0.12)}
.brand{text-align:center;margin-bottom:2.5rem}
.brand-icon{width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:18px;display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:1rem;box-shadow:0 8px 30px rgba(10,126,164,0.4);transform:rotate(-3deg);transition:transform .3s}
.brand-icon:hover{transform:rotate(0) scale(1.05)}
.brand h1{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:#fff}
.brand p{color:rgba(255,255,255,0.5);font-size:.88rem;margin-top:.3rem;letter-spacing:2px;text-transform:uppercase}
.fg{margin-bottom:1.4rem}
.fg label{display:block;font-size:.82rem;font-weight:600;color:rgba(255,255,255,0.7);margin-bottom:.5rem;letter-spacing:.8px;text-transform:uppercase}
.input-wrap{position:relative}
.input-wrap svg.ico{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:rgba(255,255,255,0.35);pointer-events:none;transition:color .3s}
.input-wrap:focus-within svg.ico{color:var(--primary)}
input[type=password]{width:100%;padding:.9rem 3rem .9rem 2.8rem;background:rgba(255,255,255,0.07);border:1.5px solid rgba(255,255,255,0.12);border-radius:14px;color:#fff;font-family:'Manrope',sans-serif;font-size:.95rem;outline:none;transition:all .3s}
input[type=password]::placeholder{color:rgba(255,255,255,0.3)}
input[type=password]:focus{border-color:var(--primary);background:rgba(10,126,164,0.12);box-shadow:0 0 0 4px rgba(10,126,164,0.15)}
.toggle-pw{position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,0.35);cursor:pointer;padding:4px;transition:color .3s}
.toggle-pw:hover{color:rgba(255,255,255,0.7)}
.error{display:flex;align-items:center;gap:.5rem;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);color:#FCA5A5;font-size:.85rem;padding:.75rem 1rem;border-radius:10px;margin-bottom:1.4rem;animation:shake .4s ease}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}
.btn{width:100%;padding:1rem;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;border:none;border-radius:14px;font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;box-shadow:0 8px 25px rgba(10,126,164,0.4);letter-spacing:.02em}
.btn:hover{transform:translateY(-2px);box-shadow:0 12px 35px rgba(10,126,164,0.5)}
.btn.loading{pointer-events:none;opacity:.8}
.spinner{display:none;width:20px;height:20px;border:2.5px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto}
.btn.loading .btn-text{display:none}
.btn.loading .spinner{display:block}
@keyframes spin{to{transform:rotate(360deg)}}
.foot{text-align:center;margin-top:1.5rem;color:rgba(255,255,255,0.3);font-size:.8rem}
.foot a{color:#F59E0B;text-decoration:none;font-weight:600}
.sec{display:flex;align-items:center;justify-content:center;gap:.4rem;margin-top:1rem;color:rgba(255,255,255,0.2);font-size:.75rem}
</style>
</head>
<body>
<div class="grid-bg"></div>
<div class="wrap">
    <div class="card">
        <div class="brand">
            <div class="brand-icon">✈️</div>
            <h1>Jettransfer</h1>
            <p>Admin Portal</p>
        </div>

        <?php if ($error): ?>
        <div class="error">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="fg">
                <label>Admin Password</label>
                <div class="input-wrap">
                    <svg class="ico" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" name="password" id="pwInput" placeholder="Enter admin password" required autofocus>
                    <button type="button" class="toggle-pw" id="togglePw">
                        <svg id="eyeIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn" id="submitBtn">
                <span class="btn-text">Sign In to Dashboard</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="foot"><a href="../index.php">← Back to main site</a></div>
        <div class="sec">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Secured Admin Access
        </div>
    </div>
</div>
<script>
const pw = document.getElementById('pwInput');
const toggle = document.getElementById('togglePw');
const eye = document.getElementById('eyeIcon');
toggle.addEventListener('click', () => {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    eye.innerHTML = show
        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
});
document.getElementById('loginForm').addEventListener('submit', () => {
    document.getElementById('submitBtn').classList.add('loading');
});
</script>
</body>
</html>