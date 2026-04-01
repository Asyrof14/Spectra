<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// FUNGSI CEK STATUS PALET DI DATABASE
// ==========================================
function isPaletteSaved($conn, $user_id, $colors) {
    $query = "SELECT id FROM palettes WHERE user_id = ? 
              AND color1 = ? AND color2 = ? AND color3 = ? AND color4 = ? AND color5 = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssss", $user_id, $colors[0], $colors[1], $colors[2], $colors[3], $colors[4]);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// 1. Ambil Warna dari URL
$colors = [];
if (isset($_GET['c']) && is_array($_GET['c'])) {
    foreach ($_GET['c'] as $hex) {
        $hex = trim($hex);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            $colors[] = strtoupper($hex);
        }
    }
}
if (empty($colors)) {
    $colors = ['#CCCCCC', '#AAAAAA', '#888888', '#666666', '#444444'];
}

// 2. Ambil Tag dari URL
$tags = [];
if (isset($_GET['t']) && is_array($_GET['t'])) {
    foreach ($_GET['t'] as $t) {
        $tags[] = htmlspecialchars(trim($t));
    }
}
if (empty($tags)) {
    $tags = ['Spectra', 'Design'];
}

function hexToRgb(string $hex): string {
    $hex = ltrim($hex, '#');
    return "rgb(" . hexdec(substr($hex,0,2)) . ", " . hexdec(substr($hex,2,2)) . ", " . hexdec(substr($hex,4,2)) . ")";
}

// Payload untuk tombol save utama
$savePayload = htmlspecialchars(json_encode(['colors' => $colors, 'tags' => $tags]), ENT_QUOTES);

// CEK STATUS PALET UTAMA (HERO)
$mainSavedClass = '';
$mainIsSaved = false;
if (isPaletteSaved($conn, $_SESSION['user_id'], $colors)) {
    $mainSavedClass = 'is-saved';
    $mainIsSaved = true;
}

