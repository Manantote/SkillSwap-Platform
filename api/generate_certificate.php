<?php
/**
 * generate_certificate.php
 * GET — Render an HTML certificate by cert_token.
 *      Add ?download=1 to trigger print/PDF save dialog.
 *
 * No authentication required (token acts as the access control).
 * Public shareable URL:  /api/generate_certificate.php?token=<cert_token>
 */

require_once __DIR__ . '/../config/db.php';

$token = trim($_GET['token'] ?? '');
if (!$token) {
    http_response_code(400);
    echo 'Certificate token is required.';
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare(
    'SELECT c.*, u.name AS user_name, sk.skill_name
     FROM certificates c
     JOIN users u  ON u.id  = c.user_id
     JOIN skills sk ON sk.skill_id = c.skill_id
     WHERE c.cert_token = ?
     LIMIT 1'
);
$stmt->execute([$token]);
$cert = $stmt->fetch();

if (!$cert) {
    http_response_code(404);
    echo 'Certificate not found or invalid token.';
    exit;
}

$userName  = htmlspecialchars($cert['user_name']);
$skillName = htmlspecialchars($cert['skill_name']);
$issuedAt  = date('F j, Y', strtotime($cert['issued_at']));
$certId    = 'SS-' . strtoupper(substr($token, 0, 10));
$download  = isset($_GET['download']) ? 'onload="window.print()"' : '';

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Certificate — <?= $skillName ?> | SkillSwap</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@400;500;600&display=swap">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: #f0f4ff;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-family: 'Inter', sans-serif;
    padding: 2rem 1rem;
  }

  .cert-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    justify-content: center;
  }

  .btn-cert {
    padding: .65rem 1.6rem;
    border-radius: 30px;
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    border: none;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    text-decoration: none;
  }

  .btn-print  { background: #6c63ff; color: #fff; }
  .btn-print:hover  { background: #5a52e0; transform: translateY(-1px); }
  .btn-share  { background: #fff; color: #6c63ff; border: 2px solid #6c63ff; }
  .btn-share:hover  { background: #f4f3ff; }
  .btn-back   { background: #fff; color: #555; border: 2px solid #ddd; }
  .btn-back:hover   { background: #f9f9f9; }

  /* ── Certificate card ─────────────────────────────────────── */
  .certificate {
    width: 100%;
    max-width: 860px;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(108,99,255,.18);
    position: relative;
  }

  .cert-border {
    position: absolute;
    inset: 12px;
    border: 2px solid transparent;
    border-radius: 12px;
    background: linear-gradient(white, white) padding-box,
                linear-gradient(135deg, #6c63ff, #ff6584, #ffb347) border-box;
    pointer-events: none;
  }

  /* Corner ornaments */
  .ornament {
    position: absolute;
    width: 60px;
    height: 60px;
    opacity: .25;
  }
  .ornament svg { width: 100%; height: 100%; }
  .orn-tl { top: 18px; left: 18px; }
  .orn-tr { top: 18px; right: 18px; transform: scaleX(-1); }
  .orn-bl { bottom: 18px; left: 18px; transform: scaleY(-1); }
  .orn-br { bottom: 18px; right: 18px; transform: scale(-1); }

  .cert-header {
    background: linear-gradient(135deg, #6c63ff 0%, #a78bfa 60%, #ff6584 100%);
    padding: 2.5rem 3rem 2rem;
    text-align: center;
    color: #fff;
    position: relative;
  }

  .cert-logo {
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: .5px;
    opacity: .9;
    margin-bottom: .4rem;
  }

  .cert-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.2rem;
    font-weight: 900;
    letter-spacing: 2px;
    text-transform: uppercase;
  }

  .cert-header p {
    font-size: .92rem;
    opacity: .85;
    margin-top: .3rem;
    letter-spacing: .5px;
  }

  .cert-body {
    padding: 2.8rem 3.5rem 2rem;
    text-align: center;
  }

  .cert-presented {
    font-size: .85rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #999;
    margin-bottom: .6rem;
  }

  .cert-name {
    font-family: 'Playfair Display', serif;
    font-size: 2.6rem;
    font-weight: 900;
    color: #1a1a2e;
    margin-bottom: .8rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: .8rem;
  }

  .cert-desc {
    font-size: .97rem;
    color: #555;
    line-height: 1.7;
    max-width: 600px;
    margin: .8rem auto 1.6rem;
  }

  .cert-skill-badge {
    display: inline-block;
    background: linear-gradient(135deg, #6c63ff, #a78bfa);
    color: #fff;
    font-size: 1.15rem;
    font-weight: 700;
    padding: .5rem 2rem;
    border-radius: 30px;
    margin-bottom: 1.6rem;
    letter-spacing: .5px;
  }

  .cert-seal {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .seal-badge {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c63ff, #ff6584);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: .7rem;
    text-align: center;
    line-height: 1.2;
    box-shadow: 0 4px 16px rgba(108,99,255,.35);
  }

  .seal-badge span { font-size: 1.3rem; }

  .cert-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 1.2rem 3.5rem 2rem;
    border-top: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 1.2rem;
  }

  .cert-sig {
    text-align: center;
  }

  .sig-line {
    width: 130px;
    height: 1px;
    background: #333;
    margin: 0 auto .3rem;
  }

  .sig-label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #888;
  }

  .cert-meta {
    text-align: right;
    font-size: .78rem;
    color: #aaa;
    line-height: 1.7;
  }

  .cert-meta strong { color: #666; }

  @media print {
    body { background: #fff; padding: 0; }
    .cert-actions { display: none !important; }
    .certificate {
      box-shadow: none;
      border-radius: 0;
      max-width: 100%;
    }
  }
</style>
</head>
<body <?= $download ?>>

<div class="cert-actions">
  <button class="btn-cert btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
  <button class="btn-cert btn-share" onclick="copyLink()">🔗 Copy Certificate Link</button>
  <a href="javascript:history.back()" class="btn-cert btn-back">← Back</a>
</div>

<div class="certificate" id="certificate">
  <div class="cert-border"></div>

  <!-- Corner ornaments (SVG floral/diamond) -->
  <?php $orn = '<svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 0 L20 20 L0 40 Z" fill="#6c63ff"/><path d="M10 0 L30 30 L0 20 Z" fill="#ff6584" opacity=".6"/><circle cx="30" cy="30" r="8" stroke="#ffb347" stroke-width="2"/></svg>'; ?>
  <div class="ornament orn-tl"><?= $orn ?></div>
  <div class="ornament orn-tr"><?= $orn ?></div>
  <div class="ornament orn-bl"><?= $orn ?></div>
  <div class="ornament orn-br"><?= $orn ?></div>

  <div class="cert-header">
    <div class="cert-logo">⟷ SkillSwap Platform</div>
    <h1>Certificate of Achievement</h1>
    <p>This certifies successful completion of a verified skill exchange</p>
  </div>

  <div class="cert-body">
    <div class="cert-presented">This certificate is proudly presented to</div>
    <div class="cert-name"><?= $userName ?></div>
    <p class="cert-desc">
      for successfully demonstrating proficiency in
    </p>
    <div class="cert-skill-badge">✦ <?= $skillName ?> ✦</div>
    <p class="cert-desc">
      Having completed the required learning sessions, accumulated the minimum
      practice time, and passed the SkillSwap proficiency assessment with a
      score of <strong>70% or above</strong>.
    </p>

    <div class="cert-seal">
      <div class="seal-badge">
        <span>★</span>
        Verified<br>Learner
      </div>
    </div>
  </div>

  <div class="cert-footer">
    <div class="cert-sig">
      <div class="sig-line"></div>
      <div class="sig-label">Platform Director — SkillSwap</div>
    </div>
    <div class="cert-sig">
      <div class="sig-line"></div>
      <div class="sig-label">Peer Instructor</div>
    </div>
    <div class="cert-meta">
      <strong>Date Issued:</strong> <?= $issuedAt ?><br>
      <strong>Certificate ID:</strong> <?= $certId ?><br>
      <span style="font-size:.7rem">Verify at: skillswap.app/verify/<?= $certId ?></span>
    </div>
  </div>
</div>

<script>
  function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(() => {
      alert('Certificate link copied to clipboard!');
    });
  }
</script>
</body>
</html>
