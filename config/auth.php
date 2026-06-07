<?php
function require_auth($role_required) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $base = (isset($_SERVER['HTTP_HOST']) &&
            ($_SERVER['HTTP_HOST'] === 'localhost' ||
             str_ends_with($_SERVER['HTTP_HOST'], '.test')))
            ? '/tugas-app' : '';

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        session_destroy();
        header("Location: {$base}/index.php");
        exit();
    }

    if ($_SESSION['user_role'] !== $role_required) {
        $role = $_SESSION['user_role'];
        if ($role == 'admin')      header("Location: {$base}/pages/admin/dashboard.php");
        elseif ($role == 'dosen')  header("Location: {$base}/pages/dosen/dashboard.php");
        else                       header("Location: {$base}/pages/mahasiswa/dashboard.php");
        exit();
    }
}
?>