# 📁 Grafana - Media Channel Manager dengan Authentication

## 📂 Struktur Folder Aktual

```
grafana/
├── 🔐 Authentication & Entry Point
│   ├── 📄 index.php                 # Session check → redirect ke login/dashboard
│   └── 📁 auth/
│       ├── 📄 login.php             # Login form (username/password)
│       ├── 📄 logout.php            # Logout & session destroy
│       ├── 📄 middleware.php        # Session verification + getCurrentUser()
│       └── 📄 check-admin.php       # Admin-only route protection (403)
│
├── 🗄️ Database & Configuration
│   ├── 📄 config.php                # Load .env, database config, server URL
│   ├── 📁 database/
│   │   ├── 📄 schema.sql            # MySQL table schema (channels, media, users)
│   │   ├── 📄 init.php              # Database initialization script
│   │   └── 📄 test-connection.php   # Test MySQL connection
│   └── 📄 .env                      # Environment variables (DB credentials, APP_PORT)
│
├── 🎯 Models (Database Layer)
│   └── 📁 models/
│       ├── 📄 ChannelModel.php      # MySQL queries untuk channel & media CRUD
│       └── 📄 UserModel.php         # MySQL queries untuk user management
│
├── 🔄 Controllers (Business Logic)
│   └── 📁 controllers/
│       ├── 📄 ManageController.php  # Handle channel/media operations
│       └── 📄 UserController.php    # Handle user CRUD operations
│
├── 🌐 Views (Frontend)
│   └── 📁 views/
│       ├── 📄 index.php             # Main dashboard (tabs: Monitor, Management, User)
│       ├── 📄 monitor.php           # Monitor tab - grid view channels
│       ├── 📄 management.php        # Management tab - channel CRUD table
│       ├── 📄 history.php           # History tab - media library/gallery
│       ├── 📄 preview.php           # TV preview - full-screen display
│       ├── 📄 user-management.php   # Admin page - user management table ✨ MOVED
│       ├── 📄 modals.php            # Reusable modal templates
│       ├── 📄 scripts.php           # JavaScript logic (semua client-side)
│       └── 📄 helpers.php           # PHP helper functions
│
├── 🎨 Styling
│   ├── 📁 src/
│   │   ├── 📄 input.css             # Tailwind imports
│   │   └── 📄 output.css            # Compiled Tailwind CSS (linked di views)
│   ├── 📄 vite.config.ts            # Vite + Tailwind build config
│   └── 📄 package.json              # NPM dependencies
│
├── 🔌 API & Entry Points
│   ├── 📄 api.php                   # REST API - GET channels/media, POST delete
│   └── 📄 (user-management.php moved to views/) ✨
│
├── 📂 File Storage
│   └── 📁 uploads/                  # Media files storage (images, videos, audio)
│
├── 📚 Documentation (Root)
│   └── 📄 PROJECT_STRUCTURE.md      # This file - active documentation
│   └── 📄 README.md                 # Project overview
│
└── 📁 archive/
    ├── 📄 .env.example              # Example .env template
    ├── 📄 index.php.backup          # Backup old index.php
    ├── 📄 setup-admin.php           # CLI tool - create initial admin user ✨ MOVED
    ├── 📄 connect_error             # Old error message file
    ├── 📄 createUser('admin'        # Old setup file remnant
    ├── 📄 debug.log                 # Old debug log
    ├── 📄 upload.php                # Deprecated upload handler
    ├── 📄 manage.php                # Deprecated manage page
    ├── 📄 UploadController.php      # Deprecated upload controller
    ├── 📁 data/                     # Old data storage
    ├── 📁 includes/                 # Old includes (auth.php, check-admin.php)
    └── 📁 docs/                     # Documentation archive
        ├── 📄 AUTHENTICATION_DESIGN.md   # Auth system design docs
        ├── 📄 CODE_ARCHITECTURE.md      # Code architecture notes
        └── 📄 MIGRATION_PLAN.md         # Migration history
```

---

