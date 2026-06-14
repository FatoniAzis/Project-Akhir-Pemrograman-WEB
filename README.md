Struktur file

C:\xampp\htdocs\absensi wajah\
│
├── index.html          <-- Halaman utama (Kamera & tombol Ambil Absen)
├── script.js           <-- Logika AI & pencocokan wajah halaman utama
│
├── registrasi.html     <-- Halaman pendaftaran mahasiswa baru
├── registrasi.js       <-- Logika pemindaian & ekstrak wajah baru
│
├── register.php        <-- Server-side: Menyimpan wajah baru ke MySQL
├── ambil_pengguna.php  <-- Server-side: Mengambil data wajah dari MySQL ke AI
├── catat_absen.php     <-- Server-side: Mencatat riwayat kehadiran sukses
│
├── dashboard.php       <-- Halaman laporan rekapitulasi absensi (Admin)
│
└── models/             <-- FOLDER KHUSUS (Berisi 6 file AI face-api.js)
    ├── tiny_face_detector_model-weights_manifest.json
    ├── tiny_face_detector_model-shard1
    ├── face_landmark_68_model-weights_manifest.json
    ├── face_landmark_68_model-shard1
    ├── face_recognition_model-weights_manifest.json
    └── face_recognition_model-shard1
