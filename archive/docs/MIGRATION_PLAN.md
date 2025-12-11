# Migration Plan: Simplified Media Gallery System

## Overview
Migrate from complex dual-folder/dual-table system to a simple 2-table media gallery where:
- 1 media file can be used by multiple channels
- Each channel displays 1 active media (current_media_id)
- All files stored in single `uploads/` folder

---

## Database Schema

### Current State
```
channels:
  - id, name, description, filename, mime_type, created_at, updated_at, deleted_at

media_history:
  - id, filename, mime_type, action, created_at
```

### New State

#### Table: `channels`
```sql
CREATE TABLE channels (
  id SERIAL PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  description TEXT,
  current_media_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Table: `media`
```sql
CREATE TABLE media (
  id SERIAL PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Changes
- ❌ Remove `media_history` table (no longer needed)
- ❌ Remove `deleted_at` column from channels
- ❌ Remove `filename`, `mime_type` from channels
- ✅ Add `current_media_id` to channels (FK to media.id)
- ✅ Create new `media` table (central media library)

---

## File Structure

### Current State
```
uploads/
  ├── active/
  │   └── {channel}/
  │       └── {filename}
  └── history/
      └── {timestamp}_{filename}
```

### New State
```
uploads/
  ├── {filename}
  ├── {filename}
  └── {filename}
```
**No subfolders, all files flat in `uploads/`**

---

## Workflow Changes

### Create Channel
**Before:**
1. Create channel in DB
2. (Optional) Upload media to `uploads/active/{channel}/`
3. Save filename in channels.filename

**After:**
1. Create channel in DB (current_media_id = null)
2. (Optional) Upload media → insert into media table
3. Link media to channel → update channels.current_media_id

### Upload Media
**Before:**
1. Save to `uploads/active/{channel}/{filename}`
2. Insert record in media_history (action='upload')
3. Update channels.filename

**After:**
1. Save to `uploads/{filename}`
2. Insert record in media table
3. Update channels.current_media_id (if first media for channel)

### Switch Media (Replace)
**Before:**
1. Move old file from `uploads/active/{channel}/` to `uploads/history/`
2. Delete old record from media_history (action='upload')
3. Insert new record in media_history (action='replace')
4. Upload new file to `uploads/active/{channel}/`
5. Update channels.filename

**After:**
1. Update channels.current_media_id to new media.id
2. That's it! Old media still exists in media table (can be used by other channels)

### Delete Media
**Before:**
1. Move all files to `uploads/history/deleted_*`
2. Hard delete from channels table
3. Hard delete from media_history table

**After:**
1. Delete file from `uploads/`
2. Delete record from media table
3. For channels using this media → set their current_media_id to NULL (or delete channel)

### Delete Channel
**Before:**
1. Delete from channels table
2. Delete associated media_history records
3. Move files to history

**After:**
1. Delete from channels table
2. Associated media files stay in media table (still usable by other channels)

### History/Gallery View
**Before:**
1. Query media_history to show replaced/deleted files

**After:**
1. Query media table to show all available media
2. Show which media is currently_active per channel
3. Allow switching between media

---

## API Endpoints Changes

### Current Endpoints
- `GET /api.php?endpoint=channels` - Get all channels
- `GET /api.php?endpoint=channel/{name}` - Get single channel
- `GET /api.php?endpoint=media_history` - Get media history
- `POST /controllers/ManageController.php` - Create/update/delete

### New Endpoints
- `GET /api.php?endpoint=channels` - Get all channels with current media
- `GET /api.php?endpoint=channel/{name}` - Get channel with current media
- `GET /api.php?endpoint=media` - Get all media (gallery)
- `GET /api.php?endpoint=media/{id}` - Get single media
- `POST /api.php?endpoint=media/{id}/download` - Download media file
- `POST /controllers/ManageController.php?action=switch_media` - Switch channel's media
- `POST /controllers/ManageController.php?action=upload_media` - Upload to media library

---

## Code Changes Required

### Models
- ✅ `ChannelModel.php` - Update methods for new schema
  - `getChannel()` - Include media data via current_media_id
  - `createChannel()` - Don't require filename
  - `updateChannel()` - Only update description, not media
  - `deleteChannel()` - Keep as is
  - `switchMedia()` - New method to update current_media_id
  - Remove `saveMediaHistory()`, `deleteMediaHistory()`
  - Add `uploadMedia()` - Save to media table
  - Add `getMedia()`, `getAllMedia()` - Query media table
  - Add `deleteMedia()` - Hard delete from media table and uploads/

### Controllers
- ✅ `ManageController.php` - Refactor actions
  - `create` - Don't handle file upload, just create channel
  - `update` - Only update description
  - Remove `replace` action (no longer needed)
  - Remove `delete_history` action
  - Add `upload_media` - Upload to library
  - Add `switch_media` - Change channel's current_media_id
  - Add `delete_media` - Delete from library

### API
- ✅ `api.php` - New endpoints
  - Add `/media` endpoint
  - Add `/media/{id}/download` endpoint
  - Update `/channels` endpoint (join with media)
  - Remove auto-cleanup (no longer needed)

### Views
- ✅ `index.php` - Update UI
  - Show current media in monitor tab
  - Show all media in gallery/history tab
  - Add media selector/switcher
- ✅ `scripts.php` - Update JS
  - Upload to media library first
  - Then link to channel
  - Switch media without re-upload
  - Show gallery of available media

### Database
- ✅ `schema.sql` - Update schema
  - Drop media_history table
  - Modify channels table (add current_media_id, remove filename/mime_type)
  - Create media table

---

## Migration Steps (Order of Execution)

1. **Backup current data** (optional)
2. **Update database schema** - Create media table, modify channels, drop media_history
3. **Migrate existing media** - Move all files from `uploads/active/*/` and `uploads/history/` to `uploads/`
4. **Migrate data** - Insert existing media into media table, update channels.current_media_id
5. **Update ChannelModel.php** - New methods for schema
6. **Update ManageController.php** - New actions
7. **Update api.php** - New endpoints
8. **Update views** - New UI for gallery
9. **Update scripts.php** - New JS for media switching
10. **Test workflow** - Create channel → upload media → switch → delete
11. **Cleanup** - Remove old folders (uploads/active, uploads/history)

---

## Benefits of New System

✅ **Simplified** - 2 tables instead of 2 tables (but cleaner relationships)
✅ **Efficient** - Media stored once, reused by multiple channels
✅ **Flexible** - Easy to switch media on-the-fly without re-upload
✅ **Clean filesystem** - Single uploads/ folder, no subfolders
✅ **No duplicates** - Media table has single source of truth
✅ **YouTube-like** - Select from gallery, switch anytime

---

## Ready to Execute?
Confirm if you want to proceed with migration! 👍
