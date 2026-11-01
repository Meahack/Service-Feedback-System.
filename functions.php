<?php
function categorize(PDO $pdo, string $text): ?array {
  $text = strtolower($text);
  $scores = [];
  foreach ($pdo->query("SELECT k.keyword, k.category_id, c.department_id
                        FROM keywords k JOIN categories c ON c.id=k.category_id") as $k) {
    if (strpos($text, strtolower($k['keyword'])) !== false) {
      $id = $k['category_id'];
      $scores[$id]['n'] = ($scores[$id]['n'] ?? 0) + 1;
      $scores[$id]['dept'] = $k['department_id'];
    }
  }
  if (!$scores) return null;
  uasort($scores, fn($a,$b) => $b['n'] <=> $a['n']);
  $cat = array_key_first($scores);
  return ['category_id' => $cat, 'department_id' => $scores[$cat]['dept']];
}

// Give the case to the officer in that department with the fewest open cases
function pickOfficer(PDO $pdo, int $deptId): ?int {
  $st = $pdo->prepare("
    SELECT u.id, COUNT(f.id) AS open_cases
    FROM users u
    LEFT JOIN feedback f ON f.assigned_to=u.id AND f.status IN ('new','assigned','in_progress')
    WHERE u.department_id=? AND u.active=1 AND u.role='officer'
    GROUP BY u.id ORDER BY open_cases ASC LIMIT 1");
  $st->execute([$deptId]);
  $r = $st->fetch();
  return $r ? (int)$r['id'] : null;
}

function detectPriority(string $type, string $text): string {
  $urgent = ['harass','abuse','theft','stolen','unsafe','threat','assault','corrupt','bribe'];
  foreach ($urgent as $w) if (stripos($text,$w)!==false) return 'high';
  return $type === 'complaint' ? 'medium' : 'low';
}

function makeRef(PDO $pdo): string {
  do {
    $ref = 'KST-' . date('y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)),0,5));
    $st = $pdo->prepare("SELECT 1 FROM feedback WHERE ref_no=?");
    $st->execute([$ref]);
  } while ($st->fetch());
  return $ref;
}