<?php
// Live numbers for the stats strip. If the database is unreachable, the page still loads.
$stats = ['total' => 0, 'resolved' => 0, 'rating' => null];
try {
  require __DIR__ . '/config.php';
  $r = $pdo->query("SELECT COUNT(*) total,
        SUM(status IN ('resolved','closed')) resolved,
        ROUND(AVG(rating),1) rating FROM feedback")->fetch();
  $stats = ['total' => (int)$r['total'], 'resolved' => (int)$r['resolved'], 'rating' => $r['rating']];
} catch (Throwable $ex) { /* ignore */ }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>KSTVET Feedback System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
 :root{
  --ink:#0d2240; --blue:#14407a; --blue-2:#1d5fb8; --sky:#e8f0fb;
  --gold:#f0b429; --paper:#f7f8fb; --line:#dde4ef; --muted:#53627a; --white:#fff;
 }
 *{box-sizing:border-box}
 html{scroll-behavior:smooth}
 body{margin:0;font-family:'Manrope',system-ui,Segoe UI,Arial,sans-serif;color:var(--ink);background:var(--paper);line-height:1.6}
 h1,h2,h3{font-family:'Sora','Segoe UI',Arial,sans-serif;line-height:1.15;margin:0}
 a{color:var(--blue-2)}
 a:focus-visible,summary:focus-visible{outline:3px solid var(--gold);outline-offset:3px;border-radius:6px}
 .wrap{max-width:1120px;margin:0 auto;padding:0 20px}

 /* top bar */
 .bar{display:flex;align-items:center;justify-content:space-between;padding:18px 0}
 .brand{display:flex;align-items:center;gap:10px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;text-decoration:none}
 .logo{width:36px;height:36px;border-radius:10px;background:var(--gold);color:var(--ink);display:grid;place-items:center;font-weight:800}
 .bar nav a{color:#cfe0f7;text-decoration:none;margin-left:22px;font-size:15px;font-weight:600}
 .bar nav a:hover{color:#fff}

 /* hero */
 .hero{background:radial-gradient(1200px 500px at 85% -10%,#2a6fd0 0%,transparent 60%),var(--ink);color:#fff;padding-bottom:90px}
 .hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:48px;align-items:center;padding-top:40px}
 .hero h1{font-size:clamp(34px,5vw,54px);font-weight:800;letter-spacing:-.02em}
 .hero p.lead{font-size:18px;color:#cfe0f7;max-width:34em;margin:18px 0 26px}
 .pills{display:flex;flex-wrap:wrap;gap:10px}
 .pills span{background:#ffffff14;border:1px solid #ffffff26;padding:7px 14px;border-radius:999px;font-size:14px;font-weight:600}
 .hero-anim{animation:rise .7s ease both}
 @keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

 /* access panel */
 .panel{background:var(--white);color:var(--ink);border-radius:20px;padding:24px;box-shadow:0 30px 60px -20px #000a}
 .panel h2{font-size:20px;margin-bottom:4px}
 .panel .sub{margin:0 0 16px;color:var(--muted);font-size:14px}
 .choices{display:grid;grid-template-columns:1fr 1fr;gap:12px}
 .choice{display:flex;flex-direction:column;gap:4px;padding:18px 16px;border-radius:14px;text-decoration:none;color:var(--ink);
   border:1.5px solid var(--line);background:var(--white);transition:border-color .15s,background .15s}
 .choice:hover{border-color:var(--blue-2);background:var(--sky)}
 .choice .ic{font-size:28px}
 .choice b{font-family:'Sora',sans-serif;font-size:17px}
 .choice small{color:var(--muted);font-size:13px;line-height:1.4}
 .choice.main{background:var(--blue);border-color:var(--blue);color:#fff}
 .choice.main small{color:#cfe0f7}
 .choice.main:hover{background:var(--blue-2);border-color:var(--blue-2)}
 .divider{display:flex;align-items:center;gap:12px;margin:18px 0 12px;color:var(--muted);font-size:13px;font-weight:600}
 .divider:before,.divider:after{content:"";flex:1;height:1px;background:var(--line)}
 .staff{display:grid;grid-template-columns:1fr 1fr;gap:10px}
 .staff a{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;border:1.5px solid var(--line);
   text-decoration:none;color:var(--ink);font-weight:700;font-size:15px}
 .staff a:hover{border-color:var(--ink);background:var(--paper)}
 .staff-more{display:block;margin-top:12px;text-align:center;font-size:14px}
 .track{margin-top:16px;padding-top:14px;border-top:1px solid var(--line);font-size:14px;text-align:center}

 /* stats strip overlapping hero */
 .stats{margin-top:-46px;position:relative;z-index:2}
 .stats .row{background:var(--white);border-radius:18px;box-shadow:0 14px 40px -18px #0d224066;display:grid;grid-template-columns:repeat(4,1fr)}
 .stats .item{padding:22px 24px;border-right:1px solid var(--line)}
 .stats .item:last-child{border-right:0}
 .stats b{display:block;font-family:'Sora',sans-serif;font-size:28px}
 .stats span{color:var(--muted);font-size:14px}

 section.block{padding:72px 0 0}
 section.block h2{font-size:clamp(26px,3.4vw,34px);margin-bottom:10px}
 section.block .intro{color:var(--muted);max-width:40em;margin:0 0 30px}

 /* feedback types */
 .types{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
 .type{padding:22px;border-radius:16px;background:var(--white);border:1px solid var(--line);border-top:5px solid var(--c)}
 .type h3{font-size:18px;margin:8px 0 6px}
 .type p{margin:0;color:var(--muted);font-size:14.5px}
 .type .e{font-size:26px}

 /* steps */
 .steps{display:grid;grid-template-columns:repeat(4,1fr);gap:0;counter-reset:s;position:relative}
 .step{padding:0 20px 0 0;position:relative}
 .step:before{counter-increment:s;content:counter(s);display:grid;place-items:center;width:42px;height:42px;border-radius:50%;
   background:var(--ink);color:#fff;font-family:'Sora',sans-serif;font-weight:700;margin-bottom:14px;position:relative;z-index:1}
 .step:after{content:"";position:absolute;top:20px;left:42px;right:0;height:2px;background:var(--line)}
 .step:last-child:after{display:none}
 .step h3{font-size:17px;margin-bottom:6px}
 .step p{margin:0;color:var(--muted);font-size:14.5px}

 /* promise + faq */
 .two{display:grid;grid-template-columns:1fr 1fr;gap:24px}
 .promise{background:var(--ink);color:#fff;border-radius:20px;padding:32px}
 .promise h2{color:#fff}
 .promise ul{padding:0;margin:18px 0 0;list-style:none;display:grid;gap:12px}
 .promise li{padding-left:30px;position:relative;color:#d7e4f7}
 .promise li:before{content:"✓";position:absolute;left:0;top:0;width:22px;height:22px;border-radius:50%;background:var(--gold);color:var(--ink);font-weight:800;font-size:13px;display:grid;place-items:center}
 details{background:var(--white);border:1px solid var(--line);border-radius:14px;padding:16px 18px;margin-bottom:10px}
 summary{cursor:pointer;font-weight:700;list-style:none;display:flex;justify-content:space-between;gap:12px}
 summary::-webkit-details-marker{display:none}
 summary:after{content:"+";font-size:20px;line-height:1;color:var(--blue-2)}
 details[open] summary:after{content:"–"}
 details p{margin:10px 0 0;color:var(--muted);font-size:14.5px}

 footer{margin-top:80px;background:var(--ink);color:#b9cae3;padding:34px 0;font-size:14px}
 footer .f{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
 footer a{color:#fff}

 @media (max-width:900px){
  .hero-grid,.two{grid-template-columns:1fr}
  .types,.steps{grid-template-columns:1fr 1fr;row-gap:30px}
  .step:after{display:none}
  .stats .row{grid-template-columns:1fr 1fr}
  .stats .item:nth-child(2){border-right:0}
  .stats .item:nth-child(-n+2){border-bottom:1px solid var(--line)}
  .bar nav a:not(.keep){display:none}
 }
 @media (max-width:520px){
  .choices,.staff,.types,.steps{grid-template-columns:1fr}
 }
 @media (prefers-reduced-motion:reduce){.hero-anim{animation:none}html{scroll-behavior:auto}}
</style>
</head>
<body>

<header class="hero">
 <div class="wrap">
  <div class="bar">
   <a class="brand" href="index.php"><span class="logo">K</span> KSTVET Feedback</a>
   <nav>
    <a href="#types">What you can send</a>
    <a href="#how">How it works</a>
    <a href="#faq">FAQ</a>
    <a class="keep" href="track.php">Track a case</a>
   </nav>
  </div>

  <div class="hero-grid">
   <div class="hero-anim">
    <h1>Tell us what is working, and what is not.</h1>
    <p class="lead">Send a suggestion, complaint, compliment or service feedback to KSTVET.
      Every message is logged, sent to the right department and followed until it is resolved.</p>
    <div class="pills">
     <span>Anonymous option</span>
     <span>Reference number for every case</span>
     <span>Reply within 48 hours</span>
    </div>
   </div>

   <div class="panel hero-anim" style="animation-delay:.12s">
    <h2>How would you like to continue?</h2>
    <p class="sub">Choose the option that describes you.</p>
    <div class="choices">
     <a class="choice main" href="feedback.php?as=student">
      <span class="ic">🎓</span><b>I am a student</b><small>Send feedback with or without your admission number</small>
     </a>
     <a class="choice" href="feedback.php?as=guest">
      <span class="ic">👤</span><b>I am a guest</b><small>Visitors, parents, partners and the public</small>
     </a>
    </div>

    <div class="divider">Staff only</div>
    <div class="staff">
     <a href="admin/login.php?as=reception">🛎️ Reception</a>
     <a href="admin/login.php?as=admin">🔐 Admin</a>
    </div>
    <a class="staff-more" href="admin/login.php?as=staff">Student Affairs, QA, ICT, Finance and other departments</a>

    <div class="track">Already submitted? <a href="track.php">Track your case</a></div>
   </div>
  </div>
 </div>
</header>

<div class="wrap stats">
 <div class="row">
  <div class="item"><b><?= $stats['total'] ?></b><span>Messages received</span></div>
  <div class="item"><b><?= $stats['resolved'] ?></b><span>Cases resolved</span></div>
  <div class="item"><b><?= $stats['rating'] !== null ? htmlspecialchars($stats['rating']) . '/5' : '–' ?></b><span>Average satisfaction</span></div>
  <div class="item"><b>48 hrs</b><span>Target for a first reply</span></div>
 </div>
</div>

<div class="wrap">

 <section class="block" id="types">
  <h2>What you can send us</h2>
  <p class="intro">Pick the type that fits. If you are unsure, choose the closest one and explain in your message. We sort it for you.</p>
  <div class="types">
   <div class="type" style="--c:#1d5fb8"><div class="e">💡</div><h3>Suggestion</h3><p>An idea to improve teaching, facilities, services or campus life.</p></div>
   <div class="type" style="--c:#c62828"><div class="e">⚠️</div><h3>Complaint</h3><p>Something went wrong and needs to be looked into and fixed.</p></div>
   <div class="type" style="--c:#2e7d32"><div class="e">👏</div><h3>Compliment</h3><p>Recognise a staff member or a service that did well.</p></div>
   <div class="type" style="--c:#ef6c00"><div class="e">📝</div><h3>Service feedback</h3><p>Tell us about your experience at reception, the library, ICT, finance and more.</p></div>
  </div>
 </section>

 <section class="block" id="how">
  <h2>What happens after you submit</h2>
  <p class="intro">You do not need to know which office to contact. The system routes your message for you.</p>
  <div class="steps">
   <div class="step"><h3>You submit</h3><p>Fill in a short form. Name, email and phone are optional.</p></div>
   <div class="step"><h3>It is sorted</h3><p>The system reads your message, picks a category and finds the responsible department.</p></div>
   <div class="step"><h3>An officer responds</h3><p>A staff member is assigned. Urgent matters such as harassment or theft are marked high priority.</p></div>
   <div class="step"><h3>You follow up</h3><p>Use your reference number to see replies, and rate the service once it is resolved.</p></div>
  </div>
 </section>

 <section class="block" id="faq">
  <div class="two">
   <div class="promise">
    <h2>Our commitment to you</h2>
    <ul>
     <li>Every message gets a reference number so nothing gets lost.</li>
     <li>Your first reply is targeted within 48 hours.</li>
     <li>Anonymous submissions are accepted. No name, email or phone is stored.</li>
     <li>Management reviews trends and response times to fix recurring problems.</li>
    </ul>
   </div>
   <div>
    <h2 style="margin-bottom:16px">Common questions</h2>
    <details><summary>Do I need to log in to send feedback?</summary>
     <p>No. Students and guests can submit without an account. Only staff log in.</p></details>
    <details><summary>Can I stay anonymous?</summary>
     <p>Yes. Tick "Submit anonymously" on the form. You can still track the case with your reference number, but we cannot contact you directly.</p></details>
    <details><summary>I lost my reference number. What do I do?</summary>
     <p>Reference numbers cannot be looked up without contact details. Submit the matter again and mention the earlier date, or visit Reception.</p></details>
    <details><summary>Who can see my message?</summary>
     <p>Only the officer and managers of the department handling your case, and system administrators.</p></details>
   </div>
  </div>
 </section>

</div>

<footer>
 <div class="wrap f">
  <div>© <?= date('Y') ?> KSTVET. Suggestions, Complaints and Service Feedback System.</div>
  <div><a href="track.php">Track a case</a> &nbsp;·&nbsp; <a href="admin/login.php?as=staff">Staff login</a></div>
 </div>
</footer>

</body>
</html>