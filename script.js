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

// 4. Fungsi Menghitung Keterbukaan Kelopak Mata (Eye Aspect Ratio / EAR)
function calculateEAR(eye) {
    // Jarak vertikal kelopak mata
    const v1 = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y);
    const v2 = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y);
    // Jarak horizontal ujung mata
    const h = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y);
    
    return (v1 + v2) / (2.0 * h);
}

// 5. Fungsi Jalankan Absen dengan Liveness Detection
async function mulaiAbsen() {
    if (!faceMatcher) {
        alert("Sistem belum siap atau belum ada data wajah mahasiswa terdaftar di database!");
        return;
    }

    const opsiDetektor = new faceapi.TinyFaceDetectorOptions({ 
        inputSize: 416,          
        scoreThreshold: 0.3      
    });

    let isEyesClosed = false;
    let livenessPassed = false;
    let detection = null;

    statusLabel.innerText = "Silakan BERKEDIP sekali untuk verifikasi wajah asli...";

    // --- LOOPING DETEKSI KEDIP ---
    while (!livenessPassed) {
        detection = await faceapi.detectSingleFace(video, opsiDetektor)
                                 .withFaceLandmarks()
                                 .withFaceDescriptor();

        if (detection) {
            const landmarks = detection.landmarks;
            const leftEye = landmarks.getLeftEye();
            const rightEye = landmarks.getRightEye();

            // Hitung nilai kerenggangan mata kiri & kanan
            const leftEAR = calculateEAR(leftEye);
            const rightEAR = calculateEAR(rightEye);
            const avgEAR = (leftEAR + rightEAR) / 2;

            // Ambang batas (Threshold) mata merem. Umumnya di bawah 0.23
            const EAR_THRESHOLD = 0.23; 

            if (avgEAR < EAR_THRESHOLD) {
                isEyesClosed = true; // Mata terdeteksi merem
                statusLabel.innerText = "Mata terpejam... Sekarang buka mata Anda!";
            } else {
                // Jika mata kembali terbuka setelah sebelumnya merem = KEDIPAN VALID!
                if (isEyesClosed) {
                    livenessPassed = true;
                    statusLabel.innerText = "Kedipan terverifikasi! Memproses identitas...";
                    break; // Keluar dari loop pemindaian kedip
                }
            }
        } else {
            statusLabel.innerText = "Wajah tidak terdeteksi! Posisikan wajah Anda di depan kamera.";
        }

        // Jeda 50ms per frame agar browser tidak crash/lag
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    // ---------------------------------

    // Proses pencocokan wajah jika lolos liveness
    if (detection) {
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
}