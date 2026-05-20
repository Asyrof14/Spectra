<?php
require_once 'config.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// AMBIL DATA USER
$query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($query);

// =====================================
// UPDATE PROFILE
// =====================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username']);

    // UPDATE USERNAME
    mysqli_query($conn,
        "UPDATE users SET username='$username' WHERE id='$user_id'"
    );
    $_SESSION['username'] = $username;

    // UPDATE PASSWORD
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_password)) {
        if (
            password_verify($old_password, $user['password']) &&
            $new_password == $confirm_password
        ) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            mysqli_query($conn,
                "UPDATE users SET password='$hashed' WHERE id='$user_id'"
            );
        }
    }

    // UPDATE FOTO PROFIL
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {

        $allowed_types = ['image/png', 'image/jpeg'];
        $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);

        // VALIDASI MIME TYPE (lebih aman dari extension)
        if (!in_array($file_type, $allowed_types)) {
            die("Hanya file PNG dan JPG yang diperbolehkan.");
        }

        // VALIDASI EXTENSION (tambahan keamanan)
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg'])) {
            die("Format file tidak valid.");
        }

        // GENERATE NAMA FILE AMAN
        $filename = uniqid() . '.' . $ext;

        // UPLOAD
        move_uploaded_file(
            $_FILES['profile_picture']['tmp_name'],
            'uploads/' . $filename
        );

        // UPDATE DATABASE
        mysqli_query($conn,
            "UPDATE users SET profile_picture='$filename' WHERE id='$user_id'"
        );

        $_SESSION['profile_picture'] = $filename;
    }

    // REFRESH DATA
    $query = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
    $user = mysqli_fetch_assoc($query);
}

// =====================================
// PROFILE PATH
// =====================================
$profilePath = "assets/default.png";

if (!empty($user['profile_picture'])) {
    $profilePath = "uploads/" . $user['profile_picture'];
}

// =====================================
// COLLECTION
// =====================================
$collectionQuery = mysqli_query($conn,
    "SELECT * FROM palettes
     WHERE user_id='$user_id'
     ORDER BY created_at DESC
     LIMIT 5"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Spectra</title>
    <link rel="stylesheet" href="css/Style.css?v=<?php echo filemtime('css/Style.css'); ?>">
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-left">
        <img class="logo" src="assets/SpectraLogo.svg">
    </div>

    <form action="search.php" method="GET" class="nav-search-static">
        <input type="text" name="q" placeholder="Try search something fancy?">
        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    </form>

    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="collection.php">Collection</a>
    </div>

    <div class="nav-right">
        <div class="profile-menu-container">
            <div class="profile-btn" id="profile-btn">
                <img src="<?php echo $profilePath; ?>">
            </div>

            <div class="profile-dropdown" id="profile-dropdown">
                <a href="profile.php" class="profile-info-link">
                    <div class="profile-info">
                        <div class="profile-avatar-large">
                            <img src="<?php echo $profilePath; ?>">
                        </div>
                        <div class="profile-name">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </div>
                    </div>
                </a>

                <ul class="profile-links">
                    <li><a href="collection.php">Collection</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="terms.php">Term</a></li>
                    <li><a href="privacy.php">Privacy</a></li>
                    <li><a href="logout.php" style="color:#ff4757;">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="main-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="tag">Popular Tags</div>
            <ul class="tag-list">
                <li><a href="search.php?q=Dark">Dark</a></li>
                <li><a href="search.php?q=Light">Light</a></li>
                <li><a href="search.php?q=Cold">Cold</a></li>
                <li><a href="search.php?q=Warm">Warm</a></li>
                <li><a href="search.php?q=Summer">Summer</a></li>
                <li><a href="search.php?q=Fall">Fall</a></li>
                <li><a href="search.php?q=Winter">Winter</a></li>
                <li><a href="search.php?q=Spring">Spring</a></li>
                <li><a href="search.php?q=Happy">Happy</a></li>
                <li><a href="search.php?q=Sad">Sad</a></li>
                <li><a href="search.php?q=Ocean">Ocean</a></li>
                <li><a href="search.php?q=Space">Space</a></li>
            </ul>
    </aside>

    <!-- CONTENT -->
    <main class="content">

        <!-- PROFILE -->
        <div class="profile-wrapper">

            <!-- LEFT -->
            <div class="profile-left">
                <div class="profile-image-wrapper">

                    <img src="<?php echo $profilePath; ?>" class="profile-image" id="profileImage">

                    <div class="profile-photo-menu" id="photoMenu">
                        <a href="<?php echo $profilePath; ?>" target="_blank">
                            View Photo
                        </a>

                        <button type="button" id="changePhotoBtn">
                            Change Photo
                        </button>
                    </div>
                </div>

                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p>Since <?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
            </div>

            <!-- RIGHT -->
            <div class="profile-right">
                <form method="POST" enctype="multipart/form-data" id="profileForm">

                    <input type="file" name="profile_picture" id="fileInput" hidden accept="image/png, image/jpeg">

                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>

                    <div class="input-group">
                        <label>Old Password</label>
                        <input type="password" name="old_password">
                    </div>

                    <div class="password-row">
                        <div class="input-group">
                            <label>New Password</label>
                            <input type="password" name="new_password">
                        </div>

                        <div class="input-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password">
                        </div>
                    </div>

                    <div class="profile-buttons">
                        <button type="reset" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save</button>
                    </div>

                </form>
            </div>
        </div>

        <!-- COLLECTION -->
        <div class="collection-section">
            <div class="collection-title">Your Collection</div>

            <div class="palette-grid">
                <?php while($palette = mysqli_fetch_assoc($collectionQuery)) { ?>
                    <?php
                        $colors = [
                            $palette['color1'],
                            $palette['color2'],
                            $palette['color3'],
                            $palette['color4'],
                            $palette['color5']
                        ];

                        $colorQuery = http_build_query(['c' => $colors]);
                    ?>

                    <div class="palette-card">
                        <div class="colors">
                            <?php foreach ($colors as $color) { ?>
                                <div class="color-stripe" style="background: <?php echo $color; ?>;">
                                    <span class="hex-text"><?php echo strtoupper($color); ?></span>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="card-footer">
                            <a class="action-btn" href="detail.php?<?php echo $colorQuery; ?>" title="View detail">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                </a>
                        </div>
                    </div>

                <?php } ?>
            </div>

            <a href="collection.php" class="more-btn">See More</a>
        </div>

    </main>
</div>

<script>
// NAVBAR DROPDOWN
const profileBtn = document.getElementById('profile-btn');
const profileDropdown = document.getElementById('profile-dropdown');

profileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('show');
});

// PHOTO MENU
const profileImage = document.getElementById('profileImage');
const photoMenu = document.getElementById('photoMenu');
const changePhotoBtn = document.getElementById('changePhotoBtn');
const fileInput = document.getElementById('fileInput');
const form = document.getElementById('profileForm');

profileImage.addEventListener('click', (e) => {
    e.stopPropagation();
    photoMenu.classList.toggle('show');
});

changePhotoBtn.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        form.submit();
    }
});

// CLOSE OUTSIDE CLICK
document.addEventListener('click', (e) => {
    if (!e.target.closest('.profile-menu-container')) {
        profileDropdown.classList.remove('show');
    }
    if (!e.target.closest('.profile-image-wrapper')) {
        photoMenu.classList.remove('show');
    }
});
</script>

</body>
</html>