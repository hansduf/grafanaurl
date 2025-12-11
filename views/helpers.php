<?php
// views/helpers.php - URL generation helpers

$config = include __DIR__ . '/../config.php';

/**
 * Get base URL dynamically
 */
function getBaseUrl() {
    global $config;
    return $config['BASE_URL'];
}

/**
 * Generate API endpoint URL
 */
function getApiUrl($endpoint = '') {
    return getBaseUrl() . '/api.php' . ($endpoint ? '?endpoint=' . $endpoint : '');
}

/**
 * Generate controller URL
 */
function getControllerUrl($controller) {
    return getBaseUrl() . '/controllers/' . $controller;
}

/**
 * Generate media download URL
 */
function getMediaUrl($mediaId) {
    return getApiUrl('media/' . $mediaId . '/download');
}
