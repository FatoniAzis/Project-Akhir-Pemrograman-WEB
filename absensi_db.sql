CREATE DATABASE IF NOT EXISTS absensi_db;
USE absensi_db;

-- Tabel untuk menyimpan data pengguna/mahasiswa
CREATE TABLE IF NOT EXISTS pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    nim_nik VARCHAR(50) NOT NULL UNIQUE,
    -- Menyimpan koordinat vektor wajah dalam bentuk teks JSON
    fitur_wajah TEXT NOT NULL 
);

-- Tabel untuk mencatat riwayat absensi
CREATE TABLE IF NOT EXISTS riwayat_absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengguna_id INT,
    waktu_absen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    STATUS ENUM('Hadir', 'Terlambat') DEFAULT 'Hadir',
    FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE
);