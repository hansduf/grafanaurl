<?php
// views/scripts.php - JavaScript Code
$config = include __DIR__ . '/../config.php';
?>
<script>
    // Global configuration
    const APP_BASE_URL = '<?= $config['BASE_URL'] ?>';
    const API_ENDPOINT = APP_BASE_URL + '/api.php';
    const CONTROLLER_URL = APP_BASE_URL + '/controllers/ManageController.php';
    
    let currentDetailName = null;

    // User Menu Dropdown
    document.addEventListener('DOMContentLoaded', () => {
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });

            userMenu.addEventListener('click', () => {
                userMenu.classList.add('hidden');
            });
        }
    });

    // Grid layout selector
    function setGridLayout(cols) {
      cols = parseInt(cols);
      document.getElementById('monitorGrid').style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
    }

    // Tab switching
    document.getElementById('tab-management').addEventListener('click', function() {
      showTab('management');
    });
    document.getElementById('tab-monitor').addEventListener('click', function() {
      showTab('monitor');
    });
    document.getElementById('tab-history').addEventListener('click', function() {
      showTab('history');
      loadHistory();
    });

    function showTab(tab) {
      document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('border-sky-500', 'text-sky-600');
        btn.classList.add('border-transparent', 'text-gray-600');
      });
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden', 'opacity-0');
        content.classList.remove('opacity-100');
      });
      
      if (tab === 'management') {
        document.getElementById('tab-management').classList.add('border-sky-500', 'text-sky-600');
        document.getElementById('tab-management').classList.remove('border-transparent', 'text-gray-600');
        const content = document.getElementById('management-content');
        content.classList.remove('hidden');
        setTimeout(() => content.classList.add('opacity-100'), 10);
      } else if (tab === 'history') {
        document.getElementById('tab-history').classList.add('border-sky-500', 'text-sky-600');
        document.getElementById('tab-history').classList.remove('border-transparent', 'text-gray-600');
        const content = document.getElementById('history');
        content.classList.remove('hidden');
        setTimeout(() => content.classList.add('opacity-100'), 10);
      } else {
        document.getElementById('tab-monitor').classList.add('border-sky-500', 'text-sky-600');
        document.getElementById('tab-monitor').classList.remove('border-transparent', 'text-gray-600');
        const content = document.getElementById('monitor-content');
        content.classList.remove('hidden');
        setTimeout(() => content.classList.add('opacity-100'), 10);
      }
    }

    // Modal functions
    document.getElementById('createChannelBtn').addEventListener('click', function() {
      document.getElementById('modalTitle').textContent = 'Create Channel';
      document.getElementById('modalSubtitle').textContent = 'Set up a new media channel with name and description';
      document.getElementById('channelAction').value = 'create';
      document.getElementById('channelName').value = '';
      document.getElementById('channelName').disabled = false;
      document.getElementById('channelDesc').value = '';
      document.getElementById('channelFile').value = '';
      document.getElementById('mediaId').value = '';
      document.getElementById('mime').value = '';
      document.getElementById('mediaFileName').textContent = '';
      document.getElementById('uploadLabel').textContent = 'Upload Media';
      document.getElementById('uploadSection').style.display = 'block';
      document.getElementById('currentMediaSection').style.display = 'none';
      document.getElementById('urlInfoSection').style.display = 'none';
      document.getElementById('editModeWarning').style.display = 'none';
      hidePreview();
      document.getElementById('channelModal').classList.remove('hidden');
    });

    // Upload Media button
    document.getElementById('uploadMediaBtn').addEventListener('click', function() {
      document.getElementById('uploadMediaForm').reset();
      document.getElementById('mediaFilePreview').classList.add('hidden');
      document.getElementById('mediaUploadPreviewImg').classList.add('hidden');
      document.getElementById('mediaUploadPreviewVideo').classList.add('hidden');
      document.getElementById('mediaUploadPreviewAudio').classList.add('hidden');
      document.getElementById('uploadMediaStatus').classList.add('hidden');
      document.getElementById('uploadMediaModal').classList.remove('hidden');
    });

    // Upload Media Form
    document.getElementById('uploadMediaForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const file = document.getElementById('mediaFileInput').files[0];

      if (!file) {
        showUploadMediaStatus('Please select a file', 'error');
        return;
      }

      showUploadMediaStatus('Uploading...', 'info');
      document.getElementById('uploadMediaSubmitText').classList.add('hidden');
      document.getElementById('uploadMediaSpinner').classList.remove('hidden');

      const fd = new FormData();
      fd.append('action', 'upload_media');
      // Only append channel if it has a valid value (not null, not undefined, not empty string)
      const uploadToChannel = currentDetailName ? String(currentDetailName).trim() : '';
      if (uploadToChannel) {
        fd.append('channel', uploadToChannel);
        console.log('[Upload] Uploading to channel:', uploadToChannel);
      } else {
        console.log('[Upload] Uploading to library (no channel specified)');
      }
      fd.append('media', file);

      try {
        const res = await fetch(CONTROLLER_URL, { method: 'POST', body: fd });
        const data = await res.json();
        
        console.log('Upload response:', { ok: res.ok, status: res.status, channel: uploadToChannel, data: data });
        
        // Check if upload was successful - either data.success or data.type === 'success'
        if (res.ok && (data.success || data.type === 'success')) {
          showUploadMediaStatus('Upload successful!', 'success');
          
          setTimeout(() => {
            closeUploadMediaModal();
            // Only refresh if uploaded to a channel - just refresh that channel
            if (uploadToChannel) {
              // Fetch just that specific channel's data
              fetchSingleChannelData(uploadToChannel);
            }
            // Always refresh media library and other tabs
            loadHistory();
          }, 800); // Close modal after 800ms to show success message
        } else {
          showUploadMediaStatus('Error: ' + (data.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        console.error('Upload error:', err);
        showUploadMediaStatus('Network error: ' + err.message, 'error');
      } finally {
        document.getElementById('uploadMediaSubmitText').classList.remove('hidden');
        document.getElementById('uploadMediaSpinner').classList.add('hidden');
      }
    });

    // Fetch single channel data and update just that channel's preview
    async function fetchSingleChannelData(channelName) {
      try {
        const response = await fetch(API_ENDPOINT + '?endpoint=channel/' + encodeURIComponent(channelName));
        const data = await response.json();
        
        if (data.type === 'success' && data.data) {
          const channel = data.data;
          const cardElement = document.querySelector(`[data-channel-name="${escapeHtml(channel.name)}"]`);
          
          if (cardElement) {
            // Update card's data attributes
            cardElement.dataset.mediaId = channel.media_id || '';
            cardElement.dataset.mimeType = channel.mime_type || '';
            
            // Update card's media preview
            const previewDiv = cardElement.querySelector('[style*="aspect-ratio"]');
            if (previewDiv && channel.media_id && channel.mime_type) {
              const url = API_ENDPOINT + '?endpoint=media/' + channel.media_id + '/download';
              let mediaHtml = '';
              
              if (channel.mime_type.startsWith('image/')) {
                mediaHtml = `<img src="${url}" alt="${escapeHtml(channel.name)}" class="max-w-full max-h-full object-contain" loading="eager" fetchpriority="high" decoding="async" style="width: 100%; height: 100%; display: block;">`;
              } else if (channel.mime_type.startsWith('video/')) {
                mediaHtml = `
                  <div class="w-full h-full bg-black flex items-center justify-center relative">
                    <video src="${url}" class="w-full h-full object-cover" loading="eager" style="pointer-events: none;"></video>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                      <svg class="w-16 h-16 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                      </svg>
                    </div>
                  </div>
                `;
              } else if (channel.mime_type.startsWith('audio/')) {
                mediaHtml = `<div class="w-full h-full bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center"><svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9 18a9 9 0 100-18 9 9 0 000 18zM9 4a5 5 0 110 10 5 5 0 010-10z"></path></svg></div>`;
              }
              
              previewDiv.innerHTML = mediaHtml;
            }
          }
        }
      } catch (err) {
        console.error('Error fetching single channel:', err);
        // Fallback to full refresh
        fetchChannels();
      }
    }

    function showUploadMediaStatus(message, type) {
      const statusDiv = document.getElementById('uploadMediaStatus');
      statusDiv.textContent = message;
      statusDiv.className = 'mb-4 text-sm px-4 py-3 rounded-lg';
      if (type === 'error') statusDiv.classList.add('bg-red-50', 'text-red-700');
      else if (type === 'success') statusDiv.classList.add('bg-green-50', 'text-green-700');
      else statusDiv.classList.add('bg-blue-50', 'text-blue-700');
      statusDiv.classList.remove('hidden');
    }

    function closeUploadMediaModal() {
      document.getElementById('uploadMediaModal').classList.add('hidden');
      document.getElementById('uploadMediaForm').reset();
    }

    function editChannel(name) {
      currentDetailName = name; // Set to name untuk upload button
      document.getElementById('modalTitle').textContent = 'Edit Channel';
      document.getElementById('modalSubtitle').textContent = 'Update channel details and media content';
      document.getElementById('channelAction').value = 'update';
      document.getElementById('channelNameOld').value = name;
      document.getElementById('channelName').value = name;
      document.getElementById('channelName').disabled = true;
      document.getElementById('uploadLabel').textContent = 'Replace Media (Optional)';
      document.getElementById('editModeWarning').style.display = 'block';
      
      // Show preview URL
      const protocol = window.location.protocol;
      const host = window.location.host;
      document.getElementById('previewUrlDisplay').textContent = `${protocol}//${host}/views/preview.php?channel=${encodeURIComponent(name)}`;
      document.getElementById('urlInfoSection').style.display = 'block';
      
      // Fetch desc and media with timeout
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 10000); // 10 second timeout
      
      fetch(API_ENDPOINT + '?endpoint=channel/' + encodeURIComponent(name), { signal: controller.signal })
        .then(r => r.json())
        .then(data => {
          clearTimeout(timeout);
          const channel = data.data ? data.data : data;
          document.getElementById('channelDesc').value = channel.description || '';
          if (channel.media_id) {
            showCurrentMedia(channel.media_id, channel.mime_type, channel.filename);
          } else {
            showCurrentMedia(null, null, null);
          }
        })
        .catch(err => {
          clearTimeout(timeout);
          if (err.name !== 'AbortError') {
            console.error('Error fetching channel:', err);
          }
        });
      document.getElementById('channelFile').value = '';
      document.getElementById('uploadSection').style.display = 'block';
      document.getElementById('currentMediaSection').style.display = 'block';
      hidePreview();
      document.getElementById('channelModal').classList.remove('hidden');
    }

    function editChannelFromDetail() {
      if (currentDetailName) {
        closeDetailModal();
        editChannel(currentDetailName);
      }
    }

    function openDetailModal(name, desc, mediaId, mime) {
      currentDetailName = name;
      document.getElementById('detailTitle').textContent = name;
      document.getElementById('detailDesc').textContent = desc || 'No description';
      
      const img = document.getElementById('detailImg');
      const video = document.getElementById('detailVideo');
      const audio = document.getElementById('detailAudio');
      const noMedia = document.getElementById('detailNoMedia');
      const urlSection = document.getElementById('detailUrlSection');
      const editBtn = document.getElementById('detailEditBtn');
      const deleteBtn = document.getElementById('detailDeleteBtn');

      img.classList.add('hidden');
      video.classList.add('hidden');
      audio.classList.add('hidden');
      noMedia.classList.add('hidden');
      urlSection.classList.add('hidden');
      
      // For channel detail modal: show edit button, hide delete button
      if (editBtn) {
        editBtn.textContent = 'Edit';
        editBtn.onclick = function() { editChannelFromDetail(); };
      }
      if (deleteBtn) {
        deleteBtn.classList.add('hidden');
      }

      if (mediaId && mime) {
        const url = API_ENDPOINT + '?endpoint=media/' + mediaId + '/download';
        if (mime.startsWith('image/')) {
          img.loading = 'eager';
          img.fetchPriority = 'high';
          img.decoding = 'async';
          img.src = url;
          img.classList.remove('hidden');
        } else if (mime.startsWith('video/')) {
          video.loading = 'eager';
          video.src = url;
          video.classList.remove('hidden');
          // Ensure video plays
          setTimeout(() => {
            video.play().catch(() => {
              // Autoplay may be blocked by browser, but loop will still work
            });
          }, 100);
        } else if (mime.startsWith('audio/')) {
          audio.src = url;
          audio.classList.remove('hidden');
        }
        
        // Show preview URL
        const previewUrl = APP_BASE_URL + '/views/preview.php?channel=' + encodeURIComponent(name);
        document.getElementById('detailUrlDisplay').textContent = previewUrl;
        urlSection.classList.remove('hidden');
      } else {
        noMedia.classList.remove('hidden');
      }

      document.getElementById('detailModal').classList.remove('hidden');
    }

    function closeDetailModal() {
      // Stop all media and clear sources
      const video = document.getElementById('detailVideo');
      const audio = document.getElementById('detailAudio');
      const img = document.getElementById('detailImg');
      
      if (video) {
        video.pause();
        video.currentTime = 0;
        video.src = '';
      }
      if (audio) {
        audio.pause();
        audio.currentTime = 0;
        audio.src = '';
      }
      if (img) {
        img.src = '';
      }
      
      document.getElementById('detailModal').classList.add('hidden');
      currentDetailName = null;
    }

    function copyPreviewUrl(evt) {
      const url = document.getElementById('previewUrlDisplay').textContent;
      const btn = evt.target.closest('button');
      navigator.clipboard.writeText(url).then(() => {
        const orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
      });
    }

    function showCurrentMedia(mediaId, mime, filename) {
      const section = document.getElementById('currentMediaSection');
      const img = document.getElementById('currentImg');
      const video = document.getElementById('currentVideo');
      const audio = document.getElementById('currentAudio');
      const noMedia = document.getElementById('noCurrentMedia');

      img.classList.add('hidden');
      video.classList.add('hidden');
      audio.classList.add('hidden');
      noMedia.classList.add('hidden');

      if (mediaId && mime) {
        const url = API_ENDPOINT + '?endpoint=media/' + mediaId + '/download';
        if (mime.startsWith('image/')) {
          img.loading = 'eager';
          img.fetchPriority = 'high';
          img.decoding = 'async';
          img.src = url;
          img.classList.remove('hidden');
        } else if (mime.startsWith('video/')) {
          video.loading = 'eager';
          video.src = url;
          video.classList.remove('hidden');
          // Ensure video plays
          setTimeout(() => {
            video.play().catch(() => {
              // Autoplay may be blocked by browser, but loop will still work
            });
          }, 100);
        } else if (mime.startsWith('audio/')) {
          audio.src = url;
          audio.classList.remove('hidden');
        }
        section.classList.remove('hidden');
      } else {
        noMedia.classList.remove('hidden');
        section.classList.remove('hidden');
      }
    }

    function closeModal() {
      // Stop all media in the modal
      const previewVideo = document.getElementById('previewVideo');
      const previewAudio = document.getElementById('previewAudio');
      const currentVideo = document.getElementById('currentVideo');
      const currentAudio = document.getElementById('currentAudio');
      
      if (previewVideo) {
        previewVideo.pause();
        previewVideo.currentTime = 0;
      }
      if (previewAudio) {
        previewAudio.pause();
        previewAudio.currentTime = 0;
      }
      if (currentVideo) {
        currentVideo.pause();
        currentVideo.currentTime = 0;
      }
      if (currentAudio) {
        currentAudio.pause();
        currentAudio.currentTime = 0;
      }
      
      document.getElementById('channelModal').classList.add('hidden');
    }

    function deleteChannel(name) {
      if (confirm('Delete channel ' + name + '?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('name', name);
        fetch(API_ENDPOINT, { method: 'POST', body: fd }).then(res => res.json()).then(data => {
          if (data.success || data.message) {
            // Refresh without full reload
            fetchChannels();
          }
        }).catch(err => console.error('Delete failed:', err));
      }
    }

    // Fetch and render channels
    async function fetchChannels() {
      showLoading(true);
      const startTime = performance.now();
      try {
        const res = await fetch(API_ENDPOINT + '?endpoint=channels');
        if (!res.ok) return [];
        const data = await res.json();
        const channels = (data.data && Array.isArray(data.data)) ? data.data : [];
        renderChannels(channels);
        const endTime = performance.now();
        console.log(`API response time: ${(endTime - startTime).toFixed(2)}ms, Channels: ${channels.length}`);
      } catch (e) {
        console.error('Failed to load channels', e);
      } finally {
        showLoading(false);
      }
    }

    function showLoading(show) {
      if (show) {
        document.getElementById('loading-management').classList.remove('hidden');
        document.getElementById('loading-monitor').classList.remove('hidden');
        document.getElementById('management-card').classList.add('hidden');
        document.getElementById('monitorGrid').classList.add('hidden');
        document.getElementById('emptyChannelsMessage').classList.add('hidden');
        document.getElementById('noChannelsMessage').classList.add('hidden');
      } else {
        document.getElementById('loading-management').classList.add('hidden');
        document.getElementById('loading-monitor').classList.add('hidden');
      }
    }

    function renderChannels(channels) {
      const tbody = document.getElementById('channelTableBody');
      const grid = document.getElementById('monitorGrid');
      const emptyMsg = document.getElementById('emptyChannelsMessage');
      const noMsg = document.getElementById('noChannelsMessage');
      
      if (!channels || channels.length === 0) {
        tbody.innerHTML = '';
        grid.innerHTML = '';
        emptyMsg.classList.remove('hidden');
        noMsg.classList.remove('hidden');
        document.getElementById('management-card').classList.add('hidden');
        document.getElementById('monitorGrid').classList.add('hidden');
        return;
      }

      emptyMsg.classList.add('hidden');
      noMsg.classList.add('hidden');
      document.getElementById('management-card').classList.remove('hidden');
      document.getElementById('monitorGrid').classList.remove('hidden');

      // Build table rows with batch update
      let tableHtml = '';
      let gridHtml = '';

      channels.forEach((c, index) => {
        const name = c.name;
        const desc = c.description || 'No description';
        const mediaId = c.media_id;
        const mime = c.mime_type;

        // Table Row for Management tab
        tableHtml += `
          <tr class="${index % 2 === 0 ? 'bg-white hover:bg-gray-50' : 'bg-gray-50 hover:bg-gray-100'}">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">${escapeHtml(name)}</td>
            <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(desc)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm"><code class="bg-sky-50 text-sky-700 px-3 py-1 rounded-md font-mono text-xs">preview/${escapeHtml(name)}</code></td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
              <button onclick="editChannel('${escapeJs(name)}')" class="text-sky-600 hover:text-sky-900 hover:underline transition">Edit</button>
              <button onclick="deleteChannel('${escapeJs(name)}')" class="text-red-600 hover:text-red-900 hover:underline transition">Delete</button>
            </td>
          </tr>
        `;

        // Card for Monitor tab
        let mediaHtml = '';
        if (mediaId && mime) {
          const url = API_ENDPOINT + '?endpoint=media/' + mediaId + '/download';
          if (mime.startsWith('image/')) {
            mediaHtml = `<img src="${url}" alt="${escapeHtml(name)}" class="max-w-full max-h-full object-contain" loading="eager" fetchpriority="high" decoding="async" style="width: 100%; height: 100%; display: block;">`;
          } else if (mime.startsWith('video/')) {
            mediaHtml = `
              <div class="w-full h-full bg-black flex items-center justify-center relative">
                <video src="${url}" class="w-full h-full object-cover" loading="eager" style="pointer-events: none;"></video>
                <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                  <svg class="w-16 h-16 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                  </svg>
                </div>
              </div>
            `;
          } else if (mime.startsWith('audio/')) {
            mediaHtml = `<div class="w-full h-full bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center"><svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9 18a9 9 0 100-18 9 9 0 000 18zM9 4a5 5 0 110 10 5 5 0 010-10z"></path></svg></div>`;
          }
        } else {
          mediaHtml = `<div class="w-full h-full bg-gray-100 flex items-center justify-center" style="width: 100%; height: 100%; display: block;"><svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>`;
        }

        gridHtml += `
          <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-xl hover:border-sky-300 transition-all duration-300 flex flex-col cursor-pointer" data-channel-name="${escapeHtml(name)}" data-channel-desc="${escapeHtml(desc)}" data-media-id="${mediaId}" data-mime-type="${mime}">
            <div class="w-full bg-gray-100 overflow-hidden flex items-center justify-center" style="aspect-ratio: 16/9; flex-shrink: 0; width: 100%; height: auto;">${mediaHtml}</div>
            <div class="p-4 flex-1 flex flex-col">
              <h4 class="font-bold text-lg text-gray-900 truncate">${escapeHtml(name)}</h4>
              <p class="text-sm text-gray-600 line-clamp-2 flex-1">${escapeHtml(desc)}</p>
              <div class="mt-3 pt-3 border-t border-gray-200">
                <button onclick="event.stopPropagation(); editChannel('${escapeJs(name)}')" class="w-full text-sm font-semibold py-2 px-3 bg-sky-50 text-sky-700 rounded-md hover:bg-sky-100 transition flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  Edit
                </button>
              </div>
            </div>
          </div>
        `;
      });

      // Batch update DOM with all content at once
      tbody.innerHTML = tableHtml;
      grid.innerHTML = gridHtml;

      // Add event listeners to grid cards using event delegation
      grid.addEventListener('click', function(e) {
        const card = e.target.closest('[data-channel-name]');
        if (card && !e.target.closest('button')) {
          openDetailModal(card.dataset.channelName, card.dataset.channelDesc, card.dataset.mediaId, card.dataset.mimeType);
        }
      }, false);
    }

    function escapeHtml(s) {
      return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function escapeJs(s) {
      return String(s).replace(/'/g, "\\'");
    }

    // Media file preview for media upload modal
    document.getElementById('mediaFileInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (!file) {
        document.getElementById('mediaFilePreview').classList.add('hidden');
        return;
      }
      const type = file.type;
      document.getElementById('mediaUploadPreviewImg').classList.add('hidden');
      document.getElementById('mediaUploadPreviewVideo').classList.add('hidden');
      document.getElementById('mediaUploadPreviewAudio').classList.add('hidden');
      document.getElementById('mediaFileNameDisplay').textContent = `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
      
      if (type.startsWith('image/')) {
        const img = document.getElementById('mediaUploadPreviewImg');
        img.src = URL.createObjectURL(file);
        img.classList.remove('hidden');
        document.getElementById('mediaFilePreview').classList.remove('hidden');
      } else if (type.startsWith('video/')) {
        const vid = document.getElementById('mediaUploadPreviewVideo');
        vid.src = URL.createObjectURL(file);
        vid.classList.remove('hidden');
        document.getElementById('mediaFilePreview').classList.remove('hidden');
      } else if (type.startsWith('audio/')) {
        const aud = document.getElementById('mediaUploadPreviewAudio');
        aud.src = URL.createObjectURL(file);
        aud.classList.remove('hidden');
        document.getElementById('mediaFilePreview').classList.remove('hidden');
      }
    });

    // Drag & Drop for media upload
    const dropZone = document.querySelector('[onclick="document.getElementById(\'mediaFileInput\').click()"]');
    if (dropZone) {
      dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-sky-500', 'bg-sky-100');
      });
      
      dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-sky-500', 'bg-sky-100');
      });
      
      dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-sky-500', 'bg-sky-100');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          document.getElementById('mediaFileInput').files = files;
          const event = new Event('change', { bubbles: true });
          document.getElementById('mediaFileInput').dispatchEvent(event);
        }
      });
    }

    // Media preview
    document.getElementById('channelFile').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (!file) {
        hidePreview();
        return;
      }
      const type = file.type;
      hidePreview();
      document.getElementById('mediaFileName').textContent = `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
      
      if (type.startsWith('image/')) {
        const img = document.getElementById('previewImg');
        img.src = URL.createObjectURL(file);
        img.classList.remove('hidden');
        document.getElementById('mediaPreview').classList.remove('hidden');
      } else if (type.startsWith('video/')) {
        const vid = document.getElementById('previewVideo');
        vid.src = URL.createObjectURL(file);
        vid.classList.remove('hidden');
        document.getElementById('mediaPreview').classList.remove('hidden');
      } else if (type.startsWith('audio/')) {
        const aud = document.getElementById('previewAudio');
        aud.src = URL.createObjectURL(file);
        aud.classList.remove('hidden');
        document.getElementById('mediaPreview').classList.remove('hidden');
      }
    });

    function hidePreview() {
      const mediaPreview = document.getElementById('mediaPreview');
      if (mediaPreview) mediaPreview.style.display = 'none';
      document.getElementById('previewImg').classList.add('hidden');
      document.getElementById('previewVideo').classList.add('hidden');
      document.getElementById('previewAudio').classList.add('hidden');
    }

    // Drag & Drop for channel file upload
    const channelDropZone = document.querySelector('[onclick="document.getElementById(\'channelFile\').click()"]');
    if (channelDropZone) {
      channelDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        channelDropZone.classList.add('border-sky-500', 'bg-sky-100');
      });
      
      channelDropZone.addEventListener('dragleave', () => {
        channelDropZone.classList.remove('border-sky-500', 'bg-sky-100');
      });
      
      channelDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        channelDropZone.classList.remove('border-sky-500', 'bg-sky-100');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          document.getElementById('channelFile').files = files;
          const event = new Event('change', { bubbles: true });
          document.getElementById('channelFile').dispatchEvent(event);
        }
      });
    }

    // Form submit
    document.getElementById('channelForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const action = document.getElementById('channelAction').value;
      const name = document.getElementById('channelName').value.trim();
      const desc = document.getElementById('channelDesc').value.trim();
      const file = document.getElementById('channelFile').files[0];
      const mediaId = document.getElementById('mediaId').value;

      // Client-side validation
      if (!/^[a-zA-Z0-9_-]+$/.test(name)) {
        showStatus('Invalid channel name: only letters, numbers, -, _ allowed.', 'error');
        return;
      }

      showStatus('Processing...', 'info');
      document.getElementById('submitText').classList.add('hidden');
      document.getElementById('submitSpinner').classList.remove('hidden');

      const fd = new FormData();
      fd.append('action', action);
      if (action === 'update') {
        fd.append('name_old', document.getElementById('channelNameOld').value);
      }
      fd.append('name', name);
      fd.append('desc', desc);
      if (file) {
        fd.append('media', file);
        showStatus('Uploading media...', 'info');
      } else if (mediaId) {
        // If no file but media selected from library
        fd.append('media_id', mediaId);
        showStatus('Linking media...', 'info');
      }

      try {
        const res = await fetch(CONTROLLER_URL, { method: 'POST', body: fd });
        const data = await res.json();
        console.log('Response:', data);
        if (res.ok && data.type === 'success') {
          showStatus('Success: ' + data.message, 'success');
          setTimeout(() => {
            closeModal();
            // Refresh channel data WITHOUT reloading entire page
            fetchChannels();
            if (document.getElementById('tab-history').classList.contains('border-sky-500')) {
              loadHistory();
            }
          }, 500);
        } else {
          showStatus('Error: ' + (data.message || 'Unknown error'), 'error');
        }
      } catch (err) {
        console.error('Fetch error:', err);
        showStatus('Network error: ' + err.message, 'error');
      } finally {
        document.getElementById('submitText').classList.remove('hidden');
        document.getElementById('submitSpinner').classList.add('hidden');
      }
    });

    function showStatus(message, type) {
      const statusDiv = document.getElementById('statusMessage');
      statusDiv.textContent = message;
      statusDiv.className = 'mb-4 text-sm px-4 py-3 rounded-lg';
      if (type === 'error') statusDiv.classList.add('bg-red-50', 'text-red-700');
      else if (type === 'success') statusDiv.classList.add('bg-green-50', 'text-green-700');
      else statusDiv.classList.add('bg-blue-50', 'text-blue-700');
      statusDiv.classList.remove('hidden');
    }

    // Load media gallery
    async function loadHistory() {
      try {
        console.log('[loadHistory] Starting to fetch media...');
        const t0 = performance.now();
        
        const res = await fetch(API_ENDPOINT + '?endpoint=media&limit=50&offset=0');
        const data = await res.json();
        
        const t1 = performance.now();
        console.log(`[loadHistory] API response received in ${Math.round(t1 - t0)}ms, items: ${data.data?.length || 0}, total: ${data.pagination?.total || 0}`);
        
        renderHistory(data.data && Array.isArray(data.data) ? data.data : []);
      } catch (err) {
        console.error('Failed to load media:', err);
        document.getElementById('historyList').innerHTML = '<p class="text-center text-red-600">Failed to load media</p>';
      }
    }

    function renderHistory(items) {
      const grid = document.getElementById('historyGrid');
      if (!items || items.length === 0) {
        grid.innerHTML = '<div class="col-span-full text-center py-12"><p class="text-gray-500">No media history yet</p></div>';
        return;
      }

      grid.innerHTML = items.map((item, idx) => {
        const isImage = item.mime_type.startsWith('image/');
        const isVideo = item.mime_type.startsWith('video/');
        const isAudio = item.mime_type.startsWith('audio/');
        const fileName = item.filename.split('/').pop();
        const cardId = `history-card-${item.id}`;
        const mediaUrl = API_ENDPOINT + `?endpoint=media/${item.id}/download`;
        
        console.log(`[Media Card ${idx}] ${fileName} | MIME: ${item.mime_type} | isVideo: ${isVideo}`);
        
        // Build media preview HTML (same as monitor tab)
        let mediaHtml = '';
        if (isImage) {
          mediaHtml = `<img src="${mediaUrl}" alt="${escapeHtml(fileName)}" class="max-w-full max-h-full object-contain" loading="lazy" fetchpriority="auto" decoding="async" style="width: 100%; height: 100%; display: block;">`;
        } else if (isVideo) {
          mediaHtml = `
            <div class="w-full h-full bg-black flex items-center justify-center relative">
              <video src="${mediaUrl}" class="w-full h-full object-cover" loading="lazy" style="pointer-events: none;"></video>
              <div class="absolute inset-0 flex items-center justify-center bg-black/20">
                <svg class="w-16 h-16 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                </svg>
              </div>
            </div>
          `;
        } else if (isAudio) {
          mediaHtml = `<div class="w-full h-full bg-gradient-to-br from-sky-500 to-sky-600 flex items-center justify-center"><svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9 18a9 9 0 100-18 9 9 0 000 18zM9 4a5 5 0 110 10 5 5 0 010-10z"></path></svg></div>`;
        } else {
          mediaHtml = `<div class="w-full h-full bg-gray-100 flex items-center justify-center"><svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>`;
        }
        
        return `
          <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-xl hover:border-sky-300 transition-all duration-300 flex flex-col cursor-pointer" data-media-id="${item.id}" data-mime-type="${item.mime_type}" data-filename="${escapeHtml(fileName)}" id="${cardId}">
            <!-- Media Preview -->
            <div class="w-full bg-gray-100 overflow-hidden flex items-center justify-center" style="aspect-ratio: 16/9; flex-shrink: 0;">
              ${mediaHtml}
            </div>
            
            <!-- Card Content -->
            <div class="p-4 flex-1 flex flex-col">
              <h4 class="font-bold text-lg text-gray-900 truncate">${escapeHtml(fileName)}</h4>
              <p class="text-sm text-gray-600 line-clamp-2 flex-1">${escapeHtml(item.mime_type)}</p>
              <div class="mt-3 pt-3 border-t border-gray-200 flex gap-2">
                <button onclick="event.stopPropagation(); downloadHistoryFile('${item.id}')" class="flex-1 text-sm font-semibold py-2 px-3 bg-sky-500 text-white rounded-md hover:bg-sky-600 transition flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                  </svg>
                  Download
                </button>
                <button onclick="event.stopPropagation(); deleteHistoryFile('${item.id}')" class="flex-1 text-sm font-semibold py-2 px-3 bg-red-500 text-white rounded-md hover:bg-red-600 transition flex items-center justify-center gap-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  Delete
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');

      // Add click handler for media preview modal (like monitor tab)
      grid.addEventListener('click', function(e) {
        const card = e.target.closest('[data-media-id]');
        if (card && !e.target.closest('button')) {
          const mediaId = card.dataset.mediaId;
          const mimeType = card.dataset.mimeType;
          const filename = card.dataset.filename;
          openMediaDetailModal(mediaId, mimeType, filename);
        }
      }, false);
    }
    
    // Open detail modal for media (similar to openDetailModal but for gallery)
    function openMediaDetailModal(mediaId, mimeType, filename) {
      const url = API_ENDPOINT + '?endpoint=media/' + mediaId + '/download';
      const img = document.getElementById('detailImg');
      const video = document.getElementById('detailVideo');
      const audio = document.getElementById('detailAudio');
      const noMedia = document.getElementById('detailNoMedia');
      
      // Hide all media types first
      img.classList.add('hidden');
      if (video) video.classList.add('hidden');
      if (audio) audio.classList.add('hidden');
      if (noMedia) noMedia.classList.add('hidden');
      
      if (mimeType.startsWith('image/')) {
        img.loading = 'eager';
        img.fetchPriority = 'high';
        img.decoding = 'async';
        img.src = url;
        img.classList.remove('hidden');
      } else if (mimeType.startsWith('video/') && video) {
        video.loading = 'eager';
        video.src = url;
        video.classList.remove('hidden');
      } else if (mimeType.startsWith('audio/') && audio) {
        audio.src = url;
        audio.classList.remove('hidden');
      } else {
        if (noMedia) noMedia.classList.remove('hidden');
      }
      
      // Set modal title and description
      document.getElementById('detailTitle').textContent = filename;
      document.getElementById('detailDesc').textContent = mimeType;
      
      // Hide edit button and show delete button for media gallery items
      const editBtn = document.getElementById('detailEditBtn');
      const deleteBtn = document.getElementById('detailDeleteBtn');
      if (editBtn) {
        editBtn.textContent = 'Download';
        editBtn.onclick = function() { downloadHistoryFile(mediaId); };
      }
      if (deleteBtn) {
        deleteBtn.classList.remove('hidden');
      }
      
      // Hide URL section for media gallery
      const urlSection = document.getElementById('detailUrlSection');
      if (urlSection) urlSection.classList.add('hidden');
      
      // Store mediaId for delete action
      window.currentDetailMediaId = mediaId;
      
      document.getElementById('detailModal').classList.remove('hidden');
    }
    
    // Delete current media from detail modal
    async function deleteCurrentMedia() {
      if (!window.currentDetailMediaId) return;
      
      if (!confirm('Delete this media permanently?')) return;
      
      try {
        // Close modal first for better UX
        closeDetailModal();
        
        // Immediately remove card from gallery
        const card = document.querySelector(`[data-media-id="${window.currentDetailMediaId}"]`);
        if (card) {
          card.style.opacity = '0';
          card.style.transform = 'scale(0.8)';
          card.style.transition = 'all 0.3s ease-out';
          setTimeout(() => card.remove(), 300);
        }
        
        const fd = new FormData();
        fd.append('action', 'delete_media');
        fd.append('media_id', window.currentDetailMediaId);
        
        const response = await fetch(CONTROLLER_URL, { method: 'POST', body: fd });
        const data = await response.json();
        
        if (data.type === 'success') {
          // Remove from mediaSelectorData cache if exists
          mediaSelectorData = mediaSelectorData.filter(m => m.id !== window.currentDetailMediaId);
          // Update pagination after deletion
          renderMediaPagination();
        } else {
          alert('Error: ' + data.message);
        }
      } catch (err) {
        console.error('Delete error:', err);
        alert('Failed to delete file: ' + err.message);
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function downloadHistoryFile(mediaId) {
      const link = document.createElement('a');
      link.href = API_ENDPOINT + '?endpoint=media/' + mediaId + '/download?download=1';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    async function deleteHistoryFile(mediaId) {
      if (!confirm('Delete this media?')) return;
      
      try {
        // Immediately remove card from gallery for visual feedback
        const card = document.getElementById(`history-card-${mediaId}`);
        if (card) {
          card.style.opacity = '0';
          card.style.transform = 'scale(0.8)';
          card.style.transition = 'all 0.3s ease-out';
          setTimeout(() => card.remove(), 300);
        }
        
        const fd = new FormData();
        fd.append('action', 'delete_media');
        fd.append('media_id', mediaId);
        
        const response = await fetch(CONTROLLER_URL, { method: 'POST', body: fd });
        const data = await response.json();
        
        if (data.type === 'success') {
          // If media was just removed visually, just update pagination
          renderMediaPagination();
        } else {
          alert('Error: ' + data.message);
          // Restore card if deletion failed
          location.reload();
        }
      } catch (err) {
        console.error('Delete error:', err);
        alert('Failed to delete file: ' + err.message);
        // Reload on error to restore UI state
        location.reload();
      }
    }

    // Refresh history button
    document.addEventListener('DOMContentLoaded', function() {
      const refreshBtn = document.getElementById('refreshHistoryBtn');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', loadHistory);
      }
    });

    // ===== MEDIA SELECTOR FUNCTIONS =====
    let mediaSelectorData = [];
    let currentMediaPage = 1;
    const itemsPerPage = 12;
    let mediaSelectorLoaded = false; // Cache flag

    async function openMediaSelector() {
      try {
        console.log('[openMediaSelector] Starting...');
        const t0 = performance.now();
        
        // Only fetch if not already cached
        if (!mediaSelectorLoaded) {
          console.log('[openMediaSelector] Fetching media...');
          const t0fetch = performance.now();
          
          const response = await fetch(API_ENDPOINT + '?endpoint=media&limit=100&offset=0');
          const data = await response.json();
          
          const t1fetch = performance.now();
          console.log(`[openMediaSelector] API fetch took ${Math.round(t1fetch - t0fetch)}ms, got ${data.data?.length || 0} items`);
          
          if (data.type === 'success' && data.data && data.data.length > 0) {
            mediaSelectorData = data.data;
            mediaSelectorLoaded = true;
          } else {
            document.getElementById('mediaSelectorGrid').innerHTML = '<div class="col-span-full text-center py-12"><p class="text-gray-500">No media available</p></div>';
            document.getElementById('mediaSelectorPagination').innerHTML = '';
            return;
          }
        } else {
          console.log('[openMediaSelector] Using cached media...');
        }
        
        currentMediaPage = 1;
        renderMediaSelector();
        
        const t1 = performance.now();
        console.log(`[openMediaSelector] Total time: ${Math.round(t1 - t0)}ms`);
        
        document.getElementById('mediaSelectorModal').classList.remove('hidden');
        document.getElementById('mediaSelectorModal').classList.add('flex');
      } catch (err) {
        console.error('Error loading media:', err);
        alert('Failed to load media library');
      }
    }

    function closeMediaSelector() {
      document.getElementById('mediaSelectorModal').classList.add('hidden');
      document.getElementById('mediaSelectorModal').classList.remove('flex');
    }

    function renderMediaSelector() {
      const grid = document.getElementById('mediaSelectorGrid');
      grid.innerHTML = '';
      
      // Show loading state
      const loading = document.createElement('div');
      loading.className = 'col-span-full text-center py-12';
      loading.innerHTML = '<div class="inline-flex animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-sky-500"></div><p class="text-gray-600 mt-2">Loading media...</p>';
      grid.appendChild(loading);
      
      // Render in next tick to show loading state
      requestAnimationFrame(() => {
        grid.innerHTML = '';
        
        const startIdx = (currentMediaPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;
        const pageItems = mediaSelectorData.slice(startIdx, endIdx);
        
        if (pageItems.length === 0) {
          grid.innerHTML = '<div class="col-span-full text-center py-12"><p class="text-gray-500">No media available</p></div>';
          return;
        }
        
        // Use DocumentFragment for batch DOM insertion (1 reflow instead of 12)
        const fragment = document.createDocumentFragment();
        
        pageItems.forEach(media => {
          const card = document.createElement('div');
          card.className = 'cursor-pointer bg-white border border-gray-200 rounded overflow-hidden hover:shadow-md transition hover:border-sky-400 group';
          card.onclick = () => selectMedia(media.id, media.filename, media.mime_type);
          
          const preview = document.createElement('div');
          preview.className = 'w-full bg-gray-100 flex items-center justify-center overflow-hidden relative';
          preview.style.aspectRatio = '16/9';
          preview.style.flexShrink = '0';
          
          // Show appropriate preview based on mime type
          if (media.mime_type && media.mime_type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = API_ENDPOINT + `?endpoint=media/${media.id}/download`;
            img.className = 'w-full h-full object-contain';
            img.loading = 'lazy';
            img.fetchPriority = 'auto';
            img.decoding = 'async';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.display = 'block';
            preview.appendChild(img);
          } else if (media.mime_type && media.mime_type.startsWith('video/')) {
            // Use poster image instead of video element for faster rendering
            const videoThumbnail = document.createElement('div');
            videoThumbnail.className = 'w-full h-full bg-black flex items-center justify-center';
            videoThumbnail.innerHTML = '<svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path></svg>';
            preview.appendChild(videoThumbnail);
          } else if (media.mime_type && media.mime_type.startsWith('audio/')) {
            preview.innerHTML = '<div class="text-3xl">🎵</div>';
          } else {
            preview.innerHTML = '<div class="text-3xl">📄</div>';
          }
          
          card.appendChild(preview);
          fragment.appendChild(card);  // Add to fragment, not grid (no reflow yet)
        });
        
        // Single appendChild triggers 1 reflow instead of 12
        grid.appendChild(fragment);
        
        // Render pagination
        renderMediaPagination();
      });
    }

    function renderMediaPagination() {
      const paginationContainer = document.getElementById('mediaSelectorPagination');
      paginationContainer.innerHTML = '';
      
      const totalPages = Math.ceil(mediaSelectorData.length / itemsPerPage);
      
      if (totalPages <= 1) return;
      
      // Previous button
      const prevBtn = document.createElement('button');
      prevBtn.className = 'px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed';
      prevBtn.textContent = 'Previous';
      prevBtn.disabled = currentMediaPage === 1;
      prevBtn.addEventListener('click', () => {
        if (currentMediaPage > 1) {
          currentMediaPage--;
          renderMediaSelector();
          document.getElementById('mediaSelectorGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
      paginationContainer.appendChild(prevBtn);
      
      // Page numbers
      for (let i = 1; i <= totalPages; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = `px-3 py-2 rounded text-sm transition ${
          i === currentMediaPage 
            ? 'bg-sky-500 text-white' 
            : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
        }`;
        pageBtn.textContent = i;
        pageBtn.addEventListener('click', () => {
          currentMediaPage = i;
          renderMediaSelector();
          document.getElementById('mediaSelectorGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        paginationContainer.appendChild(pageBtn);
      }
      
      // Next button
      const nextBtn = document.createElement('button');
      nextBtn.className = 'px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed';
      nextBtn.textContent = 'Next';
      nextBtn.disabled = currentMediaPage === totalPages;
      nextBtn.addEventListener('click', () => {
        if (currentMediaPage < totalPages) {
          currentMediaPage++;
          renderMediaSelector();
          document.getElementById('mediaSelectorGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
      paginationContainer.appendChild(nextBtn);
    }

    function selectMedia(mediaId, filename, mimeType) {
      // Set form values
      document.getElementById('mediaId').value = mediaId;
      document.getElementById('mime').value = mimeType;
      document.getElementById('mediaFileName').textContent = `Selected: ${filename}`;
      
      // Show mediaPreview section
      const previewSection = document.getElementById('mediaPreview');
      if (previewSection) {
        previewSection.style.display = 'block';
      }
      
      // Update preview
      updateMediaPreview(mediaId, mimeType);
      
      // Close modal
      closeMediaSelector();
    }

    function updateMediaPreview(mediaId, mimeType) {
      // Clear previous preview
      document.getElementById('previewImg').classList.add('hidden');
      document.getElementById('previewVideo').classList.add('hidden');
      document.getElementById('previewAudio').classList.add('hidden');
      
      const url = API_ENDPOINT + `?endpoint=media/${mediaId}/download`;
      
      if (mimeType && mimeType.startsWith('image/')) {
        const img = document.getElementById('previewImg');
        img.src = url;
        img.classList.remove('hidden');
      } else if (mimeType && mimeType.startsWith('video/')) {
        const video = document.getElementById('previewVideo');
        video.src = url;
        video.autoplay = true;
        video.loop = true;
        video.classList.remove('hidden');
      } else if (mimeType && mimeType.startsWith('audio/')) {
        const audio = document.getElementById('previewAudio');
        audio.src = url;
        audio.classList.remove('hidden');
      }
    }

    // Close media selector when clicking cancel
    document.addEventListener('DOMContentLoaded', function() {
      const cancelBtn = document.getElementById('mediaSelectorCancel');
      if (cancelBtn) {
        cancelBtn.addEventListener('click', closeMediaSelector);
      }
      
      // Detail modal copy URL button
      const copyUrlBtn = document.getElementById('detailCopyUrlBtn');
      if (copyUrlBtn) {
        copyUrlBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const url = document.getElementById('detailUrlDisplay').textContent;
          navigator.clipboard.writeText(url).then(() => {
            const orig = this.textContent;
            this.textContent = '✓ Copied!';
            setTimeout(() => this.textContent = orig, 2000);
          });
        });
      }
    });

    // Init
    document.addEventListener('DOMContentLoaded', function() { fetchChannels(); });
  </script>

