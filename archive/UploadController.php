<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/ChannelModel.php';

$config = include __DIR__ . '/../config.php';
$model = new ChannelModel($config);

$channel = $_POST['channel'] ?? '';
if (!$channel || !$model->getChannel($channel)) {
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
            $dir = $config['UPLOAD_DIR'] . '/' . $channel;
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = uniqid() . '_' . basename($file['name']);
            $path = $dir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $relativePath = 'uploads/' . $channel . '/' . $filename;
                $model->updateMedia($channel, $relativePath, $mime);
                $message = 'Media uploaded.';
            } else {
                $message = 'Failed to save file.';
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['message' => $message, 'type' => 'success']);
?>