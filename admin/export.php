<?php
require 'auth.php';
if ($me['role'] === 'officer') die('Managers and admins only.');

$from = $_GET['from'] ?? date('Y-01-01');
$to   = $_GET['to']   ?? date('Y-m-d');
$cond = "f.created_at BETWEEN ? AND ?";
$params = [$from . ' 00:00:00', $to . ' 23:59:59'];
if ($me['role'] === 'manager') { $cond .= " AND f.department_id=?"; $params[] = $me['department_id']; }

$st = $pdo->prepare("SELECT f.ref_no, f.type, f.subject, c.name category, d.name department,
    u.full_name officer, f.priority, f.status, f.created_at, f.first_response_at, f.resolved_at,
    ROUND(TIMESTAMPDIFF(MINUTE,f.created_at,f.first_response_at)/60,1) hrs_to_response,
    ROUND(TIMESTAMPDIFF(MINUTE,f.created_at,f.resolved_at)/60,1) hrs_to_resolve,
    f.rating
  FROM feedback f
  LEFT JOIN categories c ON c.id=f.category_id
  LEFT JOIN departments d ON d.id=f.department_id
  LEFT JOIN users u ON u.id=f.assigned_to
  WHERE $cond ORDER BY f.created_at");
$st->execute($params);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="kstvet_feedback_' . $from . '_to_' . $to . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 correctly

$first = true;
while ($row = $st->fetch()) {
  if ($first) { fputcsv($out, array_keys($row)); $first = false; }
  // Stop spreadsheet formula injection from user-typed text
  foreach ($row as $k => $v)
    if (is_string($v) && $v !== '' && strpos('=+-@', $v[0]) !== false) $row[$k] = "'" . $v;
  fputcsv($out, $row);
}