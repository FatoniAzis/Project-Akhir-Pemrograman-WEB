const video = document.getElementById('webcam-reg');
const statusReg = document.getElementById('status-reg');
const btnDaftar = document.getElementById('btn-daftar');

// 1. Load Model AI (menggunakan boks deteksi yang lebih akurat untuk registrasi)
Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('./models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('./models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('./models')
]).then(startWebcam);

async function startWebcam() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
        statusReg.innerText = "Model siap. Silakan isi form dan posisikan wajah tegak ke kamera.";
        statusReg.style.background = "#d4edda";
        statusReg.style.color = "#155724";
        btnDaftar.disabled = false; // Aktifkan tombol daftar
    } catch (err) {
        statusReg.innerText = "Gagal mengakses kamera.";
        console.error(err);
    }
}

// 2. Fungsi Ekstraksi & Kirim Data ke PHP
async function prosesRegistrasi() {
    const nama = document.getElementById('nama').value.trim();
    const nim = document.getElementById('nim').value.trim();

    if (!nama || !nim) {
        alert("Nama dan NIM wajib diisi!");
        return;
    }

    statusReg.innerText = "Sedang memindai wajah, jangan bergerak...";
    statusReg.style.background = "#fff3cd";
    statusReg.style.color = "#856404";

    const opsiDetektor = new faceapi.TinyFaceDetectorOptions({ 
    inputSize: 416, 
    scoreThreshold: 0.3 
    });

const detection = await faceapi.detectSingleFace(video, opsiDetektor)
                                .withFaceLandmarks()
                                .withFaceDescriptor();

    if (!detection) {
        alert("Wajah tidak terdeteksi dengan jelas. Coba atur pencahayaan atau posisi wajah.");
        statusReg.innerText = "Pindai gagal. Wajah tidak terdeteksi.";
        return;
    }

    // Mengubah descriptor Float32Array menjadi Array JavaScript biasa
    const wajahArray = Array.from(detection.descriptor);

    // Siapkan data untuk dikirim ke server backend PHP
    const dataKirim = {
        nama: nama,
        nim: nim,
        fitur_wajah: JSON.stringify(wajahArray) // Vektor diubah jadi string teks JSON
    };

    // Kirim data menggunakan Fetch API (AJAX)
    fetch('register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dataKirim)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Registrasi Berhasil!");
            window.location.href = "index.html";
        } else {
            alert("Gagal mendaftar: " + data.message);
            statusReg.innerText = "Gagal menyimpan ke database.";
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Terjadi kesalahan koneksi ke server.");
    });
}