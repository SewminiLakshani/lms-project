<?php
require_once '../includes/auth_check.php';
checkRole('admin');
require_once '../config/db.php';

$message = "";

// Delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id !== $_SESSION['user_id']) { // can't delete self
        mysqli_query($conn, "DELETE FROM users WHERE user_id = $id");
        $message = "User deleted successfully.";
    } else {
        $message = "You cannot delete your own account.";
    }
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE user_id = $id"));
    $new_status = ($user['status'] === 'active') ? 'inactive' : 'active';
    mysqli_query($conn, "UPDATE users SET status = '$new_status' WHERE user_id = $id");
    $message = "User status updated.";
}

// Add new user (admin can add lecturers/students/admins directly)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $password, $role);
    if (mysqli_stmt_execute($stmt)) {
        $message = "User added successfully.";
    } else {
        $message = "Error: Email may already exist.";
    }
}

$filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$sql = "SELECT * FROM users";
if (in_array($filter, ['admin', 'lecturer', 'student'])) {
    $sql .= " WHERE role = '$filter'";
}
$sql .= " ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);

$page_title = "Manage Users";
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="section-title mb-0">Manage Users</h3>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-circle"></i> Add User</button>
</div>

<?php if ($message): ?>
  <div class="alert alert-info auto-alert"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="mb-3">
  <a href="?role=all" class="btn btn-sm btn-outline-secondary <?php echo $filter=='all'?'active':''; ?>">All</a>
  <a href="?role=admin" class="btn btn-sm btn-outline-secondary <?php echo $filter=='admin'?'active':''; ?>">Admins</a>
  <a href="?role=lecturer" class="btn btn-sm btn-outline-secondary <?php echo $filter=='lecturer'?'active':''; ?>">Lecturers</a>
  <a href="?role=student" class="btn btn-sm btn-outline-secondary <?php echo $filter=='student'?'active':''; ?>">Students</a>
</div>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-dark">
        <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
          <td><?php echo htmlspecialchars($u['full_name']); ?></td>
          <td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><span class="badge badge-role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
          <td>
            <span class="badge <?php echo $u['status']=='active' ? 'bg-success' : 'bg-secondary'; ?>">
              <?php echo ucfirst($u['status']); ?>
            </span>
          </td>
          <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
          <td>
            <a href="?toggle=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-warning" title="Toggle status">
              <i class="bi bi-toggle2-on"></i>
            </a>
            <a href="?delete=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this user permanently?" title="Delete">
              <i class="bi bi-trash"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Add New User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="6">
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
              <option value="student">Student</option>
              <option value="lecturer">Lecturer</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
