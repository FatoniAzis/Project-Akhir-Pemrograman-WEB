<?php
// --- TAMBAHAN: Proteksi Session Admin ---
session_start();

// Cek apakah session login admin sudah ada. Jika belum, tendang kembali ke login.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
// ----------------------------------------

// 1. Koneksi ke Database
$host = "localhost"; $user = "root"; $pass = ""; $db = "absensi_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// 2. Query SQL Mengambil data absensi digabung dengan data mahasiswa
$sql = "SELECT r.id, p.nama, p.nim_nik, r.waktu_absen, r.status 
        FROM riwayat_absensi r 
        INNER JOIN pengguna p ON r.pengguna_id = p.id 
        ORDER BY r.waktu_absen DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Riwayat Absensi</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; padding: 30px; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        
        .header-box { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; }
        h2 { color: #4e73df; }
        
        /* Wadah untuk tombol aksi di kanan */
        .btn-group { display: flex; gap: 10px; }
        
        .btn { padding: 10px 18px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-decoration: none; transition: 0.3s; font-size: 14px; }
        .btn-primary { background: #4e73df; color: white; }
        .btn-primary:hover { background: #2e59d9; }
        
        /* TAMBAHAN: Gaya Tombol Logout Merah */
        .btn-danger { background: #e74a3b; color: white; }
        .btn-danger:hover { background: #be2617; }
        
        /* Gaya Tabel Responsif */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e3e6f0; }
        th { background-color: #f8f9fc; color: #4e73df; font-weight: bold; }
        tr:hover { background-color: #f8f9fc; }
        
        /* Badge Status Kehadiran */
        .badge { padding: 5px 10px; border-radius: 50px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #d4edda; color: #155724; }
        
        .empty-state { text-align: center; padding: 40px; color: #858796; font-style: italic; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2>Dasbor Log Kehadiran</h2>
            <p>Data hasil pemindaian kamera Face Recognition secara real-time.</p>
            <p style="font-size: 14px; color: #5a5c69; margin-top: 5px;">
                Administrator: <strong style="color: #4e73df;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong>
            </p>
        </div>
        <div class="btn-group">
            <a href="index.html" class="btn btn-primary">← Menu Utama Absen</a>
            <a href="logout.php" class="btn btn-danger">Keluar (Logout)</a>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Mahasiswa</th>
                    <th>NIM / NIK</th>
                    <th>Waktu Log</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    $no = 1;
                    while($row = $result->fetch_assoc()) {
                        // Memformat tampilan waktu agar lebih mudah dibaca
                        $waktu = date('d M Y - H:i:s', strtotime($row['waktu_absen']));
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nim_nik']) . "</td>";
                        echo "<td>" . $waktu . " WIB</td>";
                        echo "<td><span class='badge badge-success'>" . $row['status'] . "</span></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='empty-state'>Belum ada riwayat absensi hari ini.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>