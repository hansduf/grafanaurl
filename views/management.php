<?php
// views/management.php - Management Tab Component
?>
<!-- Management Tab -->
<div id="management-content" class="tab-content hidden opacity-0 transition-opacity duration-300">
  <div id="loading-management" class="hidden flex justify-center items-center py-12">
    <div class="text-center">
      <div class="inline-flex animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-sky-500 mb-4"></div>
      <p class="text-gray-600">Loading channels...</p>
    </div>
  </div>
  <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200" id="management-card">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-sky-50 border-b-2 border-sky-200">
          <tr>
            <th class="px-6 py-4 text-left text-sm font-bold text-sky-900 uppercase tracking-wider">Channel Name</th>
            <th class="px-6 py-4 text-left text-sm font-bold text-sky-900 uppercase tracking-wider">Description</th>
            <th class="px-6 py-4 text-left text-sm font-bold text-sky-900 uppercase tracking-wider">Preview URL</th>
            <th class="px-6 py-4 text-right text-sm font-bold text-sky-900 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white" id="channelTableBody">
          <!-- Rows will be rendered client-side -->
        </tbody>
      </table>
    </div>
  </div>
  <div id="emptyChannelsMessage" class="hidden text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
    </svg>
    <p class="text-gray-500 text-lg font-medium">No channels yet</p>
    <p class="text-gray-400 text-sm mt-2">Click "Create Channel" button to get started</p>
  </div>
</div>
