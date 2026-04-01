<?php
require_once 'config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validasi Password
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // 2. Cek apakah username/email sudah terdaftar
        $user_check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($user_check) > 0) {
            $error = "Username atau Email sudah digunakan!";
        } else {
            // 3. Hash Password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // 4. Simpan ke Database
            $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";
            if (mysqli_query($conn, $query)) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Register - Spectra</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/LogRegStyle.css">
</head>
<body>

<div class="container">
    <div class="left" style="background: url('assets/LogRegImg.svg') center/cover no-repeat;">
        <div class="overlay">
            <div class="left-text">
                <h2>Unlock the Power of Perfect Harmony.</h2>
                <p>Save your favorite harmonies, manage your collections, and discover the shades that define your next project.</p>
            </div>
        </div>
    </div>

    <div class="right">
        <div class="form-box">
            <h1>Start your Creativity!</h1>
            <p class="subtitle">Register your account</p>

            <?php if($error): ?>
                <p style="color: #ff4757; margin-bottom: 15px; font-size: 0.9rem;"><?php echo $error; ?></p>
            <?php endif; ?>
            <?php if($success): ?>
                <p style="color: #2ed573; margin-bottom: 15px; font-size: 0.9rem;"><?php echo $success; ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>

                <label>Email</label>
                <input type="email" name="email" placeholder="Email Address" required>

                <label>Password</label>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <span class="eye">👁</span>
                </div>

                <label>Confirm your password</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Repeat Password" required>
                    <span class="eye">👁</span>
                </div>

                <button type="submit">Register</button>
            </form>
            <p class="bottom">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</div>

</body>
</html>