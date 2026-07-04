<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Proteksi halaman
if (!isset($_SESSION['dosen_logged_in']) || $_SESSION['dosen_logged_in'] !== true) {
    header("Location: login_dosen.php");
    exit;
}

$dosen_id = (int)$_SESSION['dosen_id']; // Paksa jadi integer demi keamanan SQL Injection
$dosen_nama = $_SESSION['dosen_nama'];

// Koneksi Database
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "absensi_db";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// 1. Hitung jumlah mata kuliah
$q_matkul = $conn->query("SELECT COUNT(*) as total FROM mata_kuliah WHERE dosen_id = $dosen_id");
$res_matkul = $q_matkul->fetch_assoc();
$total_matkul = $res_matkul['total'] ?? 0;

// 2. Hitung jumlah mahasiswa yang hadir HARI INI
$q_hari_ini = $conn->query("SELECT COUNT(*) as total FROM riwayat_absensi r 
                            JOIN mata_kuliah mk ON r.mata_kuliah_id = mk.id 
                            WHERE mk.dosen_id = $dosen_id AND DATE(r.waktu_absen) = CURDATE()");
$res_hari_ini = $q_hari_ini->fetch_assoc();
$total_absen_hari_ini = $res_hari_ini['total'] ?? 0;

// 3. Ambil 5 aktivitas absensi terbaru (Membaca dari tabel 'pengguna')
$q_log = $conn->query("SELECT r.waktu_absen, r.STATUS, mk.nama_matkul, p.nama 
                       FROM riwayat_absensi r
                       JOIN mata_kuliah mk ON r.mata_kuliah_id = mk.id
                       LEFT JOIN pengguna p ON r.pengguna_id = p.id
                       WHERE mk.dosen_id = $dosen_id
                       ORDER BY r.waktu_absen DESC LIMIT 5");

$aktivitas_terbaru = [];
if ($q_log) {
    while ($row = $q_log->fetch_assoc()) {
        $aktivitas_terbaru[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - Sistem Absensi Wajah</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background-color: #f4f6f9;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: #4e73df;
            color: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 20px;
            letter-spacing: 1px;
        }

        .sidebar a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: block;
            font-weight: bold;
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar .logout {
            margin-top: auto;
            background: #e74a3b;
            color: white;
            text-align: center;
        }

        .sidebar .logout:hover {
            background: #c0392b;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 260px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            border-left: 5px solid #4e73df;
        }

        .welcome-banner h2 {
            color: #333;
            font-size: 24px;
        }

        .welcome-banner p {
            color: #777;
            margin-top: 5px;
            font-size: 14px;
        }

        /* Stats Grid Widgets */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 4px solid #ccd1d9;
        }

        .stat-card.blue {
            border-left-color: #4e73df;
        }

        .stat-card.green {
            border-left-color: #1cc88a;
        }

        .stat-card.yellow {
            border-left-color: #f6c23e;
        }

        .stat-info h5 {
            font-size: 11px;
            text-transform: uppercase;
            color: #858796;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-info h2 {
            font-size: 28px;
            color: #5a5c69;
            font-weight: bold;
        }

        .stat-icon {
            font-size: 32px;
            opacity: 0.3;
        }

        /* Dashboard Sections Layout */
        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr; /* Diubah menjadi 1 kolom penuh */
            gap: 25px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .panel h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid #edf0f5;
            padding-bottom: 10px;
        }

        /* Tables & Lists inside Panel */
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #edf0f5;
        }

        th {
            color: #4e73df;
            font-weight: bold;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-success {
            background: #d1e7dd;
            color: #0f5132;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h3>Portal Absensi</h3>
        <a href="dashboard_dosen.php" class="active">🏠 Dashboard</a>
        <a href="index.php" class="nav-link">📷 Kamera Absen</a>
        <a href="rekap_absen.php">📊 Rekap Absensi</a>
        <a href="input_manual.php">📝 Input Izin / Sakit</a>
        <a href="registrasi.html">👤 Daftar Wajah Baru</a>
        <a href="logout.php" class="logout">🚪 Keluar (Logout)</a>
    </div>

    <div class="main-content">
        <div class="welcome-banner">
            <h2>Selamat Datang, <?php echo htmlspecialchars($dosen_nama); ?>!</h2>
            <p>Sistem Absensi Face Recognition aktif. Berikut adalah ringkasan aktivitas kelas Anda hari ini.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-info">
                    <h5>Mata Kuliah Anda</h5>
                    <h2><?php echo $total_matkul; ?></h2>
                </div>
                <div class="stat-icon">📚</div>
            </div>
            <div class="stat-card green">
                <div class="stat-info">
                    <h5>Hadir Hari Ini</h5>
                    <h2><?php echo $total_absen_hari_ini; ?></h2>
                </div>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-info">
                    <h5>Status Mesin AI</h5>
                    <h2>Ready</h2>
                </div>
                <div class="stat-icon">🤖</div>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="panel">
                <h4>⏱️ Riwayat Scan Wajah Terbaru (Live Log)</h4>
                <?php if (count($aktivitas_terbaru) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Mahasiswa</th>
                                <th>Mata Kuliah</th>
                                <th>Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aktivitas_terbaru as $log): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($log['nama'] ?? 'Tidak Diketahui'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($log['nama_matkul']); ?></td>
                                    <td><?php echo date('H:i:s', strtotime($log['waktu_absen'])); ?> WIB</td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($log['STATUS']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999; text-align: center; padding: 30px; font-size: 14px;">Belum ada riwayat aktivitas scanning wajah hari ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>
<?php
$conn->close();
?>