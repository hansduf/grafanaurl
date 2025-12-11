<?php
require_once __DIR__ . '/../models/ChannelModel.php';

$config = include __DIR__ . '/../config.php';
$model = new ChannelModel($config);

$channel = $_POST['channel'] ?? ''; // Optional - for assigning to channel
$message = '';
$success = false;
$channelData = null;

// If channel is specified, verify it exists
if ($channel && !$model->getChannel($channel)) {
    $message = 'Invalid channel.';
} else {
    if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Upload failed.';
    } else {
        $file = $_FILES['media'];
        $mime = $file['type'];
        if (!in_array($mime, $config['ALLOWED_MIME'])) {
            $message = 'Invalid file type.';
        } else {
            // If channel specified, save to channel-specific folder; otherwise save to general uploads
            $dir = $channel 
                ? $config['UPLOAD_DIR'] . '/' . $channel 
                : $config['UPLOAD_DIR'] . '/library';
            
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $message = 'Failed to create upload directory.';
                    $dir = null;
                }
            }
            
            if ($dir) {
                $filename = uniqid() . '_' . basename($file['name']);
                $path = $dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $path)) {
                    $relativePath = $channel 
                        ? 'uploads/' . $channel . '/' . $filename 
                        : 'uploads/library/' . $filename;
                    
                    // Upload media to database and get its ID
                    $mediaId = $model->uploadMedia($relativePath, $mime);
                    if ($mediaId !== false) {
                        // If channel specified, set this media as current for the channel
                        if ($channel) {
                            $model->setChannelMedia($channel, $mediaId);
                            // Get updated channel data to return to frontend
                            $channelData = $model->getChannel($channel);
                        } else {
                            // For library upload, just return the media ID
                            $channelData = ['media_id' => $mediaId];
                        }
                        
                        $message = 'Media uploaded successfully.';
                        $success = true;
                    } else {
                        $message = 'Failed to save media to database.';
                    }
                } else {
                    $message = 'Failed to save file.';
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'message' => $message,
    'type' => $success ? 'success' : 'error',
    'success' => $success,
    'channel' => $channelData
]);
?>
]);
?>