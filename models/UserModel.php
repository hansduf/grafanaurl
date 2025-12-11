<?php
/**
 * UserModel.php
 * Handles all user-related database operations
 * Features: CRUD, password hashing (bcrypt), login verification
 */

class UserModel {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Create new user with hashed password
     * @param string $username
     * @param string $password Plain text password (will be hashed)
     * @param string $role 'admin' or 'user'
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function createUser($username, $password, $role = 'user') {
        try {
            // Validate inputs
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username dan password tidak boleh kosong'];
            }

            if (!in_array($role, ['admin', 'user'])) {
                return ['success' => false, 'message' => 'Role tidak valid. Gunakan admin atau user'];
            }

            // Check if username already exists
            $checkStmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Username sudah terdaftar'];
            }

            // Hash password using bcrypt
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, role, is_active)
                VALUES (?, ?, ?, 1)
            ");
            $stmt->bind_param("sss", $username, $password_hash, $role);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User berhasil dibuat',
                    'user_id' => $this->db->insert_id
                ];
            } else {
                return ['success' => false, 'message' => 'Gagal membuat user: ' . $stmt->error];
            }
        } catch (Exception $e) {
            error_log("UserModel::createUser Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify login credentials
     * @param string $username
     * @param string $password Plain text password to verify
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function verifyLogin($username, $password) {
        try {
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username dan password harus diisi'];
            }

            // Get user by username
            $stmt = $this->db->prepare("
                SELECT id, username, password_hash, role, is_active
                FROM users
                WHERE username = ?
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return ['success' => false, 'message' => 'Username atau password salah'];
            }

            $user = $result->fetch_assoc();

            // Check if user is active
            if (!$user['is_active']) {
                return ['success' => false, 'message' => 'User tidak aktif'];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Username atau password salah'];
            }

            // Return user data without password hash
            return [
                'success' => true,
                'message' => 'Login berhasil',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ];
        } catch (Exception $e) {
            error_log("UserModel::verifyLogin Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|null User data without password hash
     */
    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, role, is_active, created_at, updated_at
                FROM users
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->num_rows > 0 ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("UserModel::getUserById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|null User data without password hash
     */
    public function getUserByUsername($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, role, is_active, created_at, updated_at
                FROM users
                WHERE username = ?
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->num_rows > 0 ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("UserModel::getUserByUsername Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all users with pagination
     * @param int $limit
     * @param int $offset
     * @return array ['users' => array, 'total' => int]
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        try {
            // Get total count
            $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM users");
            $countStmt->execute();
            $countResult = $countStmt->get_result()->fetch_assoc();
            $total = $countResult['total'];

            // Get paginated users
            $stmt = $this->db->prepare("
                SELECT id, username, role, is_active, created_at, updated_at
                FROM users
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }

            return ['users' => $users, 'total' => $total];
        } catch (Exception $e) {
            error_log("UserModel::getAllUsers Error: " . $e->getMessage());
            return ['users' => [], 'total' => 0];
        }
    }

    /**
     * Update user
     * @param int $id
     * @param array $data ['username' => string, 'role' => string, 'is_active' => bool]
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateUser($id, $data) {
        try {
            $user = $this->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }

            $username = $data['username'] ?? $user['username'];
            $role = $data['role'] ?? $user['role'];
            $is_active = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : $user['is_active'];

            // Validate role
            if (!in_array($role, ['admin', 'user'])) {
                return ['success' => false, 'message' => 'Role tidak valid'];
            }

            // Check if new username already exists (if changed)
            if ($username !== $user['username']) {
                $existing = $this->getUserByUsername($username);
                if ($existing) {
                    return ['success' => false, 'message' => 'Username sudah terdaftar'];
                }
            }

            // Update user
            $stmt = $this->db->prepare("
                UPDATE users
                SET username = ?, role = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->bind_param("ssii", $username, $role, $is_active, $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal update user: ' . $stmt->error];
            }
        } catch (Exception $e) {
            error_log("UserModel::updateUser Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Update user password
     * @param int $id
     * @param string $new_password
     * @return array ['success' => bool, 'message' => string]
     */
    public function updatePassword($id, $new_password) {
        try {
            if (empty($new_password)) {
                return ['success' => false, 'message' => 'Password tidak boleh kosong'];
            }

            $user = $this->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }

            $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $this->db->prepare("
                UPDATE users
                SET password_hash = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $password_hash, $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Password berhasil diupdate'];
            } else {
                return ['success' => false, 'message' => 'Gagal update password: ' . $stmt->error];
            }
        } catch (Exception $e) {
            error_log("UserModel::updatePassword Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete user
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteUser($id) {
        try {
            $user = $this->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'User tidak ditemukan'];
            }

            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User berhasil dihapus'];
            } else {
                return ['success' => false, 'message' => 'Gagal hapus user: ' . $stmt->error];
            }
        } catch (Exception $e) {
            error_log("UserModel::deleteUser Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user is admin
     * @param int $id
     * @return bool
     */
    public function isAdmin($id) {
        try {
            $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ? AND is_active = 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                return $user['role'] === 'admin';
            }
            return false;
        } catch (Exception $e) {
            error_log("UserModel::isAdmin Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
