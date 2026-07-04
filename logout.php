<?php
// 1. Mulai session agar sistem tahu session mana yang mau dihapus
session_start();

// 2. Kosongkan semua variabel session yang sedang aktif
$_SESSION = array();

// 3. Hancurkan session cookie di browser (opsional tapi bagus untuk keamanan ganda)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan seluruh data session di server
session_destroy();

// 5. Alihkan kembali pengguna ke halaman login dosen
header("Location: login_dosen.php");
exit;
?>