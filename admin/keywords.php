<?php
require 'auth.php';
if ($me['role'] !== 'admin') die('Admins only.');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  checkCsrf();
  $a = $_POST['action'] ?? '';
  if ($a === 'addkw' && trim($_POST['keyword']) !== '') {
    $pdo->prepare("INSERT INTO keywords (keyword, category_id) VALUES (?,?)")
        ->execute([strtolower(trim($_POST['keyword'])), (int)$_POST['category_id']]);
    $msg = 'Keyword added.';
  }
  if ($a === 'delkw') {
    $pdo->prepare("DELETE FROM keywords WHERE id=?")->execute([(int)$_POST['id']]);
    $msg = 'Keyword removed.';
  }
  if ($a === 'addcat' && trim($_POST['name']) !== '') {
    $pdo->prepare("INSERT INTO categories (name, department_id) VALUES (?,?)")
        ->execute([trim($_POST['name']), (int)$_POST['department_id']]);
    $msg = 'Category added.';
  }
}

$cats  = $pdo->query("SELECT c.*, d.name dept FROM categories c JOIN departments d ON d.id=c.department_id ORDER BY c.name")->fetchAll();
$depts = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$kws   = $pdo->query("SELECT k.*, c.name cat FROM keywords k JOIN categories c ON c.id=k.category_id ORDER BY c.name, k.keyword")->fetchAll();
include 'header.php';
?>
<?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>

<div class="card">
 <h3>Add keyword</h3>
 <form method="post">
  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="addkw">
  <input name="keyword" placeholder="e.g. toilet" required>
  <select name="category_id">
   <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <button>Add</button>
 </form>
</div>

<div class="card">
 <h3>Add category</h3>
 <form method="post">
  <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="addcat">
  <input name="name" placeholder="Category name" required>
  <select name="department_id">
   <?php foreach ($depts as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?>
  </select>
  <button>Add</button>
 </form>
 <small>Cases in this category go to the chosen department.</small>
</div>

<div class="card">
 <h3>Current keywords</h3>
 <table>
  <tr><th>Keyword</th><th>Category</th><th></th></tr>
  <?php foreach ($kws as $k): ?>
  <tr><td><?= e($k['keyword']) ?></td><td><?= e($k['cat']) ?></td>
   <td><form method="post">
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delkw">
    <input type="hidden" name="id" value="<?= $k['id'] ?>"><button>Remove</button></form></td></tr>
  <?php endforeach; ?>
 </table>
</div>
</div></body></html>