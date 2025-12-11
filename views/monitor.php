<?php
// views/monitor.php - Monitor Tab Component
?>
<!-- Monitor Tab -->
<div id="monitor-content" class="tab-content opacity-100 transition-opacity duration-300">
  <div id="loading-monitor" class="hidden flex justify-center items-center py-12">
    <div class="text-center">
      <div class="inline-flex animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-sky-500 mb-4"></div>
      <p class="text-gray-600">Loading channels...</p>
    </div>
  </div>

  <!-- Grid Layout Selector -->
  <div class="mb-6 flex items-center gap-3">
    <label for="gridLayoutSelect" class="text-sm font-semibold text-gray-700">Grid Layout:</label>
    <select id="gridLayoutSelect" onchange="setGridLayout(this.value)" class="px-4 py-2 rounded-lg border-2 border-gray-300 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-200 transition font-medium text-sm bg-white text-gray-900 hover:border-sky-400">
      <option value="1">1 Column</option>
      <option value="2">2 Columns</option>
      <option value="3" selected>3 Columns</option>
      <option value="4">4 Columns</option>
    </select>
  </div>

  <div id="monitorGrid" class="grid gap-6" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
    <!-- Cards rendered client-side -->
  </div>
  <div id="noChannelsMessage" class="hidden text-center py-12">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
    </svg>
    <p class="text-gray-500 text-lg">No channels available</p>
    <p class="text-gray-400 text-sm mt-2">Create a channel in the Management tab to get started</p>
  </div>
</div>
