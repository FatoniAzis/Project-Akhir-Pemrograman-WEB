<?php
header('Content-Type: application/json');

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db = "absensi_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['pengguna_id'])) {

    $pengguna_id = $conn->real_escape_string($data['pengguna_id']);

    // Cek apakah sudah absen hari ini
    $cek = "
        SELECT id
        FROM riwayat_absensi
        WHERE pengguna_id = '$pengguna_id'
        AND DATE(waktu_absen) = CURDATE()
    ";

    $hasil = $conn->query($cek);

    if ($hasil->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Anda sudah melakukan absensi masuk hari ini."
        ]);
        exit;
    }

    // Simpan absensi jika belum ada
    $sql = "
        INSERT INTO riwayat_absensi (pengguna_id, status)
        VALUES ('$pengguna_id', 'Hadir')
    ";

    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            "success" => true,
            "message" => "Absensi berhasil dicatat."
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal mencatat absensi: " . $conn->error
        ]);
    }

} else {

    echo json_encode([
        "success" => false,
        "message" => "Data tidak valid."
    ]);

}

$conn->close();
?>