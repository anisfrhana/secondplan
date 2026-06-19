<?php
require_once __DIR__ . '/../config/bootstrap.php';
requireRole([ROLE_ADMIN]);

$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $settings = [
            'site_name' => sanitize($_POST['site_name'] ?? ''),
            'site_email' => sanitize($_POST['site_email'] ?? ''),
            'timezone' => sanitize($_POST['timezone'] ?? ''),
            'currency' => sanitize($_POST['currency'] ?? ''),
            'enable_registrations' => isset($_POST['enable_registrations']) ? '1' : '0',
        ];

        foreach ($settings as $key => $value) {
            setSetting($key, $value);
        }

        logActivity(getUserId(), 'settings_updated', $settings);
        setFlash('success', 'Settings updated successfully.');
        redirect('/admin/settings.php');
    }

    if ($action === 'save_media') {
        header('Content-Type: application/json');
        $mediaKeys = ['spotify_embed_url', 'youtube_embed_url'];
        foreach ($mediaKeys as $key) {
            setSetting($key, sanitize($_POST[$key] ?? ''));
        }
        logActivity(getUserId(), 'media_settings_updated', []);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'load_media') {
        header('Content-Type: application/json');
        $mediaKeys = ['spotify_embed_url', 'youtube_embed_url'];
        $result = [];
        foreach ($mediaKeys as $key) {
            $result[$key] = getSetting($key, '');
        }
        echo json_encode($result);
        exit;
    }

    if ($action === 'save_social') {
        header('Content-Type: application/json');
        $socialKeys = ['social_instagram', 'social_facebook', 'social_tiktok', 'social_youtube', 'social_whatsapp'];
        foreach ($socialKeys as $key) {
            setSetting($key, sanitize($_POST[$key] ?? ''));
        }
        logActivity(getUserId(), 'social_media_updated', []);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'load_social') {
        header('Content-Type: application/json');
        $socialKeys = ['social_instagram', 'social_facebook', 'social_tiktok', 'social_youtube', 'social_whatsapp'];
        $result = [];
        foreach ($socialKeys as $key) {
            $result[$key] = getSetting($key, '');
        }
        echo json_encode($result);
        exit;
    }
}

$stmt = $pdo->query("SELECT `key`, `value`, `description` FROM settings ORDER BY `key`");
$allSettings = [];
while ($row = $stmt->fetch()) {
    $allSettings[$row['key']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - SecondPlan</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h2>Settings</h2>
                <div class="subtitle">Manage application settings</div>
            </div>
            <div class="header-actions">
                <button class="notification-btn"></button>
                <div class="user-avatar">A</div>
            </div>
        </div>

        <div class="content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" style="padding:12px;margin-bottom:16px;border-radius:8px;background:<?= $flash['type'] === 'success' ? 'rgba(34,197,94,0.1)' : 'rgba(239,68,68,0.1)' ?>;border:1px solid <?= $flash['type'] === 'success' ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)' ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <h3>General Settings</h3>
                <form method="POST" style="max-width:600px;margin-top:16px;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update">

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Site Name</label>
                        <input type="text" name="site_name" value="<?= e($allSettings['site_name']['value'] ?? '') ?>"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Site Email</label>
                        <input type="email" name="site_email" value="<?= e($allSettings['site_email']['value'] ?? '') ?>"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Timezone</label>
                        <input type="text" name="timezone" value="<?= e($allSettings['timezone']['value'] ?? 'Asia/Kuala_Lumpur') ?>"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Currency</label>
                        <input type="text" name="currency" value="<?= e($allSettings['currency']['value'] ?? 'MYR') ?>" maxlength="3"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:24px;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:#6b7280;cursor:pointer;">
                            <input type="checkbox" name="enable_registrations" value="1"
                                   <?= ($allSettings['enable_registrations']['value'] ?? '1') === '1' ? 'checked' : '' ?>>
                            Allow new user registrations
                        </label>
                    </div>

                    <button type="submit" class="btn-primary" style="padding:12px 24px;font-size:15px;">
                        <i class="bi bi-floppy btn-icon"></i> Save Settings
                    </button>
                </form>
            </div>

            <div class="section" style="margin-top:24px;">
                <h3>Media & Embeds</h3>
                <form id="mediaForm" style="max-width:600px;margin-top:16px;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Spotify Embed URL</label>
                        <input type="url" name="spotify_embed_url" id="spotify_embed_url" placeholder="https://open.spotify.com/embed/..."
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                        <small style="color:#9ca3af;font-size:12px;">Paste the embed URL from Spotify (Share > Embed > Copy URL)</small>
                    </div>

                    <div style="margin-bottom:24px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">YouTube Embed URL</label>
                        <input type="url" name="youtube_embed_url" id="youtube_embed_url" placeholder="https://www.youtube.com/embed/..."
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                        <small style="color:#9ca3af;font-size:12px;">Paste the embed URL from YouTube (Share > Embed > Copy URL)</small>
                    </div>

                    <button type="submit" class="btn-primary" style="padding:12px 24px;font-size:15px;">
                        <i class="bi bi-floppy btn-icon"></i> Save Media Settings
                    </button>
                </form>
            </div>

            <div class="section" style="margin-top:24px;">
                <h3>Social Media</h3>
                <form id="socialForm" style="max-width:600px;margin-top:16px;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Instagram URL</label>
                        <input type="url" name="social_instagram" id="social_instagram" placeholder="https://instagram.com/yourpage"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">Facebook URL</label>
                        <input type="url" name="social_facebook" id="social_facebook" placeholder="https://facebook.com/yourpage"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">TikTok URL</label>
                        <input type="url" name="social_tiktok" id="social_tiktok" placeholder="https://tiktok.com/@yourpage"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:16px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">YouTube URL</label>
                        <input type="url" name="social_youtube" id="social_youtube" placeholder="https://youtube.com/@yourchannel"
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <div style="margin-bottom:24px;">
                        <label style="display:block;margin-bottom:6px;font-size:14px;color:#6b7280;">WhatsApp URL</label>
                        <input type="url" name="social_whatsapp" id="social_whatsapp" placeholder="https://api.whatsapp.com/send?phone=..."
                               style="width:100%;padding:10px 14px;background:#f8f5f0;border:1px solid #e0d6c8;border-radius:8px;color:#1a1a1a;font-size:15px;">
                    </div>

                    <button type="submit" class="btn-primary" style="padding:12px 24px;font-size:15px;">
                        <i class="bi bi-floppy btn-icon"></i> Save Social Media
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/common.js"></script>
<script src="../assets/js/notifications.js"></script>
<script src="assets/js/settings.js"></script>
</body>
</html>