## 🔐 Fungsi File - Layered Architecture

### **1. ENTRY POINTS & ROUTING**

| File | Fungsi | HTTP Method |
|------|--------|------------|
| **index.php** | Check session → redirect ke /auth/login.php atau /views/index.php | GET |
| **api.php** | REST API endpoint untuk GET channels/media, POST delete channel | GET, POST |
| **user-management.php** | Admin UI untuk user CRUD (Create, Read, Update, Delete) | GET, POST |
| **setup-admin.php** | CLI tool untuk membuat initial admin user | CLI |

### **2. AUTHENTICATION SYSTEM**

| File | Fungsi | Dependencies |
|------|--------|------------|
| **auth/login.php** | Login form + session creation (username/password) | config.php, ChannelModel |
| **auth/logout.php** | Destroy session & redirect ke login | - |
| **auth/middleware.php** | Verify session + get current user dari DB | ChannelModel |
| **auth/check-admin.php** | Verify user is admin, else return 403 | middleware.php |

**Flow:** Login → Session di $_SESSION['user_id'] → middleware verify di DB → getCurrentUser()

### **3. DATABASE LAYER (Models)**

| File | Fungsi | Database |
|------|--------|----------|
| **models/ChannelModel.php** | CRUD channels & media (300+ lines) | MySQL (channels, media tables) |
| **models/UserModel.php** | CRUD users + password hashing (300+ lines) | MySQL (users table) |

**Methods di ChannelModel:**
- `getAllChannels()` - SELECT dengan LEFT JOIN media
- `getChannel($name)` - SELECT single channel
- `createChannel($name, $desc)` - INSERT
- `deleteChannel($name)` - DELETE
- `uploadMedia($filename, $mimeType)` - INSERT media
- `setChannelMedia($channelName, $mediaId)` - UPDATE link media

**Methods di UserModel:**
- `createUser($username, $password, $role)` - bcrypt hashing
- `verifyLogin($username, $password)` - password_verify
- `getUserById($id)` - SELECT
- `updateUser($id, $data)` - UPDATE
- `deleteUser($id)` - DELETE
- `getAllUsers()` - SELECT all

### **4. BUSINESS LOGIC (Controllers)**

| File | Fungsi | Input | Output |
|------|--------|-------|--------|
| **controllers/ManageController.php** | Handle create/update/delete channels & media | POST form-data | JSON response |
| **controllers/UserController.php** | Wrapper untuk user CRUD operations | POST data | Array response |
| **controllers/UploadController.php** | (Deprecated - moved to ManageController) | - | - |

### **5. FRONTEND (Views)**

| File | Fungsi | Type | Features |
|------|--------|------|----------|
| **views/index.php** | Main HTML - auth check + tabs wrapper | HTML + PHP | Header, user dropdown, 3 tabs |
| **views/scripts.php** | All JavaScript logic (1300+ lines) | JavaScript | API calls, DOM, pagination, forms |
| **views/modals.php** | Modal templates (create, edit, upload) | HTML | Form templates |
| **views/monitor.php** | Monitor tab - channel grid view | HTML | Responsive grid (2/3/4 cols) |
| **views/management.php** | Management tab - channel table | HTML | CRUD table, edit/delete buttons |
| **views/history.php** | History tab - media library | HTML | Paginated gallery grid |
| **views/preview.php** | TV preview - full-screen display | HTML | Single channel video/image |
| **views/helpers.php** | PHP utilities | PHP | URL generation functions |

### **6. STYLING**

| File | Fungsi | Build Tool |
|------|--------|-----------|
| **src/input.css** | Tailwind CSS imports | Vite |
| **src/output.css** | Compiled CSS (auto-generated) | Vite |
| **vite.config.ts** | Vite config for Tailwind 4 | npm run build |

---

## 🔄 Data Flow (Request-Response)

