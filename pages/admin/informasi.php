<?php
require_once '../../config/db.php';
require_once '../../config/auth.php';
require_auth('admin');

$success = $error = "";

// Cek kolom poster ada belum
$cols = mysqli_query($conn,"SHOW COLUMNS FROM informasi");
$col_names=[];
while($c=mysqli_fetch_assoc($cols)) $col_names[]=$c['Field'];
if(!in_array('poster',$col_names)){
    mysqli_query($conn,"ALTER TABLE informasi ADD COLUMN poster VARCHAR(255) DEFAULT NULL, ADD COLUMN warna_bg VARCHAR(20) DEFAULT '#1a3a5c', ADD COLUMN tipe ENUM('teks','poster') DEFAULT 'teks'");
}

// TAMBAH
if (isset($_POST['tambah'])) {
    $judul    = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $isi      = mysqli_real_escape_string($conn, trim($_POST['isi']));
    $tipe     = $_POST['tipe'];
    $warna    = mysqli_real_escape_string($conn, $_POST['warna_bg'] ?? '#1a3a5c');
    $admin_id = $_SESSION['user_id'];
    $poster   = '';

    if ($tipe == 'poster' && isset($_FILES['poster']) && $_FILES['poster']['error']===0) {
        $file=$_FILES['poster'];
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png']) && $file['size']<=5*1024*1024) {
            $poster='poster_'.time().'.'.$ext;
            // ✅ FIX: pakai BASE_PATH
            move_uploaded_file($file['tmp_name'], BASE_PATH . '/uploads/poster/' . $poster);
        } else { $error="Format JPG/PNG maks 5MB!"; }
    }

    if (!$error) {
        mysqli_query($conn,"INSERT INTO informasi (judul,isi,admin_id,poster,warna_bg,tipe) VALUES ('$judul','$isi',$admin_id,'$poster','$warna','$tipe')");
        $success="Informasi berhasil diposting!";
    }
}

// EDIT — ambil
$edit_info = null;
if (isset($_GET['edit'])) {
    $edit_info = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM informasi WHERE id=".intval($_GET['edit'])));
}

// EDIT — simpan
if (isset($_POST['simpan_edit'])) {
    $id    = intval($_POST['edit_id']);
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $isi   = mysqli_real_escape_string($conn, trim($_POST['isi']));
    $warna = mysqli_real_escape_string($conn, $_POST['warna_bg'] ?? '#1a3a5c');
    $tipe  = $_POST['tipe'];
    $poster_sql = "";

    if ($tipe=='poster' && isset($_FILES['poster_edit']) && $_FILES['poster_edit']['error']===0) {
        $file=$_FILES['poster_edit'];
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png']) && $file['size']<=5*1024*1024) {
            $pname='poster_'.time().'.'.$ext;
            // ✅ FIX: pakai BASE_PATH
            move_uploaded_file($file['tmp_name'], BASE_PATH . '/uploads/poster/' . $pname);
            $poster_sql=", poster='$pname'";
        }
    }

    mysqli_query($conn,"UPDATE informasi SET judul='$judul',isi='$isi',warna_bg='$warna',tipe='$tipe' $poster_sql WHERE id=$id");
    $success="Informasi berhasil diperbarui!";
    $edit_info=null;
}

// HAPUS
if (isset($_GET['hapus'])) {
    mysqli_query($conn,"DELETE FROM informasi WHERE id=".intval($_GET['hapus']));
    $success="Informasi dihapus.";
}

// Buat folder poster kalau belum ada
// ✅ FIX: pakai BASE_PATH
if (!file_exists(BASE_PATH . '/uploads/poster/')) {
    mkdir(BASE_PATH . '/uploads/poster/', 0755, true);
}

