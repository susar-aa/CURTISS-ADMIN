<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $payment */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge badge-ios-black font-monospace" style="letter-spacing: 0.05em; font-size: 0.85rem;">
          <?= htmlspecialchars($payment['receipt_number']) ?>
        </span>
        <?= FormatHelper::statusBadge($payment['status']) ?>
      </div>
      <h1>Receipt: <?= FormatHelper::currency($payment['amount']) ?></h1>
      <p>Received: <?= FormatHelper::date($payment['payment_date']) ?> &bull; From: <?= htmlspecialchars($payment['company_name']) ?></p>
    </div>

    <!-- Actions Toolbar -->
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Preview PDF -->
      <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $payment['id'] ?>&mode=preview" target="_blank" class="btn-ios-secondary">
        <i data-lucide="eye"></i>
        <span>Preview PDF</span>
      </a>

      <!-- Download PDF -->
      <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $payment['id'] ?>&mode=download" class="btn-ios-secondary">
        <i data-lucide="download"></i>
        <span>Download PDF</span>
      </a>

      <!-- Share Action -->
      <button type="button" id="shareReceiptBtn" class="btn-ios-secondary" title="Share Receipt">
        <i data-lucide="share-2"></i>
        <span>Share</span>
      </button>

      <!-- Reversal / Cancel Action -->
      <?php if ($payment['status'] === 'completed'): ?>
        <button type="button" class="btn-logout" data-bs-toggle="modal" data-bs-target="#reverseModal" title="Reverse / Cancel Payment" style="color: var(--c-danger); padding: 8px;">
          <i data-lucide="rotate-ccw"></i>
          <span class="ms-1 small">Reverse</span>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-dismissible fade show py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #ecfdf5; color: #059669;">
      <i data-lucide="check-circle" style="width: 16px; height: 16px; display: inline-block; vertical-align: -3px; margin-right: 6px;"></i>
      <?= htmlspecialchars($flashSuccess) ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($flashError)): ?>
    <div class="alert alert-dismissible fade show py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <i data-lucide="alert-triangle" style="width: 16px; height: 16px; display: inline-block; vertical-align: -3px; margin-right: 6px;"></i>
      <?= htmlspecialchars($flashError) ?>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Receipt Overview Card -->
    <div class="col-12 col-lg-8">
      <div class="ios-card p-4 mb-4">
        <div class="card-header-clean">
          <h3 class="card-title-clean">Receipt Statement</h3>
          <span class="text-muted small">Official Curtiss Remittance Record</span>
        </div>

        <div class="p-4 text-center my-3" style="background: var(--c-surface); border-radius: var(--radius-md); border: var(--border-subtle);">
          <div class="text-muted text-uppercase small fw-bold mb-1" style="letter-spacing: 0.08em;">Total Amount Received</div>
          <div class="h2 fw-bold font-monospace mb-0"><?= FormatHelper::currency($payment['amount']) ?></div>
          <span class="badge badge-ios-black mt-2"><?= ucwords(str_replace('_', ' ', $payment['payment_method'])) ?></span>
        </div>

        <table class="ios-table">
          <tbody>
            <tr>
              <td class="text-muted" style="width: 160px;">Receipt Number</td>
              <td class="fw-bold font-monospace"><?= htmlspecialchars($payment['receipt_number']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Payment Date</td>
              <td class="fw-semibold"><?= FormatHelper::date($payment['payment_date'], 'l, F j, Y') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Transaction Ref</td>
              <td class="font-monospace"><?= htmlspecialchars($payment['reference_no'] ?: 'Direct Transfer / Handed') ?></td>
            </tr>
            <tr>
              <td class="text-muted">Allocation</td>
              <td>
                <?php if (!empty($payment['invoice_number'])): ?>
                  <a href="<?= BASE_URL ?>/index.php?page=billing_view&id=<?= $payment['invoice_id'] ?>" class="fw-bold text-decoration-none text-dark">
                    Applied to Invoice #<?= htmlspecialchars($payment['invoice_number']) ?>
                  </a>
                  <div class="text-muted small">
                    Invoice Total: <?= FormatHelper::currency($payment['invoice_total']) ?> &bull; 
                    Balance Remaining: <strong class="text-danger"><?= FormatHelper::currency($payment['invoice_balance']) ?></strong>
                  </div>
                <?php else: ?>
                  <strong><?= htmlspecialchars($payment['custom_description'] ?: 'Direct Client Payment') ?></strong>
                <?php endif; ?>
              </td>
            </tr>
            <?php if (!empty($payment['notes'])): ?>
              <tr>
                <td class="text-muted">Notes / Remarks</td>
                <td><?= nl2br(htmlspecialchars($payment['notes'])) ?></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Payment Proof Attachment Preview (If Uploaded) -->
      <?php if (!empty($payment['proof_path']) && file_exists($payment['proof_path'])): ?>
        <div class="ios-card p-4">
          <h4 class="card-title-clean mb-3">Deposit Slip / Bank Slip Attachment</h4>
          <?php 
            $ext = strtolower(pathinfo($payment['proof_path'], PATHINFO_EXTENSION));
            $proofUrl = BASE_URL . '/storage/proofs/' . basename($payment['proof_path']);
          ?>
          <?php if (in_array($ext, ['jpg', 'jpeg', 'png'], true)): ?>
            <div class="text-center p-2 bg-light" style="border-radius: var(--radius-sm);">
              <img src="<?= $proofUrl ?>" alt="Payment Proof" class="img-fluid" style="max-height: 400px; border-radius: 8px;">
            </div>
          <?php else: ?>
            <a href="<?= $proofUrl ?>" target="_blank" class="btn-ios-secondary">
              <i data-lucide="file"></i>
              <span>View Attached File (<?= strtoupper($ext) ?>)</span>
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Client & Issuer Info -->
    <div class="col-12 col-lg-4">
      <div class="ios-card p-4 mb-4">
        <h4 class="card-title-clean mb-3">Client Profile</h4>
        <div class="fw-bold"><?= htmlspecialchars($payment['company_name']) ?></div>
        <div class="text-muted small mb-3">Client Code: #<?= htmlspecialchars($payment['client_code']) ?></div>
        
        <div class="d-flex flex-column gap-2 small">
          <div><i data-lucide="user" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> <?= htmlspecialchars($payment['contact_person']) ?></div>
          <div><i data-lucide="phone" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> <?= htmlspecialchars($payment['phone']) ?></div>
          <?php if (!empty($payment['email'])): ?>
            <div><i data-lucide="mail" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> <?= htmlspecialchars($payment['email']) ?></div>
          <?php endif; ?>
        </div>

        <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $payment['client_id'] ?>&tab=payments" class="btn-ios-secondary mt-3 w-100 justify-content-center">
          <span>View All Client Payments</span>
        </a>
      </div>

      <div class="ios-card p-4">
        <h4 class="card-title-clean mb-2">Issued By</h4>
        <div class="fw-bold">Curtiss Financial Department</div>
        <div class="text-muted small">trycurtiss@gmail.com &bull; 0761407875</div>
        <div class="text-muted small mt-2">79, Dambakanda Estate, Boyagane, Kurunegala</div>
      </div>
    </div>
  </div>
</main>

<!-- Reversal / Cancel Modal -->
<div class="modal fade" id="reverseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border: var(--border-subtle);">
      <form action="<?= BASE_URL ?>/index.php?page=payment_cancel" method="POST">
        <?= AuthHelper::csrfInput() ?>
        <input type="hidden" name="id" value="<?= $payment['id'] ?>">

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold text-danger">Reverse Payment Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body pt-3">
          <p class="text-muted small mb-3">
            Reversing receipt <strong><?= htmlspecialchars($payment['receipt_number']) ?></strong> (<?= FormatHelper::currency($payment['amount']) ?>). 
            If linked to an invoice, the invoice outstanding balance will be automatically restored.
          </p>

          <div class="mb-3">
            <label for="reason" class="form-label-ios">Audit Reason for Reversal <span class="text-danger">*</span></label>
            <textarea 
              id="reason" 
              name="reason" 
              rows="3" 
              class="form-control-ios" 
              placeholder="e.g. Cheque returned by bank / Customer duplicated payment..." 
              required
            ></textarea>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Keep Payment</button>
          <button type="submit" class="btn-ios-primary" style="width: auto; background-color: var(--c-danger); border-color: var(--c-danger);">
            Confirm Reversal
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const shareBtn = document.getElementById('shareReceiptBtn');
  if (shareBtn) {
    shareBtn.addEventListener('click', async () => {
      const shareUrl = "<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $payment['id'] ?>&mode=preview";
      const shareTitle = "Curtiss Receipt <?= htmlspecialchars($payment['receipt_number']) ?>";
      const shareText = "Official Payment Receipt from Curtiss for <?= htmlspecialchars($payment['company_name']) ?> (Amount: <?= FormatHelper::currency($payment['amount']) ?>)";

      if (navigator.share) {
        try {
          await navigator.share({
            title: shareTitle,
            text: shareText,
            url: shareUrl
          });
        } catch (err) {
          if (err.name !== 'AbortError') {
            copyToClipboard(shareUrl);
          }
        }
      } else {
        copyToClipboard(shareUrl);
      }
    });
  }

  function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
      alert('Receipt PDF link copied to clipboard!');
    }).catch(() => {
      prompt('Copy this link:', text);
    });
  }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
