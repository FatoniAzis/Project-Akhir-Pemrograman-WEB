<?php
session_start();
// Proteksi halaman
if (!isset($_SESSION['dosen_logged_in']) || $_SESSION['dosen_logged_in'] !== true) {
    header("Location: login_dosen.php");
    exit;
}

$host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "absensi_db";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$dosen_id = $_SESSION['dosen_id'];
$dosen_nama = $_SESSION['dosen_nama'];

// QUERY UTAMA REKAPITULASI
$query = "SELECT 
            a.id, 
            a.pengguna_id, 
            p.nama AS nama_mahasiswa,   
            a.mata_kuliah_id, 
            mk.nama_matkul, 
            a.waktu_absen, 
            a.STATUS 
          FROM riwayat_absensi a
          JOIN pengguna p ON a.pengguna_id = p.id  
          JOIN mata_kuliah mk ON a.mata_kuliah_id = mk.id
          WHERE mk.dosen_id = ?
          ORDER BY a.waktu_absen DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    die("<div style='background:#f8d7da; color:#842029; padding:20px; border-radius:8px; font-family:sans-serif;'>
            <h3>❌ Query SQL Error!</h3>
            <p><strong>Pesan Error MySQL:</strong> " . $conn->error . "</p>
         </div>");
}

$stmt->bind_param("i", $dosen_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Mahasiswa</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; padding: 30px; }
        .container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f4f6f9; padding-bottom: 15px; }
        h2 { color: #333; }
        .btn-group { display: flex; gap: 10px; }
        .btn { padding: 8px 15px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 14px; cursor: pointer; display: inline-block; transition: 0.3s; }
        .btn-back { background: #858796; color: white; }
        .btn-back:hover { background: #717384; }
        .btn-pdf { background: #e74a3b; color: white; }
        .btn-pdf:hover { background: #be2617; }
        .btn-manual { background: #f6c23e; color: white; }
        .btn-manual:hover { background: #dfa515; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e3e6f0; }
        th { background-color: #4e73df; color: white; font-size: 14px; }
        tr:hover { background-color: #f8f9fc; }
        
        /* WARNA BADGE DINAMIS UNTUK BERBAGAI STATUS */
        .badge { padding: 5px 10px; border-radius: 30px; font-size: 12px; font-weight: bold; display: inline-block; text-align: center; min-width: 85px; }
        .badge-hadir { background: #d1e7dd; color: #0f5132; }
        .badge-izin { background: #fff3cd; color: #664d03; }
        .badge-sakit { background: #cff4fc; color: #055160; }
        .badge-terlambat { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>Rekap Absensi Kelas</h2>
            <p style="color: #777; font-size: 14px;">Dosen Pengampu: <strong><?php echo htmlspecialchars($dosen_nama); ?></strong></p>
        </div>
        <div class="btn-group">
            <a href="input_manual.php" class="btn btn-manual">📝 Input Izin / Sakit</a>
            <a href="cetak_pdf.php" target="_blank" class="btn btn-pdf">📄 Download PDF</a>
            <a href="dashboard_dosen.php" class="btn btn-back">◀ Kembali ke Dashboard</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>ID Pengguna</th>
                <th>Nama Mahasiswa</th>
                <th>ID Matkul</th>
                <th>Mata Kuliah</th>
                <th>Waktu Scan / Input</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    // PROSES DETEKSI WARNA STATUS
                    $status_sekarang = $row['STATUS'];
                    $class_warna = 'badge-hadir'; // Status Standar Bawaan

                    if ($status_sekarang === 'Izin') {
                        $class_warna = 'badge-izin';
                    } elseif ($status_sekarang === 'Sakit') {
                        $class_warna = 'badge-sakit';
                    } elseif ($status_sekarang === 'Terlambat') {
                        $class_warna = 'badge-terlambat';
                    }

                    echo "<tr>";
                    echo "<td>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['pengguna_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_mahasiswa']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['mata_kuliah_id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_matkul']) . "</td>";
                    echo "<td>" . date('d M Y - H:i', strtotime($row['waktu_absen'])) . " WIB</td>";
                    echo "<td><span class='badge {$class_warna}'>" . htmlspecialchars($status_sekarang) . "</span></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center; color:#999; padding: 20px;'>Belum ada data absensi untuk mata kuliah Anda.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php 
$stmt->close();
$conn->close();
?>