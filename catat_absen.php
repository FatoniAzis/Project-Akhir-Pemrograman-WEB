<?php
header('Content-Type: application/json');

// Koneksi ke database MySQL XAMPP
$host = "localhost"; $user = "root"; $pass = ""; $db = "absensi_db";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal."]);
    exit;
}

// Menangkap kiriman data mentah JSON dari JavaScript (fetch)
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['pengguna_id'])) {
    $pengguna_id = $conn->real_escape_string($data['pengguna_id']);
    
    // Query SQL untuk mencatat riwayat kehadiran
    $sql = "INSERT INTO riwayat_absensi (pengguna_id, status) VALUES ('$pengguna_id', 'Hadir')";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Absensi berhasil dicatat."]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal mencatat ke database: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Data tidak valid."]);
}

$conn->close();
?>