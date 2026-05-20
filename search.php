<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

<<<<<<< HEAD
function generateAIPalettes() {
=======
$user_id = $_SESSION['user_id'];
$profilePath = getProfilePath($conn, $user_id);

// Menangkap query pencarian jika ada
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

function generateAIPalettes($query = '') {
>>>>>>> d13217a (update profil)
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
        echo "<div style='background:orange; padding:10px;'>API Key belum di-set di config.php!</div>";
        return [];
    }

<<<<<<< HEAD
    // Gunakan streamGenerateContent agar response diterima per-chunk, tidak timeout
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:streamGenerateContent?alt=sse&key=" . GEMINI_API_KEY;

    $prompt = 'Generate exactly 10 color palettes. For each palette, provide exactly 5 hex color codes and 3 descriptive single-word tags (e.g., "Ocean", "Dark", "Cyberpunk"). Output ONLY a valid JSON array of objects. No markdown, no explanation. Example: [{"colors":["#111111","#222222","#333333","#444444","#555555"], "tags":["Dark", "Monochrome", "Night"]}]';

    $data = ["contents" => [["parts" => [["text" => $prompt]]]]];

    // Buffer untuk menampung semua chunk SSE yang masuk
    $rawBuffer = '';
=======
    // Menggunakan endpoint generateContent biasa agar parsing stabil & cepat
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;

    // Prompt diringkas & dibuat dinamis mengikuti input pencarian ($query)
    $context = !empty($query) ? "theme/vibe: '$query'" : "random creative themes";
    $prompt = "Generate 10 color palettes for $context. Return ONLY a JSON array of objects. 
    Format: [{\"colors\":[\"#hex1\",...], \"tags\":[\"tag1\",...]}]";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "responseMimeType" => "application/json" // Memaksa Gemini mengembalikan JSON murni
        ]
    ];
>>>>>>> d13217a (update profil)

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
<<<<<<< HEAD
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Tangkap setiap chunk SSE ke buffer, jangan tunggu selesai semua
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$rawBuffer) {
        $rawBuffer .= $chunk;
        return strlen($chunk); // harus return panjang chunk agar cURL tidak error
    });

    curl_exec($ch);
=======
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
>>>>>>> d13217a (update profil)
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

