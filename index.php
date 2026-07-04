<?php
session_start();

// 1. Validasi Utama: Pastikan Dosen sudah login secara sah
if (!isset($_SESSION['dosen_logged_in']) || $_SESSION['dosen_logged_in'] !== true) {
    // Jika belum login dosen, paksa ke login portal dosen
    header("Location: login_dosen.php");
    exit;
}

// 2. Perbaikan/Fallback: Jika belum memilih kelas, otomatis buatkan session default agar tidak mental atau error
if (!isset($_SESSION['active_matkul_id'])) {
    $_SESSION['active_matkul_id'] = 1; 
}
if (!isset($_SESSION['active_matkul_nama'])) {
    $_SESSION['active_matkul_nama'] = "Pemrograman Web"; 
}
if (!isset($_SESSION['dosen_nama'])) {
    $_SESSION['dosen_nama'] = "Dosen Pengampu";
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistem Absensi Wajah Pintar</title>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }
      body {
        background-color: #f4f6f9;
        display: flex;
        flex-direction: column;
        align-items: center;
        min-height: 100vh;
        padding: 20px;
      }
      header {
        text-align: center;
        margin-bottom: 20px;
      }
      header h1 {
        color: #333;
        font-size: 24px;
      }
      header p {
        color: #666;
      }
      .info-kelas {
        background: #ffffff; 
        padding: 8px 15px; 
        border-radius: 20px; 
        display: inline-block; 
        margin-top: 10px; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        font-size: 14px;
        color: #5a5c69;
      }
      .container {
        background: #ffffff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 640px;
        text-align: center;
      }
      .camera-area {
        position: relative;
        width: 100%;
        max-width: 600px;
        height: 450px;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
        margin: 0 auto 20px auto;
      }
      video {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      canvas {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
      }
      .status-box {
        padding: 12px;
        background: #e1f5fe;
        color: #0288d1;
        border-radius: 6px;
        font-weight: bold;
        margin-bottom: 15px;
        transition: all 0.3s ease;
      }
      .btn-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
      }
      .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: 0.3s;
        font-size: 14px;
      }
      .btn-primary {
        background: #4e73df;
        color: white;
      }
      .btn-primary:hover {
        background: #2e59d9;
      }
      .btn-success {
        background: #1cc88a;
        color: white;
      }
      .btn-success:hover {
        background: #17a673;
      }
      .btn-secondary {
        background: #858796;
        color: white;
      }
      .btn-secondary:hover {
        background: #5a5c69;
      }
    </style>
  </head>
  <body>
    <header>
      <h1>Sistem Absensi Face Recognition</h1>
      <p>Proyek Akhir Pemrograman Web</p>
      
      <div class="info-kelas">
        Mata Kuliah: <strong style="color: #4e73df;"><?php echo htmlspecialchars($_SESSION['active_matkul_nama']); ?></strong> 
        | Dosen: <strong style="color: #1cc88a;"><?php echo htmlspecialchars($_SESSION['dosen_nama']); ?></strong>
      </div>
    </header>

    <div class="container">
      <div class="status-box" id="status-label">
        Menghubungkan kamera dan memuat AI model...
      </div>

      <div class="camera-area">
        <video id="webcam" autoplay muted playsinline></video>
      </div>

      <div class="btn-group">
        <button class="btn btn-primary" onclick="mulaiAbsen()">Ambil Absen</button>
        <a href="registrasi.html" class="btn btn-success">Daftar Wajah Baru</a>
        <a href="dashboard_dosen.php" class="btn btn-secondary">Kembali ke Dashboard</a>
      </div>
    </div>

    <script src="script.js"></script>
  </body>
</html>