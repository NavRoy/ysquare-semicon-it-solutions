<?php
/* ============================================================
   submit.php — YSQUARE Internship Application Backend
   Accepts POST only. Returns JSON { success, refId, error }.
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');

/* ── Guard: POST only ───────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

require_once __DIR__ . '/config.php';

/* ── Helper: sanitize ───────────────────────────────────── */
function clean(string $val): string {
    return htmlspecialchars(trim(strip_tags($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Collect & sanitize fields ─────────────────────────── */
$firstName   = clean($_POST['firstName']   ?? '');
$lastName    = clean($_POST['lastName']    ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone       = clean($_POST['phone']       ?? '');
$college     = clean($_POST['college']     ?? '');
$branch      = clean($_POST['branch']      ?? '');
$gradYear    = clean($_POST['gradYear']    ?? '');
$domain      = clean($_POST['domain']      ?? '');
$motivation  = clean($_POST['motivation']  ?? '');
$certify     = isset($_POST['certify']) ? true : false;

/* ── Validate ───────────────────────────────────────────── */
$errors = [];

if (!$firstName)                          $errors[] = 'First name is required.';
if (!$lastName)                           $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (!preg_match('/^[\+]?[\d\s\-\(\)]{7,15}$/', $phone)) $errors[] = 'A valid phone number is required.';
if (!$college)                            $errors[] = 'College / University is required.';
if (!$branch)                             $errors[] = 'Branch / Department is required.';
if (!in_array($gradYear, VALID_YEARS))    $errors[] = 'Please select a valid graduation year.';
if (!in_array($domain, VALID_DOMAINS))    $errors[] = 'Please select a valid domain.';
if (strlen($motivation) < 20)             $errors[] = 'Please tell us more about why you want to join YSQUARE (min 20 characters).';
if (!$certify)                            $errors[] = 'You must certify that the information is accurate.';

/* ── Validate & upload resume ───────────────────────────── */
$resumeOrigName = '';
$resumeSavedAs  = '';

if (empty($_FILES['resume']['name'])) {
    $errors[] = 'Please upload your resume.';
} else {
    $file     = $_FILES['resume'];
    $origName = basename($file['name']);
    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if ($file['error'] !== UPLOAD_ERR_OK)          $errors[] = 'File upload failed. Please try again.';
    elseif ($file['size'] > MAX_FILE_SIZE)          $errors[] = 'Resume must be under 5 MB.';
    elseif (!in_array($ext, ALLOWED_EXTS))          $errors[] = 'Only PDF, DOC or DOCX files are accepted.';
    elseif (!in_array($mimeType, ALLOWED_TYPES))    $errors[] = 'File type not permitted.';
    else {
        /* Safe filename: sanitize, add timestamp + random suffix */
        $safeName  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $savedName = $safeName . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $savedName)) {
            $errors[] = 'Could not save your resume. Please contact us directly.';
        } else {
            $resumeOrigName = $origName;
            $resumeSavedAs  = $savedName;
        }
    }
}

/* ── Return errors ──────────────────────────────────────── */
if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

/* ── Generate Reference ID ──────────────────────────────── */
$refId = REF_PREFIX . mt_rand(1000, 9999);

/* ── Submission metadata ────────────────────────────────── */
$submittedAt = date('d M Y, h:i A T');
$ipAddress   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$fullName    = $firstName . ' ' . $lastName;

/* ── HR Email (HTML) ────────────────────────────────────── */
$hrSubject = "New Internship Application — {$fullName} [{$refId}]";

$hrBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
  body{font-family:'Inter',Arial,sans-serif;background:#f8fafc;margin:0;padding:0;}
  .wrap{max-width:640px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;
        box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .top{background:linear-gradient(135deg,#0ea5e9,#0369a1);padding:36px 40px;color:#fff;}
  .top h1{font-size:22px;font-weight:800;margin:0 0 6px;letter-spacing:-.4px;}
  .top p{font-size:13px;opacity:.85;margin:0;}
  .ref{display:inline-block;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);
       padding:6px 16px;border-radius:6px;font-size:13px;font-weight:700;margin-top:14px;letter-spacing:.5px;}
  .body{padding:36px 40px;}
  .row{display:flex;padding:12px 0;border-bottom:1px solid #f1f5f9;}
  .row:last-child{border-bottom:none;}
  .lbl{width:180px;flex-shrink:0;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;padding-top:1px;}
  .val{font-size:15px;color:#0f172a;font-weight:500;flex:1;}
  .note{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px 20px;
        font-size:14px;color:#0369a1;margin-top:24px;line-height:1.7;}
  .foot{background:#f8fafc;padding:20px 40px;font-size:12px;color:#94a3b8;text-align:center;}
</style></head>
<body>
<div class="wrap">
  <div class="top">
    <h1>New Internship Application</h1>
    <p>YSQUARE Engineering Internship Program</p>
    <div class="ref">Reference: {$refId}</div>
  </div>
  <div class="body">
    <div class="row"><div class="lbl">Full Name</div><div class="val">{$fullName}</div></div>
    <div class="row"><div class="lbl">Email</div><div class="val">{$email}</div></div>
    <div class="row"><div class="lbl">Phone</div><div class="val">{$phone}</div></div>
    <div class="row"><div class="lbl">College</div><div class="val">{$college}</div></div>
    <div class="row"><div class="lbl">Branch</div><div class="val">{$branch}</div></div>
    <div class="row"><div class="lbl">Grad Year</div><div class="val">{$gradYear}</div></div>
    <div class="row"><div class="lbl">Domain</div><div class="val">{$domain}</div></div>
    <div class="row"><div class="lbl">Resume</div><div class="val">{$resumeOrigName}</div></div>
    <div class="row"><div class="lbl">Submitted</div><div class="val">{$submittedAt}</div></div>
    <div class="row"><div class="lbl">IP Address</div><div class="val">{$ipAddress}</div></div>
    <div class="note"><strong>Motivation:</strong><br>{$motivation}</div>
  </div>
  <div class="foot">YSQUARE Semicon IT Solutions · Confidential HR Communication</div>
</div>
</body></html>
HTML;

/* ── Applicant Acknowledgement (HTML) ──────────────────── */
$ackSubject = "Application Received — {$refId} | YSQUARE Engineering Internship";

$ackBody = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
  body{font-family:'Inter',Arial,sans-serif;background:#f8fafc;margin:0;padding:0;}
  .wrap{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;
        box-shadow:0 4px 24px rgba(0,0,0,.08);}
  .top{background:linear-gradient(135deg,#0ea5e9,#0369a1);padding:40px;text-align:center;color:#fff;}
  .top h1{font-size:24px;font-weight:900;letter-spacing:-.5px;margin:0 0 8px;}
  .top p{font-size:14px;opacity:.85;margin:0;}
  .ref{display:inline-block;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);
       padding:8px 20px;border-radius:30px;font-size:13px;font-weight:700;margin-top:16px;letter-spacing:.5px;}
  .body{padding:40px;}
  .hi{font-size:18px;font-weight:700;color:#0f172a;margin-bottom:16px;}
  .para{font-size:15px;color:#475569;line-height:1.8;margin-bottom:20px;}
  .summary{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:28px;}
  .srow{display:flex;padding:11px 18px;border-bottom:1px solid #f1f5f9;}
  .srow:last-child{border-bottom:none;}
  .slbl{width:130px;flex-shrink:0;font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;}
  .sval{font-size:14px;color:#0f172a;font-weight:500;}
  .timeline{margin-bottom:28px;}
  .trow{display:flex;align-items:center;gap:14px;padding:10px 0;}
  .dot{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:11px;font-weight:700;}
  .dot.done{background:#0ea5e9;color:#fff;}
  .dot.pend{background:#e2e8f0;color:#94a3b8;}
  .tlbl{font-size:14px;font-weight:600;color:#0f172a;}
  .tlbl.pend{color:#94a3b8;}
  .foot{background:#f8fafc;padding:20px 40px;font-size:12px;color:#94a3b8;text-align:center;line-height:1.7;}
</style></head>
<body>
<div class="wrap">
  <div class="top">
    <h1>Application Received ✓</h1>
    <p>YSQUARE Engineering Internship Program 2026</p>
    <div class="ref">{$refId}</div>
  </div>
  <div class="body">
    <div class="hi">Hi {$firstName},</div>
    <div class="para">
      Thank you for applying to the YSQUARE Engineering Internship Program. We have received your application and our HR team will personally review it within <strong>48 business hours</strong>.
    </div>
    <div class="para">
      Please keep your reference ID safe — you may need it for any future correspondence regarding your application.
    </div>
    <div class="summary">
      <div class="srow"><div class="slbl">Name</div><div class="sval">{$fullName}</div></div>
      <div class="srow"><div class="slbl">Email</div><div class="sval">{$email}</div></div>
      <div class="srow"><div class="slbl">College</div><div class="sval">{$college}</div></div>
      <div class="srow"><div class="slbl">Domain</div><div class="sval">{$domain}</div></div>
      <div class="srow"><div class="slbl">Resume</div><div class="sval">{$resumeOrigName}</div></div>
      <div class="srow"><div class="slbl">Submitted</div><div class="sval">{$submittedAt}</div></div>
    </div>
    <div class="para" style="font-weight:600;color:#0f172a;">Recruitment Timeline</div>
    <div class="timeline">
      <div class="trow"><div class="dot done">✓</div><div class="tlbl">Application Submitted</div></div>
      <div class="trow"><div class="dot done">✓</div><div class="tlbl">Under HR Review</div></div>
      <div class="trow"><div class="dot pend">○</div><div class="tlbl pend">Shortlisting</div></div>
      <div class="trow"><div class="dot pend">○</div><div class="tlbl pend">Interview</div></div>
      <div class="trow"><div class="dot pend">○</div><div class="tlbl pend">Final Selection</div></div>
    </div>
    <div class="para">
      If you have any questions, contact us at <a href="mailto:{$hrEmail}" style="color:#0ea5e9;">{$hrEmail}</a>.
      We look forward to reviewing your application.
    </div>
  </div>
  <div class="foot">
    © 2026 YSQUARE Semicon IT Solutions · All rights reserved<br>
    This is an automated confirmation. Please do not reply to this email.
  </div>
</div>
</body></html>
HTML;

/* ── Send emails using PHP mail() ───────────────────────── */
$hrEmail   = HR_EMAIL;  // used in ack template above
$boundary  = md5(time());

/* — HR email with resume attachment — */
$hrHeaders  = "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
$hrHeaders .= "Reply-To: {$email}\r\n";
$hrHeaders .= "MIME-Version: 1.0\r\n";
$hrHeaders .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$hrMessage  = "--{$boundary}\r\n";
$hrMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
$hrMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
$hrMessage .= chunk_split(base64_encode($hrBody)) . "\r\n";

/* Attach resume if saved */
if ($resumeSavedAs && file_exists(UPLOAD_DIR . $resumeSavedAs)) {
    $fileData = file_get_contents(UPLOAD_DIR . $resumeSavedAs);
    $hrMessage .= "--{$boundary}\r\n";
    $hrMessage .= "Content-Type: application/octet-stream; name=\"{$resumeOrigName}\"\r\n";
    $hrMessage .= "Content-Disposition: attachment; filename=\"{$resumeOrigName}\"\r\n";
    $hrMessage .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $hrMessage .= chunk_split(base64_encode($fileData)) . "\r\n";
}
$hrMessage .= "--{$boundary}--";

@mail(HR_EMAIL, $hrSubject, $hrMessage, $hrHeaders);

/* — Applicant acknowledgement — */
$ackHeaders  = "From: " . SENDER_NAME . " <" . SENDER_EMAIL . ">\r\n";
$ackHeaders .= "MIME-Version: 1.0\r\n";
$ackHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
$ackHeaders .= "Content-Transfer-Encoding: base64\r\n";

@mail($email, $ackSubject, chunk_split(base64_encode($ackBody)), $ackHeaders);

/* ── Return success ─────────────────────────────────────── */
echo json_encode([
    'success'     => true,
    'refId'       => $refId,
    'name'        => $fullName,
    'email'       => $email,
    'phone'       => $phone,
    'college'     => $college,
    'domain'      => $domain,
    'resumeName'  => $resumeOrigName,
    'submittedAt' => $submittedAt,
]);