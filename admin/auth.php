<?php
require __DIR__ . '/../config.php';
if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
$me = $_SESSION['user'];

function csrf(){
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function checkCsrf(){
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('Invalid request.');
}
function badge($text){
  $colors = ['high'=>'#c62828','medium'=>'#ef6c00','low'=>'#2e7d32',
             'new'=>'#6a1b9a','assigned'=>'#1565c0','in_progress'=>'#ef6c00',
             'resolved'=>'#2e7d32','closed'=>'#555'];
  $c = $colors[$text] ?? '#555';
  return "<span class='badge' style='background:$c'>" . e(str_replace('_',' ',$text)) . "</span>";
}