<<<<<<< HEAD
    if ($curlError) {
        echo "<div style='background:red;color:white;padding:20px;border:2px solid black;margin:20px;'>";
        echo "<h3>cURL Error</h3><p>" . htmlspecialchars($curlError) . "</p>";
        echo "</div>";
        return [];
    }

    if ($httpCode !== 200) {
        preg_match('/\{.*\}/s', $rawBuffer, $matches);
        $errData = $matches ? json_decode($matches[0], true) : null;
        $errMsg  = $errData['error']['message'] ?? htmlspecialchars(substr($rawBuffer, 0, 300));

        echo "<div style='background:red;color:white;padding:20px;border:2px solid black;margin:20px;'>";
        echo "<h3>Error $httpCode</h3><p>$errMsg</p>";
        echo "</div>";
        return [];
    }

    // Parse SSE: setiap baris dimulai dengan "data: {...}"
    // Gabungkan semua field "text" dari setiap chunk menjadi satu string
    $fullText = '';
    foreach (explode("\n", $rawBuffer) as $line) {
        $line = trim($line);
        if (strpos($line, 'data:') !== 0) continue;

        $json = trim(substr($line, 5)); // hapus "data: " di depan
        if ($json === '[DONE]') break;

        $chunk = json_decode($json, true);
        $piece = $chunk['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $fullText .= $piece;
    }

    // Bersihkan markdown fence jika ada (``` atau ```json)
    $fullText = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/\s*```$/m', '', $fullText)));
=======
    if ($curlError || $httpCode !== 200) {
        echo "<div style='background:red;color:white;padding:10px;margin:10px;'>Gagal mengambil data dari AI (Code: $httpCode)</div>";
        return [];
    }

    $resData = json_decode($response, true);
    $fullText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

    // fallback jika teks mengandung markdown fence
    $fullText = trim(preg_replace('/^```(?:json)?\s*/i', '', preg_replace('/\s*
```$/m', '', $fullText)));
>>>>>>> d13217a (update profil)

    $palettes = json_decode($fullText, true);
    return is_array($palettes) ? $palettes : [];
}

<<<<<<< HEAD
if (isset($_GET['reset'])) unset($_SESSION['ai_palettes']);
if (!isset($_SESSION['ai_palettes']) || empty($_SESSION['ai_palettes'])) {
    $_SESSION['ai_palettes'] = generateAIPalettes();
}
$palettes = $_SESSION['ai_palettes'];

function isPaletteSaved($conn, $user_id, $colors) {
=======
// Logika reset atau pencarian baru
if (isset($_GET['reset']) || !empty($searchQuery)) {
    // Jika ada kata kunci pencarian, generate langsung berdasarkan keyword tersebut
    $_SESSION['ai_palettes'] = generateAIPalettes($searchQuery);
}

if (!isset($_SESSION['ai_palettes']) || empty($_SESSION['ai_palettes'])) {
    $_SESSION['ai_palettes'] = generateAIPalettes();
}

$palettes = $_SESSION['ai_palettes'];

function isPaletteSaved($conn, $user_id, $colors) {
    if (count($colors) < 5) return false;
>>>>>>> d13217a (update profil)
    $query = "SELECT id FROM palettes WHERE user_id = ? 
              AND color1 = ? AND color2 = ? AND color3 = ? AND color4 = ? AND color5 = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssss", $user_id, $colors[0], $colors[1], $colors[2], $colors[3], $colors[4]);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spectra - Color Palettes</title>
    <link rel="stylesheet" href="css/Style.css?v=<?php echo filemtime('css/Style.css'); ?>">
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <img class="logo" src="assets/SpectraLogo.svg" alt="Spectra">
        </div>
<<<<<<< HEAD
        <form action="search.php" method="GET" class="nav-search-wrapper" id="nav-search-wrapper">
            <input type="text" name="q" placeholder="Try search something fancy?">
=======
        <!-- Search bar di navbar diubah action-nya ke index.php agar memicu AI -->
        <form action="index.php" method="GET" class="nav-search-wrapper" id="nav-search-wrapper">
            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search theme with AI (e.g. Cyberpunk, Pastel)...">
>>>>>>> d13217a (update profil)
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
        </form>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="collection.php">Collection</a>
        </div>
        <div class="nav-right">
            <div class="profile-menu-container">
                <div class="profile-btn" id="profile-btn">
<<<<<<< HEAD
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <div class="profile-dropdown" id="profile-dropdown">
                    <div class="profile-info">
                        <div class="profile-avatar-large">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div class="profile-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>
=======
                    <img src="<?php echo $profilePath; ?>">
                </div>
                <div class="profile-dropdown" id="profile-dropdown">
                    <a href="profile.php" class="profile-info-link">
                        <div class="profile-info">
                            <div class="profile-avatar-large">
                                <img src="<?php echo $profilePath; ?>">
                            </div>
                            <div class="profile-name">
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                            </div>
                        </div>
                    </a>
>>>>>>> d13217a (update profil)
                    <ul class="profile-links">
                        <li><a href="collection.php">Collection</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="terms.php">Term of Service</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="logout.php" style="color:#ff4757;">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <aside class="sidebar">
            <div class="tag">Popular Tags</div>
            <ul class="tag-list">
<<<<<<< HEAD
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
=======
                <!-- Tag populer diarahkan ke index.php?q= agar dicari lewat AI -->
                <li><a href="index.php?q=Dark">Dark</a></li>
                <li><a href="index.php?q=Light">Light</a></li>
                <li><a href="index.php?q=Cold">Cold</a></li>
                <li><a href="index.php?q=Warm">Warm</a></li>
                <li><a href="index.php?q=Summer">Summer</a></li>
                <li><a href="index.php?q=Fall">Fall</a></li>
                <li><a href="index.php?q=Winter">Winter</a></li>
                <li><a href="index.php?q=Spring">Spring</a></li>
                <li><a href="index.php?q=Happy">Happy</a></li>
                <li><a href="index.php?q=Sad">Sad</a></li>
                <li><a href="index.php?q=Ocean">Ocean</a></li>
                <li><a href="index.php?q=Space">Space</a></li>
>>>>>>> d13217a (update profil)
            </ul>
        </aside>

        <main class="content" id="scroll-content">
            <section class="hero">
                <div class="hero-text">
                    <h1>The Simplest Way to Choose Your Brand Colors</h1>
                    <p>Explore thousands of curated color palettes generated by AI and find the exact shades that bring your creative vision to life.</p>
                </div>
            </section>

            <section class="search-container" id="main-search-container">
<<<<<<< HEAD
                <form action="search.php" method="GET" class="search-box">
                    <input type="text" name="q" placeholder="Try search something fancy?" required>
=======
                <!-- Search box utama diubah action-nya ke index.php -->
                <form action="index.php" method="GET" class="search-box">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Ask AI for something fancy (e.g., Retro Neon, Cozy Autumn)..." required>
>>>>>>> d13217a (update profil)
                    <button type="submit" style="background:none;border:none;outline:none;cursor:pointer;display:flex;align-items:center;padding:0;">
                        <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </button>
                </form>
            </section>

<<<<<<< HEAD
=======
            <?php if (!empty($searchQuery)): ?>
                <div style="margin: 0 0 20px 0; color: #555;">
                    Menampilkan hasil pencarian AI untuk: <strong>"<?php echo htmlspecialchars($searchQuery); ?>"</strong> 
                    | <a href="index.php" style="color:#ff4757; text-decoration:none;">Clear Search</a>
                </div>
            <?php endif; ?>

>>>>>>> d13217a (update profil)
            <section class="palette-grid">
                <?php if (!empty($palettes) && is_array($palettes)): ?>
                    <?php foreach ($palettes as $paletteData): ?>
                        <?php 
                            if (!is_array($paletteData) || !isset($paletteData['colors'])) continue; 
                            
                            $colors = $paletteData['colors'];
                            $tags = isset($paletteData['tags']) && is_array($paletteData['tags']) ? $paletteData['tags'] : ['Spectra']; 

                            $savedClass = '';
                            $isSaved = false;
                            if (isPaletteSaved($conn, $_SESSION['user_id'], $colors)) {
                                $savedClass = 'is-saved';
                                $isSaved = true;
                            }

                            $colorQuery = http_build_query(['c' => $colors, 't' => $tags]);
                            $savePayload = htmlspecialchars(json_encode(['colors' => $colors, 'tags' => $tags]), ENT_QUOTES); 
                        ?>
                        <div class="palette-card">
                            <div class="colors">
                                <?php foreach ($colors as $hex): ?>
                                    <div class="color-stripe" style="background:<?php echo trim($hex);?>;">
                                        <span class="hex-text"><?php echo strtoupper(trim($hex)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="card-footer palette-card-footer-extended">
                                <div class="footer-actions-row">
                                    <div class="action-btn save-btn <?php echo $savedClass; ?>" 
                                        data-payload='<?php echo $savePayload; ?>'
                                        data-saved="<?php echo $isSaved ? 'true' : 'false'; ?>">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </div>
                                    <a class="action-btn" href="detail.php?<?php echo $colorQuery; ?>" title="View detail">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
<<<<<<< HEAD
                    <p style="text-align:center;width:100%;color:#888;">AI sedang berpikir... Coba refresh halaman jika palet tidak muncul.</p>
=======
                    <p style="text-align:center;width:100%;color:#888;">AI tidak berhasil menemukan palet untuk tema tersebut. Coba kata kunci lain.</p>
>>>>>>> d13217a (update profil)
                <?php endif; ?>
            </section>

            <div class="generate-btn-container">
                <a href="index.php?reset=1" class="btn-generate">
                    <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                    Generate New Palettes
                </a>
            </div>
        </main>
    </div>

<<<<<<< HEAD
=======
    <!-- Script JavaScript Anda tetap sama dan berfungsi dengan baik -->
>>>>>>> d13217a (update profil)
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
<<<<<<< HEAD
            profileBtn.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target))
=======
            if(profileBtn) {
                profileBtn.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            }
            document.addEventListener('click', (e) => {
                if (profileBtn && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target))
>>>>>>> d13217a (update profil)
                    profileDropdown.classList.remove('show');
            });

            document.querySelectorAll('.color-stripe').forEach(stripe => {
                stripe.addEventListener('click', () => {
                    const hexEl = stripe.querySelector('.hex-text');
                    navigator.clipboard.writeText(hexEl.innerText).then(() => {
                        const orig = hexEl.innerText;
                        hexEl.innerText = 'Copied!';
                        setTimeout(() => { hexEl.innerText = orig; }, 1000);
                    });
                });
            });

            document.querySelectorAll('.save-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (this.dataset.saved === 'true' || this.dataset.sending === 'true') return;
                    this.dataset.sending = 'true';

                    const payload = JSON.parse(this.dataset.payload);
                    const formData = new URLSearchParams();
                    
                    formData.append('action', 'save');
                    payload.colors.forEach(color => formData.append('colors[]', color));
                    payload.tags.forEach(tag => formData.append('tags[]', tag));

                    fetch('collection.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success' || data.status === 'already_saved') {
                            this.classList.add('is-saved');
                            this.dataset.saved = 'true';
                        } else {
                            alert('Gagal simpan: ' + (data.message || 'Coba login ulang'));
                            this.dataset.sending = 'false';
                        }
                    })
                    .catch(err => { console.error('Error:', err); this.dataset.sending = 'false'; });
                });
            });
        });
    </script>
</body>
</html>