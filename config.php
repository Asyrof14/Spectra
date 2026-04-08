<?php
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $host = "sql207.infinityfree.com";
// $user = "if0_41506027";
// $pass = "Spectra0987";
// $db   = "if0_41506027_spectra"; 

$host = "localhost";
$user = "root";
$pass = "";
$db   = "spectra";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

define('GEMINI_API_KEY', 'AIzaSyC1rm1zKUQhEKbURgk9UwJM_QTSKEOPlBo'); 
?>