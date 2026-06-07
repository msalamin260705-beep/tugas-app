<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('mahasiswa');

$mhs_id  = $_SESSION['user_id'];
$success = $error = "";

// Ambil data mahasiswa
$mhs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$mhs_id"));
$semester_sekarang = $mhs['semester'];
$kelas_sekarang    = $mhs['kelas'];
$semester_tujuan   = $semester_sekarang + 1;

// Cek apakah ada pengajuan yang sedang menunggu
$cek_pending = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM pengajuan_semester 
     WHERE mahasiswa_id=$mhs_id AND status='menunggu'"));

// PROSES PENGAJUAN
if (isset($_POST['ajukan'])) {
    if ($cek_pending) {
        $error = "Kamu sudah memiliki pengajuan yang sedang menunggu persetujuan!";
    } else {
        $pesan = mysqli_real_escape_string($conn, trim($_POST['pesan'] ?? ''));
        $sql = "INSERT INTO pengajuan_semester 
                (mahasiswa_id, semester_asal, semester_tujuan, kelas_asal, pesan)
                VALUES ($mhs_id, $semester_sekarang, $semester_tujuan, '$kelas_sekarang', '$pesan')";
        if (mysqli_query($conn, $sql)) {
            $success = "Pengajuan naik semester berhasil dikirim! Tunggu persetujuan admin.";
            $cek_pending = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT * FROM pengajuan_semester WHERE mahasiswa_id=$mhs_id AND status='menunggu'"));
        } else {
            $error = "Gagal mengirim pengajuan.";
        }
    }
}

// Riwayat pengajuan
$riwayat = mysqli_query($conn,
    "SELECT * FROM pengajuan_semester WHERE mahasiswa_id=$mhs_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Naik Semester</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; display:grid; grid-template-columns:400px 1fr; gap:24px; align-items:start; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; margin-bottom:20px; }
        .card-header { padding:16px 20px; }
        .card-header h3 { color:white; font-size:15px; }
        .card-body { padding:24px; }
        .form-group { margin-bottom:14px; }
        label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:5px; }
        textarea { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:14px; outline:none; resize:vertical; min-height:100px; font-family:inherit; }
        textarea:focus { border-color:#1a5276; }
        .btn-primary { background:#1a5276; color:white; border:none; border-radius:8px; padding:12px; width:100%; font-size:14px; font-weight:600; cursor:pointer; }
        .btn-primary:hover { background:#154360; }
        .btn-primary:disabled { background:#aaa; cursor:not-allowed; }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        .alert-warning { background:#fff8e1; color:#e65100; border:1px solid #ffe082; }
        .info-box { background:#e8f0fe; border-radius:10px; padding:16px 18px; margin-bottom:18px; }
        .info-box .row { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .info-box .row:last-child { margin-bottom:0; }
        .info-box .label { font-size:12px; color:#555; }
        .info-box .val { font-size:16px; font-weight:700; color:#1a5276; }
        .arrow { font-size:24px; text-align:center; margin:10px 0; color:#1a5276; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:10px 16px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:12px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-menunggu  { background:#fff8e1; color:#e65100; }
        .badge-disetujui { background:#e8f5e9; color:#2e7d32; }
        .badge-ditolak   { background:#ffebee; color:#c62828; }
        .pending-box { background:#fff8e1; border:1px solid #ffe082; border-radius:10px; padding:16px 18px; margin-bottom:16px; }
        .pending-box h4 { color:#e65100; font-size:14px; margin-bottom:6px; }
        .pending-box p { font-size:13px; color:#777; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>🎓 Pengajuan Naik Semester</h1></div>

    <div class="page-body">

        <!-- Kolom kiri: Form pengajuan -->
        <div>
            <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= $error ?></div>
            <?php endif; ?>

            <!-- Info semester sekarang -->
            <div class="card">
                <div class="card-header" style="background:#1a5276"><h3>📊 Status Semester Kamu</h3></div>
                <div class="card-body">
                    <div class="info-box">
                        <div class="row">
                            <span class="label">Semester Sekarang</span>
                            <span class="val">Semester <?= $semester_sekarang ?> — Kelas <?= $kelas_sekarang ?></span>
                        </div>
                        <div class="arrow">⬇️</div>
                        <div class="row">
                            <span class="label">Semester Tujuan</span>
                            <span class="val" style="color:#27ae60">Semester <?= $semester_tujuan ?></span>
                        </div>
                    </div>

                    <?php if ($cek_pending): ?>
                    <!-- Ada pengajuan pending -->
                    <div class="pending-box">
                        <h4>⏳ Pengajuan Sedang Diproses</h4>
                        <p>Pengajuan naik ke <strong>Semester <?= $cek_pending['semester_tujuan'] ?></strong> 
                           dikirim pada <?= date('d M Y H:i', strtotime($cek_pending['created_at'])) ?>.</p>
                        <p style="margin-top:6px">Mohon tunggu keputusan dari admin.</p>
                    </div>
                    <button class="btn-primary" disabled>⏳ Menunggu Persetujuan Admin</button>

                    <?php else: ?>
                    <!-- Form pengajuan -->
                    <form method="POST">
                        <div class="form-group">
                            <label>Pesan / Keterangan (opsional)</label>
                            <textarea name="pesan" placeholder="Contoh: Saya telah menyelesaikan seluruh tugas semester <?= $semester_sekarang ?> dan siap naik ke semester <?= $semester_tujuan ?>..."></textarea>
                        </div>
                        <button type="submit" name="ajukan" class="btn-primary">
                            🎓 Ajukan Naik Semester <?= $semester_tujuan ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: Riwayat pengajuan -->
        <div class="card">
            <div class="card-header" style="background:#145a32"><h3>📋 Riwayat Pengajuan</h3></div>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Dari → Ke</th>
                        <th>Status</th>
                        <th>Catatan Admin</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $ada = false;
                while ($r = mysqli_fetch_assoc($riwayat)):
                    $ada = true;
                ?>
                <tr>
                    <td style="font-size:12px;color:#888">
                        <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                        <?= date('H:i', strtotime($r['created_at'])) ?>
                    </td>
                    <td>
                        <strong>Sem <?= $r['semester_asal'] ?></strong>
                        <span style="color:#aaa"> → </span>
                        <strong style="color:#27ae60">Sem <?= $r['semester_tujuan'] ?></strong>
                    </td>
                    <td>
                        <?php
                        $badge = [
                            'menunggu'  => 'badge-menunggu',
                            'disetujui' => 'badge-disetujui',
                            'ditolak'   => 'badge-ditolak',
                        ];
                        $label = [
                            'menunggu'  => '⏳ Menunggu',
                            'disetujui' => '✅ Disetujui',
                            'ditolak'   => '❌ Ditolak',
                        ];
                        ?>
                        <span class="badge <?= $badge[$r['status']] ?>">
                            <?= $label[$r['status']] ?>
                        </span>
                        <?php if ($r['status'] === 'disetujui'): ?>
                        <div style="font-size:11px;color:#27ae60;margin-top:4px">
                            Naik ke Sem <?= $r['semester_tujuan'] ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#777">
                        <?= $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '<span style="color:#ccc">-</span>' ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$ada): ?>
                <tr>
                    <td colspan="4" style="text-align:center;color:#aaa;padding:40px">
                        Belum ada riwayat pengajuan
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>