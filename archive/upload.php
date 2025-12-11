<?php
// uploads API wrapper - JSON only
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controllers/UploadController.php';

$config = include __DIR__ . '/config.php';
$controller = new UploadController($config);
$message = $controller->handleUpload();

header('Content-Type: application/json');
echo json_encode(['message' => $message, 'type' => 'success']);
?>
