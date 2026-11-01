<?php
require 'auth.php';
if ($me['role'] !== 'admin') die('Admins only.');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  checkCsrf();
  $a = $_POST['action'] ?? '';

  if ($a === 'add') {
    $role = $_POST['role'] ?? 'officer';
    $dept = $role === 'admin' ? null : (int)$_POST['department_id'];
    $pw   = $_POST['password'] ?? '';
    if (strlen($pw) < 8) { $msg = 'Password must be at least 8 characters.'; }
    else {
      try {
        $pdo->prepare("INSERT INTO users (full_name,email,password_hash,role,department_id) VALUES (?,?,?,?,?)")
            ->execute([trim($_POST['full_name']), trim($_POST['email']),
                       password_hash($pw, PASSWORD_DEFAULT), $role, $dept]);
        $msg = 'User added.';
      } catch (PDOException $ex) { $msg = 'That email already exists.'; }
    }
  }
  if ($a === 'toggle' && (int)$_POST['id'] !== (int)$me['id']) {
    $pdo->prepare("UPDATE users SET active = 1 - active WHERE id=?")->execute([(int)$_POST['id']]);
    $msg = 'Updated.';
  }
  if ($a === 'reset' && strlen($_POST['password'] ?? '') >= 8) {
    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
        ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), (int)$_POST['id']]);
    $msg = 'Password reset.';
  }
}

$users = $pdo->query("SELECT u.*, d.name dept FROM users u LEFT JOIN departments d ON d.id=u.department_id ORDER BY u.role, u.full_name")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
include 'header.php';
?>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>

<div class="card">
 <h3>Add user</h3>
 <form method="post">
  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="add">
  <input name="full_name" placeholder="Full name" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="text" name="password" placeholder="Password (min 8)" required>
  <select name="role"><option>officer</option><option>manager</option><option>admin</option></select>
  <select name="department_id">
   <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
  </select>
  <button>Add</button>
 </form>
 <small>Department is ignored for admins.</small>
</div>

<div class="card">
 <h3>All users</h3>
 <table>
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Active</th><th>Actions</th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
   <td><?= e($u['full_name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td>
   <td><?= e($u['dept'] ?? '-') ?></td><td><?= $u['active'] ? 'Yes' : 'No' ?></td>
   <td>
    <form method="post" style="display:inline">
     <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="toggle">
     <input type="hidden" name="id" value="<?= $u['id'] ?>">
     <button><?= $u['active'] ? 'Deactivate' : 'Activate' ?></button>
    </form>
    <form method="post" style="display:inline">
     <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="reset">
     <input type="hidden" name="id" value="<?= $u['id'] ?>">
     <input type="text" name="password" placeholder="New password" size="12">
     <button>Reset</button>
    </form>
   </td>
  </tr>
  <?php endforeach; ?>
 </table>
</div>
</div></body></html>