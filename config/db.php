<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['HTTP_HOST'] === 'localhost' || 
    str_ends_with($_SERVER['HTTP_HOST'], '.test')) {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "db_tugas";
    define('BASE_URL', '/tugas-app');
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/tugas-app');
} else {
    $host = "sql301.infinityfree.com";
    $user = "if0_42107572";
    $pass = "vmfh5uXDyRksEzZ";
    $db   = "if0_42107572_db_tugas";
    define('BASE_URL', '');
    define('BASE_PATH', $_SERVER['DOCUMENT_ROOT']);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect($host, $user, $pass, $db);
    mysqli_set_charset($conn, "utf8");
} catch (Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>