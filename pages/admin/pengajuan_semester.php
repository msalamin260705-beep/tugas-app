<?php
session_start();
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$success = $error = "";

// PROSES APPROVE / TOLAK
if (isset($_POST['proses'])) {
    $id             = intval($_POST['pengajuan_id']);
    $aksi           = $_POST['aksi']; // 'disetujui' atau 'ditolak'
    $catatan        = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));

    // Ambil data pengajuan
    $pengajuan = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM pengajuan_semester WHERE id=$id AND status='menunggu'"));

    if (!$pengajuan) {
        $error = "Pengajuan tidak ditemukan atau sudah diproses.";
    } else {
        // Update status pengajuan
        mysqli_query($conn, "
            UPDATE pengajuan_semester 
            SET status='$aksi', catatan_admin='$catatan'
            WHERE id=$id
        ");

        // Kalau disetujui → update semester mahasiswa
        if ($aksi === 'disetujui') {
            $mhs_id          = $pengajuan['mahasiswa_id'];
            $semester_baru   = $pengajuan['semester_tujuan'];
            mysqli_query($conn, "
                UPDATE users SET semester=$semester_baru WHERE id=$mhs_id
            ");
            $success = "Pengajuan disetujui! Semester mahasiswa telah diperbarui ke Semester $semester_baru.";
        } else {
            $success = "Pengajuan ditolak. Mahasiswa dapat mengajukan kembali.";
        }
    }
}

// Filter status
$filter = isset($_GET['status']) ? $_GET['status'] : 'menunggu';
$where  = in_array($filter, ['menunggu','disetujui','ditolak'])
          ? "WHERE ps.status='$filter'"
          : "";

$pengajuan_list = mysqli_query($conn, "
    SELECT ps.*, u.nama, u.nim_nidn, u.kelas, u.email
    FROM pengajuan_semester ps
    JOIN users u ON ps.mahasiswa_id = u.id
    $where
    ORDER BY ps.created_at DESC
");

// Hitung badge tiap status
$jml_menunggu  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM pengajuan_semester WHERE status='menunggu'"))['n'];
$jml_disetujui = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM pengajuan_semester WHERE status='disetujui'"))['n'];
$jml_ditolak   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as n FROM pengajuan_semester WHERE status='ditolak'"))['n'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Naik Semester</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f5f7fa; }
        .topbar { background:white; padding:16px 30px; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .topbar h1 { font-size:20px; color:#1e3a5f; }
        .page-body { padding:28px; }
        .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
        .stat-box { background:white; border-radius:12px; padding:20px 24px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; align-items:center; gap:16px; }
        .stat-box .num { font-size:32px; font-weight:700; }
        .stat-box p { font-size:13px; color:#888; margin-top:2px; }
        .tabs { display:flex; gap:8px; margin-bottom:20px; }
        .tab { padding:9px 20px; border-radius:20px; font-size:13px; font-weight:600; text-decoration:none; border:2px solid #e0e0e0; color:#888; background:white; }
        .tab.aktif-menunggu  { background:#fff8e1; color:#e65100; border-color:#ffe082; }
        .tab.aktif-disetujui { background:#e8f5e9; color:#2e7d32; border-color:#c8e6c9; }
        .tab.aktif-ditolak   { background:#ffebee; color:#c62828; border-color:#ffcdd2; }
        .card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.06); overflow:hidden; }
        .alert { padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .alert-error   { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        table { width:100%; border-collapse:collapse; }
        th { background:#f8f9fa; padding:10px 16px; text-align:left; font-size:11px; color:#888; font-weight:700; text-transform:uppercase; }
        td { padding:13px 16px; font-size:13px; color:#444; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        .badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-menunggu  { background:#fff8e1; color:#e65100; }
        .badge-disetujui { background:#e8f5e9; color:#2e7d32; }
        .badge-ditolak   { background:#ffebee; color:#c62828; }
        .btn-approve { background:#27ae60; color:white; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; }
        .btn-tolak   { background:#e74c3c; color:white; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; }
        .btn-approve:hover { background:#1e8449; }
        .btn-tolak:hover   { background:#c0392b; }
        .arrow { color:#27ae60; font-weight:700; font-size:15px; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:14px; width:100%; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.2); overflow:hidden; animation:modalIn 0.2s ease; }
        @keyframes modalIn { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
        .modal-header { padding:16px 20px; display:flex; justify-content:space-between; align-items:center; }
        .modal-header h3 { color:white; font-size:15px; }
        .modal-close { background:none; border:none; color:white; font-size:22px; cursor:pointer; }
        .modal-body { padding:22px; }
        .modal-footer { padding:0 22px 22px; display:flex; gap:10px; }
        .modal-info { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:16px; font-size:13px; color:#555; }
        .modal-info strong { color:#1e3a5f; }
        textarea.catatan { width:100%; padding:10px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:13px; resize:vertical; min-height:80px; font-family:inherit; outline:none; }
        textarea.catatan:focus { border-color:#1a5276; }
        .btn-full { flex:1; padding:11px; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; }
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar">
        <h1>🎓 Pengajuan Naik Semester</h1>
        <?php if ($jml_menunggu > 0): ?>
        <span style="background:#e74c3c;color:white;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:700">
            <?= $jml_menunggu ?> menunggu
        </span>
        <?php endif; ?>
    </div>

    <div class="page-body">
        <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

        <!-- Statistik -->
        <div class="stats-row">
            <div class="stat-box">
                <div style="background:#fff8e1;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px">⏳</div>
                <div><div class="num" style="color:#e65100"><?= $jml_menunggu ?></div><p>Menunggu</p></div>
            </div>
            <div class="stat-box">
                <div style="background:#e8f5e9;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px">✅</div>
                <div><div class="num" style="color:#27ae60"><?= $jml_disetujui ?></div><p>Disetujui</p></div>
            </div>
            <div class="stat-box">
                <div style="background:#ffebee;width:50px;height:50px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px">❌</div>
                <div><div class="num" style="color:#e74c3c"><?= $jml_ditolak ?></div><p>Ditolak</p></div>
            </div>
        </div>

        <!-- Tab filter -->
        <div class="tabs">
            <a href="?status=menunggu"  class="tab <?= $filter==='menunggu' ?'aktif-menunggu':'' ?>">⏳ Menunggu (<?= $jml_menunggu ?>)</a>
            <a href="?status=disetujui" class="tab <?= $filter==='disetujui'?'aktif-disetujui':'' ?>">✅ Disetujui (<?= $jml_disetujui ?>)</a>
            <a href="?status=ditolak"   class="tab <?= $filter==='ditolak'  ?'aktif-ditolak':'' ?>">❌ Ditolak (<?= $jml_ditolak ?>)</a>
            <a href="?status=semua"     class="tab <?= $filter==='semua'    ?'aktif-menunggu':'' ?>">📋 Semua</a>
        </div>

        <!-- Tabel -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Pengajuan</th>
                        <th>Pesan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <?php if ($filter === 'menunggu' || $filter === 'semua'): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $ada = false;
                while ($p = mysqli_fetch_assoc($pengajuan_list)):
                    $ada = true;
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($p['nama']) ?></strong><br>
                        <small style="color:#aaa"><?= htmlspecialchars($p['nim_nidn']) ?></small><br>
                        <small style="color:#aaa">Kelas <?= $p['kelas'] ?></small>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#1e3a5f">Sem <?= $p['semester_asal'] ?></span>
                        <span class="arrow"> → </span>
                        <span style="font-weight:700;color:#27ae60">Sem <?= $p['semester_tujuan'] ?></span>
                    </td>
                    <td style="max-width:180px;font-size:12px;color:#777">
                        <?= $p['pesan'] ? htmlspecialchars(substr($p['pesan'],0,100)) . (strlen($p['pesan'])>100?'...':'') : '<span style="color:#ccc">-</span>' ?>
                    </td>
                    <td style="font-size:12px;color:#888">
                        <?= date('d M Y', strtotime($p['created_at'])) ?><br>
                        <?= date('H:i', strtotime($p['created_at'])) ?>
                    </td>
                    <td>
                        <?php
                        $badge = ['menunggu'=>'badge-menunggu','disetujui'=>'badge-disetujui','ditolak'=>'badge-ditolak'];
                        $label = ['menunggu'=>'⏳ Menunggu','disetujui'=>'✅ Disetujui','ditolak'=>'❌ Ditolak'];
                        ?>
                        <span class="badge <?= $badge[$p['status']] ?>"><?= $label[$p['status']] ?></span>
                        <?php if ($p['catatan_admin']): ?>
                        <div style="font-size:11px;color:#888;margin-top:4px">
                            📝 <?= htmlspecialchars($p['catatan_admin']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php if ($filter === 'menunggu' || $filter === 'semua'): ?>
                    <td>
                        <?php if ($p['status'] === 'menunggu'): ?>
                        <div style="display:flex;gap:6px">
                            <button class="btn-approve"
                                onclick="bukaModal(<?= $p['id'] ?>,'disetujui',
                                '<?= htmlspecialchars(addslashes($p['nama'])) ?>',
                                <?= $p['semester_asal'] ?>,<?= $p['semester_tujuan'] ?>)">
                                ✅ Setujui
                            </button>
                            <button class="btn-tolak"
                                onclick="bukaModal(<?= $p['id'] ?>,'ditolak',
                                '<?= htmlspecialchars(addslashes($p['nama'])) ?>',
                                <?= $p['semester_asal'] ?>,<?= $p['semester_tujuan'] ?>)">
                                ❌ Tolak
                            </button>
                        </div>
                        <?php else: ?>
                        <span style="font-size:12px;color:#ccc">Sudah diproses</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                <?php if (!$ada): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#aaa;padding:50px">
                        <div style="font-size:40px;margin-bottom:12px">📭</div>
                        Tidak ada pengajuan <?= $filter !== 'semua' ? $filter : '' ?>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal konfirmasi -->
<div class="modal-overlay" id="modalProses">
    <div class="modal">
        <form method="POST">
            <input type="hidden" name="pengajuan_id" id="modal_id">
            <input type="hidden" name="aksi" id="modal_aksi">

            <div class="modal-header" id="modal_header">
                <h3 id="modal_judul">Konfirmasi</h3>
                <button type="button" class="modal-close" onclick="tutupModal()">×</button>
            </div>

            <div class="modal-body">
                <div class="modal-info" id="modal_info"></div>

                <label style="display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:6px">
                    Catatan untuk Mahasiswa <span style="font-weight:400;color:#aaa">(opsional)</span>
                </label>
                <textarea name="catatan" class="catatan" id="modal_catatan"
                    placeholder="Tulis catatan atau alasan keputusan..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-full" onclick="tutupModal()"
                    style="background:#f0f0f0;color:#666">Batal</button>
                <button type="submit" name="proses" class="btn-full" id="modal_btn">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModal(id, aksi, nama, semAsal, semTujuan) {
    document.getElementById('modal_id').value   = id;
    document.getElementById('modal_aksi').value = aksi;
    document.getElementById('modal_catatan').value = '';

    const header = document.getElementById('modal_header');
    const btn    = document.getElementById('modal_btn');
    const info   = document.getElementById('modal_info');

    if (aksi === 'disetujui') {
        header.style.background = '#27ae60';
        document.getElementById('modal_judul').textContent = '✅ Setujui Pengajuan';
        btn.style.background = '#27ae60';
        btn.textContent = '✅ Ya, Setujui';
        info.innerHTML = `<strong>${nama}</strong> akan naik dari <strong>Semester ${semAsal}</strong> ke <strong>Semester ${semTujuan}</strong>.<br>Semester mahasiswa akan otomatis diperbarui.`;
    } else {
        header.style.background = '#e74c3c';
        document.getElementById('modal_judul').textContent = '❌ Tolak Pengajuan';
        btn.style.background = '#e74c3c';
        btn.textContent = '❌ Ya, Tolak';
        info.innerHTML = `Pengajuan <strong>${nama}</strong> akan <strong>ditolak</strong>.<br>Mahasiswa tetap di Semester ${semAsal} dan dapat mengajukan kembali.`;
    }

    document.getElementById('modalProses').classList.add('active');
}

function tutupModal() {
    document.getElementById('modalProses').classList.remove('active');
}

document.getElementById('modalProses').addEventListener('click', function(e) {
    if (e.target === this) tutupModal();
});
</script>
</body>
</html>