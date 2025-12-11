<?php
// views/modals.php - Modal Components
?>
<!-- Channel Detail Modal (for Monitor tab) -->
<div id="detailModal" class="fixed inset-0 backdrop-blur-md bg-white/10 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden">
    <div class="bg-gray-900 relative overflow-hidden flex items-center justify-center" id="detailModalMedia" style="aspect-ratio: 16/9;">
      <img id="detailImg" class="max-w-full max-h-full object-contain hidden">
      <video id="detailVideo" class="max-w-full max-h-full object-contain hidden" controls autoplay loop></video>
      <audio id="detailAudio" class="hidden" controls></audio>
      <p id="detailNoMedia" class="absolute inset-0 flex items-center justify-center text-gray-400">No media</p>
    </div>
    <div class="p-6">
      <h3 class="text-xl font-bold text-gray-900" id="detailTitle"></h3>
      <p class="text-sm text-gray-600 mt-2" id="detailDesc"></p>
      
      <!-- Preview URL Section -->
      <div id="detailUrlSection" class="mt-4 p-3 bg-sky-50 rounded-lg border border-sky-200 hidden">
        <p class="text-xs font-semibold text-sky-900 mb-2">Preview URL:</p>
        <code id="detailUrlDisplay" class="text-xs text-sky-800 break-all block font-mono bg-white px-2 py-1 rounded border border-sky-200 mb-2"></code>
        <button type="button" id="detailCopyUrlBtn" class="text-xs text-sky-700 hover:text-sky-900 font-medium underline">Copy URL</button>
      </div>
      
      <div class="mt-4 pt-4 border-t border-gray-200 flex gap-2">
        <button onclick="closeDetailModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">Close</button>
        <button id="detailEditBtn" onclick="editChannelFromDetail()" class="flex-1 px-4 py-2 bg-sky-500 text-white font-semibold rounded-lg hover:bg-sky-600 transition">Edit</button>
        <button id="detailDeleteBtn" onclick="deleteCurrentMedia()" class="flex-1 px-4 py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition hidden">Delete</button>
      </div>
    </div>
  </div>
</div>

