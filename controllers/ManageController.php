<?php
// API endpoint for channel and media management (JSON only)
require_once __DIR__ . '/../models/ChannelModel.php';

$config = include __DIR__ . '/../config.php';
$model = new ChannelModel($config);
$result = ['message' => '', 'type' => 'info'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message = '';
    $messageType = 'info';

    // ============ CHANNEL ACTIONS ============

    if ($action === 'create') {
        // Create new channel (with optional media upload)
        $name = strtolower(trim($_POST['name'] ?? ''));
        $desc = trim($_POST['desc'] ?? '');

        if (!$model->sanitizeChannel($name)) {
            $message = 'Invalid channel name: only letters, numbers, -, _ allowed.';
            $messageType = 'error';
        } elseif ($model->getChannel($name)) {
            $message = 'Channel already exists.';
            $messageType = 'error';
        } else {
            // First, create the channel
            if (!$model->createChannel($name, $desc)) {
                $message = 'Failed to create channel.';
                $messageType = 'error';
            } else {
                // Now handle media upload if file provided
                $mediaId = null;
                if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                    file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: HANDLING MEDIA UPLOAD\n", FILE_APPEND);
                    
                    $file = $_FILES['media'];
                    $mime = $file['type'];
                    
                    // Check file size
                    if ($file['size'] > $config['MAX_FILE_SIZE']) {
                        $message = 'File size exceeds ' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB limit. Channel created but media upload failed.';
                        $messageType = 'warning';
                        file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: FILE TOO LARGE - " . $file['size'] . " bytes\n", FILE_APPEND);
                    } elseif (!in_array($mime, $config['ALLOWED_MIME'])) {
                        $message = 'Invalid file type. Channel created but media upload failed.';
                        $messageType = 'warning';
                        file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: INVALID MIME TYPE - " . $mime . "\n", FILE_APPEND);
                    } else {
                        // Save to uploads/ (flat structure, no subfolders)
                        $uploadsDir = $config['UPLOAD_DIR'];
                        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

                        $filename = uniqid() . '_' . basename($file['name']);
                        $path = $uploadsDir . '/' . $filename;
                        file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: SAVING MEDIA TO " . $path . "\n", FILE_APPEND);

                        if (move_uploaded_file($file['tmp_name'], $path)) {
                            file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: MEDIA FILE SAVED\n", FILE_APPEND);
                            
                            // Insert into media table
                            $uploadResult = $model->uploadMedia($filename, $mime);
                            file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: DB INSERT RESULT - " . ($uploadResult ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
                            
                            if ($uploadResult) {
                                // Get the inserted media id
                                $media = $model->getMediaByFilename($filename);
                                if ($media) {
                                    $mediaId = $media['id'];
                                    // Link media to channel
                                    $model->setChannelMedia($name, $mediaId);
                                    $message = 'Channel created and media uploaded.';
                                    $messageType = 'success';
                                    file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: MEDIA LINKED - ID " . $mediaId . "\n", FILE_APPEND);
                                } else {
                                    $message = 'Channel created but media linking failed.';
                                    $messageType = 'warning';
                                }
                            } else {
                                @unlink($path); // Delete file if DB insert failed
                                $message = 'Channel created but media save to database failed.';
                                $messageType = 'warning';
                                file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: MEDIA DB SAVE FAILED\n", FILE_APPEND);
                            }
                        } else {
                            $message = 'Channel created but failed to save media file.';
                            $messageType = 'warning';
                            file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: MEDIA FILE SAVE FAILED\n", FILE_APPEND);
                        }
                    }
                } elseif (!empty($_POST['media_id'])) {
                    // Link existing media from library
                    $mediaId = intval($_POST['media_id']);
                    if ($model->getMedia($mediaId)) {
                        $model->setChannelMedia($name, $mediaId);
                        $message = 'Channel created and media linked.';
                        $messageType = 'success';
                        file_put_contents(__DIR__ . '/../debug.log', "CREATE_CHANNEL: EXISTING MEDIA LINKED - ID " . $mediaId . "\n", FILE_APPEND);
                    } else {
                        $message = 'Channel created but media not found.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Channel created.';
                    $messageType = 'success';
                }
            }
        }

    } elseif ($action === 'update') {
        // Update channel (description and optionally media)
        $nameOld = strtolower(trim($_POST['name_old'] ?? ''));
        $desc = trim($_POST['desc'] ?? '');

        if (!$model->getChannel($nameOld)) {
            $message = 'Channel not found.';
            $messageType = 'error';
        } else {
            // First update description
            if (!$model->updateChannel($nameOld, $desc)) {
                $message = 'Failed to update channel.';
                $messageType = 'error';
            } else {
                // Now handle media upload if file provided
                if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                    file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: HANDLING MEDIA UPLOAD\n", FILE_APPEND);
                    
                    $file = $_FILES['media'];
                    $mime = $file['type'];
                    
                    // Check file size
                    if ($file['size'] > $config['MAX_FILE_SIZE']) {
                        $message = 'File size exceeds ' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB limit. Channel updated but media upload failed.';
                        $messageType = 'warning';
                        file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: FILE TOO LARGE - " . $file['size'] . " bytes\n", FILE_APPEND);
                    } elseif (!in_array($mime, $config['ALLOWED_MIME'])) {
                        $message = 'Invalid file type. Channel updated but media upload failed.';
                        $messageType = 'warning';
                        file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: INVALID MIME TYPE - " . $mime . "\n", FILE_APPEND);
                    } else {
                        // Save to uploads/ (flat structure, no subfolders)
                        $uploadsDir = $config['UPLOAD_DIR'];
                        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

                        $filename = uniqid() . '_' . basename($file['name']);
                        $path = $uploadsDir . '/' . $filename;
                        file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: SAVING MEDIA TO " . $path . "\n", FILE_APPEND);

                        if (move_uploaded_file($file['tmp_name'], $path)) {
                            file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: MEDIA FILE SAVED\n", FILE_APPEND);
                            
                            // Insert into media table
                            $uploadResult = $model->uploadMedia($filename, $mime);
                            file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: DB INSERT RESULT - " . ($uploadResult ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
                            
                            if ($uploadResult) {
                                // Get the inserted media id
                                $media = $model->getMediaByFilename($filename);
                                if ($media) {
                                    // Link media to channel (replace old media)
                                    $model->setChannelMedia($nameOld, $media['id']);
                                    $message = 'Channel updated and media replaced.';
                                    $messageType = 'success';
                                    file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: MEDIA LINKED - ID " . $media['id'] . "\n", FILE_APPEND);
                                } else {
                                    $message = 'Channel updated but media linking failed.';
                                    $messageType = 'warning';
                                }
                            } else {
                                @unlink($path); // Delete file if DB insert failed
                                $message = 'Channel updated but media save to database failed.';
                                $messageType = 'warning';
                                file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: MEDIA DB SAVE FAILED\n", FILE_APPEND);
                            }
                        } else {
                            $message = 'Channel updated but failed to save media file.';
                            $messageType = 'warning';
                            file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: MEDIA FILE SAVE FAILED\n", FILE_APPEND);
                        }
                    }
                } elseif (!empty($_POST['media_id'])) {
                    // Link existing media from library
                    $mediaId = intval($_POST['media_id']);
                    if ($model->getMedia($mediaId)) {
                        $model->setChannelMedia($nameOld, $mediaId);
                        $message = 'Channel updated and media linked.';
                        $messageType = 'success';
                        file_put_contents(__DIR__ . '/../debug.log', "UPDATE_CHANNEL: EXISTING MEDIA LINKED - ID " . $mediaId . "\n", FILE_APPEND);
                    } else {
                        $message = 'Channel updated but media not found.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Channel updated.';
                    $messageType = 'success';
                }
            }
        }

    } elseif ($action === 'delete') {
        // Delete channel
        $name = strtolower(trim($_POST['name'] ?? ''));

        if (!$model->getChannel($name)) {
            $message = 'Channel not found.';
            $messageType = 'error';
        } else {
            if ($model->deleteChannel($name)) {
                $message = 'Channel deleted.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete channel.';
                $messageType = 'error';
            }
        }

    } elseif ($action === 'set_media') {
        // Link media to channel (set as current/active)
        $channelName = strtolower(trim($_POST['channel_name'] ?? $_POST['channel'] ?? ''));
        $mediaId = intval($_POST['media_id'] ?? 0);

        if (!$model->getChannel($channelName)) {
            $message = 'Channel not found.';
            $messageType = 'error';
        } elseif (!$model->getMedia($mediaId)) {
            $message = 'Media not found.';
            $messageType = 'error';
        } else {
            if ($model->setChannelMedia($channelName, $mediaId)) {
                $message = 'Media set for channel.';
                $messageType = 'success';
            } else {
                $message = 'Failed to set media.';
                $messageType = 'error';
            }
        }

    // ============ MEDIA ACTIONS ============

    } elseif ($action === 'upload_media') {
        // Upload media to library (simple upload to /uploads)
        file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: START\n", FILE_APPEND);
        
        if (empty($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            $message = 'No media file uploaded or upload error.';
            $messageType = 'error';
            file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: FILE ERROR - " . ($_FILES['media']['error'] ?? 'missing') . "\n", FILE_APPEND);
        } else {
            $file = $_FILES['media'];
            $mime = $file['type'];
            file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: FILE RECEIVED - " . $file['name'] . " (" . $mime . ")\n", FILE_APPEND);

            // Check file size
            if ($file['size'] > $config['MAX_FILE_SIZE']) {
                $message = 'File size exceeds ' . ($config['MAX_FILE_SIZE'] / 1024 / 1024) . 'MB limit.';
                $messageType = 'error';
                file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: FILE TOO LARGE - " . $file['size'] . " bytes\n", FILE_APPEND);
            } elseif (!in_array($mime, $config['ALLOWED_MIME'])) {
                $message = 'Invalid file type.';
                $messageType = 'error';
                file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: INVALID MIME TYPE - " . $mime . "\n", FILE_APPEND);
            } else {
                // Save to uploads/ (flat structure, no subfolders)
                $uploadsDir = $config['UPLOAD_DIR'];
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

                $filename = uniqid() . '_' . basename($file['name']);
                $path = $uploadsDir . '/' . $filename;
                file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: SAVING TO " . $path . "\n", FILE_APPEND);

                if (move_uploaded_file($file['tmp_name'], $path)) {
                    file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: FILE SAVED\n", FILE_APPEND);
                    
                    // Insert into media table
                    $uploadResult = $model->uploadMedia($filename, $mime);
                    file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: DB INSERT RESULT - " . ($uploadResult ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
                    
                    if ($uploadResult) {
                        // Optionally set to channel if provided
                        $channelName = strtolower(trim($_POST['channel'] ?? ''));
                        if (!empty($channelName)) {
                            $media = $model->getMediaByFilename($filename);
                            if ($media) {
                                $model->setChannelMedia($channelName, $media['id']);
                            }
                        }

                        $message = 'Media uploaded successfully.';
                        $messageType = 'success';
                        file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: SUCCESS\n", FILE_APPEND);
                    } else {
                        @unlink($path); // Delete file if DB insert failed
                        $message = 'File uploaded but failed to save to database.';
                        $messageType = 'error';
                        file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: DB SAVE FAILED\n", FILE_APPEND);
                    }
                } else {
                    $message = 'Failed to save media file.';
                    $messageType = 'error';
                    file_put_contents(__DIR__ . '/../debug.log', "UPLOAD_MEDIA: FILE SAVE FAILED\n", FILE_APPEND);
                }
            }
        }

    } elseif ($action === 'delete_media') {
        // Delete media from library (hard delete)
        $mediaId = intval($_POST['media_id'] ?? 0);

        if (!$model->getMedia($mediaId)) {
            $message = 'Media not found.';
            $messageType = 'error';
        } else {
            // Get media info before deleting
            $media = $model->getMedia($mediaId);

            // Delete file from filesystem
            $filePath = $config['UPLOAD_DIR'] . '/' . $media['filename'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // Delete from database
            if ($model->deleteMedia($mediaId)) {
                $message = 'Media deleted.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete media.';
                $messageType = 'error';
            }
        }

    } else {
        $message = 'Invalid action.';
        $messageType = 'error';
    }

    $result['message'] = $message;
    $result['type'] = $messageType;
}

header('Content-Type: application/json');
echo json_encode($result);
?>