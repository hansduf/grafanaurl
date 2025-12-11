<?php
/**
 * UserController.php
 * Handles user management business logic
 * Used by user-management.php for CRUD operations
 */

require_once __DIR__ . '/../models/UserModel.php';

class UserController {
    private $userModel;
    private $db;

    public function __construct($database) {
        $this->db = $database;
        $this->userModel = new UserModel($database);
    }

    /**
     * Handle API requests for user management
     * @param string $action (create, read, update, delete, list)
     * @param array $data Request data
     * @return array Response
     */
    public function handleRequest($action, $data = []) {
        switch ($action) {
            case 'create':
                return $this->createUser($data);
            case 'read':
                return $this->readUser($data['id'] ?? null);
            case 'update':
                return $this->updateUser($data['id'] ?? null, $data);
            case 'delete':
                return $this->deleteUser($data['id'] ?? null);
            case 'list':
                return $this->listUsers($data['page'] ?? 1, $data['limit'] ?? 50);
            case 'change-password':
                return $this->changePassword($data['id'] ?? null, $data['new_password'] ?? '');
            default:
                return ['success' => false, 'message' => 'Action tidak dikenal'];
        }
    }

    /**
     * Create new user
     */
    private function createUser($data) {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'user';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username dan password harus diisi'];
        }

        return $this->userModel->createUser($username, $password, $role);
    }

    /**
     * Read single user
     */
    private function readUser($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'User ID diperlukan'];
        }

        $user = $this->userModel->getUserById($id);
        if ($user) {
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'message' => 'User tidak ditemukan'];
    }

    /**
     * Update user
     */
    private function updateUser($id, $data) {
        if (!$id) {
            return ['success' => false, 'message' => 'User ID diperlukan'];
        }

        $updateData = [];
        if (isset($data['username'])) {
            $updateData['username'] = trim($data['username']);
        }
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (bool)$data['is_active'];
        }

        if (empty($updateData)) {
            return ['success' => false, 'message' => 'Tidak ada data untuk diupdate'];
        }

        return $this->userModel->updateUser($id, $updateData);
    }

    /**
     * Delete user
     */
    private function deleteUser($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'User ID diperlukan'];
        }

        // Prevent deleting own account
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri'];
        }

        return $this->userModel->deleteUser($id);
    }

    /**
     * List users with pagination
     */
    private function listUsers($page = 1, $limit = 50) {
        $page = max(1, (int)$page);
        $limit = min(100, max(10, (int)$limit));
        $offset = ($page - 1) * $limit;

        $result = $this->userModel->getAllUsers($limit, $offset);
        
        return [
            'success' => true,
            'users' => $result['users'],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'total_pages' => ceil($result['total'] / $limit)
            ]
        ];
    }

    /**
     * Change password
     */
    private function changePassword($id, $new_password) {
        if (!$id || empty($new_password)) {
            return ['success' => false, 'message' => 'ID dan password baru diperlukan'];
        }

        return $this->userModel->updatePassword($id, $new_password);
    }
}
?>
