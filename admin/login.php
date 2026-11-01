<?php
require __DIR__ . '/../config.php';

$as = $_GET['as'] ?? 'staff';
if (!in_array($as, ['admin','reception','staff'])) $as = 'staff';
$titles = ['admin'=>'Admin login','reception'=>'Reception login','staff'=>'Staff login'];
$icons  = ['admin'=>'🔐','reception'=>'🛎️','staff'=>'👥'];
$blurbs = [
  'admin'     => 'Manage users, keywords and view the full management dashboard.',
  'reception' => 'Receive, respond to and follow up customer service cases.',
  'staff'     => 'Handle the cases assigned to your department.',
];

$error = '';
$emailVal = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emailVal = trim($_POST['email'] ?? '');
  $st = $pdo->prepare("SELECT * FROM users WHERE email=? AND active=1");
  $st->execute([$emailVal]);
  $u = $st->fetch();

  if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    $okRole = true;
    if ($as === 'admin'     && $u['role'] !== 'admin') $okRole = false;
    if ($as === 'reception' && !($u['department_id'] == 1 && $u['role'] !== 'admin')) $okRole = false;

    if ($okRole) {
      session_regenerate_id(true);
      $_SESSION['user'] = ['id'=>$u['id'],'full_name'=>$u['full_name'],
                           'role'=>$u['role'],'department_id'=>$u['department_id']];
      header('Location: index.php'); exit;
    }
    $error = 'This account cannot log in here. Go back and choose the right option.';
  } else {
    $error = 'Wrong email or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titles[$as]) ?> | KSTVET</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
 :root{--ink:#0d2240;--blue:#14407a;--blue-2:#1d5fb8;--sky:#e8f0fb;--gold:#f0b429;
       --paper:#f7f8fb;--line:#dde4ef;--muted:#53627a;--white:#fff;--red:#c62828}
 *{box-sizing:border-box}
 body{margin:0;font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;color:var(--ink);background:var(--paper);line-height:1.6}
 h1,h2{font-family:'Sora','Segoe UI',Arial,sans-serif;line-height:1.2;margin:0}
 a{color:var(--blue-2)}
 a:focus-visible,input:focus-visible,button:focus-visible{outline:3px solid var(--gold);outline-offset:2px}

 .shell{min-height:100vh;display:grid;grid-template-columns:1.05fr .95fr}

 /* left art panel */
 .art{position:relative;overflow:hidden;color:#fff;padding:36px 48px;display:flex;flex-direction:column;
      background:radial-gradient(800px 500px at 90% 0,#2a6fd0 0,transparent 60%),radial-gradient(600px 400px at 0 100%,#1d5fb866 0,transparent 60%),var(--ink)}
 .art:before,.art:after{content:"";position:absolute;border-radius:50%;border:1.5px solid #ffffff1f}
 .art:before{width:520px;height:520px;right:-180px;bottom:-160px}
 .art:after{width:340px;height:340px;right:-90px;bottom:-70px;border-color:#f0b4294d}
 .brand{display:flex;align-items:center;gap:10px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;text-decoration:none;position:relative;z-index:1}
 .logo{width:36px;height:36px;border-radius:10px;background:var(--gold);color:var(--ink);display:grid;place-items:center;font-weight:800}
 .art-body{margin:auto 0;position:relative;z-index:1;max-width:460px}
 .art-icon{width:74px;height:74px;border-radius:20px;background:#ffffff1a;border:1px solid #ffffff2e;display:grid;place-items:center;font-size:38px;margin-bottom:22px}
 .art h1{font-size:clamp(30px,3.6vw,42px);font-weight:800;letter-spacing:-.02em}
 .art p{color:#cfe0f7;font-size:17px;margin:14px 0 26px}
 .feats{list-style:none;margin:0;padding:0;display:grid;gap:10px}
 .feats li{display:flex;gap:10px;align-items:center;color:#d7e4f7;font-size:15px}
 .feats li:before{content:"✓";flex:none;width:22px;height:22px;border-radius:50%;background:var(--gold);color:var(--ink);font-weight:800;font-size:12px;display:grid;place-items:center}
 .art-foot{position:relative;z-index:1;font-size:13px;color:#9db4d6}

 /* right form panel */
 .side{display:flex;align-items:center;justify-content:center;padding:32px 24px}
 .box{width:100%;max-width:400px}
 .back{display:inline-block;margin-bottom:26px;text-decoration:none;font-weight:600;font-size:14px}
 .box h2{font-size:28px;font-weight:800;letter-spacing:-.01em}
 .box .sub{color:var(--muted);margin:6px 0 24px}
 .err{display:flex;gap:10px;background:#fdecea;color:var(--red);border:1px solid #f5c2bf;padding:12px 14px;border-radius:12px;font-weight:600;font-size:14.5px;margin-bottom:18px}
 label{display:block;font-weight:700;font-size:14.5px;margin-bottom:6px}
 .field{margin-bottom:18px}
 .input{position:relative}
 .input input{width:100%;padding:14px 16px;border:1.5px solid var(--line);border-radius:12px;font:inherit;color:var(--ink);background:#fff;transition:border-color .15s}
 .input input:focus{border-color:var(--blue-2);outline:none;box-shadow:0 0 0 3px #1d5fb833}
 .input.pw input{padding-right:78px}
 .toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:var(--sky);color:var(--blue);border:0;border-radius:8px;padding:6px 12px;font-weight:700;font-size:13px;cursor:pointer}
 .go{width:100%;background:var(--blue);color:#fff;border:0;border-radius:12px;padding:15px;font:700 16px 'Sora',sans-serif;cursor:pointer;margin-top:4px;transition:background .15s}
 .go:hover{background:var(--blue-2)}
 .others{margin-top:26px;padding-top:20px;border-top:1px solid var(--line);font-size:14px;color:var(--muted)}
 .others a{font-weight:700;text-decoration:none;margin-right:12px}
 .secure{margin-top:18px;font-size:13px;color:var(--muted);display:flex;gap:8px;align-items:center}

 @media (max-width:860px){
  .shell{grid-template-columns:1fr}
  .art{padding:22px 22px 28px}
  .art-body{margin:30px 0 4px}
  .art-icon{width:56px;height:56px;font-size:28px;margin-bottom:14px}
  .feats,.art-foot{display:none}
  .art p{font-size:15px;margin:8px 0 0}
 }
</style>
</head>
<body>
<div class="shell">

 <section class="art">
  <a class="brand" href="../index.php"><span class="logo">K</span> KSTVET Feedback</a>
  <div class="art-body">
   <div class="art-icon"><?= $icons[$as] ?></div>
   <h1><?= e($titles[$as]) ?></h1>
   <p><?= e($blurbs[$as]) ?></p>
   <ul class="feats">
    <li>Every case tracked from receipt to resolution</li>
    <li>Automatic routing to the right department</li>
    <li>Response times and satisfaction at a glance</li>
   </ul>
  </div>
  <div class="art-foot">Kenya School of TVET &middot; Suggestions, Complaints and Service Feedback System</div>
 </section>

 <main class="side">
  <div class="box">
   <a class="back" href="../index.php">&larr; Back to home</a>
   <h2>Welcome back</h2>
   <p class="sub">Sign in to continue to the staff portal.</p>

   <?php if ($error): ?><div class="err" role="alert"><span>⚠️</span><span><?= e($error) ?></span></div><?php endif; ?>

   <form method="post" autocomplete="on">
    <div class="field">
     <label for="email">Email address</label>
     <div class="input"><input id="email" type="email" name="email" value="<?= e($emailVal) ?>" placeholder="name@kstvet.ac.ke" required autofocus autocomplete="username"></div>
    </div>
    <div class="field">
     <label for="pw">Password</label>
     <div class="input pw">
      <input id="pw" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
      <button type="button" class="toggle" id="tg" aria-label="Show password">Show</button>
     </div>
    </div>
    <button class="go" type="submit">Sign in</button>
   </form>

   <div class="secure">🔒 Authorised KSTVET staff only. Activity is recorded.</div>

   <div class="others">
    Signing in as someone else?<br>
    <?php if ($as !== 'reception'): ?><a href="login.php?as=reception">Reception</a><?php endif; ?>
    <?php if ($as !== 'admin'): ?><a href="login.php?as=admin">Admin</a><?php endif; ?>
    <?php if ($as !== 'staff'): ?><a href="login.php?as=staff">Other departments</a><?php endif; ?>
   </div>
  </div>
 </main>
</div>

<script>
 const pw = document.getElementById('pw'), tg = document.getElementById('tg');
 tg.addEventListener('click', () => {
   const show = pw.type === 'password';
   pw.type = show ? 'text' : 'password';
   tg.textContent = show ? 'Hide' : 'Show';
   tg.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
 });
</script>
</body>
</html>