$info_list = mysqli_query($conn,"SELECT i.*,u.nama AS nama_admin FROM informasi i JOIN users u ON i.admin_id=u.id ORDER BY i.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Informasi Global</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',sans-serif;background:#f5f7fa;}
        .topbar{background:white;padding:16px 30px;border-bottom:1px solid #e0e0e0;display:flex;justify-content:space-between;align-items:center;}
        .topbar h1{font-size:20px;color:#1e3a5f;}
        .page-body{padding:28px;}
        .row-2{display:grid;grid-template-columns:400px 1fr;gap:24px;align-items:start;}
        .card{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:16px;}
        .card-header{padding:16px 20px;}
        .card-header h3{color:white;font-size:15px;}
        .card-body{padding:20px;}
        .form-group{margin-bottom:14px;}
        label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:5px;}
        input[type="text"],textarea,select,input[type="color"]{width:100%;padding:10px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;outline:none;transition:0.2s;font-family:inherit;}
        input:focus,textarea:focus,select:focus{border-color:#784212;}
        textarea{resize:vertical;min-height:100px;}
        .btn-primary{background:#784212;color:white;border:none;border-radius:8px;padding:11px;width:100%;font-size:14px;font-weight:600;cursor:pointer;}
        .btn-primary:hover{background:#5d3510;}
        .btn-danger{background:#e74c3c;color:white;font-size:12px;padding:4px 10px;border:none;border-radius:6px;cursor:pointer;text-decoration:none;}
        .btn-edit{font-size:12px;padding:4px 10px;background:#e8f0fe;color:#1a5276;border-radius:6px;text-decoration:none;font-weight:600;}
        .alert{padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;}
        .alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;}
        .alert-error{background:#ffebee;color:#c62828;border:1px solid #ffcdd2;}
        .tipe-toggle{display:flex;gap:8px;margin-bottom:14px;}
        .tipe-btn{flex:1;padding:10px;border:2px solid #e0e0e0;border-radius:8px;text-align:center;cursor:pointer;font-size:13px;font-weight:600;color:#888;transition:0.2s;background:white;}
        .tipe-btn.aktif{border-color:#784212;background:#784212;color:white;}
        .info-card{border-radius:12px;overflow:hidden;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
        .poster-wrap{position:relative;}
        .poster-wrap img{width:100%;max-height:300px;object-fit:cover;display:block;}
        .poster-overlay{position:absolute;bottom:0;left:0;right:0;padding:20px;background:linear-gradient(transparent,rgba(0,0,0,0.8));color:white;}
        .poster-overlay h4{font-size:18px;margin-bottom:4px;}
        .poster-overlay p{font-size:12px;opacity:0.8;}
        .poster-generated{padding:40px 30px;text-align:center;color:white;min-height:200px;display:flex;flex-direction:column;align-items:center;justify-content:center;}
        .poster-generated .icon{font-size:48px;margin-bottom:12px;}
        .poster-generated h4{font-size:22px;font-weight:800;margin-bottom:10px;line-height:1.3;}
        .poster-generated p{font-size:14px;opacity:0.85;line-height:1.6;max-width:400px;}
        .poster-generated .tanggal{margin-top:14px;font-size:12px;opacity:0.6;border-top:1px solid rgba(255,255,255,0.2);padding-top:12px;}
        .info-teks{padding:18px 20px;background:white;border-left:4px solid #784212;}
        .info-teks h4{font-size:15px;color:#1e3a5f;margin-bottom:6px;}
        .info-teks p{font-size:13px;color:#555;line-height:1.6;}
        .info-footer{background:white;padding:10px 16px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f0f0f0;}
        .info-footer span{font-size:11px;color:#aaa;}
        .file-drop{border:2px dashed #c0a060;border-radius:8px;padding:20px;text-align:center;cursor:pointer;background:#fffbf0;transition:0.2s;}
        .file-drop:hover{border-color:#784212;background:#fff5e0;}
        .file-drop input{display:none;}
        .file-drop p{font-size:13px;color:#888;}
        .divider{border:none;border-top:1px solid #f0f0f0;margin:16px 0;}
    </style>
</head>
<body>

<?php require_once '../../config/sidebar.php'; ?>

<div style="margin-left:220px;">
    <div class="topbar"><h1>📢 Informasi Global</h1></div>

    <div class="page-body">
        <?php if($success):?><div class="alert alert-success">✅ <?=$success?></div><?php endif;?>
        <?php if($error):?><div class="alert alert-error">⚠️ <?=$error?></div><?php endif;?>

        <div class="row-2">
            <div class="card">
                <div class="card-header" style="background:#784212">
                    <h3><?= $edit_info ? '✏️ Edit Informasi' : '📝 Buat Informasi' ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data"
                          action="<?= $edit_info ? '?edit='.$edit_info['id'] : '' ?>">

                        <?php if ($edit_info): ?>
                            <input type="hidden" name="edit_id" value="<?= $edit_info['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Jenis Informasi</label>
                            <div class="tipe-toggle">
                                <div class="tipe-btn <?= (!$edit_info || $edit_info['tipe']=='teks') ? 'aktif' : '' ?>"
                                     onclick="setTipe('teks',this)">📄 Teks Biasa</div>
                                <div class="tipe-btn <?= ($edit_info && $edit_info['tipe']=='poster') ? 'aktif' : '' ?>"
                                     onclick="setTipe('poster',this)">🖼️ Poster</div>
                            </div>
                            <input type="hidden" name="tipe" id="input_tipe"
                                   value="<?= $edit_info ? $edit_info['tipe'] : 'teks' ?>">
                        </div>

                        <div class="form-group">
                            <label>Judul</label>
                            <input type="text" name="judul"
                                   value="<?= htmlspecialchars($edit_info['judul'] ?? '') ?>"
                                   placeholder="Judul pengumuman" required>
                        </div>

                        <div class="form-group">
                            <label>Isi / Keterangan</label>
                            <textarea name="isi" placeholder="Tulis isi pengumuman..."><?= htmlspecialchars($edit_info['isi'] ?? '') ?></textarea>
                        </div>

                        <div id="opsi_poster" style="display:<?= ($edit_info && $edit_info['tipe']=='poster') ? 'block' : 'none' ?>">
                            <div class="form-group">
                                <label>Warna Background Poster</label>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <input type="color" name="warna_bg" id="color_pick"
                                           value="<?= $edit_info['warna_bg'] ?? '#1a3a5c' ?>"
                                           style="width:60px;height:44px;padding:2px;cursor:pointer">
                                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                                        <?php
                                        $warna_preset=['#1a3a5c','#145a32','#6c3483','#784212','#c0392b','#16a085','#2c3e50','#e67e22'];
                                        foreach($warna_preset as $w):
                                        ?>
                                        <div onclick="document.getElementById('color_pick').value='<?=$w?>'"
                                             style="width:28px;height:28px;border-radius:6px;background:<?=$w?>;cursor:pointer;border:2px solid rgba(0,0,0,0.1)"></div>
                                        <?php endforeach;?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Upload Gambar Poster <small style="font-weight:400;color:#aaa">(opsional)</small></label>
                                <div class="file-drop" onclick="document.getElementById('poster_input').click()">
                                    <input type="file" id="poster_input"
                                           name="<?= $edit_info ? 'poster_edit' : 'poster' ?>"
                                           accept=".jpg,.jpeg,.png"
                                           onchange="previewPoster(this)">
                                    <div id="poster_preview_wrap">
                                        <?php
                                        // ✅ FIX: hapus file_exists, langsung tampilkan kalau ada nama poster
                                        if ($edit_info && $edit_info['poster']):?>
                                            <img src="<?= BASE_URL ?>/uploads/poster/<?=$edit_info['poster']?>"
                                                 style="max-height:120px;border-radius:8px;margin-bottom:8px">
                                        <?php else: ?>
                                            <p>🖼️ Klik untuk upload gambar<br><small>JPG/PNG maks 5MB</small></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex;gap:8px">
                            <button type="submit"
                                    name="<?= $edit_info ? 'simpan_edit' : 'tambah' ?>"
                                    class="btn-primary" style="flex:1">
                                <?= $edit_info ? '💾 Simpan' : '📢 Posting' ?>
                            </button>
                            <?php if ($edit_info): ?>
                            <a href="informasi.php"
                               style="flex:1;padding:10px;background:#f0f0f0;color:#666;border-radius:8px;text-align:center;text-decoration:none;font-size:14px">
                               Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div>
                <?php
                $ada=false;
                while($row=mysqli_fetch_assoc($info_list)):
                    $ada=true;
                    $tipe=$row['tipe']??'teks';
                    $warna=$row['warna_bg']??'#1a3a5c';
                ?>
                <div class="info-card">
                    <?php if ($tipe=='poster'): ?>
                        <?php
                        // ✅ FIX: hapus file_exists, langsung cek nama poster
                        if ($row['poster']):?>
                            <div class="poster-wrap">
                                <!-- ✅ FIX: pakai BASE_URL -->
                                <img src="<?= BASE_URL ?>/uploads/poster/<?=$row['poster']?>" alt="Poster">
                                <div class="poster-overlay">
                                    <h4><?=htmlspecialchars($row['judul'])?></h4>
                                    <p><?=htmlspecialchars(substr($row['isi'],0,100))?>...</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="poster-generated" style="background:<?=$warna?>">
                                <div class="icon">📢</div>
                                <h4><?=htmlspecialchars($row['judul'])?></h4>
                                <p><?=nl2br(htmlspecialchars($row['isi']))?></p>
                                <div class="tanggal">📅 <?=date('d F Y',strtotime($row['created_at']))?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="info-teks">
                            <h4>📣 <?=htmlspecialchars($row['judul'])?></h4>
                            <p style="margin-top:6px"><?=nl2br(htmlspecialchars($row['isi']))?></p>
                        </div>
                    <?php endif; ?>

                    <div class="info-footer">
                        <span>👤 <?=htmlspecialchars($row['nama_admin'])?> &nbsp;|&nbsp; 📅 <?=date('d M Y H:i',strtotime($row['created_at']))?></span>
                        <div style="display:flex;gap:6px">
                            <a href="?edit=<?=$row['id']?>" class="btn-edit">✏️ Edit</a>
                            <a href="?hapus=<?=$row['id']?>"
                               onclick="return confirm('Hapus informasi ini?')"
                               class="btn-danger">🗑️ Hapus</a>
                        </div>
                    </div>
                </div>
                <?php endwhile;?>
                <?php if(!$ada):?>
                    <div class="card"><div class="card-body" style="text-align:center;color:#aaa;padding:40px">Belum ada informasi.</div></div>
                <?php endif;?>
            </div>
        </div>
    </div>
</div>

<script>
function setTipe(t, el) {
    document.querySelectorAll('.tipe-btn').forEach(b => b.classList.remove('aktif'));
    el.classList.add('aktif');
    document.getElementById('input_tipe').value = t;
    document.getElementById('opsi_poster').style.display = t==='poster' ? 'block' : 'none';
}

function previewPoster(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('poster_preview_wrap').innerHTML =
                '<img src="'+e.target.result+'" style="max-height:120px;border-radius:8px;margin-bottom:8px">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>