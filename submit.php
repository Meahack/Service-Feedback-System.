<?php
require 'config.php'; require 'functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

$type    = $_POST['type'] ?? '';
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
if (!in_array($type, ['suggestion','complaint','compliment','service_feedback']) || !$subject || !$message)
  die('Invalid submission.');

$as   = (($_POST['as'] ?? '') === 'student') ? 'student' : 'guest';
$anon = isset($_POST['anon']) ? 1 : 0;
$cat  = categorize($pdo, "$subject $message");
$dept = $cat['department_id'] ?? 1;       // default: Reception
$catId = $cat['category_id'] ?? null;
$officer = pickOfficer($pdo, $dept);
$status = $officer ? 'assigned' : 'new';
$ref = makeRef($pdo);

$st = $pdo->prepare("INSERT INTO feedback
 (ref_no,type,submitter_name,submitter_email,submitter_phone,is_anonymous,subject,message,
  category_id,department_id,assigned_to,priority,status,submitter_type,admission_no)
 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$st->execute([
  $ref,$type,
  $anon?null:trim($_POST['name']??''),
  $anon?null:trim($_POST['email']??''),
  $anon?null:trim($_POST['phone']??''),
  $anon,$subject,$message,$catId,$dept,$officer,
  detectPriority($type,"$subject $message"),$status,
  $as,
  ($as==='student' && !$anon) ? substr(trim($_POST['admission_no']??''),0,40) : null
]);
header('Location: feedback.php?as=' . $as . '&ref=' . urlencode($ref));