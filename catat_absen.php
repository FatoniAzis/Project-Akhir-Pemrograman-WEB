<?php
// 1. Mulai session agar bisa membaca Mata Kuliah yang sedang aktif dibuka Dosen
session_start();

header('Content-Type: application/json');

// Validasi Keamanan: Pastikan Dosen sudah login dan memilih kelas aktif
if (!isset($_SESSION['dosen_logged_in']) || !isset($_SESSION['active_matkul_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Akses ditolak. Sesi Dosen atau Mata Kuliah tidak aktif.'
    ]);
    exit;
}

// 2. Ambil data JSON (pengguna_id) yang dikirim oleh script.js lewat fetch()
$input = json_decode(file_get_contents('php://input'), true);
$pengguna_id = isset($input['pengguna_id']) ? intval($input['pengguna_id']) : 0;

// Ambil ID Mata Kuliah dari session server
$mata_kuliah_id = $_SESSION['active_matkul_id']; 

if ($pengguna_id > 0) {
    $host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "absensi_db";
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal.']);
        exit;
    }

    // 3. PENCEGAHAN ABSEN GANDA: Cek apakah mahasiswa ini sudah absen di matkul ini PADA HARI INI
    $hari_ini = date('Y-m-d');
    $cek_absen = $conn->prepare("SELECT id FROM riwayat_absensi WHERE pengguna_id = ? AND mata_kuliah_id = ? AND DATE(waktu_absen) = ?");
    $cek_absen->bind_param("iis", $pengguna_id, $mata_kuliah_id, $hari_ini);
    $cek_absen->execute();
    $hasil_cek = $cek_absen->get_result();

    if ($hasil_cek->num_rows > 0) {
        // Jika sudah pernah scan di jam/hari yang sama
        echo json_encode([
            'success' => false, 
            'message' => 'Anda sudah melakukan absensi pada mata kuliah ini hari ini!'
        ]);
    } else {
        // 4. JIKA BELUM ABSEN: Masukkan data baru ke tabel riwayat_absensi lengkap dengan mata_kuliah_id
        $stmt = $conn->prepare("INSERT INTO riwayat_absensi (pengguna_id, mata_kuliah_id, waktu_absen, status) VALUES (?, ?, NOW(), 'Hadir')");
        $stmt->bind_param("ii", $pengguna_id, $mata_kuliah_id);

        if ($stmt->execute()) {
            // Mengembalikan respon sukses ke script.js untuk memicu alert() di browser
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan riwayat absen ke database.']);
        }
        $stmt->close();
    }
    
    $cek_absen->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Data ID Pengguna tidak valid.']);
}
?>