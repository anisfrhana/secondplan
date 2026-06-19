var socialFields = ['social_instagram', 'social_facebook', 'social_tiktok', 'social_youtube', 'social_whatsapp'];
var mediaFields = ['spotify_embed_url', 'youtube_embed_url'];

function loadSocialSettings() {
    var formData = new FormData();
    formData.append('csrf', document.querySelector('#socialForm input[name="csrf"]').value);
    formData.append('action', 'load_social');

    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        socialFields.forEach(function(key) {
            var el = document.getElementById(key);
            if (el && data[key]) {
                el.value = data[key];
            }
        });
    })
    .catch(function() {
        showToast('Failed to load social media settings', 'error');
    });
}

function loadMediaSettings() {
    var formData = new FormData();
    formData.append('csrf', document.querySelector('#mediaForm input[name="csrf"]').value);
    formData.append('action', 'load_media');

    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        mediaFields.forEach(function(key) {
            var el = document.getElementById(key);
            if (el && data[key]) {
                el.value = data[key];
            }
        });
    })
    .catch(function() {
        showToast('Failed to load media settings', 'error');
    });
}

document.getElementById('socialForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('action', 'save_social');

    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Social media settings saved', 'success');
        } else {
            showToast('Failed to save settings', 'error');
        }
    })
    .catch(function() {
        showToast('Failed to save social media settings', 'error');
    });
});

document.getElementById('mediaForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('action', 'save_media');

    fetch('settings.php', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Media settings saved', 'success');
        } else {
            showToast('Failed to save settings', 'error');
        }
    })
    .catch(function() {
        showToast('Failed to save media settings', 'error');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadSocialSettings();
    loadMediaSettings();
});
