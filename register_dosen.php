<?php
session_start();
$host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "absensi_db";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$pesan = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_dosen = trim($_POST['nama_dosen']);
    $nidn_nip   = trim($_POST['nidn_nip']); // Mengambil input NIDN/NIP baru
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']); 
    $kode_matkul = trim($_POST['kode_matkul']);
    $nama_matkul = trim($_POST['nama_matkul']);

    if (!empty($nama_dosen) && !empty($nidn_nip) && !empty($username) && !empty($password) && !empty($kode_matkul) && !empty($nama_matkul)) {
        
        // 1. Cek apakah username sudah digunakan
        $cek_user = $conn->prepare("SELECT id FROM dosen WHERE username = ?");
        $cek_user->bind_param("s", $username);
        $cek_user->execute();
        $hasil_cek = $cek_user->get_result();

        // 2. Cek apakah NIDN/NIP sudah digunakan
        $cek_nidn = $conn->prepare("SELECT id FROM dosen WHERE nidn_nip = ?");
        $cek_nidn->bind_param("s", $nidn_nip);
        $cek_nidn->execute();
        $hasil_cek_nidn = $cek_nidn->get_result();

        if ($hasil_cek->num_rows > 0) {
            $pesan = "Username sudah terdaftar! Gunakan username lain.";
            $status = "gagal";
        } elseif ($hasil_cek_nidn->num_rows > 0) {
            $pesan = "NIDN / NIP sudah terdaftar! Gunakan NIDN/NIP asli Anda.";
            $status = "gagal";
        } else {
            // 3. SIMPAN DATA DOSEN (Termasuk NIDN/NIP)
            $stmt_dosen = $conn->prepare("INSERT INTO dosen (nama_dosen, nidn_nip, username, password) VALUES (?, ?, ?, ?)");
            
            if (!$stmt_dosen) {
                die("Error Struktur Tabel Dosen: " . $conn->error);
            }

            $stmt_dosen->bind_param("ssss", $nama_dosen, $nidn_nip, $username, $password);
            
            if ($stmt_dosen->execute()) {
                $dosen_id_baru = $conn->insert_id;

                // 4. SIMPAN DATA MATA KULIAH
                $stmt_matkul = $conn->prepare("INSERT INTO mata_kuliah (kode_matkul, nama_matkul, dosen_id) VALUES (?, ?, ?)");
                
                if (!$stmt_matkul) {
                    die("Error Struktur Tabel Mata Kuliah: " . $conn->error);
                }

                $stmt_matkul->bind_param("ssi", $kode_matkul, $nama_matkul, $dosen_id_baru);
                
                if ($stmt_matkul->execute()) {
                    $pesan = "Registrasi Berhasil! Akun Dosen & Mata Kuliah telah aktif. Silakan Login.";
                    $status = "sukses";
                } else {
                    $pesan = "Gagal eksekusi matkul: " . $stmt_matkul->error;
                    $status = "gagal";
                }
                $stmt_matkul->close();
            } else {
                $pesan = "Gagal eksekusi dosen: " . $stmt_dosen->error;
                $status = "gagal";
            }
            $stmt_dosen->close();
        }
        $cek_user->close();
        $cek_nidn->close();
    } else {
        $pesan = "Semua kolom form wajib diisi!";
        $status = "gagal";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Dosen Baru</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .register-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 450px; }
        h2 { color: #4e73df; text-align: center; margin-bottom: 5px; }
        p.subtitle { text-align: center; color: #777; font-size: 13px; margin-bottom: 20px; }
        .alert { padding: 10px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .alert-sukses { background: #d1e7dd; color: #0f5132; }
        .alert-gagal { background: #f8d7da; color: #842029; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #4e73df; }
        .section-title { font-size: 14px; font-weight: bold; color: #4e73df; margin: 20px 0 10px 0; border-bottom: 1px solid #e3e6f0; padding-bottom: 5px; }
        .btn-register { width: 100%; padding: 11px; background: #4e73df; border: none; color: white; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 10px; }
        .btn-register:hover { background: #2e59d9; }
        .login-link { text-align: center; margin-top: 15px; font-size: 13px; }
        .login-link a { color: #4e73df; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="register-box">
    <h2>Daftar Akun Dosen</h2>
    <p class="subtitle">Buat akun baru sekaligus daftarkan kelas mengajar Anda</p>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?php echo $status; ?>">
            <?php echo $pesan; ?>
        </div>
    <?php endif; ?>

    <form action="register_dosen.php" method="POST">
        <div class="section-title">Data Diri & Akun</div>
        
        <div class="form-group">
            <label>Nama Lengkap (Beserta Gelar)</label>
            <input type="text" name="nama_dosen" class="form-control" placeholder="Contoh: Alif Wiki D., M.Kom" required>
        </div>

        <div class="form-group">
            <label>NIDN / NIP</label>
            <input type="text" name="nidn_nip" class="form-control" placeholder="Masukkan NIDN atau NIP Anda" required>
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username login" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>

        <div class="section-title">Kelas / Mata Kuliah Pertama</div>
        <div class="form-group">
            <label>Kode Kelas / Kode Matkul</label>
            <input type="text" name="kode_matkul" class="form-control" placeholder="Contoh: INF-2026 atau TI-3A" required>
        </div>
        <div class="form-group">
            <label>Nama Mata Kuliah / Pelajaran</label>
            <input type="text" name="nama_matkul" class="form-control" placeholder="Contoh: Pemrograman Web (Proyek Akhir)" required>
        </div>

        <button type="submit" class="btn-register">Daftar Akun & Kelas</button>
    </form>

    <div class="login-link">
        Sudah punya akun? <a href="login_dosen.php">Login di Sini</a>
    </div>
</div>

</body>
</html>