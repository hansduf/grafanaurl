<?php
require_once __DIR__ . '/auth/middleware.php';

$config = include __DIR__ . '/config.php';
require_once __DIR__ . '/models/ChannelModel.php';

$model = new ChannelModel($config);

// Handle POST requests (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $name = strtolower(trim($_POST['name'] ?? ''));
        
        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Channel name required']);
            exit;
        }
        
        if (!$model->getChannel($name)) {
            http_response_code(404);
            echo json_encode(['error' => 'Channel not found']);
            exit;
        }
        
        if ($model->deleteChannel($name)) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Channel deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete channel']);
        }
        exit;
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$endpoint = $_GET['endpoint'] ?? '';

if ($endpoint === 'channels') {
    header('Content-Type: application/json');
    $channels = $model->getAllChannels();
    
    // Data already includes media details from LEFT JOIN, no need to loop and fetch again!
    echo json_encode([
        'type' => 'success',
        'data' => $channels ?: []
    ]);
    exit;

} elseif (preg_match('#^channel/(.+)$#', $endpoint, $matches)) {
    $channelName = urldecode($matches[1]);
    $channel = $model->getChannel($channelName);
    
    header('Content-Type: application/json');
    
    if ($channel) {
        // Enrich with current media details
        if (!empty($channel['current_media_id'])) {
            $media = $model->getMedia($channel['current_media_id']);
            if ($media) {
                $channel['media_id'] = $media['id'];
                $channel['filename'] = $media['filename'];
                $channel['mime_type'] = $media['mime_type'];
            }
        }
        echo json_encode([
            'type' => 'success',
            'data' => $channel
        ]);
    } else {
        // Return 200 OK with null data instead of 404 to prevent polling spam
        echo json_encode([
            'type' => 'error',
            'message' => 'Channel not found',
            'data' => null
        ]);
    }
    exit;

} elseif ($endpoint === 'media') {
    // Get all media (library) with pagination
    header('Content-Type: application/json');
    $limit = max(1, min(100, intval($_GET['limit'] ?? 50))); // Limit 1-100, default 50
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    $media = $model->getAllMedia($limit, $offset);
    $total = $model->getMediaCount();
    
    echo json_encode([
        'type' => 'success',
        'data' => $media ?: [],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total
        ]
    ]);
    exit;

} elseif (preg_match('#^media/(\d+)$#', $endpoint, $matches)) {
    // Get single media
    $mediaId = intval($matches[1]);
    $media = $model->getMedia($mediaId);
    
    if ($media) {
        // Check which channels use this media
        $channels = $model->getChannelsUsingMedia($mediaId);
        $media['used_by_channels'] = $channels;
        
        header('Content-Type: application/json');
        echo json_encode($media);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Media not found']);
    }
    exit;

} elseif (preg_match('#^media/(\d+)/download$#', $endpoint, $matches)) {
    // Download media file
    $mediaId = intval($matches[1]);
    $media = $model->getMedia($mediaId);
    
    if ($media && file_exists($config['UPLOAD_DIR'] . '/' . $media['filename'])) {
        $filePath = $config['UPLOAD_DIR'] . '/' . $media['filename'];
        
        // Set headers
        header('Content-Type: ' . ($media['mime_type'] ?? 'application/octet-stream'));
        
        // For download (with attachment)
        if (!empty($_GET['download'])) {
            header('Content-Disposition: attachment; filename="' . basename($media['filename']) . '"');
        }
        
        // Send file content
        readfile($filePath);
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Media file not found']);
        exit;
    }

    exit;

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid API endpoint']);
}
?>