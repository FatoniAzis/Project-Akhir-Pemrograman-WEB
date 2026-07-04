<?php
$host = "localhost"; $user = "root"; $pass = ""; $db = "absensi_db";
$conn = new mysqli($host, $user, $pass, $db);

// Menghasilkan hash password yang sinkron dengan versi PHP di laptopmu
$password_asli = "admin123";
$password_hash = password_hash($password_asli, PASSWORD_DEFAULT);

// Update password milik username admin
$sql = "UPDATE admin SET password = '$password_hash' WHERE username = 'admin'";

if ($conn->query($sql) === TRUE) {
    echo "<h3>Sukses! Password admin berhasil diperbarui secara lokal.</h3>";
    echo "<p>Silakan kembali ke <a href='login.php'>Halaman Login</a> dan masuk dengan password: <b>admin123</b></p>";
} else {
    echo "Gagal memperbarui database: " . $conn->error;
}

$conn->close();
?>