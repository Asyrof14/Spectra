<?php
require_once 'config.php';

// 1. Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service</title>
    <link rel="stylesheet" href="css/Style.css?v=<?php echo filemtime('css/Style.css'); ?>">
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
       <img class="logo" src="assets/SpectraLogo.svg" alt="">
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
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>

                <div class="profile-dropdown" id="profile-dropdown">
                    <div class="profile-info">
                        <div class="profile-avatar-large">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>

                    <ul class="profile-links">
                        <li><a href="collection.php">Collection</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="terms.php">Term of Service</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="logout.php" class="login" style="color:#ff4757;">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-container">
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

        <main class="content" id="scroll-content">
            <p class="about-title">Terms of Service</p>
            <div class="about">
                <p class="about-subtitle">Acceptance of Terms</p>
                <p>By accessing and using this website, you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you are prohibited from using or accessing this site.</p>
            </div>
            <div class="about">
                <p class="about-subtitle">Use License</p>
                <p>1. You are granted permission to use the generated color palettes for both personal and commercial design projects.</p>
                <p>2. You may not use this service to engage in any automated data scraping or "spamming" the generation system.</p>
                <p>3. The smart generation system is provided "as is." We do not guarantee that the colors generated will perfectly meet specific accessibility standards or branding requirements without your own further review.</p>
            </div>
            <div class="about">
                <p class="about-subtitle">User Account</p>
                <p>When you create an account, you must provide accurate information. You are responsible for maintaining the security of your account and any activities that occur under your password.</p>
            </div>
            <div class="about">
                <p class="about-subtitle">Limitations</p>
                <p>In no event shall the platform owners be liable for any damages arising out of the use or inability to use the materials on this website, even if notified orally or in writing of the possibility of such damage.</p>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scrollContent = document.getElementById('scroll-content');
            const mainSearch = document.getElementById('main-search-container');
            const navSearchWrapper = document.getElementById('nav-search-wrapper');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    navSearchWrapper.classList.toggle('active', !entry.isIntersecting);
                });
            }, { root: scrollContent, threshold: 0 });
            if (mainSearch) observer.observe(mainSearch);

            const profileBtn = document.getElementById('profile-btn');
            const profileDropdown = document.getElementById('profile-dropdown');
            profileBtn.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target))
                    profileDropdown.classList.remove('show');
            });
        });
    </script>
</body>
</html>