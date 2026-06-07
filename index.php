<?php
require_once 'config/db.php';

$base = (isset($_SERVER['HTTP_HOST']) &&
        ($_SERVER['HTTP_HOST'] === 'localhost' ||
         str_ends_with($_SERVER['HTTP_HOST'], '.test')))
        ? '/tugas-app' : '';

// Kalau sudah login → redirect ke dashboard
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($role == 'admin')      { header("Location: {$base}/pages/admin/dashboard.php"); exit(); }
    elseif ($role == 'dosen')  { header("Location: {$base}/pages/dosen/dashboard.php"); exit(); }
    else                       { header("Location: {$base}/pages/mahasiswa/dashboard.php"); exit(); }
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = MD5($_POST['password']);

    $sql    = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_nama'] = $user['nama'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_foto'] = $user['foto'];

        if ($user['role'] === 'mahasiswa') {
            $_SESSION['user_semester'] = $user['semester'];
            $_SESSION['user_kelas']    = $user['kelas'];
        }

        if ($user['role'] == 'admin')      { header("Location: {$base}/pages/admin/dashboard.php"); exit(); }
        elseif ($user['role'] == 'dosen')  { header("Location: {$base}/pages/dosen/dashboard.php"); exit(); }
        else                               { header("Location: {$base}/pages/mahasiswa/dashboard.php"); exit(); }
    } else {
        $error = "Email atau password salah!";
    }
}

