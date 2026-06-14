<?php
header('Content-Type: application/json');

// 1. Koneksi ke Database MySQL
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "absensi_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// 2. Ambil data pengguna dan fitur wajah yang sudah terdaftar
$result = $conn->query("SELECT id, nama, nim_nik, fitur_wajah FROM pengguna");
$data = [];

while($row = $result->fetch_assoc()) {
    // Mengubah string teks JSON dari database kembali menjadi array angka asli
    $row['fitur_wajah'] = json_decode($row['fitur_wajah'], true);
    $data[] = $row;
}

// 3. Kirimkan data ke JavaScript dalam format JSON
echo json_encode($data);

$conn->close();
?>