<?php
require 'auth.php';
$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT f.*, d.name AS dept, c.name AS category, u.full_name AS officer
  FROM feedback f
  LEFT JOIN departments d ON d.id=f.department_id
  LEFT JOIN categories c ON c.id=f.category_id
  LEFT JOIN users u ON u.id=f.assigned_to WHERE f.id=?");
$st->execute([$id]);
$case = $st->fetch();
if (!$case) die('Case not found.');

// Access control
if ($me['role']==='officer' && $case['assigned_to'] != $me['id']) die('Not allowed.');
if ($me['role']==='manager' && $case['department_id'] != $me['department_id']) die('Not allowed.');

$statuses = ['new','assigned','in_progress','resolved','closed'];
$notice = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  checkCsrf();
  $action = $_POST['action'] ?? '';

  if ($action === 'reply') {
    $msg = trim($_POST['message'] ?? '');
    $newStatus = $_POST['status'] ?? $case['status'];
    if (!in_array($newStatus,$statuses)) $newStatus = $case['status'];

    if ($msg !== '') {
      $pdo->prepare("INSERT INTO responses (feedback_id,user_id,message,is_public) VALUES (?,?,?,?)")
          ->execute([$id,$me['id'],$msg,isset($_POST['internal'])?0:1]);
      // First response time is only counted for replies the submitter can see
      if (!isset($_POST['internal']) && !$case['first_response_at'])
        $pdo->prepare("UPDATE feedback SET first_response_at=NOW() WHERE id=?")->execute([$id]);
      // Replying to a fresh case moves it to in-progress automatically
      if (in_array($case['status'],['new','assigned']) && $newStatus===$case['status'])
        $newStatus = 'in_progress';
    }

    if ($newStatus !== $case['status']) {
      $resolvedSql = in_array($newStatus,['resolved','closed'])
          ? 'resolved_at = COALESCE(resolved_at, NOW())' : 'resolved_at = NULL';
      $pdo->prepare("UPDATE feedback SET status=?, $resolvedSql WHERE id=?")->execute([$newStatus,$id]);
    }
    header("Location: case.php?id=$id&ok=1"); exit;
  }

  if ($action === 'assign' && in_array($me['role'],['admin','manager'])) {
    $to = (int)$_POST['officer'];
    $newStatus = $case['status']==='new' ? 'assigned' : $case['status'];
    $pdo->prepare("UPDATE feedback SET assigned_to=?, status=? WHERE id=?")->execute([$to,$newStatus,$id]);
    header("Location: case.php?id=$id&ok=1"); exit;
  }
}

$rs = $pdo->prepare("SELECT r.*, u.full_name FROM responses r JOIN users u ON u.id=r.user_id
                     WHERE feedback_id=? ORDER BY r.created_at");
$rs->execute([$id]); $replies = $rs->fetchAll();

$officers = [];
if (in_array($me['role'],['admin','manager'])) {
  $sql = "SELECT id, full_name FROM users WHERE role='officer' AND active=1" .
         ($me['role']==='manager' ? " AND department_id=" . (int)$me['department_id'] : '');
  $officers = $pdo->query($sql)->fetchAll();
}
include 'header.php';
?>
<p><a href="index.php">&larr; Back to cases</a></p>
<?php if(isset($_GET['ok'])): ?><div class="ok">Saved.</div><?php endif; ?>

<div class="card">
 <h2><?= e($case['subject']) ?> <?= badge($case['status']) ?> <?= badge($case['priority']) ?></h2>
 <p><b>Ref:</b> <?= e($case['ref_no']) ?> | <b>Type:</b> <?= e($case['type']) ?>
  | <b>Category:</b> <?= e($case['category'] ?? 'Uncategorized') ?>
  | <b>Dept:</b> <?= e($case['dept']) ?><br>
  <b>Assigned to:</b> <?= e($case['officer'] ?? 'nobody') ?>
  | <b>Received:</b> <?= e($case['created_at']) ?></p>
 <p><b>From:</b> <?= $case['is_anonymous'] ? 'Anonymous' :
   e(($case['submitter_name'] ?: 'Not given') . ' / ' . ($case['submitter_email'] ?: '-') . ' / ' . ($case['submitter_phone'] ?: '-')) ?></p>
 <div class="reply"><?= nl2br(e($case['message'])) ?></div>
 <?php if($case['rating']): ?>
  <p><b>Satisfaction rating:</b> <?= (int)$case['rating'] ?>/5 <?= e($case['rating_comment']) ?></p>
 <?php endif; ?>
</div>

<div class="card">
 <h3>Conversation</h3>
 <?php foreach($replies as $r): ?>
  <div class="reply <?= $r['is_public']?'':'internal' ?>">
   <b><?= e($r['full_name']) ?></b> <small>(<?= e($r['created_at']) ?>)
   <?= $r['is_public']?'':' — INTERNAL NOTE' ?></small><br><?= nl2br(e($r['message'])) ?>
  </div>
 <?php endforeach; if(!$replies) echo '<p>No replies yet.</p>'; ?>
</div>

<div class="card">
 <h3>Reply / update status</h3>
 <form method="post">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="action" value="reply">
  <textarea name="message" rows="4" placeholder="Write a reply (the submitter sees it on the tracking page)"></textarea><br><br>
  <label><input type="checkbox" name="internal" value="1"> Internal note (hidden from submitter)</label><br><br>
  Status:
  <select name="status">
   <?php foreach($statuses as $s): ?>
    <option value="<?= $s ?>" <?= $case['status']===$s?'selected':'' ?>><?= $s ?></option>
   <?php endforeach; ?></select>
  <button>Save</button>
 </form>
</div>

<?php if($officers): ?>
<div class="card">
 <h3>Assign / reassign</h3>
 <form method="post">
  <input type="hidden" name="csrf" value="<?= csrf() ?>">
  <input type="hidden" name="action" value="assign">
  <select name="officer">
   <?php foreach($officers as $o): ?>
    <option value="<?= $o['id'] ?>" <?= $case['assigned_to']==$o['id']?'selected':'' ?>><?= e($o['full_name']) ?></option>
   <?php endforeach; ?></select>
  <button>Assign</button>
 </form>
</div>
<?php endif; ?>
</div></body></html>