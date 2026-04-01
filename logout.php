<?php
require_once 'config.php'; // Mengambil session_start() dari config

// 1. Hapus semua data di dalam variabel $_SESSION
$_SESSION = array();

// 2. Perintahkan browser untuk menghapus Cookie "PHPSESSID"
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Hancurkan data sesi yang tersimpan di server
session_destroy();

// 4. Lempar pengguna kembali ke halaman utama (index.php)
header("Location: index.php");
exit;
?>