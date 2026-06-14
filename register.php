<?php
header('Content-Type: application/json');

// 1. Koneksi ke Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "absensi_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Koneksi database gagal."]);
    exit;
}

// 2. Menangkap kiriman data JSON dari Fetch API JavaScript
$inputRaw = file_get_contents("php://input");
$data = json_decode($inputRaw, true);

if (!isset($data['nama']) || !isset($data['nim']) || !isset($data['fitur_wajah'])) {
    echo json_encode(["success" => false, "message" => "Data input tidak lengkap."]);
    exit;
}

// 3. Sanitasi Input (Keamanan Dasar mencegah SQL Injection)
$nama = $conn->real_escape_string($data['nama']);
$nim = $conn->real_escape_string($data['nim']);
$fitur_wajah = $conn->real_escape_string($data['fitur_wajah']);

// Check apakah NIM sudah pernah terdaftar
$checkNim = $conn->query("SELECT id FROM pengguna WHERE nim_nik = '$nim'");
if ($checkNim->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "NIM sudah terdaftar sebelumnya."]);
    exit;
}

// 4. Jalankan Query Insert ke tabel pengguna
$sql = "INSERT INTO pengguna (nama, nim_nik, fitur_wajah) VALUES ('$nama', '$nim', '$fitur_wajah')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Data berhasil disimpan."]);
} else {
    echo json_encode(["success" => false, "message" => "Error database: " . $conn->error]);
}

$conn->close();
?>