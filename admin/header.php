<?php
// Shared layout for every staff page
$cur = basename($_SERVER['SCRIPT_NAME']);
$titles = [
  'index.php'     => ['Cases',      'All feedback cases you can see, newest and most urgent first.'],
  'case.php'      => ['Case',       'Review the message, reply to the submitter and update the status.'],
  'dashboard.php' => ['Dashboard',  'Trends, response times and satisfaction scores.'],
  'users.php'     => ['Users',      'Add staff, deactivate accounts and reset passwords.'],
  'keywords.php'  => ['Keywords',   'Tune how new messages are categorised and routed.'],
];
[$pageTitle, $pageSub] = $titles[$cur] ?? ['Staff portal', ''];
$initials = '';
foreach (preg_split('/\s+/', trim($me['full_name'])) as $w) { if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
$initials = mb_substr($initials, 0, 2);
function nav_link($file, $label, $icon, $cur, $alsoActive = []) {
  $on = ($cur === $file || in_array($cur, $alsoActive)) ? ' class="on"' : '';
  return "<a$on href=\"$file\"><span>$icon</span> $label</a>";
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | KSTVET Staff Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
 :root{--ink:#0d2240;--blue:#14407a;--blue-2:#1d5fb8;--sky:#e8f0fb;--gold:#f0b429;
       --paper:#f7f8fb;--line:#dde4ef;--muted:#53627a;--white:#fff;--red:#c62828;--green:#2e7d32}
 *{box-sizing:border-box}
 body{font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;background:var(--paper);color:var(--ink);margin:0;line-height:1.55;font-size:15px}
 h1,h2,h3{font-family:'Sora','Segoe UI',Arial,sans-serif;line-height:1.25;margin:0 0 10px}
 h2{font-size:21px} h3{font-size:17px}
 a{color:var(--blue-2)}
 a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,button:focus-visible{outline:3px solid var(--gold);outline-offset:2px}

 /* top bar */
 .top{background:var(--ink);position:sticky;top:0;z-index:20}
 .top .in{max-width:1280px;margin:0 auto;padding:0 20px;display:flex;align-items:center;gap:6px;min-height:62px}
 .brand{display:flex;align-items:center;gap:10px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;text-decoration:none;margin-right:18px;white-space:nowrap}
 .logo{width:34px;height:34px;border-radius:10px;background:var(--gold);color:var(--ink);display:grid;place-items:center;font-weight:800}
 .menu{display:flex;gap:4px;overflow-x:auto}
 .menu a{color:#cfe0f7;text-decoration:none;padding:9px 14px;border-radius:10px;font-weight:600;font-size:14.5px;white-space:nowrap;display:flex;gap:7px;align-items:center}
 .menu a:hover{background:#ffffff14;color:#fff}
 .menu a.on{background:#ffffff1f;color:#fff;box-shadow:inset 0 -3px 0 var(--gold)}
 .me{margin-left:auto;display:flex;align-items:center;gap:12px;color:#fff;white-space:nowrap}
 .av{width:36px;height:36px;border-radius:50%;background:var(--gold);color:var(--ink);display:grid;place-items:center;font:800 13px 'Sora',sans-serif}
 .who{line-height:1.2;font-size:14px;font-weight:700}
 .who small{display:block;font-weight:500;color:#9db4d6;font-size:12px;text-transform:capitalize}
 .out{color:#fff;text-decoration:none;border:1px solid #ffffff40;padding:7px 14px;border-radius:10px;font-weight:600;font-size:14px}
 .out:hover{background:#ffffff1a}

 /* page title band */
 .band{background:radial-gradient(800px 260px at 90% -40%,#2a6fd0 0,transparent 60%),var(--blue);color:#fff;padding:26px 0 56px}
 .band .in{max-width:1280px;margin:0 auto;padding:0 20px}
 .band h1{font-size:clamp(24px,3.4vw,32px);font-weight:800;letter-spacing:-.01em;margin:0}
 .band p{margin:6px 0 0;color:#cfe0f7}

 .wrap{max-width:1280px;margin:-34px auto 40px;padding:0 20px;position:relative}

 /* 'Back' link that sits on the blue band */
 .wrap > p:first-child{margin:0 0 14px}
 .wrap > p:first-child a{color:#fff;font-weight:600;text-decoration:none}

 /* cards and stats */
 .card{background:var(--white);padding:22px;border-radius:18px;border:1px solid var(--line);box-shadow:0 14px 34px -24px #0d224099;margin-bottom:18px}
 .stats{display:flex;gap:14px;flex-wrap:wrap}
 .stat{background:var(--white);padding:16px 22px;border-radius:16px;border:1px solid var(--line);border-top:4px solid var(--blue);
       box-shadow:0 14px 34px -24px #0d224099;min-width:150px;color:var(--muted);font-size:14px;font-weight:600}
 .stat b{font-family:'Sora',sans-serif;font-size:26px;display:block;color:var(--ink)}

 /* tables */
 .tablewrap{overflow-x:auto}
 table{width:100%;border-collapse:collapse}
 th,td{padding:11px 10px;text-align:left;font-size:14px;border-bottom:1px solid #eaeff6}
 th{background:var(--sky);color:var(--blue);font-weight:700;font-size:13px}
 th:first-child{border-radius:10px 0 0 10px} th:last-child{border-radius:0 10px 10px 0}
 tbody tr:hover td,table tr:hover td{background:#f6f9fe}

 /* forms */
 input,select,textarea{padding:10px 12px;border:1.5px solid var(--line);border-radius:10px;font:inherit;color:var(--ink);background:#fff;max-width:100%}
 input:focus,select:focus,textarea:focus{border-color:var(--blue-2);outline:none;box-shadow:0 0 0 3px #1d5fb833}
 textarea{width:100%;resize:vertical}
 input[type=checkbox]{accent-color:var(--blue)}
 button{background:var(--blue);color:#fff;border:0;padding:10px 20px;border-radius:10px;cursor:pointer;font:700 14px 'Sora',sans-serif;transition:background .15s}
 button:hover{background:var(--blue-2)}
 td button{padding:7px 12px;font-size:13px}
 label{font-weight:600}
 small{color:var(--muted)}

 /* messages and conversation */
 .ok{background:#e6f4ea;color:var(--green);border:1px solid #bfe0c6;padding:12px 16px;border-radius:12px;font-weight:600;margin-bottom:16px}
 .reply{background:var(--sky);padding:12px 16px;border-radius:4px 14px 14px 14px;margin:10px 0}
 .internal{background:#fff6dd;border:1px dashed #e8c15a}
 .badge{display:inline-block;white-space:nowrap;padding:3px 11px;border-radius:999px;font-size:12px;font-weight:700;color:#fff}

 @media (max-width:820px){
  .top .in{flex-wrap:wrap;padding:10px 16px}
  .menu{order:3;width:100%}
  .who{display:none}
  .brand{margin-right:auto}
  .me{margin-left:0}
 }
</style></head><body>

<header class="top"><div class="in">
 <a class="brand" href="index.php"><span class="logo">K</span> KSTVET Staff</a>
 <nav class="menu">
  <?= nav_link('index.php', 'Cases', '📋', $cur, ['case.php']) ?>
  <?php if ($me['role'] !== 'officer') echo nav_link('dashboard.php', 'Dashboard', '📊', $cur); ?>
  <?php if ($me['role'] === 'admin') { echo nav_link('users.php', 'Users', '👥', $cur); echo nav_link('keywords.php', 'Keywords', '🏷️', $cur); } ?>
 </nav>
 <div class="me">
  <span class="av"><?= e($initials) ?></span>
  <span class="who"><?= e($me['full_name']) ?><small><?= e($me['role']) ?></small></span>
  <a class="out" href="logout.php">Log out</a>
 </div>
</div></header>

<div class="band"><div class="in">
 <h1><?= e($pageTitle) ?></h1>
 <?php if ($pageSub): ?><p><?= e($pageSub) ?></p><?php endif; ?>
</div></div>

<div class="wrap">