<?php
// ============================================================
//  Jettransfer Travels
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'jettransfer');   // your database name
define('DB_USER',    'root');          // XAMPP default
define('DB_PASS',    '');              // XAMPP default (empty)
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';dbname='    . DB_NAME
             . ';charset='   . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show a clean HTML error page instead of JSON
            http_response_code(500);
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Error – Jettransfer</title>
    <style>
        body { font-family: sans-serif; background: #F1F5F9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: white; border-radius: 16px; padding: 2.5rem; max-width: 500px; width: 92%; box-shadow: 0 4px 20px rgba(0,0,0,.1); text-align: center; }
        .icon { font-size: 3rem; margin-bottom: 1rem; }
        h2 { font-size: 1.4rem; color: #0F172A; margin-bottom: .5rem; }
        p  { color: #64748B; font-size: .95rem; margin-bottom: 1rem; }
        code { background: #F1F5F9; padding: .4rem .8rem; border-radius: 8px; font-size: .85rem; color: #EF4444; display: block; margin-top: .5rem; }
        .checklist { text-align: left; margin-top: 1.5rem; }
        .checklist li { margin-bottom: .5rem; color: #475569; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">⚠️</div>
        <h2>Database Connection Failed</h2>
        <p>Could not connect to the MySQL database.</p>
        <code>' . htmlspecialchars($e->getMessage()) . '</code>
        <ul class="checklist">
            <li>✅ Make sure <strong>XAMPP MySQL</strong> is running (green)</li>
            <li>✅ Database name in <code>db.php</code> is <strong>jettransfer</strong></li>
            <li>✅ Username is <strong>root</strong> and password is <strong>empty</strong></li>
            <li>✅ Go to <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a> and confirm the database exists</li>
        </ul>
    </div>
</body>
</html>';
            exit;
        }
    }

    return $pdo;
}


