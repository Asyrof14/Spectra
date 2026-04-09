<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$palettes = [];

function searchAIPalettes($keyword) {
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY) || empty($keyword)) return [];

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent?key=" . GEMINI_API_KEY;
    // Prompt Pintar: Meminta Object JSON dengan tag yang sangat berkaitan dengan keyword pencarian
    $prompt = "Generate exactly 20 color palettes strongly inspired by the theme: '{$keyword}'. For each palette, provide exactly 5 hex color codes and 3 descriptive single-word tags where at least one tag is closely related to '{$keyword}'. Output ONLY a valid JSON array of objects. No markdown, no explanation. Example: [{\"colors\":[\"#111111\",\"#222222\",\"#333333\",\"#444444\",\"#555555\"], \"tags\":[\"{$keyword}\", \"Dark\", \"Night\"]}]";

    $data = ["contents" => [["parts" => [["text" => $prompt]]]]];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $aiText = $responseData['candidates'][0]['content']['parts'][0]['text'];
        $start = strpos($aiText, '[');
        $end = strrpos($aiText, ']');
        if ($start !== false && $end !== false) {
            $jsonString = substr($aiText, $start, $end - $start + 1);
            $palettesArray = json_decode($jsonString, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($palettesArray)) {
                return $palettesArray;
            }
        }
    }
    return [];
}

if (!empty($query)) {
    $palettes = searchAIPalettes($query);
}

function isPaletteSaved($conn, $user_id, $colors) {
    // Kita cek apakah ada palet dengan kombinasi 5 warna yang sama untuk user ini
    $query = "SELECT id FROM palettes WHERE user_id = ? 
              AND color1 = ? AND color2 = ? AND color3 = ? AND color4 = ? AND color5 = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssss", $user_id, $colors[0], $colors[1], $colors[2], $colors[3], $colors[4]);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0; // Mengembalikan true jika sudah disimpan
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Spectra</title>
    <link rel="stylesheet" href="css/Style.css?v=<?php echo filemtime('css/Style.css'); ?>">
</head>
<body>

<nav class="navbar">
        <div class="nav-left">
            <img class="logo" src="assets/SpectraLogo.svg" alt="Spectra">
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
            <section class="hero">
                <div class="hero-text">
                    <h1>Your Search<?php echo $query ? ': <em style="font-style:italic;opacity:0.7;">' . htmlspecialchars($query) . '</em>' : ''; ?></h1>
                </div>
            </section>

            <section class="palette-grid">
                <?php if (!empty($palettes) && is_array($palettes)): ?>
                    <?php foreach ($palettes as $paletteData): ?>
                        <?php 
                            // 1. Pastikan data valid dulu
                            if (!is_array($paletteData) || !isset($paletteData['colors'])) continue; 
                            
                            // 2. AMBIL WARNA DULU (Ini yang tadi terbalik urutannya)
                            $colors = $paletteData['colors'];
                            $tags = isset($paletteData['tags']) && is_array($paletteData['tags']) ? $paletteData['tags'] : ['Spectra']; 

                            // 3. BARU CEK STATUS KE DATABASE
                            $savedClass = '';
                            $isSaved = false;
                            if (isPaletteSaved($conn, $_SESSION['user_id'], $colors)) {
                                $savedClass = 'is-saved';
                                $isSaved = true;
                            }

                            // Siapkan query dan payload
                            $colorQuery = http_build_query(['c' => $colors, 't' => $tags]);
                            $savePayload = htmlspecialchars(json_encode(['colors' => $colors, 'tags' => $tags]), ENT_QUOTES); 
                        ?>
                        <?php 
                            if (!is_array($paletteData) || !isset($paletteData['colors'])) continue;
                            
                            $colors = $paletteData['colors'];
                            $tags = isset($paletteData['tags']) && is_array($paletteData['tags']) ? $paletteData['tags'] : []; 
                            
                            // ==========================================
                            // LOGIKA "PAKSA TAG" Sesuai Kata Kunci
                            // ==========================================
                            $keywordMatch = false;
                            foreach ($tags as $t) {
                                if (strtolower(trim($t)) === strtolower(trim($query))) {
                                    $keywordMatch = true;
                                    break;
                                }
                            }

                            // Jika AI "lupa" masukin tag utama, kita paksa taruh di paling depan!
                            if (!$keywordMatch && !empty($query)) {
                                array_unshift($tags, ucwords(strtolower($query))); 
                                // Kalau tagnya kepanjangan (jadi 4), kita buang yang paling belakang biar tetap 3
                                if (count($tags) > 3) {
                                    array_pop($tags);
                                }
                            }
                            // ==========================================

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
                    <p style="text-align:center;width:100%;color:#888;">
                        <?php echo $query ? 'Mencari palet untuk "' . htmlspecialchars($query) . '"...' : 'Ketik sesuatu di kolom pencarian di atas.'; ?>
                    </p>
                <?php endif; ?>
            </section>

            <?php if (!empty($palettes)): ?>
            <div class="generate-btn-container">
                <a href="search.php?q=<?php echo urlencode($query); ?>" class="btn-generate">
                    <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                    Regenerate "<?php echo htmlspecialchars($query); ?>"
                </a>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profileBtn = document.getElementById('profile-btn');
            const profileDropdown = document.getElementById('profile-dropdown');
            profileBtn.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            document.addEventListener('click', (e) => {
                if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target))
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
                btn.addEventListener('click', function() {
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
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' || data.status === 'already_saved') {
                            this.classList.add('is-saved');
                            this.dataset.saved = 'true';
                        } else {
                            this.dataset.sending = 'false';
                            alert('Gagal simpan: ' + (data.message || 'Coba login ulang'));
                        }
                    })
                    .catch(() => { this.dataset.sending = 'false'; });
                });
            });
        });
    </script>
</body>
</html>