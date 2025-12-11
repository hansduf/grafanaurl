<?php
// views/index.php - Authentication required
require_once __DIR__ . '/../auth/middleware.php';

$currentUser = getCurrentUser();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Media Player Console</title>
  <link href="/src/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
  <!-- Header -->
  <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
    <div class="container mx-auto px-4 md:px-6 lg:px-8 py-4 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-sky-600 rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Media Console</h1>
          <p class="text-xs text-gray-500">Channel Management & Monitoring</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <button id="createChannelBtn" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-sky-500 to-sky-600 text-white font-semibold rounded-lg hover:shadow-lg hover:from-sky-600 hover:to-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 transition-all duration-200 shadow-md">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Create Channel
        </button>

        <!-- User Menu -->
        <div class="relative user-menu-container">
          <button class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors" id="userMenuBtn">
            <div class="w-8 h-8 bg-gradient-to-br from-sky-500 to-sky-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
              <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
            </div>
            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
            <div class="px-4 py-2 border-b border-gray-200">
              <p class="text-xs text-gray-600">Logged in as</p>
              <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($currentUser['username']); ?></p>
              <p class="text-xs text-gray-500 mt-1">
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">
                  <?php echo ucfirst($currentUser['role']); ?>
                </span>
              </p>
            </div>

            <?php if (isCurrentUserAdmin()): ?>
              <a href="/views/user-management.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                👥 User Management
              </a>
              <div class="border-t border-gray-200"></div>
            <?php endif; ?>

            <a href="/auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
              🚪 Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="container mx-auto px-4 md:px-6 lg:px-8 py-8">

    <!-- Tabs Navigation -->
    <div class="mb-8 flex space-x-4 border-b border-gray-200">
      <button id="tab-monitor" class="tab-button group flex items-center space-x-2 px-4 py-4 border-b-2 border-sky-500 text-sky-600 font-semibold transition-colors duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        <span>Monitor</span>
      </button>
      <button id="tab-management" class="tab-button group flex items-center space-x-2 px-4 py-4 border-b-2 border-transparent text-gray-600 hover:text-sky-600 hover:border-sky-300 transition-colors duration-200 font-semibold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <span>Management</span>
      </button>
      <button id="tab-history" class="tab-button group flex items-center space-x-2 px-4 py-4 border-b-2 border-transparent text-gray-600 hover:text-sky-600 hover:border-sky-300 transition-colors duration-200 font-semibold">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span>Media Gallery</span>
      </button>
    </div>

    <?php include 'management.php'; ?>
    <?php include 'monitor.php'; ?>
    <?php include 'history.php'; ?>
  </div>

  <?php include 'modals.php'; ?>
  <?php include 'scripts.php'; ?>
</body>
</html>
