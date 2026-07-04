<?php
session_start();
// Atur zona waktu agar sinkron dengan waktu lokal
date_default_timezone_set('Asia/Jakarta'); 

// 1. Proteksi Halaman: Pastikan hanya dosen yang bisa mencetak
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

// 3. Tarik seluruh riwayat absensi mahasiswa khusus untuk mata kuliah milik dosen ini
$sql = "SELECT p.nama, p.nim_nik, mk.nama_matkul, r.waktu_absen, r.status 
        FROM riwayat_absensi r 
        JOIN pengguna p ON r.pengguna_id = p.id
        JOIN mata_kuliah mk ON r.mata_kuliah_id = mk.id
        WHERE mk.dosen_id = $dosen_id
        ORDER BY r.waktu_absen DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_Rekap_Absensi_<?php echo date('Y-m-d'); ?></title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            padding: 20px;
            color: #333;
            background-color: #fff;
        }
        /* Desain KOP Surat Khas Kampus */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .kop-surat h2 {
            font-size: 20px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .kop-surat h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .kop-surat p {
            font-size: 12px;
            font-style: italic;
        }
        .judul-laporan {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
            text-decoration: underline;
        }
        .meta-info {
            margin-bottom: 15px;
            font-size: 14px;
        }
        /* Desain Tabel Formal Laporan */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px 12px;
            font-size: 13px;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        
        /* Bagian Tanda Tangan Dosen Pengampu */
        .ttd-area {
            float: right;
            text-align: center;
            margin-top: 30px;
            width: 250px;
            font-size: 14px;
        }
        .ttd-space {
            height: 80px;
        }

        /* Sembunyikan tombol cetak saat proses print ke PDF berlangsung */
        @media print {
            .no-print {
                display: none;
            }
        }
        .btn-print {
            padding: 10px 20px;
            background-color: #e74a3b;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button class="btn-print" onclick="window.print()">📥 Simpan Sebagai PDF</button>
    </div>

    <div class="kop-surat">
        <h2>UNIVERSITAS AMIKOM PURWOKERTO</h2>
        <h3>FAKULTAS ILMU KOMPUTER - PROGRAM STUDI TEKNOLOGI INFORMASI</h3>
        <p>Jl. Letjend Pol. Soemarto, Purwokerto Utara, Kabupaten Banyumas, Jawa Tengah</p>
    </div>

    <div class="judul-laporan">
        LAPORAN REKAPITULASI KEHADIRAN MAHASISWA
    </div>

    <div class="meta-info">
        Tanggal Cetak : <strong><?php echo date('d F Y - H:i'); ?> WIB</strong><br>
        Dosen Pengampu: <strong><?php echo htmlspecialchars($dosen_nama); ?></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama Mahasiswa</th>
                <th style="width: 20%;">NIM / NIK</th>
                <th style="width: 25%;">Mata Kuliah</th>
                <th style="width: 15%;">Waktu Log</th>
                <th style="width: 10%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result && $result->num_rows > 0) {
                $no = 1;
                while($row = $result->fetch_assoc()) {
                    $waktu = date('d-m-Y H:i', strtotime($row['waktu_absen']));
                    echo "<tr>";
                    echo "<td class='text-center'>" . $no++ . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                    echo "<td class='text-center'>" . htmlspecialchars($row['nim_nik']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['nama_matkul']) . "</td>";
                    echo "<td class='text-center'>" . $waktu . "</td>";
                    echo "<td class='text-center' style='font-weight:bold; color:green;'>" . htmlspecialchars($row['status']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center' style='font-style:italic;'>Belum ada data riwayat absensi mahasiswa.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="ttd-area">
        <p>Purwokerto, <?php echo date('d F Y'); ?></p>
        <p>Dosen Pengampu,</p>
        <div class="ttd-space"></div>
        <p><strong><u><?php echo htmlspecialchars($dosen_nama); ?></u></strong></p>
        <p>NIDN. Anggota Pengajar</p>
    </div>

    <script>
        window.onload = function() {
            // Otomatis membuka jendela print/save PDF browser
            window.print();
        }
    </script>

</body>
</html>
<?php $conn->close(); ?>