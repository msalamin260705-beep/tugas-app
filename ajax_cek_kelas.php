<?php
require_once 'config/db.php';

$semester = intval($_GET['semester'] ?? 0);
$kelas    = $_GET['kelas'] ?? '';

$kelas_valid = ['A','B','C','D','E'];
if (!in_array($kelas, $kelas_valid) || $semester < 1 || $semester > 8) {
    echo json_encode([]);
    exit;
}

$result = mysqli_query($conn, "
    SELECT k.nama_kelas, k.kode_matkul, k.sks, u.nama AS nama_dosen
    FROM kelas k
    JOIN users u ON k.dosen_id = u.id
    WHERE k.semester=$semester AND k.kelas='$kelas'
    ORDER BY k.nama_kelas
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);