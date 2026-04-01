<?php
require_once 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Cari user berdasarkan username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);

        // 2. Verifikasi password hash
        if (password_verify($password, $row['password'])) {
            // 3. Set Session (Menyimpan data user yang login)
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];

    
            header("Location: index.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Login - Spectra</title>
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
            <h1>Welcome back, Buddy</h1>
            <p class="subtitle">Sign in your account</p>

            <?php if($error): ?>
                <p style="color: #ff4757; margin-bottom: 15px; font-size: 0.9rem;"><?php echo $error; ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <label>Username</label>
                <input type="text" name="username" placeholder="Username" required>
                
                <label>Password</label>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <span class="eye">👁</span>
                </div>
                
                <button type="submit">Login</button>
            </form>
            <p class="bottom">Don't have any account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>

</body>
</html>