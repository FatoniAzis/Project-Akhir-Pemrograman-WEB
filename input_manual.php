<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// 1. Proteksi Halaman Dosen
if (!isset($_SESSION['dosen_logged_in']) || $_SESSION['dosen_logged_in'] !== true) {
    header("Location: login_dosen.php");
    exit;
}

$dosen_id = (int)$_SESSION['dosen_id'];
$dosen_nama = $_SESSION['dosen_nama'];

// 2. Koneksi ke Database
$host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "absensi_db";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$pesan = "";
$status_pesan = "";

// 3. Proses Simpan Data Form POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pengguna_id = (int)$_POST['pengguna_id'];
    $mata_kuliah_id = (int)$_POST['mata_kuliah_id'];
    $status_pilihan = $_POST['status']; // 'Izin' atau 'Sakit'

    if ($pengguna_id > 0 && $mata_kuliah_id > 0 && in_array($status_pilihan, ['Izin', 'Sakit'])) {
        $hari_ini = date('Y-m-d');
        
        // Cek apakah mahasiswa ini sudah ada riwayat log hari ini di matkul tersebut
        $cek = $conn->prepare("SELECT id FROM riwayat_absensi WHERE pengguna_id = ? AND mata_kuliah_id = ? AND DATE(waktu_absen) = ?");
        $cek->bind_param("iis", $pengguna_id, $mata_kuliah_id, $hari_ini);
        $cek->execute();
        $res_cek = $cek->get_result();

        if ($res_cek->num_rows > 0) {
            $pesan = "Mahasiswa tersebut sudah memiliki riwayat log absensi (Hadir/Izin/Sakit) hari ini!";
            $status_pesan = "gagal";
        } else {
            // Masukkan data log izin/sakit manual
            $stmt = $conn->prepare("INSERT INTO riwayat_absensi (pengguna_id, mata_kuliah_id, waktu_absen, status) VALUES (?, ?, NOW(), ?)");
            $stmt->bind_param("iis", $pengguna_id, $mata_kuliah_id, $status_pilihan);
            
            if ($stmt->execute()) {
                $pesan = "Berhasil mencatat status ketiadakhadiran ($status_pilihan) secara manual.";
                $status_pesan = "sukses";
            } else {
                $pesan = "Gagal menyimpan data ke database: " . $conn->error;
                $status_pesan = "gagal";
            }
            $stmt->close();
        }
        $cek->close();
    } else {
        $pesan = "Mohon pilih data form secara lengkap dan benar!";
        $status_pesan = "gagal";
    }
}

// 4. Ambil Kumpulan Data Dropdown Form
$mhs_query = $conn->query("SELECT id, nama, nim_nik FROM pengguna ORDER BY nama ASC");
$mk_query = $conn->query("SELECT id, nama_matkul FROM mata_kuliah WHERE dosen_id = $dosen_id");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Absensi Manual</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; }
        
        /* Sidebar Styling */
        .sidebar { width: 260px; background: #4e73df; color: white; padding: 25px 20px; display: flex; flex-direction: column; gap: 10px; }
        .sidebar h3 { font-size: 20px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 12px 15px; border-radius: 8px; font-weight: 600; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: white; }
        
        /* Content Styling */
        .main-content { flex: 1; padding: 35px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto; }
        h2 { color: #333; margin-bottom: 20px; font-size: 22px; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; color: #5a5c69; margin-bottom: 8px; font-size: 14px; }
        select { width: 100%; padding: 10px 15px; border: 1px solid #d1d3e2; border-radius: 6px; font-size: 15px; outline: none; background-color: #fff; }
        select:focus { border-color: #4e73df; }
        
        .btn-submit { width: 100%; padding: 12px; background: #4e73df; border: none; color: white; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; transition: 0.3s; }
        .btn-submit:hover { background: #2e59d9; }
        
        /* Alert Styling */
        .alert { padding: 12px 15px; border-radius: 6px; font-weight: bold; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .alert-sukses { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-gagal { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h3>Portal Absensi</h3>
        <a href="dashboard_dosen.php">🏠 Dashboard</a>
        <a href="index.php">📸 Kamera Absen</a> 
        <a href="rekap_absen.php">📊 Rekap Absensi</a>
        <a href="input_manual.php" class="active">📝 Input Izin / Sakit</a>
        <a href="registrasi.html">👤 Daftar Wajah Baru</a>
    </div>

    <div class="main-content">
        <div class="card">
            <h2>📝 Form Input Absen Manual</h2>

            <?php if (!empty($pesan)): ?>
                <div class="alert <?php echo ($status_pesan == 'sukses') ? 'alert-sukses' : 'alert-gagal'; ?>">
                    <?php echo $pesan; ?>
                </div>
            <?php endif; ?>

            <form action="input_manual.php" method="POST">
                <div class="form-group">
                    <label>Pilih Mahasiswa</label>
                    <select name="pengguna_id" required>
                        <option value="">-- Cari Nama Mahasiswa --</option>
                        <?php while($mhs = $mhs_query->fetch_assoc()): ?>
                            <option value="<?php echo $mhs['id']; ?>">
                                <?php echo htmlspecialchars($mhs['nama']) . " (" . $mhs['nim_nik'] . ")"; ?>
                            </option>
                        <?php endwhile; ?> </select>
                </div>

                <div class="form-group">
                    <label>Mata Kuliah Anda</label>
                    <select name="mata_kuliah_id" required>
                        <option value="">-- Pilih Mata Kuliah Kelas --</option>
                        <?php while($mk = $mk_query->fetch_assoc()): ?>
                            <option value="<?php echo $mk['id']; ?>">
                                <?php echo htmlspecialchars($mk['nama_matkul']); ?>
                            </option>
                        <?php endwhile; ?> </select>
                </div>

                <div class="form-group">
                    <label>Status Keterangan</label>
                    <select name="status" required>
                        <option value="">-- Pilih Keterangan Absen --</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">💾 Simpan Absensi Manual</button>
            </form>
        </div>
    </div>

</body>
</html>
<?php $conn->close(); ?>