// 3. Logika Palet Serupa (Sistem Skoring Tag Hibrida)
$similarPalettes = [];
if (!empty($_SESSION['ai_palettes']) && is_array($_SESSION['ai_palettes'])) {
    $pool = $_SESSION['ai_palettes'];
    $currentTagsLower = array_map('strtolower', $tags);
    
    $matchedPalettes = [];
    $unmatchedPalettes = [];

    foreach ($pool as $paletteData) {
        if (!is_array($paletteData) || !isset($paletteData['colors'])) continue;
        
        $otherColors = $paletteData['colors'];
        $otherTags = isset($paletteData['tags']) ? $paletteData['tags'] : ['Spectra'];
        
        $otherUpper = array_map('strtoupper', $otherColors);
        if ($otherUpper === $colors) continue;

        $otherTagsLower = array_map('strtolower', $otherTags);
        $matchCount = count(array_intersect($currentTagsLower, $otherTagsLower));

        $item = [
            'colors' => $otherColors,
            'tags'   => $otherTags,
            'score'  => $matchCount
        ];

        // Pisahkan yang mirip dan yang tidak mirip
        if ($matchCount > 0) {
            $matchedPalettes[] = $item;
        } else {
            $unmatchedPalettes[] = $item;
        }
    }

    // Acak masing-masing grup agar bervariasi tiap refresh
    shuffle($matchedPalettes);
    shuffle($unmatchedPalettes);

    // Urutkan yang mirip berdasarkan skor tertinggi
    usort($matchedPalettes, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // Gabungkan: Yang mirip di depan, sisanya (tidak mirip) untuk menuhin kuota 10 palet
    $combinedList = array_merge($matchedPalettes, $unmatchedPalettes);
    $similarPalettes = array_slice($combinedList, 0, 10);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palette Detail - Spectra</title>
    <link rel="stylesheet" href="css/Style.css?v=<?php echo filemtime('css/Style.css'); ?>">
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <a><img class="logo" src="assets/SpectraLogo.svg" alt="Spectra"></a>
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

        <main class="content content-detail-container" id="scroll-content">

            <div class="palette-hero">
                <div class="main-preview-box">
                    <?php foreach ($colors as $hex): ?>
                        <div class="palette-column" style="background-color:<?php echo $hex;?>;"></div>
                    <?php endforeach; ?>
                </div>

                <div class="palette-color-info-list">
                    <?php foreach ($colors as $i => $hex): ?>
                        <div class="color-detail-item <?php echo $i === 0 ? 'active' : ''; ?>" data-hex="<?php echo $hex; ?>">
                            <div class="color-preview-circle" style="background-color:<?php echo $hex;?>;"></div>
                            <div class="color-value-text">
                                <span>
                                    <?php echo hexToRgb($hex); ?>
                                    <svg class="copy-icon" style="width:14px;height:14px;fill:currentColor;margin-left:8px;cursor:pointer;" viewBox="0 0 24 24" title="Copy hex">
                                        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                    </svg>
                                </span>
                                <strong><?php echo $hex; ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="palette-actions-wrapper">
                <div class="palette-actions">
                    <button class="btn-spectra btn-save-detail <?php echo $mainSavedClass; ?>" 
                            data-payload='<?php echo $savePayload; ?>'
                            data-saved="<?php echo $mainIsSaved ? 'true' : 'false'; ?>">
                        <svg style="width:18px;height:18px;fill:currentColor;" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <?php echo $mainIsSaved ? 'Saved!' : 'Save'; ?>
                    </button>
                    <button class="btn-spectra btn-copy-link">
                        <svg style="width:18px;height:18px;fill:currentColor;" viewBox="0 0 24 24">
                            <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
                        </svg>
                        Copy Link
                    </button>
                    <a href="javascript:history.back()" class="btn-spectra">
                        <svg style="width:18px;height:18px;fill:currentColor;" viewBox="0 0 24 24">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                        </svg>
                        Back
                    </a>
                </div>

                <div class="tags-container">
                    <span>Tags:</span>
                    <?php foreach ($tags as $tag): ?>
                        <a href="search.php?q=<?php echo urlencode($tag); ?>" class="tag-pill">
                            <?php echo htmlspecialchars($tag); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <section class="more-similar-section">
                <h2 class="section-title">More similar palettes</h2>
                <div class="palette-grid">
                    <?php foreach ($similarPalettes as $otherPalette): ?>
                        <?php 
                            $simColors = $otherPalette['colors'];
                            $simTags = $otherPalette['tags'];
                            $simQuery = http_build_query(['c' => $simColors, 't' => $simTags]); 
                            $simPayload = htmlspecialchars(json_encode(['colors' => $simColors, 'tags' => $simTags]), ENT_QUOTES);
                            
                            // CEK STATUS PALET SERUPA DI DATABASE
                            $simSavedClass = '';
                            $simIsSaved = false;
                            if (isPaletteSaved($conn, $_SESSION['user_id'], $simColors)) {
                                $simSavedClass = 'is-saved';
                                $simIsSaved = true;
                            }
                        ?>
                        <div class="palette-card">
                            <div class="colors">
                                <?php foreach ($simColors as $hex): ?>
                                    <div class="color-stripe" style="background:<?php echo trim($hex);?>;">
                                        <span class="hex-text"><?php echo strtoupper(trim($hex)); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer palette-card-footer-extended">
                                <div class="footer-actions-row">
                                    <div class="action-btn save-btn <?php echo $simSavedClass; ?>" 
                                         data-payload='<?php echo $simPayload; ?>'
                                         data-saved="<?php echo $simIsSaved ? 'true' : 'false'; ?>">
                                        <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                    </div>
                                    <a class="action-btn" href="detail.php?<?php echo $simQuery; ?>" title="View detail">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
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

            document.querySelectorAll('.color-detail-item, .color-stripe').forEach(el => {
                el.addEventListener('click', (e) => {
                    // Mencegah trigger ganda jika klik icon copy di dalam detail item
                    if (e.target.closest('.copy-icon') && el.classList.contains('color-detail-item')) return;
                    
                    const hex = el.dataset.hex || el.querySelector('.hex-text').innerText;
                    navigator.clipboard.writeText(hex).then(() => {
                        const textEl = el.querySelector('strong') || el.querySelector('.hex-text');
                        const orig = textEl.innerText;
                        textEl.innerText = 'Copied!';
                        setTimeout(() => { textEl.innerText = orig; }, 1000);
                    });
                });
            });
            
            // Khusus ikon copy bulat kecil
            document.querySelectorAll('.copy-icon').forEach(icon => {
                icon.addEventListener('click', (e) => {
                    const item = icon.closest('.color-detail-item');
                    const hex = item.dataset.hex;
                    navigator.clipboard.writeText(hex).then(() => {
                        const strong = item.querySelector('strong');
                        const orig = strong.innerText;
                        strong.innerText = 'Copied!';
                        setTimeout(() => { strong.innerText = orig; }, 1000);
                    });
                });
            });

            function doSave(btn, payload, onSuccess) {
                if (btn.dataset.saved === 'true' || btn.dataset.sending === 'true') return;
                btn.dataset.sending = 'true';

                const formData = new URLSearchParams();
                formData.append('action', 'save');
                payload.colors.forEach(c => formData.append('colors[]', c));
                payload.tags.forEach(t => formData.append('tags[]', t)); 

                fetch('collection.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success' || data.status === 'already_saved') {
                        btn.dataset.saved = 'true';
                        btn.classList.add('is-saved'); // Aktifkan class CSS merah
                        onSuccess();
                    } else {
                        btn.dataset.sending = 'false';
                        alert('Gagal simpan: ' + (data.message || 'Coba login ulang'));
                    }
                })
                .catch(() => { btn.dataset.sending = 'false'; });
            }

            const saveBtn = document.querySelector('.btn-save-detail');
            if (saveBtn) {
                saveBtn.addEventListener('click', function () {
                    doSave(this, JSON.parse(this.dataset.payload), () => {
                        this.innerHTML = this.innerHTML.replace('Save', 'Saved!');
                    });
                });
            }

            document.querySelectorAll('.save-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    doSave(this, JSON.parse(this.dataset.payload), () => {
                        // Class is-saved sudah ditambahkan otomatis di dalam doSave
                    });
                });
            });

            document.querySelector('.btn-copy-link')?.addEventListener('click', function () {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    const orig = this.innerHTML;
                    this.innerHTML = this.innerHTML.replace('Copy Link', 'Copied!');
                    setTimeout(() => { this.innerHTML = orig; }, 1500);
                });
            });
        });
    </script>
</body>
</html>