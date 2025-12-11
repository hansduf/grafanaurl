# Authentication & Role-Based Access Control System

## Overview
Simple login system dengan 2 roles (admin, user). NO email, NO notifications, NO password reset.
- Admin: Full access (monitor, management, gallery, user management)
- User: Limited access (monitor, management, gallery - NO user management)

---

## 1. Database Schema

### Users Table
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**No fields**: email, phone, reset tokens, notifications, audit logs
**Simple & lean**: Only what's needed for authentication + authorization

---

## 2. Login Flow

### Process
1. User visits `/index.php` → middleware checks session
2. No session → redirect to `/login.php`
3. User enters username + password
4. Backend verifies: `SELECT * FROM users WHERE username = ? AND is_active = 1`
5. Verify password with `password_verify(input, hash)`
6. Match → Set `$_SESSION['user']` → redirect to `/index.php`
7. No match → Show error "Username atau password salah"

### Session Data Structure
```php
$_SESSION['user'] = [
  'id' => 1,
  'username' => 'admin_user',
  'role' => 'admin'  // or 'user'
];
```

### Logout
- POST to `/logout.php`
- `session_destroy()`
- `session_unset()`
- Redirect to `/login.php`

---

## 3. User Management (Admin Only)

### Features
- **CREATE**: Username, Password (plain text input), Role (dropdown)
  - Admin manually types password (no auto-generate)
  - Hash with `password_hash()`
  - Insert to DB
  
- **LIST**: Show all users in table
  - Columns: Username | Role | Status | Actions
  
- **EDIT**: Edit Role (admin ↔ user), Status (Active ↔ Inactive)
  - NO edit username
  - NO change password (too risky, delete + recreate if needed)
  
- **DELETE**: Soft delete (set `is_active = 0`)
  - User still exists in DB (history preserved)
  - Can be reactivated
  - Or hard delete if preferred

### UI
- Tab: "User Management" (admin only, hidden for regular users)
- Button: "+ Create User"
- Table with CRUD actions per user
- Simple, no pagination for now (assume < 100 users)

---

## 4. Access Control

### Routes & Permissions

| Route | Admin | User | Notes |
|-------|-------|------|-------|
| `/login.php` | PUBLIC | PUBLIC | Anyone, no session needed |
| `/logout.php` | ✅ | ✅ | Authenticated only |
| `/index.php` | ✅ | ✅ | Dashboard (main page) |
| `/views/monitor.php` | ✅ | ✅ | Monitor tab (included in dashboard) |
| `/views/management.php` | ✅ | ✅ | Management tab (included in dashboard) |
| `/views/history.php` | ✅ | ✅ | Gallery tab (included in dashboard) |
| `/views/user-management.php` | ✅ ONLY | ❌ | User CRUD (admin only) |
| `/api.php` | ✅ | ✅ | API endpoints (channels, media) |
| `/controllers/ManageController.php` | ✅ | ✅ | Channel/media actions |
| `/controllers/UserController.php` | ✅ ONLY | ❌ | User CRUD actions |

### Middleware

**File: `/includes/auth.php`** - Check session exists
```php
if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    exit;
}
```

**File: `/includes/check-admin.php`** - Check role is admin
```php
if ($_SESSION['user']['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}
```

---

## 5. File Structure

### New Files to Create
```
/login.php                              ← Login page
/logout.php                             ← Logout endpoint
/includes/auth.php                      ← Session check middleware
/includes/check-admin.php               ← Admin-only check middleware
/views/user-management.php              ← User CRUD page (admin only)
/controllers/UserController.php         ← User CRUD logic
/models/UserModel.php                   ← User DB queries
```

### Modified Files
```
/index.php                              ← Add session check, show logged-in user
/controllers/ManageController.php       ← Add session check (already there?)
/api.php                                ← Add session check
```

---

## 6. UI Changes

### Header
- Add: "Logged in as: [username]" (top-right)
- Add: "Logout" button (top-right)

### Navigation
- Monitor (all roles)
- Management (all roles)
- Gallery (all roles)
- **User Management** (admin only - hidden for users)

### User Management Page
- Title: "User Management"
- Button: "+ Create User"
- Table:
  - Username | Role | Status | Edit | Delete
  - Rows: All users
- Modal/Form for Create/Edit

---

## 7. Implementation Steps (Execution Order)

1. **Create DB table** - Add `users` table to MySQL
2. **Create Models** - `UserModel.php` with CRUD methods
3. **Create Auth Files** - `auth.php`, `check-admin.php` middleware
4. **Create Login Page** - `/login.php` (form + validation)
5. **Create Logout** - `/logout.php` (destroy session)
6. **Create User Management** - `/views/user-management.php` (CRUD UI)
7. **Create User Controller** - `/controllers/UserController.php` (business logic)
8. **Update Index** - `/index.php` (add middleware + show user)
9. **Update APIs** - `/api.php` (add session check)
10. **Initial Admin Setup** - Insert first admin user to DB

---

## 8. Security Checklist

✅ **Password Hashing**: `password_hash()` (bcrypt)
✅ **SQL Injection Prevention**: Prepared statements
✅ **Session Security**: Built-in PHP sessions
✅ **Role-Based Access**: Check session role before show
✅ **Logout Cleanup**: `session_destroy()` + `session_unset()`
✅ **Inactive Users**: Check `is_active = 1` on login

❌ **NOT Implementing** (Keep Simple):
- Email verification
- Password reset/recovery
- Two-factor authentication
- Account lockout after failed attempts
- CSRF tokens (can add later if needed)
- Audit logging
- Remember me / persistent login

---

## 9. Initial Setup

**First Time Setup**:
- Create `users` table in MySQL
- Manually insert one admin user:
  ```sql
  INSERT INTO users (username, password_hash, role, is_active) 
  VALUES ('admin', '$2y$10$...hashed_password...', 'admin', 1);
  ```
- OR create a setup script
- Login with this admin user
- Create other users via User Management page

---

## 10. Error Handling

### Login Errors
- "Username atau password salah" (generic - no user enumeration)
- "User account tidak aktif" (optional, safer to just say password salah)

### Admin Page Access
- Regular user tries `/views/user-management.php` → redirected to `/index.php`
- Show optional message: "Anda tidak memiliki akses ke halaman ini"

---

## 11. Future Enhancements (Not in scope now)
- Password complexity validation
- Session timeout (idle 30 min)
- CSRF tokens on forms
- Two-factor authentication
- Email-based password reset
- Audit log of admin actions
- User activity tracking

---

## Status
- [ ] Design finalized (THIS DOCUMENT)
- [ ] Database created
- [ ] UserModel implemented
- [ ] Auth middleware created
- [ ] Login page built
- [ ] User management page built
- [ ] All pages updated with auth checks
- [ ] Testing & debugging
- [ ] Ready for production

