<?php
require_once '../auth/middleware.php';
require_once '../auth/check-admin.php';
require_once '../controllers/UserController.php';

// Load environment
if (!function_exists('loadEnv')) {
    function loadEnv($filePath = __DIR__ . '/../.env') {
        if (!file_exists($filePath)) {
            throw new Exception('.env file not found at: ' . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv();

$host = getenv('MYSQL_HOST') ?: 'localhost';
$port = getenv('MYSQL_PORT') ?: 3306;
$database = getenv('MYSQL_DATABASE') ?: 'grafana';
$username = getenv('MYSQL_USERNAME') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

$db = new mysqli($host, $username, $password, $database, $port);
if ($db->connect_error) die('Database connection error: ' . $db->connect_error);
$db->set_charset('utf8mb4');

$userController = new UserController($db);
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = $userController->handleRequest($action, $_POST);
    $message = $result['message'];
    $message_type = $result['success'] ? 'success' : 'error';
    if ($result['success'] && $action === 'create') $_POST = [];
}

$listResult = $userController->handleRequest('list', ['page' => $_GET['page'] ?? 1, 'limit' => 50]);
$users = $listResult['users'];
$pagination = $listResult['pagination'];
$currentUser = getCurrentUser();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management - Media Console</title>
  <link href="/src/output.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
  <!-- Header -->
  <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
    <div class="container mx-auto px-4 md:px-6 lg:px-8 py-4 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
        <p class="text-xs text-gray-500">Manage system users and permissions</p>
      </div>
      <div class="flex items-center space-x-3">
        <button onclick="openCreateModal()" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-sky-500 to-sky-600 text-white font-semibold rounded-lg hover:shadow-lg hover:from-sky-600 hover:to-sky-700 transition-all duration-200 shadow-md">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          Add User
        </button>
        <a href="/" class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
          ← Back
        </a>
      </div>
    </div>
  </header>

  <div class="container mx-auto px-4 md:px-6 lg:px-8 py-8">
    <!-- Alert Messages -->
    <?php if ($message): ?>
      <div class="mb-6 p-4 rounded-lg border <?php echo $message_type === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> flex items-start space-x-3 animate-in">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></span>
      </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <!-- Total -->
      <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
        <p class="text-xs text-gray-600 font-semibold">Total Users</p>
        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $pagination['total']; ?></p>
      </div>

      <!-- Admins -->
      <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
        <p class="text-xs text-gray-600 font-semibold">Administrators</p>
        <p class="text-3xl font-bold text-blue-600 mt-2">
          <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?>
        </p>
      </div>

      <!-- Active -->
      <div class="bg-white rounded-lg shadow-md border border-gray-200 p-4">
        <p class="text-xs text-gray-600 font-semibold">Active Users</p>
        <p class="text-3xl font-bold text-green-600 mt-2">
          <?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?>
        </p>
      </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-gray-900">All Users</h2>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">User</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Role</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Joined</th>
              <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="5" class="px-6 py-12 text-center text-gray-500 text-sm">
                  No users yet. Create one above.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                    <?php echo htmlspecialchars($user['username']); ?>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $user['role'] === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'; ?>">
                      <?php echo ucfirst($user['role']); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $user['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                      <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-600">
                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-2">
                      <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['role']; ?>', <?php echo $user['is_active']; ?>)" class="text-sky-600 hover:text-sky-800 font-medium text-sm">Edit</button>
                      <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($pagination['total_pages'] > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-center items-center space-x-2">
          <?php if ($pagination['page'] > 1): ?>
            <a href="?page=1" class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">First</a>
            <a href="?page=<?php echo $pagination['page'] - 1; ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Prev</a>
          <?php endif; ?>

          <span class="text-sm text-gray-600">Page <?php echo $pagination['page']; ?> of <?php echo $pagination['total_pages']; ?></span>

          <?php if ($pagination['page'] < $pagination['total_pages']): ?>
            <a href="?page=<?php echo $pagination['page'] + 1; ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Next</a>
            <a href="?page=<?php echo $pagination['total_pages']; ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Last</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Create User Modal -->
  <div id="createModal" class="hidden fixed inset-0 backdrop-blur-md flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-4">Add New User</h2>

      <form method="POST" action="" class="space-y-4">
        <div>
          <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
          <input type="text" id="username" name="username" placeholder="john.doe" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
          <input type="password" id="password" name="password" placeholder="Min 6 characters" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
        </div>

        <div>
          <label for="role" class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
          <select id="role" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
            <option value="user">👤 Regular User</option>
            <option value="admin">👑 Administrator</option>
          </select>
        </div>

        <input type="hidden" name="action" value="create">
        <div class="flex gap-3 pt-4 border-t border-gray-200">
          <button type="submit" class="flex-1 py-2 px-4 bg-gradient-to-r from-sky-500 to-sky-600 text-white font-semibold rounded-lg hover:shadow-lg hover:from-sky-600 hover:to-sky-700 transition-all duration-200 text-sm">
            Create User
          </button>
          <button type="button" onclick="closeCreateModal()" class="flex-1 py-2 px-4 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors text-sm">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div id="editModal" class="hidden fixed inset-0 backdrop-blur-md flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-4">Edit User</h2>

      <form id="editForm" method="POST" action="" class="space-y-4">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="editUserId" name="id">

        <div>
          <label for="editUsername" class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
          <input type="text" id="editUsername" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
        </div>

        <div>
          <label for="editPassword" class="block text-sm font-semibold text-gray-700 mb-1">Password (leave empty to keep current)</label>
          <input type="password" id="editPassword" name="password" placeholder="Optional" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="editRole" class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
            <select id="editRole" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div>
            <label for="editIsActive" class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
            <select id="editIsActive" name="is_active" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent text-sm">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-200">
          <button type="submit" class="flex-1 py-2 px-4 bg-gradient-to-r from-sky-500 to-sky-600 text-white font-semibold rounded-lg hover:shadow-lg hover:from-sky-600 hover:to-sky-700 transition-all duration-200 text-sm">
            Save
          </button>
          <button type="button" onclick="closeEditModal()" class="flex-1 py-2 px-4 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors text-sm">
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openCreateModal() {
      document.getElementById('createModal').classList.remove('hidden');
      document.getElementById('username').value = '';
      document.getElementById('password').value = '';
      document.getElementById('role').value = 'user';
    }

    function closeCreateModal() {
      document.getElementById('createModal').classList.add('hidden');
    }

    function editUser(id, username, role, isActive) {
      document.getElementById('editUserId').value = id;
      document.getElementById('editUsername').value = username;
      document.getElementById('editPassword').value = '';
      document.getElementById('editRole').value = role;
      document.getElementById('editIsActive').value = isActive ? '1' : '0';
      document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }

    function deleteUser(id, username) {
      if (confirm(`Delete user "${username}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
      }
    }

    document.getElementById('createModal').addEventListener('click', function(e) {
      if (e.target === this) closeCreateModal();
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) closeEditModal();
    });
  </script>
</body>
</html>
