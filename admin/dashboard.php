<?php
require 'auth.php';
if ($me['role'] === 'officer') die('Managers and admins only.');

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$cond   = "f.created_at BETWEEN ? AND ?";
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($me['role'] === 'manager') { $cond .= " AND f.department_id=?"; $params[] = $me['department_id']; }

function rows($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function hrs($h) { if ($h === null) return '–'; return $h >= 24 ? round($h/24, 1) . ' days' : $h . ' hrs'; }

$resp = "ROUND(AVG(TIMESTAMPDIFF(MINUTE,f.created_at,f.first_response_at))/60,1)";
$reso = "ROUND(AVG(TIMESTAMPDIFF(MINUTE,f.created_at,f.resolved_at))/60,1)";

$t = rows($pdo, "SELECT COUNT(*) total,
    SUM(f.status IN ('new','assigned','in_progress')) open_cases,
    SUM(f.status IN ('resolved','closed')) done,
    ROUND(AVG(f.rating),2) avg_rating, COUNT(f.rating) rated,
    $resp avg_resp, $reso avg_reso
  FROM feedback f WHERE $cond", $params)[0];

$overdue = rows($pdo, "SELECT COUNT(*) n FROM feedback f
  WHERE $cond AND f.first_response_at IS NULL AND f.created_at < (NOW() - INTERVAL 48 HOUR)", $params)[0]['n'];

$byType = rows($pdo, "SELECT f.type name, COUNT(*) n FROM feedback f WHERE $cond GROUP BY f.type", $params);

$byDept = rows($pdo, "SELECT COALESCE(d.name,'Unassigned') name, COUNT(*) n,
    $resp resp, $reso reso, ROUND(AVG(f.rating),2) rating
  FROM feedback f LEFT JOIN departments d ON d.id=f.department_id
  WHERE $cond GROUP BY d.id, d.name ORDER BY n DESC", $params);

$byCat = rows($pdo, "SELECT COALESCE(c.name,'Uncategorized') name, COUNT(*) n
  FROM feedback f LEFT JOIN categories c ON c.id=f.category_id
  WHERE $cond GROUP BY c.id, c.name ORDER BY n DESC LIMIT 10", $params);

$byMonth = rows($pdo, "SELECT DATE_FORMAT(f.created_at,'%Y-%m') m, COUNT(*) n
  FROM feedback f WHERE $cond GROUP BY m ORDER BY m", $params);

$ratings = rows($pdo, "SELECT f.rating r, COUNT(*) n FROM feedback f
  WHERE $cond AND f.rating IS NOT NULL GROUP BY f.rating ORDER BY f.rating", $params);

include 'header.php';
?>
<div class="card">
 <form method="get">
  From <input type="date" name="from" value="<?= e($from) ?>">
  To <input type="date" name="to" value="<?= e($to) ?>">
  <button>Apply</button>
  &nbsp; <a href="export.php?from=<?= e($from) ?>&to=<?= e($to) ?>">⬇ Export CSV</a>
 </form>
</div>

<div class="stats">
 <div class="stat"><b><?= (int)$t['total'] ?></b>Total cases</div>
 <div class="stat"><b><?= (int)$t['open_cases'] ?></b>Open</div>
 <div class="stat"><b><?= (int)$t['done'] ?></b>Resolved/Closed</div>
 <div class="stat"><b style="color:#c62828"><?= (int)$overdue ?></b>No reply after 48h</div>
 <div class="stat"><b><?= hrs($t['avg_resp']) ?></b>Avg first response</div>
 <div class="stat"><b><?= hrs($t['avg_reso']) ?></b>Avg resolution</div>
 <div class="stat"><b><?= $t['avg_rating'] ?? '–' ?>/5</b>Satisfaction (<?= (int)$t['rated'] ?> ratings)</div>
</div>
<br>

<div class="card"><h3>Monthly trend</h3><canvas id="trend" height="90"></canvas></div>

<div style="display:flex;gap:16px;flex-wrap:wrap">
 <div class="card" style="flex:1;min-width:300px"><h3>Cases by type</h3><canvas id="types"></canvas></div>
 <div class="card" style="flex:1;min-width:300px"><h3>Satisfaction ratings</h3><canvas id="rate"></canvas></div>
</div>

<div class="card"><h3>Recurring issues (top categories)</h3><canvas id="cats" height="90"></canvas></div>

<div class="card">
 <h3>Performance by department</h3>
 <table>
  <tr><th>Department</th><th>Cases</th><th>Avg first response</th><th>Avg resolution</th><th>Avg rating</th></tr>
  <?php foreach ($byDept as $d): ?>
   <tr><td><?= e($d['name']) ?></td><td><?= (int)$d['n'] ?></td>
       <td><?= hrs($d['resp']) ?></td><td><?= hrs($d['reso']) ?></td>
       <td><?= $d['rating'] ?? '–' ?></td></tr>
  <?php endforeach; if (!$byDept) echo '<tr><td colspan="5">No data in this period.</td></tr>'; ?>
 </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const J = (x) => x;
new Chart(document.getElementById('trend'), {type:'line',
 data:{labels:<?= json_encode(array_column($byMonth,'m'), JSON_HEX_TAG) ?>,
  datasets:[{label:'Cases',data:<?= json_encode(array_map('intval',array_column($byMonth,'n'))) ?>,
  borderColor:'#14407a',backgroundColor:'#14407a33',fill:true,tension:.3}]}});
new Chart(document.getElementById('types'), {type:'doughnut',
 data:{labels:<?= json_encode(array_column($byType,'name'), JSON_HEX_TAG) ?>,
  datasets:[{data:<?= json_encode(array_map('intval',array_column($byType,'n'))) ?>,
  backgroundColor:['#1565c0','#c62828','#2e7d32','#ef6c00']}]}});
new Chart(document.getElementById('rate'), {type:'bar',
 data:{labels:<?= json_encode(array_map(fn($r)=>$r['r'].' star', $ratings)) ?>,
  datasets:[{label:'Ratings',data:<?= json_encode(array_map('intval',array_column($ratings,'n'))) ?>,backgroundColor:'#2e7d32'}]}});
new Chart(document.getElementById('cats'), {type:'bar',
 data:{labels:<?= json_encode(array_column($byCat,'name'), JSON_HEX_TAG) ?>,
  datasets:[{label:'Cases',data:<?= json_encode(array_map('intval',array_column($byCat,'n'))) ?>,backgroundColor:'#14407a'}]},
 options:{indexAxis:'y'}});
</script>
</div></body></html>