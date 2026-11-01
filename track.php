<?php
require 'config.php';
$case = null; $replies = [];
$refInput = trim($_GET['ref'] ?? '');

if ($refInput !== '') {
  $st = $pdo->prepare("SELECT f.*, d.name AS dept, c.name AS category FROM feedback f
        LEFT JOIN departments d ON d.id=f.department_id
        LEFT JOIN categories c ON c.id=f.category_id WHERE ref_no=?");
  $st->execute([$refInput]);
  $case = $st->fetch();
  if ($case) {
    // Rating
    if ($_SERVER['REQUEST_METHOD']==='POST' && in_array($case['status'],['resolved','closed']) && !$case['rating']) {
      $r = max(1,min(5,(int)($_POST['rating'] ?? 5)));
      $pdo->prepare("UPDATE feedback SET rating=?, rating_comment=? WHERE id=?")
          ->execute([$r, substr($_POST['comment']??'',0,255), $case['id']]);
      header('Location: track.php?ref='.urlencode($case['ref_no']).'&rated=1'); exit;
    }
    $q = $pdo->prepare("SELECT r.message, r.created_at, u.full_name FROM responses r
                        JOIN users u ON u.id=r.user_id
                        WHERE r.feedback_id=? AND r.is_public=1 ORDER BY r.created_at");
    $q->execute([$case['id']]);
    $replies = $q->fetchAll();
  }
}

$labels = ['suggestion'=>'Suggestion','complaint'=>'Complaint','compliment'=>'Compliment','service_feedback'=>'Service feedback'];
$icons  = ['suggestion'=>'💡','complaint'=>'⚠️','compliment'=>'👏','service_feedback'=>'📝'];
$stages = ['new'=>'Received','assigned'=>'Assigned','in_progress'=>'In progress','resolved'=>'Resolved','closed'=>'Closed'];
$stageKeys = array_keys($stages);
$current = $case ? array_search($case['status'], $stageKeys) : 0;
$messages = [
  'new'         => 'We have received your message and it is waiting to be assigned.',
  'assigned'    => 'An officer has been assigned and will review your message soon.',
  'in_progress' => 'An officer is working on your case.',
  'resolved'    => 'Your case has been resolved. Please tell us how we did.',
  'closed'      => 'This case is closed. Thank you for helping us improve.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Track case | KSTVET</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
 :root{--ink:#0d2240;--blue:#14407a;--blue-2:#1d5fb8;--sky:#e8f0fb;--gold:#f0b429;
       --paper:#f7f8fb;--line:#dde4ef;--muted:#53627a;--white:#fff;--green:#2e7d32}
 *{box-sizing:border-box}
 body{margin:0;font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;color:var(--ink);background:var(--paper);line-height:1.6}
 h1,h2,h3{font-family:'Sora','Segoe UI',Arial,sans-serif;line-height:1.2;margin:0}
 a{color:var(--blue-2)}
 a:focus-visible,input:focus-visible,textarea:focus-visible,button:focus-visible{outline:3px solid var(--gold);outline-offset:2px}
 .wrap{max-width:760px;margin:0 auto;padding:0 20px}

 .hero{background:radial-gradient(900px 420px at 90% -20%,#2a6fd0 0,transparent 60%),var(--ink);color:#fff;padding-bottom:84px}
 .bar{display:flex;justify-content:space-between;align-items:center;padding:18px 0}
 .brand{display:flex;align-items:center;gap:10px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;text-decoration:none}
 .logo{width:36px;height:36px;border-radius:10px;background:var(--gold);color:var(--ink);display:grid;place-items:center;font-weight:800}
 .bar a.home{color:#cfe0f7;text-decoration:none;font-weight:600;font-size:15px}
 .hero h1{font-size:clamp(28px,4.5vw,40px);font-weight:800;letter-spacing:-.02em;margin-top:18px}
 .hero p{color:#cfe0f7;margin:8px 0 0}

 .search{margin-top:-34px;display:flex;gap:10px;background:var(--white);padding:10px;border-radius:16px;
         box-shadow:0 18px 40px -20px #0d224088;border:1px solid var(--line)}
 .search input{flex:1;border:0;font:600 16px 'Manrope',sans-serif;padding:12px 14px;border-radius:10px;min-width:0;color:var(--ink)}
 .search input:focus{outline:none}
 .search button{background:var(--blue);color:#fff;border:0;border-radius:12px;padding:0 26px;font:700 15px 'Sora',sans-serif;cursor:pointer}
 .search button:hover{background:var(--blue-2)}

 .card{background:var(--white);border:1px solid var(--line);border-radius:20px;padding:26px;margin-top:20px;box-shadow:0 18px 40px -30px #0d224088}
 .notfound{text-align:center;padding:34px 24px}
 .notfound .big{font-size:40px}
 .help{margin-top:20px;text-align:center;color:var(--muted);font-size:14px}

 .head{display:flex;gap:14px;align-items:flex-start}
 .ic{flex:none;width:50px;height:50px;border-radius:14px;background:var(--sky);display:grid;place-items:center;font-size:26px}
 .head h2{font-size:21px}
 .meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
 .chip{background:var(--paper);border:1px solid var(--line);border-radius:999px;padding:3px 12px;font-size:13px;font-weight:600;color:var(--muted)}
 .chip.ref{background:var(--sky);color:var(--blue);border-color:#c5d8f2}
 .status-line{margin:20px 0 6px;padding:14px 16px;border-radius:14px;background:var(--sky);color:var(--blue);font-weight:600}
 .status-line.good{background:#e6f4ea;color:var(--green)}

 /* stepper */
 .steps{list-style:none;display:flex;margin:26px 0 8px;padding:0}
 .steps li{flex:1;text-align:center;position:relative;font-size:12.5px;font-weight:600;color:var(--muted)}
 .steps li:before{content:"";display:block;width:26px;height:26px;border-radius:50%;background:var(--white);border:2px solid var(--line);margin:0 auto 8px;position:relative;z-index:1}
 .steps li:after{content:"";position:absolute;top:12px;left:-50%;width:100%;height:2px;background:var(--line)}
 .steps li:first-child:after{display:none}
 .steps li.done{color:var(--ink)}
 .steps li.done:before{background:var(--blue);border-color:var(--blue)}
 .steps li.done:after{background:var(--blue)}
 .steps li.now:before{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 5px #f0b42940}

 h3.sec{font-size:17px;margin:0 0 14px}
 .msg{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:14px 16px;color:var(--ink);white-space:pre-line}
 .bubble{background:var(--sky);border-radius:4px 16px 16px 16px;padding:12px 16px;margin-bottom:12px}
 .bubble .who{font-weight:700;font-size:14px}
 .bubble .when{font-size:12.5px;color:var(--muted)}
 .bubble p{margin:6px 0 0}
 .empty{color:var(--muted);font-size:14.5px;margin:0}

 /* rating */
 .stars{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px;margin-bottom:12px}
 .stars input{position:absolute;opacity:0;pointer-events:none}
 .stars label{font-size:38px;color:#cdd5e3;cursor:pointer;line-height:1;transition:color .1s}
 .stars label:hover,.stars label:hover ~ label,.stars input:checked ~ label{color:var(--gold)}
 .stars input:focus-visible + label{outline:3px solid var(--blue-2);border-radius:6px}
 textarea{width:100%;padding:12px 14px;border:1.5px solid var(--line);border-radius:12px;font:inherit;resize:vertical;min-height:80px}
 textarea:focus{border-color:var(--blue-2);outline:none;box-shadow:0 0 0 3px #1d5fb833}
 .go{margin-top:12px;background:var(--blue);color:#fff;border:0;border-radius:12px;padding:13px 26px;font:700 15px 'Sora',sans-serif;cursor:pointer}
 .go:hover{background:var(--blue-2)}
 .thanks{background:#e6f4ea;color:var(--green);padding:14px 16px;border-radius:14px;font-weight:600}
 footer{text-align:center;color:var(--muted);font-size:14px;padding:30px 0 50px}

 @media (max-width:560px){
  .steps li{font-size:11px}
  .search{flex-direction:column}
  .search button{padding:12px}
 }
</style>
</head>
<body>

<header class="hero">
 <div class="wrap">
  <div class="bar">
   <a class="brand" href="index.php"><span class="logo">K</span> KSTVET Feedback</a>
   <a class="home" href="index.php">&larr; Home</a>
  </div>
  <h1>Track your case</h1>
  <p>Enter the reference number you received when you submitted.</p>
 </div>
</header>

<div class="wrap">
 <form class="search" method="get">
  <input name="ref" placeholder="e.g. KST-26-AB12C" value="<?= e($refInput) ?>" aria-label="Reference number" required>
  <button type="submit">Check status</button>
 </form>

 <?php if ($refInput !== '' && !$case): ?>
  <div class="card notfound">
   <div class="big">🔍</div>
   <h2 style="margin:8px 0 6px">No case found</h2>
   <p class="empty">We could not find <b><?= e($refInput) ?></b>. Check for typing mistakes. Reference numbers look like KST-26-AB12C.</p>
  </div>
 <?php endif; ?>

 <?php if ($case): ?>
  <div class="card">
   <div class="head">
    <div class="ic"><?= $icons[$case['type']] ?? '📝' ?></div>
    <div>
     <h2><?= e($case['subject']) ?></h2>
     <div class="meta">
      <span class="chip ref"><?= e($case['ref_no']) ?></span>
      <span class="chip"><?= e($labels[$case['type']] ?? $case['type']) ?></span>
      <span class="chip">Handled by <?= e($case['dept'] ?? 'Reception') ?></span>
      <span class="chip">Submitted <?= e(date('d M Y, H:i', strtotime($case['created_at']))) ?></span>
     </div>
    </div>
   </div>

   <div class="status-line <?= in_array($case['status'],['resolved','closed'])?'good':'' ?>">
    <?= e($messages[$case['status']] ?? '') ?>
   </div>

   <ol class="steps" aria-label="Case progress">
    <?php foreach ($stageKeys as $i => $k): ?>
     <li class="<?= $i < $current ? 'done' : ($i === $current ? 'done now' : '') ?>"><?= e($stages[$k]) ?></li>
    <?php endforeach; ?>
   </ol>
  </div>

  <div class="card">
   <h3 class="sec">Your message</h3>
   <div class="msg"><?= e($case['message']) ?></div>
  </div>

  <div class="card">
   <h3 class="sec">Replies from KSTVET</h3>
   <?php foreach ($replies as $r): ?>
    <div class="bubble">
     <span class="who"><?= e($r['full_name']) ?></span>
     <span class="when"> &middot; <?= e(date('d M Y, H:i', strtotime($r['created_at']))) ?></span>
     <p><?= nl2br(e($r['message'])) ?></p>
    </div>
   <?php endforeach; ?>
   <?php if (!$replies): ?><p class="empty">No reply yet. We aim to respond within 48 hours. Check back soon.</p><?php endif; ?>
  </div>

  <?php if (in_array($case['status'],['resolved','closed'])): ?>
   <div class="card">
    <h3 class="sec">How satisfied are you?</h3>
    <?php if ($case['rating']): ?>
     <div class="thanks">Thank you for rating us <?= (int)$case['rating'] ?>/5<?= !empty($_GET['rated']) ? '. Your feedback has been saved.' : '.' ?></div>
    <?php else: ?>
     <form method="post">
      <div class="stars">
       <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" name="rating" id="s<?= $i ?>" value="<?= $i ?>" <?= $i===5?'required':'' ?>>
        <label for="s<?= $i ?>" title="<?= $i ?> star<?= $i>1?'s':'' ?>">★</label>
       <?php endfor; ?>
      </div>
      <textarea name="comment" maxlength="255" placeholder="Anything else you would like to tell us? (optional)"></textarea>
      <button class="go" type="submit">Submit rating</button>
     </form>
    <?php endif; ?>
   </div>
  <?php endif; ?>
 <?php endif; ?>

 <?php if ($refInput === ''): ?>
  <p class="help">Lost your reference number? Visit Reception, or <a href="feedback.php">submit a new message</a>.</p>
 <?php endif; ?>
</div>

<footer>&copy; <?= date('Y') ?> KSTVET &middot; <a href="index.php">Home</a> &middot; <a href="feedback.php">Send feedback</a></footer>
</body>
</html>