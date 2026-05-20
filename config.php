<<<<<<< HEAD


=======
>>>>>>> d13217a (update profil)
<?php
// 1. FUNGSI PEMBACA FILE .env
$envFilePath = __DIR__ . '/.env';
if (file_exists($envFilePath)) {
    $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Abaikan baris yang diawali dengan # (Komentar)
        if (strpos(trim($line), '#') === 0) continue;
        
        // Pisahkan nama kunci dan nilainya
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Masukkan ke dalam environment server
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
// $host = "sql207.infinityfree.com";
// $user = "if0_41506027";
// $pass = "Spectra0987";
// $db   = "if0_41506027_spectra"; 

// 2. AMBIL DATA DARI ENVIRONMENT
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'spectra';

// 3. KONEKSI KE DATABASE
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// 4. PENGATURAN SESI
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
<<<<<<< HEAD
=======

function getProfilePath($conn, $user_id) {
    $defaultPath = "assets/default.png";

    // Ambil data dari database
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && !empty($user['profile_picture'])) {
        $relativePath = "uploads/" . $user['profile_picture'];
        $absolutePath = __DIR__ . "/" . $relativePath;

        if (file_exists($absolutePath)) {
            return $relativePath;
        }
    }

    return $defaultPath;
}
>>>>>>> d13217a (update profil)
?>