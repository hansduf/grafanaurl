<?php
// Simple preview page for a channel. Reads channel metadata and renders media.
require_once __DIR__ . '/../models/ChannelModel.php';

$config = include __DIR__ . '/../config.php';
$model = new ChannelModel($config);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$name = '';
// support /views/preview.php/<name> or /views/preview.php?channel=<name>
if (isset($parts[1]) && $parts[1] === 'preview.php' && isset($parts[2])) {
    $name = $parts[2];
} elseif (!empty($_GET['channel'])) {
    $name = $_GET['channel'];
}

if (!$name) {
    http_response_code(400);
    echo 'Channel not specified.';
    exit;
}

$channel = $model->getChannel($name);
if (!$channel) {
    http_response_code(404);
    echo 'Channel not found.';
    exit;
}

?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Preview <?=htmlspecialchars($name)?></title>
  <link href="/src/output.css" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; }
    body { background: #000; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: auto; width: 100vw; height: 100vh; }
    #container { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #000; }
    img, video { max-width: 100%; max-height: 100%; width: auto; height: auto; display: block; }
    audio { width: 90%; max-width: 600px; }
    .fade-out { animation: fadeOut 0.3s ease-out; }
    .fade-in { animation: fadeIn 0.3s ease-in; }
    @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  </style>
</head>
<body>
  <div id="container">
    <!-- Media placeholder - will be filled by JavaScript -->
  </div>
  
  <script>
    const CHANNEL_NAME = '<?=$name?>';
    const API_BASE = '<?=$config['BASE_URL']?>';
    const POLLING_INTERVAL = 3000; // 3 seconds
    
    let currentMediaId = null;
    let isUpdating = false;
    
    // Initialize preview
    async function initPreview() {
      await updatePreview();
      // Start polling for changes
      setInterval(updatePreview, POLLING_INTERVAL);
    }
    
    // Fetch channel data and update media if changed
    async function updatePreview() {
      try {
        const response = await fetch(API_BASE + '/api.php?endpoint=channel/' + encodeURIComponent(CHANNEL_NAME));
        const data = await response.json();
        
        if (data.type === 'success' && data.data) {
          const channel = data.data;
          const newMediaId = channel.current_media_id;
          
          // Check if media changed
          if (newMediaId !== currentMediaId) {
            currentMediaId = newMediaId;
            renderMedia(channel);
          }
        }
      } catch (err) {
        console.error('Error updating preview:', err);
      }
    }
    
    // Render media with fade effect
    function renderMedia(channel) {
      if (isUpdating) return;
      isUpdating = true;
      
      const container = document.getElementById('container');
      const mediaId = channel.media_id || channel.current_media_id;
      const mime = channel.mime_type || '';
      
      if (!mediaId || !mime) {
        container.innerHTML = '<div style="text-align: center; color: #999;">No media for this channel</div>';
        isUpdating = false;
        return;
      }
      
      const mediaUrl = API_BASE + '/api.php?endpoint=media/' + mediaId + '/download';
      let html = '';
      
      if (mime.startsWith('image/')) {
        html = `<img src="${mediaUrl}" alt="Channel media">`;
      } else if (mime.startsWith('video/')) {
        html = `<video src="${mediaUrl}" autoplay loop playsinline controls style="pointer-events: auto;"></video>`;
      } else if (mime.startsWith('audio/')) {
        html = `<audio src="${mediaUrl}" autoplay controls></audio>`;
      } else {
        html = '<div style="text-align: center; color: #999;">Unsupported media type</div>';
      }
      
      // Fade effect
      container.classList.add('fade-out');
      setTimeout(() => {
        container.innerHTML = html;
        container.classList.remove('fade-out');
        container.classList.add('fade-in');
        setTimeout(() => container.classList.remove('fade-in'), 300);
        isUpdating = false;
      }, 300);
    }
    
    // Start on page load
    document.addEventListener('DOMContentLoaded', initPreview);
  </script>
</body>
</html>