$setting = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pengaturan LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= htmlspecialchars($setting['nama_hima'] ?? 'Sistem Tugas') ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; min-height:100vh; display:flex; background:#0a1628; }
        .left-panel { flex:1; background:linear-gradient(145deg,#0a1628 0%,#1a3a5c 50%,#0d2137 100%); display:flex; flex-direction:column; align-items:center; justify-content:center; padding:40px; position:relative; overflow:hidden; }
        .left-panel::before { content:''; position:absolute; width:400px; height:400px; border-radius:50%; border:2px solid rgba(255,200,0,0.08); top:-100px; left:-100px; }
        .left-panel::after  { content:''; position:absolute; width:300px; height:300px; border-radius:50%; border:2px solid rgba(255,200,0,0.06); bottom:-60px; right:-60px; }
        .logo-wrap { position:relative; z-index:1; text-align:center; }
        .logo-ring { width:180px; height:180px; border-radius:50%; border:4px solid #f5c518; padding:6px; margin:0 auto 24px; box-shadow:0 0 40px rgba(245,197,24,0.3),0 0 80px rgba(245,197,24,0.1); animation:pulse-ring 3s ease-in-out infinite; }
        @keyframes pulse-ring { 0%,100%{box-shadow:0 0 40px rgba(245,197,24,0.3),0 0 80px rgba(245,197,24,0.1);} 50%{box-shadow:0 0 60px rgba(245,197,24,0.5),0 0 100px rgba(245,197,24,0.2);} }
        .logo-ring img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
        .brand-name { font-size:22px; font-weight:800; color:#f5c518; letter-spacing:1px; margin-bottom:6px; }
        .brand-univ { font-size:14px; color:rgba(255,255,255,0.6); margin-bottom:4px; }
        .divider-line { width:60px; height:2px; background:linear-gradient(90deg,transparent,#f5c518,transparent); margin:20px auto; }
        .feature-list { list-style:none; text-align:left; position:relative; z-index:1; }
        .feature-list li { color:rgba(255,255,255,0.6); font-size:13px; padding:6px 0; display:flex; align-items:center; gap:10px; }
        .feature-list li span { color:#f5c518; font-size:15px; }
        .right-panel { width:440px; background:white; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 40px; }
        .right-panel h2 { font-size:26px; color:#0a1628; margin-bottom:6px; font-weight:800; }
        .right-panel .subtitle { font-size:13px; color:#888; margin-bottom:32px; }
        .form-group { width:100%; margin-bottom:18px; }
        label { display:block; font-size:12px; font-weight:700; color:#444; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px; }
        .input-wrap { position:relative; }
        .input-wrap .icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); font-size:16px; color:#aaa; }
        input[type="email"], input[type="password"] { width:100%; padding:13px 14px 13px 42px; border:2px solid #e8e8e8; border-radius:10px; font-size:14px; outline:none; transition:all 0.2s; font-family:inherit; background:#fafafa; }
        input:focus { border-color:#1a3a5c; background:white; box-shadow:0 0 0 4px rgba(26,58,92,0.08); }
        .btn-login { width:100%; padding:14px; background:linear-gradient(135deg,#1a3a5c,#0a1628); color:white; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; transition:0.2s; letter-spacing:0.5px; margin-top:4px; }
        .btn-login:hover { background:linear-gradient(135deg,#0a1628,#000); transform:translateY(-1px); box-shadow:0 6px 20px rgba(10,22,40,0.3); }
        .error-msg { width:100%; background:#fff0f0; color:#c0392b; border:1px solid #f5c6c6; border-left:4px solid #e74c3c; padding:11px 14px; border-radius:8px; font-size:13px; margin-bottom:18px; }
        .register-link { text-align:center; margin-top:22px; font-size:13px; color:#888; }
        .register-link a { color:#1a3a5c; font-weight:700; text-decoration:none; }
        .register-link a:hover { text-decoration:underline; }
        .role-info { width:100%; margin-top:24px; padding-top:20px; border-top:1px solid #f0f0f0; }
        .role-info p { font-size:11px; color:#bbb; text-align:center; margin-bottom:10px; text-transform:uppercase; letter-spacing:1px; }
        .role-badges { display:flex; justify-content:center; gap:8px; }
        .role-badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:600; }
        @media (max-width:700px) { .left-panel{display:none;} .right-panel{width:100%;padding:40px 24px;} }
    </style>
</head>
<body>
<div class="left-panel">
    <div class="logo-wrap">
        <div class="logo-ring">
            <img src="https://i.ibb.co.com/m5xTZzBB/logo-hima-jpg.jpg" alt="Logo HIMA">
        </div>
        <div class="brand-name"><?= htmlspecialchars($setting['nama_hima'] ?? 'Teknik Informatika') ?></div>
        <div class="brand-univ"><?= htmlspecialchars($setting['nama_kampus'] ?? 'Universitas Yudharta Pasuruan') ?></div>
        <div class="divider-line"></div>
        <ul class="feature-list">
            <li><span>🎓</span> Portal Akademik Mahasiswa</li>
            <li><span>📝</span> Sistem Pengumpulan Tugas</li>
            <li><span>📊</span> Monitoring Nilai & Progress</li>
            <li><span>📢</span> Informasi & Pengumuman</li>
        </ul>
    </div>
</div>
<div class="right-panel">
    <h2>👋 SELAMAT DATANG 👋</h2>
    <p class="subtitle">SELAMAT DATANG MAHASISWA PRODI TEKNIK INFORMATIKA</p>
    <?php if ($error): ?>
        <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" style="width:100%">
        <div class="form-group">
            <label>Email</label>
            <div class="input-wrap">
                <span class="icon">📧</span>
                <input type="email" name="email" placeholder="Masukkan email kamu" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
        </div>
        <button type="submit" class="btn-login">Masuk</button>
    </form>
    <div class="register-link">Belum punya akun? <a href="<?= $base ?>/register.php">Daftar</a></div>
    <div class="role-info">
        <p>Akses Untuk</p>
        <div class="role-badges">
            <span class="role-badge" style="background:#f0f4ff;color:#1a3a5c">👑 Admin</span>
            <span class="role-badge" style="background:#f0fff4;color:#145a32">👨‍🏫 Dosen</span>
            <span class="role-badge" style="background:#fff8e1;color:#784212">🎓 Mahasiswa</span>
        </div>
    </div>
</div>
</body>
</html>