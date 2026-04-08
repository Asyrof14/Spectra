<?php
require_once 'config.php';

// 1. CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Login diperlukan']);
        exit;
    }
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ==========================================
// 2A. LOGIKA DELETE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $palette_id = intval($_POST['palette_id']);

    $check = $conn->prepare("SELECT id FROM palettes WHERE id = ? AND user_id = ?");
    $check->bind_param("ii", $palette_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $conn->query("DELETE FROM palette_tags WHERE palette_id = $palette_id");
        $del = $conn->prepare("DELETE FROM palettes WHERE id = ? AND user_id = ?");
        $del->bind_param("ii", $palette_id, $user_id);
        $del->execute();
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ditemukan']);
    }
    exit;
}

// ==========================================
// 2B. LOGIKA SAVE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $colors = $_POST['colors'];
    $raw_tags = isset($_POST['tags']) ? $_POST['tags'] : ['AI Generated'];

    // Jika user sudah punya palette dengan 5 warna yang sama persis, tolak
    $dupCheck = $conn->prepare(
        "SELECT id FROM palettes 
         WHERE user_id = ? 
           AND color1 = ? AND color2 = ? AND color3 = ? AND color4 = ? AND color5 = ?
         LIMIT 1"
    );
    $dupCheck->bind_param("isssss", $user_id, $colors[0], $colors[1], $colors[2], $colors[3], $colors[4]);
    $dupCheck->execute();
    $dupCheck->store_result();

    if ($dupCheck->num_rows > 0) {
        // Sudah ada — kembalikan 'already_saved' supaya JS bisa kunci tombol
        echo json_encode(['status' => 'already_saved']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO palettes (user_id, color1, color2, color3, color4, color5) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $colors[0], $colors[1], $colors[2], $colors[3], $colors[4]);

    if ($stmt->execute()) {
        $palette_id = $conn->insert_id;

        foreach ($raw_tags as $tag_name) {
            $tag_name = trim($tag_name);
            $stmt_tag = $conn->prepare("INSERT IGNORE INTO tags (tag_name) VALUES (?)");
            $stmt_tag->bind_param("s", $tag_name);
            $stmt_tag->execute();

            $res_tag = $conn->query("SELECT id FROM tags WHERE tag_name = '$tag_name'");
            $tag_data = $res_tag->fetch_assoc();
            $tag_id = $tag_data['id'];

            $conn->query("INSERT INTO palette_tags (palette_id, tag_id) VALUES ($palette_id, $tag_id)");
        }
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// ==========================================
// 3. LOGIKA TAMPIL
// ==========================================
$query = "SELECT p.*, GROUP_CONCAT(t.tag_name) as all_tags 
          FROM palettes p 
          LEFT JOIN palette_tags pt ON p.id = pt.palette_id
          LEFT JOIN tags t ON pt.tag_id = t.id
          WHERE p.user_id = ? 
          GROUP BY p.id 
          ORDER BY p.created_at DESC";
$stmt_fetch = $conn->prepare($query);
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$saved_palettes = $stmt_fetch->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collection - Spectra</title>
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
                    <h1>Your Collection</h1>
                </div>
            </section>

            <section class="palette-grid" id="palette-grid">
                <?php if (!empty($saved_palettes)): ?>
                    <?php foreach ($saved_palettes as $row): ?>
                        <?php
                            $colorQuery = http_build_query(['c' => [
                                $row['color1'], $row['color2'], $row['color3'],
                                $row['color4'], $row['color5']
                            ]]);
                        ?>
                        <div class="palette-card" data-id="<?php echo $row['id']; ?>">
                            <div class="colors">
                                <div class="color-stripe" style="background:<?php echo $row['color1'];?>;"><span class="hex-text"><?php echo $row['color1'];?></span></div>
                                <div class="color-stripe" style="background:<?php echo $row['color2'];?>;"><span class="hex-text"><?php echo $row['color2'];?></span></div>
                                <div class="color-stripe" style="background:<?php echo $row['color3'];?>;"><span class="hex-text"><?php echo $row['color3'];?></span></div>
                                <div class="color-stripe" style="background:<?php echo $row['color4'];?>;"><span class="hex-text"><?php echo $row['color4'];?></span></div>
                                <div class="color-stripe" style="background:<?php echo $row['color5'];?>;"><span class="hex-text"><?php echo $row['color5'];?></span></div>
                            </div>
                            <div class="card-footer">
                                <div class="action-btn liked heart-btn" title="Click to unlike, click again to remove" style="color: #ff4757;">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                </div>
                                <a class="action-btn" href="detail.php?<?php echo $colorQuery; ?>" title="View detail">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p id="empty-msg" style="text-align:center;width:100%;color:#888;">Belum ada palet yang disimpan.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const profileBtn = document.getElementById('profile-btn');
            const profileDropdown = document.getElementById('profile-dropdown');
            profileBtn.onclick = (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); };
            document.onclick = () => profileDropdown.classList.remove('show');

            document.querySelectorAll('.color-stripe').forEach(stripe => {
                stripe.onclick = () => {
                    const hex = stripe.querySelector('.hex-text').innerText;
                    navigator.clipboard.writeText(hex);
                    alert('Copied: ' + hex);
                };
            });

            document.querySelectorAll('.heart-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (this.dataset.sending === 'true') return;

                    const card = this.closest('.palette-card');
                    const paletteId = card.dataset.id;

                    if (this.classList.contains('liked')) {
                        this.classList.remove('liked');
                        this.classList.add('unliked');
                        this.style.color = '#555';
                        this.title = 'Click again to remove from collection';
                    } else {
                        this.dataset.sending = 'true';
                        this.style.opacity = '0.4';

                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('palette_id', paletteId);

                        fetch('collection.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                card.classList.add('removing');
                                card.addEventListener('animationend', () => {
                                    card.remove();
                                    const grid = document.getElementById('palette-grid');
                                    if (!grid.querySelector('.palette-card')) {
                                        const msg = document.createElement('p');
                                        msg.id = 'empty-msg';
                                        msg.style.cssText = 'text-align:center;width:100%;color:#888;';
                                        msg.textContent = 'Belum ada palet yang disimpan.';
                                        grid.appendChild(msg);
                                    }
                                }, { once: true });
                            } else {
                                alert('Gagal menghapus palet. Coba lagi.');
                                this.dataset.sending = 'false';
                                this.style.opacity = '1';
                                this.classList.remove('unliked');
                                this.classList.add('liked');
                                this.style.color = '#ff4757';
                            }
                        })
                        .catch(() => {
                            alert('Terjadi kesalahan jaringan.');
                            this.dataset.sending = 'false';
                            this.style.opacity = '1';
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>