<?php
require 'auth.php';

$where = []; $params = [];
if ($me['role'] === 'officer') { $where[] = 'f.assigned_to=?';   $params[] = $me['id']; }
if ($me['role'] === 'manager') { $where[] = 'f.department_id=?'; $params[] = $me['department_id']; }

$base = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Summary numbers (respect the same visibility rules)
$open = $pdo->prepare("SELECT COUNT(*) FROM feedback f $base " . ($base ? 'AND' : 'WHERE') . " f.status IN ('new','assigned','in_progress')");
$open->execute($params);
$waiting = $pdo->prepare("SELECT COUNT(*) FROM feedback f $base " . ($base ? 'AND' : 'WHERE') . " f.first_response_at IS NULL AND f.created_at < (NOW() - INTERVAL 48 HOUR)");
$waiting->execute($params);

// Filters
$fs = $_GET['status'] ?? ''; $ft = $_GET['type'] ?? '';
if ($fs) { $where[] = 'f.status=?'; $params[] = $fs; }
if ($ft) { $where[] = 'f.type=?';   $params[] = $ft; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$perPage = 3;
$cnt = $pdo->prepare("SELECT COUNT(*) FROM feedback f $whereSql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT f.*, d.name AS dept, u.full_name AS officer
        FROM feedback f
        LEFT JOIN departments d ON d.id=f.department_id
        LEFT JOIN users u ON u.id=f.assigned_to
        $whereSql
        ORDER BY FIELD(f.priority,'high','medium','low'), f.created_at DESC
        LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
$st = $pdo->prepare($sql); $st->execute($params);
$cases = $st->fetchAll();

// Build a link to another page, keeping the current filters
function pageUrl($p) {
  $q = $_GET; $q['page'] = $p;
  return '?' . http_build_query($q);
}
$from = $total ? $offset + 1 : 0;
$to   = min($offset + $perPage, $total);

include 'header.php';
?>
<style>
 .wrap{max-width:1280px}
 table{table-layout:auto;width:100%}
 table td,table th{padding:10px 8px;font-size:13.5px;vertical-align:middle}
 /* keep short columns on one line: Ref, Priority, Status, Date */
 table td:nth-child(1),table td:nth-child(6),table td:nth-child(7),table td:nth-child(8),
 table th:nth-child(1),table th:nth-child(6),table th:nth-child(7),table th:nth-child(8){white-space:nowrap}
 /* let long text columns wrap instead of widening the table */
 table td:nth-child(3),table td:nth-child(4),table td:nth-child(5){white-space:normal;word-break:break-word}
 .tablewrap{overflow-x:auto}
 .pager{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:16px;font-size:14px}
 .pages{display:flex;gap:6px;flex-wrap:wrap}
 .pages a,.pages span{min-width:36px;text-align:center;padding:7px 10px;border-radius:8px;border:1px solid #dde4ef;
   text-decoration:none;color:#14407a;background:#fff;font-weight:600}
 .pages a:hover{background:#eef3fb}
 .pages .cur{background:#14407a;color:#fff;border-color:#14407a}
 .pages .off{color:#aaa;background:#f5f5f5}
 .pages .gap{border:0;background:none;color:#888}
</style>

<div class="stats">
 <div class="stat"><b><?= (int)$open->fetchColumn() ?></b>Open cases</div>
 <div class="stat"><b style="color:#c62828"><?= (int)$waiting->fetchColumn() ?></b>No reply after 48h</div>
</div>
<br>
<div class="card">
 <form method="get">
  <select name="status"><option value="">All statuses</option>
   <?php foreach(['new','assigned','in_progress','resolved','closed'] as $s): ?>
    <option value="<?= $s ?>" <?= $fs===$s?'selected':'' ?>><?= $s ?></option>
   <?php endforeach; ?></select>
  <select name="type"><option value="">All types</option>
   <?php foreach(['suggestion','complaint','compliment','service_feedback'] as $t): ?>
    <option value="<?= $t ?>" <?= $ft===$t?'selected':'' ?>><?= $t ?></option>
   <?php endforeach; ?></select>
  <button>Filter</button>
  <?php if ($fs || $ft): ?> &nbsp;<a href="index.php">Clear filters</a><?php endif; ?>
 </form><br>

 <div class="tablewrap">
 <table>
  <tr><th>Ref</th><th>Type</th><th>Subject</th><th>Dept</th><th>Assigned to</th><th>Priority</th><th>Status</th><th>Date</th></tr>
  <?php foreach($cases as $c): ?>
  <tr>
   <td><a href="case.php?id=<?= $c['id'] ?>"><?= e($c['ref_no']) ?></a></td>
   <td><?= e(str_replace('_',' ',$c['type'])) ?></td>
   <td class="wrap"><?= e($c['subject']) ?></td>
   <td><?= e($c['dept']) ?></td>
   <td><?= e($c['officer'] ?? '— unassigned —') ?></td>
   <td><?= badge($c['priority']) ?></td>
   <td><?= badge($c['status']) ?></td>
   <td><?= e(substr($c['created_at'],0,10)) ?></td>
  </tr>
  <?php endforeach; if(!$cases): ?><tr><td colspan="8">No cases found.</td></tr><?php endif; ?>
 </table>
 </div>

 <?php if ($total > 0): ?>
 <div class="pager">
  <div>Showing <b><?= $from ?></b>–<b><?= $to ?></b> of <b><?= $total ?></b> cases</div>
  <?php if ($pages > 1): ?>
  <div class="pages">
   <?php if ($page > 1): ?><a href="<?= e(pageUrl($page-1)) ?>">&laquo; Prev</a><?php else: ?><span class="off">&laquo; Prev</span><?php endif; ?>
   <?php
    $last = 0;
    for ($i = 1; $i <= $pages; $i++) {
      if ($i == 1 || $i == $pages || abs($i - $page) <= 2) {
        if ($last && $i - $last > 1) echo '<span class="gap">…</span>';
        echo $i == $page ? "<span class='cur'>$i</span>" : "<a href='" . e(pageUrl($i)) . "'>$i</a>";
        $last = $i;
      }
    }
   ?>
   <?php if ($page < $pages): ?><a href="<?= e(pageUrl($page+1)) ?>">Next &raquo;</a><?php else: ?><span class="off">Next &raquo;</span><?php endif; ?>
  </div>
  <?php endif; ?>
 </div>
 <?php endif; ?>
</div></div></body></html>