### **Authentication Flow**
```
Browser Login
    ↓
auth/login.php (POST username/password)
    ↓
ChannelModel→verifyLogin() [DB check]
    ↓
Set $_SESSION['user_id']
    ↓
Redirect ke /views/index.php
    ↓
middleware.php verify session
    ↓
Dashboard dengan user info
```

### **Channel Management Flow**
```
User clicks "Delete Channel"
    ↓
views/scripts.php deleteChannel()
    ↓
fetch(api.php) POST action=delete
    ↓
api.php (validate + delete)
    ↓
ChannelModel→deleteChannel()
    ↓
MySQL DELETE channels
    ↓
JSON response {success: true}
    ↓
views/scripts.php fetchChannels()
    ↓
UI refresh
```

### **User Management Flow** (Admin Only)
```
Admin visits /user-management.php
    ↓
middleware.php + check-admin.php verify admin
    ↓
Form POST action=create/update/delete
    ↓
UserController→handleRequest()
    ↓
UserModel→createUser/updateUser/deleteUser
    ↓
MySQL INSERT/UPDATE/DELETE users table
    ↓
Message display (success/error)
    ↓
Table refresh
```

---

## 📋 Database Schema (MySQL)

```sql
-- Channels Table
CREATE TABLE channels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    current_media_id INT,
    created_at TIMESTAMP,
    FOREIGN KEY (current_media_id) REFERENCES media(id) ON DELETE SET NULL
);

-- Media Table
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(500) UNIQUE NOT NULL,
    mime_type VARCHAR(100),
    created_at TIMESTAMP
);

-- Users Table (for authentication)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP
);
```

---

## 🎯 Key Features Implemented

✅ **Authentication**
- Login dengan username/password
- Bcrypt password hashing (cost 12)
- Session-based verification
- Role-based access control (admin/user)
- Admin-only user management page

✅ **Channel Management**
- Create/Read/Update/Delete channels
- Link media to channels
- Delete channel via API

✅ **Media Management**
- Upload media files (images, videos, audio)
- Responsive gallery grid
- Pagination (12 items/page)
- Drag & drop upload

✅ **UI/UX**
- Tailwind CSS styling (consistent)
- Responsive design (mobile-friendly)
- Modal forms (backdrop-blur)
- Real-time refresh (no full page reload)
- User dropdown menu

---

## ⚙️ Configuration (config.php)

```php
loadEnv();  // Load .env file

APP_PORT = 8000
MYSQL_HOST = localhost
MYSQL_DATABASE = grafana
MYSQL_USERNAME = root
MYSQL_PASSWORD = (empty)
UPLOAD_DIR = uploads/
MAX_FILE_SIZE = 100MB
ALLOWED_MIME = image/png,image/jpeg,video/mp4,etc
```

---

## 🚀 Development & Deployment

```bash
# Install dependencies
npm install

# Development (Tailwind auto-compile)
npm run dev
php -S localhost:8000

# Production (minify CSS)
npm run build

# Create initial admin user
php setup-admin.php
```

**Access:** http://localhost:8000
**Admin Credentials:** admin / admin123 (created via setup-admin.php)

---

## 📊 Project Status (Phase 74)

| Component | Status | Notes |
|-----------|--------|-------|
| Channel Management | ✅ Complete | CRUD + delete working |
| Media Upload | ✅ Complete | Drag & drop, pagination |
| Authentication | ✅ Complete | Login, session, roles |
| User Management | ✅ Complete | Admin CRUD interface |
| UI/UX | ✅ Complete | Tailwind CSS, responsive |
| API Endpoints | ✅ Complete | GET channels/media, POST delete |
| Performance | ✅ Optimized | LCP <1.5s (media gallery) |

---

## 🔧 Recent Improvements (Phase 74)

1. ✅ Added authentication system (login, session, middleware)
2. ✅ Implemented user management with role-based access
3. ✅ Fixed database connections across all files
4. ✅ Added POST handler to api.php for delete operations
5. ✅ Converted UI to Tailwind CSS for consistency
6. ✅ Created user-management.php with modal forms
7. ✅ Setup admin user creation CLI tool
