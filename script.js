const video = document.getElementById('webcam');
const statusLabel = document.getElementById('status-label');
let faceMatcher = null; 

// 1. Muat Model AI dari CDN Online
statusLabel.innerText = "Memuat model AI wajah, mohon tunggu...";
Promise.all([
    faceapi.nets.tinyFaceDetector.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights'),
    faceapi.nets.faceLandmark68Net.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights'),
    faceapi.nets.faceRecognitionNet.loadFromUri('https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights')
]).then(persiapkanDataWajah).catch(err => {
    statusLabel.innerText = "Gagal memuat model AI. Periksa koneksi internet.";
    console.error(err);
});

// 2. Ambil data wajah dari database
async function persiapkanDataWajah() {
    statusLabel.innerText = "Sinkronisasi data wajah dari database...";
    try {
        const res = await fetch('ambil_pengguna.php');
        const dataPengguna = await res.json();

        if (dataPengguna.length === 0) {
            statusLabel.innerText = "Belum ada wajah terdaftar. Silakan klik 'Daftar Wajah Baru'.";
            setupCamera();
            return;
        }

        const labeledDescriptors = dataPengguna.map(user => {
            const descriptor = new Float32Array(user.fitur_wajah);
            return new faceapi.LabeledFaceDescriptors(`${user.id}_${user.nama}`, [descriptor]);
        });

        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
        setupCamera();
    } catch (err) {
        statusLabel.innerText = "Gagal mengambil data dari database.";
        console.error(err);
    }
}

// 3. Hidupkan Kamera
async function setupCamera() {
    statusLabel.innerText = "Menghubungkan kamera...";
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
        statusLabel.innerText = "Sistem Absensi Siap! Posisikan wajah Anda di depan kamera.";
    } catch (err) {
        statusLabel.innerText = "Gagal mengakses kamera. Izinkan akses webcam.";
    }
}

async function mulaiAbsen() {
    if (!faceMatcher) {
        alert("Sistem belum siap atau belum ada data wajah mahasiswa terdaftar di database!");
        return;
    }

    statusLabel.innerText = "Memverifikasi wajah Anda, mohon jangan bergerak...";
    
    // --- PERBAIKAN DI SINI: Mengatur parameter detektor agar lebih sensitif ---
    const opsiDetektor = new faceapi.TinyFaceDetectorOptions({ 
        inputSize: 416,          // Dinaikkan dari 160 ke 416 agar bisa mendeteksi wajah lebih jauh
        scoreThreshold: 0.3      // Diturunkan dari 0.5 ke 0.3 agar AI lebih toleran pada pencahayaan redup
    });

    // Jalankan deteksi dengan opsi baru
    const detection = await faceapi.detectSingleFace(video, opsiDetektor)
                                    .withFaceLandmarks()
                                    .withFaceDescriptor();
    // -------------------------------------------------------------------------

    if (!detection) {
        alert("Wajah tidak terdeteksi oleh kamera! Coba atur pencahayaan atau dekatkan wajah.");
        statusLabel.innerText = "Absen gagal. Wajah tidak terdeteksi.";
        return;
    }

    // Cocokkan wajah kamera dengan database
    const match = faceMatcher.findBestMatch(detection.descriptor);
    
    if (match.label === 'unknown') {
        alert("Wajah tidak dikenali! Pastikan Anda sudah terdaftar.");
        statusLabel.innerText = "Absen ditolak. Wajah tidak dikenal.";
    } else {
        const [id, nama] = match.label.split('_');
        statusLabel.innerText = `Wajah cocok: ${nama}. Mengirim data kehadiran...`;

        fetch('catat_absen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pengguna_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`Selamat, ${nama}! Absensi Anda berhasil dicatat.`);
                statusLabel.innerText = `Absen Sukses: ${nama}`;
            } else {
                alert("Gagal menyimpan absensi: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Terjadi kesalahan koneksi server saat mencatat absen.");
        });
    }
}