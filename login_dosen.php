<?php
session_start();
$host = "localhost"; $db_user = "root"; $db_pass = ""; $db_name = "absensi_db";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Ambil data dosen berdasarkan username
        $stmt = $conn->prepare("SELECT id, nama_dosen, password FROM dosen WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // COCOKKAN PASSWORD 
            // Karena di register tadi disimpan sebagai teks biasa, gunakan pencocokan langsung (===)
            if ($password === $row['password']) {
                
                // Mendaftarkan Session yang WAJIB ada untuk Dashboard & Kamera Absen
                $_SESSION['dosen_logged_in'] = true;
                $_SESSION['dosen_id'] = $row['id'];
                $_SESSION['dosen_nama'] = $row['nama_dosen'];

                // Alihkan ke Dashboard Dosen
                header("Location: dashboard_dosen.php");
                exit;
            } else {
                $pesan = "Password yang Anda masukkan salah!";
            }
        } else {
            $pesan = "Username tidak ditemukan! Silakan registrasi terlebih dahulu.";
        }
        $stmt->close();
    } else {
        $pesan = "Username dan password wajib diisi!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal Dosen</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { color: #4e73df; text-align: center; margin-bottom: 20px; }
        .alert { padding: 10px; background: #f8d7da; color: #842029; border-radius: 6px; font-size: 14px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #4e73df; }
        .btn-login { width: 100%; padding: 10px; background: #4e73df; border: none; color: white; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; }
        .btn-login:hover { background: #2e59d9; }
        .reg-link { text-align: center; margin-top: 15px; font-size: 13px; }
        .reg-link a { color: #4e73df; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Login Portal Dosen</h2>

    <?php if (!empty($pesan)): ?>
        <div class="alert"><?php echo $pesan; ?></div>
    <?php endif; ?>

    <form action="login_dosen.php" method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
        <button type="submit" class="btn-login">Masuk ke Dashboard</button>
    </form>

    <div class="reg-link">
        Belum punya akun? <a href="register_dosen.php">Daftar di Sini</a>
    </div>
</div>

</body>
</html>