<?php
// logout.php — destroys user session (not admin)
session_start();
session_unset();
session_destroy();
header('Location: login.php?logout=1');
exit;
