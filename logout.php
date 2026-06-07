<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
session_destroy();

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ FIX: deteksi environment otomatis
$base = ($_SERVER['HTTP_HOST'] === 'localhost' || 
         str_ends_with($_SERVER['HTTP_HOST'], '.test'))
        ? '/tugas-app' : '';

header("Location: {$base}/index.php");
exit();
?>