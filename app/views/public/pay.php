<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Public Payment Details Page
 * Accessible without login: ?page=pay
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Fetch active bank accounts from database
$db = Database::getConnection();
$stmt = $db->query(
    "SELECT id, bank_name, branch, account_name, account_number, is_primary 
     FROM bank_accounts 
     WHERE status = 'active' 
     ORDER BY is_primary DESC, id ASC"
);
$bankAccounts = $stmt->fetchAll() ?: [];

// Fallback if none seeded
if (empty($bankAccounts)) {
    $bankAccounts = [
        [
            'id'             => 1,
            'bank_name'      => 'Commercial Bank',
            'branch'         => 'Kurunegala City Office',
            'account_name'   => 'G L DASUN SUSARA SENARATHNE',
            'account_number' => '8018526390',
            'is_primary'     => 1
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#09090b">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>Curtiss Payment Details | Official Remittance Information</title>

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.json">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/assets/images/favicon.png">
  <link rel="shortcut icon" href="<?= BASE_URL ?>/public/favicon.ico">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/public/assets/images/favicon.png">

  <!-- Inter Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Curtiss iOS Design System -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= APP_VERSION ?>">

  <style>
    body {
      background-color: #f4f4f6;
      padding-bottom: 30px;
    }
    .public-pay-wrap {
      max-width: 480px;
      margin: 0 auto;
      padding: 32px 16px;
    }
    .pay-card {
      background: #ffffff;
      border: 1px solid #e4e4e7;
      border-radius: 28px;
      padding: 30px 24px;
      box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.06);
    }
    .pay-badge-icon {
      width: 58px;
      height: 58px;
      border-radius: 20px;
      background: #09090b;
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
    }
    .bank-detail-item {
      background: #f8f8fa;
      border: 1px solid #e4e4e7;
      border-radius: 18px;
      padding: 14px 16px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: all 0.15s ease;
    }
    .copy-pill-btn {
      background: #ffffff;
      border: 1px solid #e4e4e7;
      border-radius: 999px;
      padding: 6px 14px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #09090b;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .copy-pill-btn:hover {
      background: #09090b;
      color: #ffffff;
      border-color: #09090b;
    }
    .copy-pill-btn.copied {
      background: #10b981 !important;
      color: #ffffff !important;
      border-color: #10b981 !important;
    }
    .security-notice {
      background: #fafafa;
      border: 1px dashed #d4d4d8;
      border-radius: 18px;
      padding: 16px;
      font-size: 0.82rem;
      color: #52525b;
      line-height: 1.45;
    }
  </style>
</head>
<body>

<div class="public-pay-wrap">
  <!-- Brand Header -->
  <div class="text-center mb-4">
    <img src="<?= BASE_URL ?>/public/assets/images/logo.png" alt="Curtiss" style="height: 40px; max-width: 190px; object-fit: contain; margin-bottom: 8px;">
    <p class="text-muted small mb-0">Official Bank Remittance Information</p>
  </div>

  <?php foreach ($bankAccounts as $idx => $acc): ?>
    <div class="pay-card mb-4">
      <?php if (!empty($acc['is_primary'])): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="badge badge-ios-black" style="font-size: 0.75rem;">Primary Account</span>
          <span class="text-muted small">Commercial Bank of Ceylon</span>
        </div>
      <?php endif; ?>

      <!-- Bank Name -->
      <div class="bank-detail-item">
        <div>
          <div class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;">Bank</div>
          <div class="fw-bold" style="font-size: 1.05rem;"><?= htmlspecialchars($acc['bank_name']) ?></div>
        </div>
        <div class="text-muted small text-end">
          <span class="d-block text-muted" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;">Branch</span>
          <span class="fw-semibold text-dark"><?= htmlspecialchars($acc['branch']) ?></span>
        </div>
      </div>

      <!-- Account Number (Highlight) -->
      <div class="bank-detail-item" style="border: 2px solid #09090b; background: #ffffff;">
        <div>
          <div class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;">Account Number</div>
          <div class="fw-bold font-monospace" style="font-size: 1.35rem; letter-spacing: 0.05em;" id="accNoVal<?= $idx ?>">
            <?= htmlspecialchars($acc['account_number']) ?>
          </div>
        </div>
        <button type="button" class="copy-pill-btn copy-btn" data-target="accNoVal<?= $idx ?>" data-text="<?= htmlspecialchars($acc['account_number']) ?>">
          <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
          <span>Copy</span>
        </button>
      </div>

      <!-- Account Name -->
      <div class="bank-detail-item">
        <div style="min-width: 0; padding-right: 8px;">
          <div class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;">Account Name</div>
          <div class="fw-bold" style="font-size: 0.95rem; word-break: break-word;" id="accNameVal<?= $idx ?>">
            <?= htmlspecialchars($acc['account_name']) ?>
          </div>
        </div>
        <button type="button" class="copy-pill-btn copy-btn" data-target="accNameVal<?= $idx ?>" data-text="<?= htmlspecialchars($acc['account_name']) ?>">
          <i data-lucide="copy" style="width: 14px; height: 14px;"></i>
          <span>Copy</span>
        </button>
      </div>

      <!-- Copy All & Share Buttons -->
      <div class="d-flex flex-column gap-2 mt-4">
        <button 
          type="button" 
          class="btn-ios-primary justify-content-center copy-all-btn"
          data-bank="<?= htmlspecialchars($acc['bank_name']) ?>"
          data-branch="<?= htmlspecialchars($acc['branch']) ?>"
          data-name="<?= htmlspecialchars($acc['account_name']) ?>"
          data-number="<?= htmlspecialchars($acc['account_number']) ?>"
        >
          <i data-lucide="copy-check" style="width: 16px; height: 16px;"></i>
          <span>Copy All Details</span>
        </button>

        <button 
          type="button" 
          class="btn-ios-secondary justify-content-center share-btn"
          data-text="Curtiss Official Bank Details:&#10;Bank: <?= htmlspecialchars($acc['bank_name']) ?>&#10;Branch: <?= htmlspecialchars($acc['branch']) ?>&#10;Account Name: <?= htmlspecialchars($acc['account_name']) ?>&#10;Account Number: <?= htmlspecialchars($acc['account_number']) ?>"
        >
          <i data-lucide="share-2" style="width: 16px; height: 16px;"></i>
          <span>Share Payment Details</span>
        </button>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Security Notice & Reminders -->
  <div class="security-notice mb-4">
    <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold">
      <i data-lucide="shield-alert" style="width: 18px; height: 18px;"></i>
      <span>Important Security Notice</span>
    </div>
    <p class="mb-2">
      Please verify that you are transferring only to the official Curtiss account specified above. 
      Curtiss will never request remittance to alternative unverified bank accounts.
    </p>
    <div class="small text-muted">
      After completing your deposit or bank transfer, please send your payment receipt to:
      <div class="mt-1">
        <strong>Email:</strong> trycurtiss@gmail.com<br>
        <strong>WhatsApp:</strong> 0761407875 / 0771234567
      </div>
    </div>
  </div>

  <div class="text-center text-muted small">
    &copy; <?= date('Y') ?> Curtiss. All rights reserved.<br>
    79, Dambakanda Estate, Boyagane, Kurunegala &bull; www.trycurtiss.com
  </div>
</div>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Copy individual buttons
  document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-text');
      navigator.clipboard.writeText(text).then(() => {
        const origHtml = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = `<i data-lucide="check" style="width: 14px; height: 14px;"></i><span>Copied!</span>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => {
          btn.classList.remove('copied');
          btn.innerHTML = origHtml;
          if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 2000);
      });
    });
  });

  // Copy all details
  document.querySelectorAll('.copy-all-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const bank = btn.getAttribute('data-bank');
      const branch = btn.getAttribute('data-branch');
      const name = btn.getAttribute('data-name');
      const number = btn.getAttribute('data-number');

      const allDetails = `Curtiss Bank Transfer Details:\nBank: ${bank}\nBranch: ${branch}\nAccount Name: ${name}\nAccount Number: ${number}`;

      navigator.clipboard.writeText(allDetails).then(() => {
        const origText = btn.querySelector('span').textContent;
        btn.querySelector('span').textContent = 'All Details Copied!';
        btn.style.backgroundColor = '#10b981';
        btn.style.borderColor = '#10b981';
        setTimeout(() => {
          btn.querySelector('span').textContent = origText;
          btn.style.backgroundColor = '';
          btn.style.borderColor = '';
        }, 2000);
      });
    });
  });

  // Native share
  document.querySelectorAll('.share-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const text = btn.getAttribute('data-text');
      const url = window.location.href;

      if (navigator.share) {
        try {
          await navigator.share({
            title: 'Curtiss Bank Payment Details',
            text: text,
            url: url
          });
        } catch (e) {}
      } else {
        navigator.clipboard.writeText(text + "\nLink: " + url).then(() => {
          alert('Payment details copied to clipboard!');
        });
      }
    });
  });
});
</script>
</body>
</html>
