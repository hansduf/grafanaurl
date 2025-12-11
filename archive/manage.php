<?php
// API endpoint for channel management (JSON only)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/ChannelModel.php';
require_once __DIR__ . '/UploadController.php';

$config = include __DIR__ . '/../config.php';
$model = new ChannelModel($config);
$result = ['message' => '', 'type' => 'info'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If a file is uploaded, delegate to UploadController
    if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $u = new UploadController($config);
        $msg = $u->handleUpload();
        $result = ['message' => $msg, 'type' => 'success'];
    } else {
        $action = $_POST['action'] ?? '';
        $message = '';
        $messageType = 'info';

        if ($action === 'create') {
            $name = strtolower(trim($_POST['name'] ?? ''));
            $desc = trim($_POST['desc'] ?? '');
            if (!$model->sanitizeChannel($name)) {
                $message = 'Invalid channel name.';
                $messageType = 'error';
            } elseif ($model->getChannel($name)) {
                $message = 'Channel already exists.';
                $messageType = 'error';
            } else {
                if ($model->createChannel($name, $desc)) {
                    $message = 'Channel created.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to create channel.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'update') {
            $nameOld = strtolower(trim($_POST['name_old'] ?? ''));
            $name = strtolower(trim($_POST['name'] ?? ''));
            $desc = trim($_POST['desc'] ?? '');
            if (!$model->getChannel($nameOld)) {
                $message = 'Channel not found.';
                $messageType = 'error';
            } elseif (!$model->sanitizeChannel($name)) {
                $message = 'Invalid new name.';
                $messageType = 'error';
            } elseif ($name !== $nameOld && $model->getChannel($name)) {
                $message = 'New name exists.';
                $messageType = 'error';
            } else {
                if ($model->updateChannel($nameOld, $name, $desc)) {
                    $message = 'Channel updated.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update.';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete') {
            $name = strtolower(trim($_POST['name'] ?? ''));
            if ($model->deleteChannel($name)) {
                $message = 'Channel deleted.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete.';
                $messageType = 'error';
            }
        }

        $result = ['message' => $message, 'type' => $messageType];
    }
} else {
    http_response_code(405);
    $result = ['message' => 'Method not allowed', 'type' => 'error'];
}

header('Content-Type: application/json');
echo json_encode($result);
?>