<!-- Create/Edit Channel Modal -->
<div id="channelModal" class="fixed inset-0 backdrop-blur-md bg-white/10 hidden z-50 flex items-center justify-center p-4">
  <div class="relative w-full max-w-5xl shadow-2xl rounded-2xl bg-white max-h-[95vh] overflow-y-auto">
    <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors z-10">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>

    <form id="channelForm" class="flex flex-col">
      <input type="hidden" id="channelAction" value="create">
      <input type="hidden" id="channelNameOld" value="">
      <input type="hidden" id="mediaId" value="">
      <input type="hidden" id="mime" value="">

      <!-- Header -->
      <div class="px-8 py-6 border-b border-gray-200 sticky top-0 bg-gradient-to-r from-sky-50 to-blue-50">
        <h3 class="text-2xl font-bold text-gray-900" id="modalTitle">Create Channel</h3>
        <p class="text-sm text-gray-600 mt-2" id="modalSubtitle">Set up a new media channel with name and description</p>
      </div>

      <!-- Content - 2 Column Grid -->
      <div class="px-8 py-6 grid grid-cols-2 gap-8">
        <!-- LEFT: Form Fields -->
        <div class="space-y-5">
          <div id="statusMessage" class="mb-4 text-sm px-4 py-3 rounded-lg hidden"></div>

          <!-- Warning for edit mode -->
          <div id="editModeWarning" class="hidden p-3 bg-red-50 rounded-lg shadow-sm border border-red-200">
            <div class="flex items-start space-x-2">
              <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
              </svg>
              <div>
                <p class="text-sm font-bold text-red-700">Name cannot change</p>
                <p class="text-xs text-red-700 mt-0.5">Update description & media only</p>
              </div>
            </div>
          </div>

          <!-- Channel Name -->
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
              <svg class="w-4 h-4 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
              </svg>
              Channel Name
            </label>
            <input type="text" id="channelName" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 bg-white text-gray-900" required placeholder="e.g. lobby">
            <p class="text-xs text-gray-500 mt-1">Letters, numbers, hyphens, underscores</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
              <svg class="w-4 h-4 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Description
            </label>
            <textarea id="channelDesc" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition duration-200 bg-white text-gray-900 resize-none" rows="3" placeholder="Describe this channel..."></textarea>
          </div>

          <!-- Preview URL Info (for edit) -->
          <div id="urlInfoSection" class="hidden p-3 bg-sky-50 rounded-lg border border-sky-200">
            <p class="text-xs font-semibold text-sky-900 mb-1">Preview URL:</p>
            <code id="previewUrlDisplay" class="text-xs text-sky-800 break-all block font-mono bg-white px-2 py-1 rounded border border-sky-200"></code>
            <button type="button" class="text-xs text-sky-700 hover:text-sky-900 mt-1 font-medium underline" onclick="copyPreviewUrl(event)">Copy</button>
          </div>

          <!-- Media Upload -->
          <div id="uploadSection">
            <label class="block text-gray-700 text-sm font-bold mb-2 flex items-center">
              <svg class="w-4 h-4 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              <span id="uploadLabel">Upload Media</span>
            </label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-sky-400 hover:bg-sky-50 transition-all cursor-pointer bg-gray-50" onclick="document.getElementById('channelFile').click()">
              <input type="file" id="channelFile" class="hidden" accept="image/*,video/*,audio/*">
              <div class="text-center">
                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
                </svg>
                <p class="text-gray-700 font-medium text-sm">Click to upload</p>
                <p class="text-xs text-gray-500 mt-1">PNG, JPG, MP4, MP3, etc</p>
              </div>
            </div>
            <p id="mediaFileName" class="text-xs text-gray-500 mt-2"></p>
            <button type="button" onclick="openMediaSelector()" class="mt-3 w-full px-3 py-2 text-sm border border-sky-300 text-sky-700 bg-sky-50 rounded-lg hover:bg-sky-100 transition font-medium">
              Or Browse Existing Media
            </button>
          </div>
        </div>

        <!-- RIGHT: Media Preview -->
        <div class="space-y-4">
          <!-- Current Media (for edit) -->
          <div id="currentMediaSection" class="hidden">
            <p class="text-sm font-bold text-gray-700 mb-2">Current Media</p>
            <div class="border border-gray-300 rounded-lg p-3 bg-gray-50 flex items-center justify-center overflow-hidden" style="aspect-ratio: 16/9;">
              <img id="currentImg" class="max-w-full max-h-full object-contain hidden">
              <video id="currentVideo" class="max-w-full max-h-full object-contain hidden" controls autoplay loop></video>
              <audio id="currentAudio" class="w-full hidden" controls></audio>
              <p id="noCurrentMedia" class="text-gray-400 text-center text-xs">No media</p>
            </div>
          </div>

          <!-- New Media Preview -->
          <div id="mediaPreview" class="space-y-2">
            <p class="text-sm font-bold text-gray-700 mb-2">Preview</p>
            <div class="border border-gray-200 rounded-lg overflow-hidden bg-black flex items-center justify-center" style="aspect-ratio: 16/9;">
              <img id="previewImg" class="max-w-full max-h-full object-contain hidden">
              <video id="previewVideo" class="max-w-full max-h-full object-contain hidden" controls autoplay loop></video>
              <audio id="previewAudio" class="w-full hidden" controls></audio>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-8 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 sticky bottom-0">
        <button type="button" onclick="closeModal()" class="px-6 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-white focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all duration-200">
          Cancel
        </button>
        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-sky-600 to-sky-700 text-white font-semibold rounded-lg hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all duration-200 flex items-center gap-2">
          <span id="submitText">Save Channel</span>
          <span id="submitSpinner" class="hidden inline-block animate-spin">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Media Selector Modal (Browse existing media) -->
