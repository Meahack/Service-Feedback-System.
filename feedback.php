<?php
require 'config.php';
$as   = (($_GET['as'] ?? '') === 'student') ? 'student' : 'guest';
$ref  = trim($_GET['ref'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KSTVET Feedback</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
 :root{--ink:#0d2240;--blue:#14407a;--blue-2:#1d5fb8;--sky:#e8f0fb;--gold:#f0b429;
       --paper:#f7f8fb;--line:#dde4ef;--muted:#53627a;--white:#fff}
 *{box-sizing:border-box}
 body{margin:0;font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;color:var(--ink);background:var(--paper);line-height:1.6}
 h1,h2,h3{font-family:'Sora','Segoe UI',Arial,sans-serif;line-height:1.2;margin:0}
 a{color:var(--blue-2)}
 a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible,button:focus-visible{outline:3px solid var(--gold);outline-offset:2px}

 .shell{min-height:100vh;display:grid;grid-template-columns:380px 1fr}

 /* left side */
 aside{background:radial-gradient(700px 400px at 100% 0,#2a6fd0 0,transparent 60%),var(--ink);color:#fff;
       padding:32px 36px;display:flex;flex-direction:column;gap:28px;position:sticky;top:0;height:100vh}
 .brand{display:flex;align-items:center;gap:10px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;text-decoration:none}
 .logo{width:36px;height:36px;border-radius:10px;background:var(--gold);color:var(--ink);display:grid;place-items:center;font-weight:800}
 aside h2{font-size:26px;font-weight:800;letter-spacing:-.01em}
 aside p{color:#cfe0f7;margin:8px 0 0;font-size:15px}
 .tips{list-style:none;margin:0;padding:0;display:grid;gap:14px}
 .tips li{display:flex;gap:12px;font-size:14.5px;color:#d7e4f7}
 .tips .d{flex:none;width:30px;height:30px;border-radius:9px;background:#ffffff1a;display:grid;place-items:center}
 .tips b{display:block;color:#fff}
 .side-foot{margin-top:auto;font-size:14px}
 .side-foot a{color:#fff}

 /* right side */
 main{padding:36px 24px 60px;display:flex;justify-content:center}
 .form-wrap{width:100%;max-width:680px}
 .topline{display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:600;margin-bottom:22px}
 .topline a{text-decoration:none}
 .role{display:inline-block;background:var(--sky);color:var(--blue);padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700}
 h1{font-size:clamp(26px,3.6vw,34px);font-weight:800;letter-spacing:-.02em}
 .lead{color:var(--muted);margin:8px 0 26px}

 form{background:var(--white);border:1px solid var(--line);border-radius:20px;padding:28px;box-shadow:0 18px 40px -26px #0d224088}
 fieldset{border:0;margin:0 0 22px;padding:0}
 legend,label.f{font-weight:700;font-size:14.5px;margin-bottom:8px;display:block;padding:0}

 /* type tiles */
 .tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
 .tile input{position:absolute;opacity:0;pointer-events:none}
 .tile label{display:flex;flex-direction:column;align-items:center;gap:4px;text-align:center;cursor:pointer;
   padding:14px 6px;border:1.5px solid var(--line);border-radius:14px;font-weight:700;font-size:13.5px;transition:border-color .15s,background .15s}
 .tile label .e{font-size:24px}
 .tile label:hover{border-color:var(--blue-2)}
 .tile input:checked + label{border-color:var(--blue);background:var(--sky);box-shadow:inset 0 0 0 1px var(--blue)}
 .tile input:focus-visible + label{outline:3px solid var(--gold);outline-offset:2px}

 .field{margin-bottom:20px}
 input[type=text],input[type=email],input:not([type]),textarea{width:100%;padding:13px 14px;border:1.5px solid var(--line);
   border-radius:12px;font:inherit;color:var(--ink);background:#fff;transition:border-color .15s}
 input:focus,textarea:focus{border-color:var(--blue-2);outline:none;box-shadow:0 0 0 3px #1d5fb833}
 textarea{resize:vertical;min-height:150px}
 .hint{display:flex;justify-content:space-between;font-size:13px;color:var(--muted);margin-top:6px}
 .opt{font-weight:500;color:var(--muted)}

 .about{border-top:1px dashed var(--line);padding-top:22px;margin-top:6px}
 .about h3{font-size:16px;margin-bottom:4px}
 .about .sub{margin:0 0 16px;color:var(--muted);font-size:14px}
 .row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
 #contact{transition:opacity .2s}
 #contact.off{opacity:.35;pointer-events:none}

 .anon{display:flex;gap:12px;align-items:flex-start;background:var(--paper);border:1.5px solid var(--line);border-radius:14px;padding:14px 16px;cursor:pointer;margin-bottom:22px}
 .anon input{width:20px;height:20px;margin-top:2px;accent-color:var(--blue)}
 .anon b{display:block;font-size:14.5px}
 .anon span{font-size:13px;color:var(--muted)}

 button.go{width:100%;background:var(--blue);color:#fff;border:0;border-radius:14px;padding:16px;font:700 16px 'Sora',sans-serif;cursor:pointer;transition:background .15s}
 button.go:hover{background:var(--blue-2)}
 .fine{text-align:center;font-size:13px;color:var(--muted);margin:12px 0 0}

 /* success */
 .done{background:var(--white);border:1px solid var(--line);border-radius:20px;padding:36px 28px;text-align:center;box-shadow:0 18px 40px -26px #0d224088}
 .tick{width:64px;height:64px;border-radius:50%;background:#2e7d32;color:#fff;font-size:32px;display:grid;place-items:center;margin:0 auto 16px}
 .refbox{display:inline-flex;align-items:center;gap:12px;background:var(--sky);border:1.5px dashed var(--blue);border-radius:14px;padding:12px 18px;margin:18px 0 8px}
 .refbox code{font:700 22px 'Sora',monospace;letter-spacing:.04em;color:var(--blue)}
 .refbox button{border:0;background:var(--blue);color:#fff;border-radius:8px;padding:7px 12px;font-weight:700;cursor:pointer}
 .btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:22px}
 .btn{padding:12px 20px;border-radius:12px;text-decoration:none;font-weight:700;border:1.5px solid var(--blue);color:var(--blue)}
 .btn.fill{background:var(--blue);color:#fff}

 @media (max-width:900px){
  .shell{grid-template-columns:1fr}
  aside{position:static;height:auto;padding:22px 20px;gap:16px}
  .tips,.side-foot{display:none}
  aside h2{font-size:21px}
 }
 @media (max-width:560px){
  .tiles{grid-template-columns:1fr 1fr}
  .row2{grid-template-columns:1fr}
  form{padding:20px}
 }
</style>
</head>
<body>
<div class="shell">

 <aside>
  <a class="brand" href="index.php"><span class="logo">K</span> KSTVET Feedback</a>
  <div>
   <h2>Your feedback helps us improve.</h2>
   <p>Tell us what happened or what you would like to see. It takes about two minutes.</p>
  </div>
  <ul class="tips">
   <li><span class="d">🔎</span><div><b>Be specific</b>Include the place, date and people involved so we can act quickly.</div></li>
   <li><span class="d">🔒</span><div><b>Your choice to be named</b>Tick the anonymous box and we store no contact details.</div></li>
   <li><span class="d">⏱️</span><div><b>Reply within 48 hours</b>An officer is assigned automatically and you can follow progress online.</div></li>
  </ul>
  <div class="side-foot">Already sent something? <a href="track.php">Track your case</a></div>
 </aside>

 <main>
  <div class="form-wrap">
   <div class="topline">
    <a href="index.php">&larr; Home</a>
    <span class="role"><?= $as === 'student' ? '🎓 Student' : '👤 Guest' ?></span>
   </div>

   <?php if ($ref !== ''): ?>
    <div class="done">
     <div class="tick">✓</div>
     <h1>Thank you. We have your message.</h1>
     <p class="lead" style="margin-bottom:0">Save this reference number. You need it to track your case and see replies.</p>
     <div class="refbox"><code id="ref"><?= e($ref) ?></code><button type="button" onclick="copyRef(this)">Copy</button></div>
     <div class="btns">
      <a class="btn fill" href="track.php?ref=<?= urlencode($ref) ?>">Track this case</a>
      <a class="btn" href="feedback.php?as=<?= e($as) ?>">Send another</a>
      <a class="btn" href="index.php">Home</a>
     </div>
    </div>

   <?php else: ?>
    <h1><?= $as === 'student' ? 'Student feedback' : 'Guest feedback' ?></h1>
    <p class="lead">Share a suggestion, complaint, compliment or service feedback.</p>

    <form method="post" action="submit.php">
     <input type="hidden" name="as" value="<?= e($as) ?>">

     <fieldset>
      <legend>What would you like to send?</legend>
      <div class="tiles">
       <div class="tile"><input type="radio" id="t1" name="type" value="suggestion" required checked>
        <label for="t1"><span class="e">💡</span>Suggestion</label></div>
       <div class="tile"><input type="radio" id="t2" name="type" value="complaint">
        <label for="t2"><span class="e">⚠️</span>Complaint</label></div>
       <div class="tile"><input type="radio" id="t3" name="type" value="compliment">
        <label for="t3"><span class="e">👏</span>Compliment</label></div>
       <div class="tile"><input type="radio" id="t4" name="type" value="service_feedback">
        <label for="t4"><span class="e">📝</span>Service feedback</label></div>
      </div>
     </fieldset>

     <div class="field">
      <label class="f" for="subject">Subject</label>
      <input id="subject" name="subject" required maxlength="200" placeholder="Short summary, e.g. Wi-Fi not working in computer lab">
     </div>

     <div class="field">
      <label class="f" for="message">Your message</label>
      <textarea id="message" name="message" required maxlength="3000" placeholder="Tell us what happened or what you suggest. Include place, date and any details that help."></textarea>
      <div class="hint"><span>The more detail you give, the faster we can help.</span><span id="count">0 / 3000</span></div>
     </div>

     <div class="about">
      <h3>About you</h3>
      <p class="sub">All of this is optional. We use it only to reply to you.</p>

      <label class="anon">
       <input type="checkbox" name="anon" value="1" id="anon">
       <div><b>Send anonymously</b><span>No name, email, phone or admission number will be saved.</span></div>
      </label>

      <div id="contact">
       <?php if ($as === 'student'): ?>
        <div class="field">
         <label class="f" for="adm">Admission number <span class="opt">(optional)</span></label>
         <input id="adm" name="admission_no" maxlength="40">
        </div>
       <?php endif; ?>
       <div class="row2">
        <div class="field">
         <label class="f" for="name">Your name <span class="opt">(optional)</span></label>
         <input id="name" name="name" autocomplete="name">
        </div>
        <div class="field">
         <label class="f" for="phone">Phone <span class="opt">(optional)</span></label>
         <input id="phone" name="phone" autocomplete="tel">
        </div>
       </div>
       <div class="field">
        <label class="f" for="email">Email <span class="opt">(optional, for updates)</span></label>
        <input id="email" type="email" name="email" autocomplete="email">
       </div>
      </div>
     </div>

     <button class="go" type="submit">Send feedback</button>
     <p class="fine">You will get a reference number to track your case.</p>
    </form>
   <?php endif; ?>
  </div>
 </main>
</div>

<script>
 // Character counter
 const msg = document.getElementById('message'), cnt = document.getElementById('count');
 if (msg) msg.addEventListener('input', () => cnt.textContent = msg.value.length + ' / 3000');

 // Anonymous: grey out and disable personal fields so nothing is sent
 const anon = document.getElementById('anon'), box = document.getElementById('contact');
 if (anon) anon.addEventListener('change', () => {
   box.classList.toggle('off', anon.checked);
   box.querySelectorAll('input').forEach(i => i.disabled = anon.checked);
 });

 // Copy reference number
 function copyRef(btn) {
   const t = document.getElementById('ref').textContent;
   (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(
     () => btn.textContent = 'Copied', () => { btn.textContent = 'Select and copy'; });
 }
</script>
</body>
</html>