<div id="mediaSelectorModal" class="fixed inset-0 backdrop-blur-md bg-white/10 hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
    <div class="px-6 py-4 border-b border-gray-200 bg-sky-50 flex items-center justify-between">
      <h3 class="text-xl font-bold text-gray-900">Select Media from Library</h3>
      <button onclick="closeMediaSelector()" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <div class="flex-1 overflow-auto p-3">
      <div id="mediaSelectorGrid" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-2">
        <!-- Media cards will be rendered here -->
      </div>
      <div id="mediaSelectorEmpty" class="text-center py-12 hidden">
        <p class="text-gray-500">No media in library yet</p>
      </div>
    </div>

    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
      <!-- Pagination -->
      <div id="mediaSelectorPagination" class="flex items-center justify-center gap-2 mb-4">
        <!-- Pagination buttons will be rendered here -->
      </div>
      
      <!-- Action buttons -->
      <div class="flex justify-end gap-2">
        <button id="mediaSelectorCancel" class="px-4 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-white transition">
          Cancel
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Upload Media Modal (for Media Gallery) -->
<div id="uploadMediaModal" class="fixed inset-0 backdrop-blur-md bg-white/10 hidden z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden">
    <button onclick="closeUploadMediaModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors z-10">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>

    <form id="uploadMediaForm" class="flex flex-col">
      <!-- Header -->
      <div class="px-6 py-6 border-b border-gray-200 bg-gradient-to-r from-sky-50 to-blue-50">
        <h3 class="text-2xl font-bold text-gray-900">Upload Media</h3>
        <p class="text-sm text-gray-600 mt-2">Add media to library. Can be used in any channel.</p>
      </div>

      <!-- Content -->
      <div class="px-6 py-6 space-y-6">
        <div id="uploadMediaStatus" class="mb-4 text-sm px-4 py-3 rounded-lg hidden"></div>

        <!-- File Upload -->
        <div>
          <label class="block text-gray-700 text-sm font-bold mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Select Media File
          </label>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 hover:border-sky-400 hover:bg-sky-50 transition-all cursor-pointer bg-gray-50" onclick="document.getElementById('mediaFileInput').click()">
            <input type="file" id="mediaFileInput" class="hidden" accept="image/*,video/*,audio/*">
            <div class="text-center">
              <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"></path>
              </svg>
              <p class="text-gray-700 font-medium">Click to upload or drag and drop</p>
              <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, MP4, WebM, MP3, OGG</p>
            </div>
          </div>
          <div id="mediaFilePreview" class="mt-4 hidden">
            <p class="text-sm text-gray-600 mb-3 font-semibold">Preview:</p>
            <div class="border border-gray-200 rounded-lg overflow-hidden bg-black">
              <div class="aspect-video flex items-center justify-center bg-gray-900">
                <img id="mediaUploadPreviewImg" class="max-w-full max-h-full object-contain hidden">
                <video id="mediaUploadPreviewVideo" class="w-full h-full object-contain hidden" controls></video>
                <audio id="mediaUploadPreviewAudio" class="w-full hidden" controls></audio>
              </div>
            </div>
            <p id="mediaFileNameDisplay" class="text-xs text-gray-500 mt-2"></p>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
        <button type="button" onclick="closeUploadMediaModal()" class="px-6 py-2 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-white focus:outline-none focus:ring-2 focus:ring-gray-300 transition-all duration-200">
          Cancel
        </button>
        <button type="submit" class="px-8 py-2 bg-gradient-to-r from-sky-600 to-sky-700 text-white font-semibold rounded-lg hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-all duration-200 flex items-center gap-2">
          <span id="uploadMediaSubmitText">Upload</span>
          <span id="uploadMediaSpinner" class="hidden inline-block animate-spin">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </span>
        </button>
      </div>
    </form>
  </div>
</div>
