<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $invoice */
/** @var array $items */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge badge-ios-black font-monospace" style="letter-spacing: 0.05em; font-size: 0.85rem;">
          <?= htmlspecialchars($invoice['invoice_number']) ?>
        </span>
        <?= FormatHelper::statusBadge($invoice['status']) ?>
      </div>
      <h1><?= htmlspecialchars($invoice['company_name']) ?></h1>
      <p>Issued: <?= FormatHelper::date($invoice['issue_date']) ?> &bull; Due: <?= FormatHelper::date($invoice['due_date']) ?></p>
    </div>

    <!-- Actions Toolbar -->
    <div class="header-actions">
      <!-- Record Payment Button (Mark as Paid) -->
      <?php if (!in_array($invoice['status'], ['paid', 'cancelled'], true)): ?>
        <button type="button" class="btn-ios-primary" data-bs-toggle="modal" data-bs-target="#recordPaymentModal" style="background: #059669; border-color: #059669;">
          <i data-lucide="check-circle-2"></i>
          <span>Record Payment</span>
        </button>
      <?php endif; ?>

      <!-- Print Invoice (Direct Web Print View) -->
      <a href="<?= BASE_URL ?>/index.php?page=invoice_print&id=<?= $invoice['id'] ?>" target="_blank" class="btn-ios-primary">
        <i data-lucide="printer"></i>
        <span>Print Invoice</span>
      </a>

      <!-- Preview PDF -->
      <a href="<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $invoice['id'] ?>&mode=preview" target="_blank" class="btn-ios-secondary">
        <i data-lucide="eye"></i>
        <span>Preview PDF</span>
      </a>

      <!-- Download PDF -->
      <a href="<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $invoice['id'] ?>&mode=download" class="btn-ios-secondary">
        <i data-lucide="download"></i>
        <span>Download PDF</span>
      </a>

      <!-- Share Action -->
      <button type="button" id="sharePdfBtn" class="btn-ios-secondary" title="Share Invoice">
        <i data-lucide="share-2"></i>
        <span>Share</span>
      </button>

      <!-- Edit (Only if draft or unpaid) -->
      <?php if (in_array($invoice['status'], ['draft', 'unpaid'], true)): ?>
        <a href="<?= BASE_URL ?>/index.php?page=billing_edit&id=<?= $invoice['id'] ?>" class="btn-ios-secondary">
          <i data-lucide="pencil"></i>
          <span>Edit</span>
        </a>
      <?php endif; ?>

      <!-- Finalize Action (If Draft) -->
      <?php if ($invoice['status'] === 'draft'): ?>
        <form action="<?= BASE_URL ?>/index.php?page=billing_finalize" method="POST" onsubmit="return confirm('Finalize this invoice for billing?');">
          <?= AuthHelper::csrfInput() ?>
          <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
          <button type="submit" class="btn-ios-primary">
            <i data-lucide="check"></i>
            <span>Finalize</span>
          </button>
        </form>
      <?php endif; ?>

      <!-- Cancel Action -->
      <?php if (!in_array($invoice['status'], ['paid', 'cancelled'], true)): ?>
        <form action="<?= BASE_URL ?>/index.php?page=billing_cancel" method="POST" onsubmit="return confirm('Are you sure you want to cancel this invoice?');">
          <?= AuthHelper::csrfInput() ?>
          <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
          <button type="submit" class="btn-ios-secondary" title="Cancel Invoice" style="color: var(--c-danger); border-color: rgba(239, 68, 68, 0.2);">
            <i data-lucide="x-circle"></i>
            <span>Cancel</span>
          </button>
        </form>
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

  <!-- Invoice Meta Cards Grid -->
  <div class="row g-4 mb-4">
    <!-- Client Details Card -->
    <div class="col-12 col-lg-6">
      <div class="ios-card h-100 p-4">
        <div class="card-header-clean">
          <h3 class="card-title-clean">Client / Bill To</h3>
          <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $invoice['client_id'] ?>" class="btn-ios-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
            <span>Client Profile</span>
          </a>
        </div>
        <div class="fw-bold fs-5 mb-1"><?= htmlspecialchars($invoice['company_name']) ?></div>
        <div class="text-muted small mb-2">ID: #<?= htmlspecialchars($invoice['client_code']) ?> &bull; Attn: <?= htmlspecialchars($invoice['contact_person']) ?></div>
        <div class="d-flex flex-column gap-1 small">
          <div><i data-lucide="phone" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> <?= htmlspecialchars($invoice['phone']) ?></div>
          <?php if (!empty($invoice['whatsapp'])): ?>
            <div><i data-lucide="message-square" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> WA: <?= htmlspecialchars($invoice['whatsapp']) ?></div>
          <?php endif; ?>
          <?php if (!empty($invoice['email'])): ?>
            <div><i data-lucide="mail" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i> <?= htmlspecialchars($invoice['email']) ?></div>
          <?php endif; ?>
          <?php if (!empty($invoice['address'])): ?>
            <div class="text-muted mt-1"><?= nl2br(htmlspecialchars($invoice['address'])) ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Invoice Details Card -->
    <div class="col-12 col-lg-6">
      <div class="ios-card h-100 p-4">
        <div class="card-header-clean">
          <h3 class="card-title-clean">Invoice Overview</h3>
          <?= FormatHelper::statusBadge($invoice['status']) ?>
        </div>
        <table class="ios-table">
          <tbody>
            <tr>
              <td class="text-muted" style="width: 130px;">Invoice #</td>
              <td class="fw-bold font-monospace"><?= htmlspecialchars($invoice['invoice_number']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Issue Date</td>
              <td><?= FormatHelper::date($invoice['issue_date']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Due Date</td>
              <td class="fw-semibold"><?= FormatHelper::date($invoice['due_date']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Payment Terms</td>
              <td><?= htmlspecialchars($invoice['payment_terms'] ?: 'Due upon receipt') ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Itemized Line Items -->
  <div class="ios-card p-0 overflow-hidden mb-4">
    <div class="p-3 border-bottom">
      <h3 class="card-title-clean">Itemized Statement</h3>
    </div>
    <div class="table-responsive">
      <table class="ios-table">
        <thead>
          <tr>
            <th class="text-center" style="width: 50px;">#</th>
            <th>Description</th>
            <th class="text-end" style="width: 180px;">Amount (LKR)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $idx => $item): ?>
            <tr>
              <td class="text-center text-muted small"><?= $idx + 1 ?></td>
              <td class="fw-semibold"><?= htmlspecialchars($item['description']) ?></td>
              <td class="text-end fw-bold font-monospace"><?= FormatHelper::currency($item['line_total']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Totals Area -->
    <div class="p-4 bg-light border-top">
      <div class="row justify-content-end">
        <div class="col-12 col-md-6 col-lg-5">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal:</span>
            <span class="fw-semibold"><?= FormatHelper::currency($invoice['subtotal']) ?></span>
          </div>
          <?php if ((float)$invoice['discount'] > 0): ?>
            <div class="d-flex justify-content-between mb-2 text-danger">
              <span>Discount:</span>
              <span>-<?= FormatHelper::currency($invoice['discount']) ?></span>
            </div>
          <?php endif; ?>
          <?php if ((float)$invoice['tax'] > 0): ?>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Tax:</span>
              <span><?= FormatHelper::currency($invoice['tax']) ?></span>
            </div>
          <?php endif; ?>
          <hr style="margin: 8px 0; border-top: 2px solid #09090b;">
          <div class="d-flex justify-content-between mb-2">
            <span class="h5 mb-0 fw-bold">Grand Total:</span>
            <span class="h5 mb-0 fw-bold font-monospace"><?= FormatHelper::currency($invoice['total_amount']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Amount Paid:</span>
            <span class="text-success fw-semibold"><?= FormatHelper::currency($invoice['paid_amount']) ?></span>
          </div>
          <div class="d-flex justify-content-between pt-2 border-top">
            <span class="fw-bold" style="color: <?= (float)$invoice['balance_due'] > 0 ? 'var(--c-danger)' : 'inherit' ?>;">Balance Due:</span>
            <span class="fw-bold font-monospace fs-5" style="color: <?= (float)$invoice['balance_due'] > 0 ? 'var(--c-danger)' : 'inherit' ?>;">
              <?= FormatHelper::currency($invoice['balance_due']) ?>
            </span>
          </div>

          <?php if (!in_array($invoice['status'], ['paid', 'cancelled'], true)): ?>
            <div class="mt-3 text-end">
              <button type="button" class="btn-ios-primary w-100" data-bs-toggle="modal" data-bs-target="#recordPaymentModal" style="background: #059669; border-color: #059669; padding: 10px 16px;">
                <i data-lucide="check-circle-2"></i>
                <span>Record Payment (Mark Paid)</span>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Recorded Payments & Receipts Table -->
  <?php if (!empty($payments)): ?>
    <div class="ios-card p-0 overflow-hidden mb-4">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h3 class="card-title-clean">Payment History & Receipts</h3>
        <span class="badge badge-ios-black"><?= count($payments) ?> Recorded</span>
      </div>
      <div class="table-responsive">
        <table class="ios-table">
          <thead>
            <tr>
              <th style="width: 140px;">Receipt #</th>
              <th>Payment Date</th>
              <th>Credited Account</th>
              <th>Reference / Slip</th>
              <th class="text-end">Amount Paid</th>
              <th class="text-end" style="width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $pay): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $pay['id'] ?>" class="badge badge-ios-black font-monospace text-decoration-none">
                    <?= htmlspecialchars($pay['receipt_number']) ?>
                  </a>
                </td>
                <td class="text-muted small"><?= FormatHelper::date($pay['payment_date']) ?></td>
                <td>
                  <span class="small fw-semibold"><?= htmlspecialchars($primaryBank['bank_name']) ?></span>
                  <span class="text-muted small d-block"><?= htmlspecialchars($primaryBank['branch']) ?></span>
                </td>
                <td class="text-muted small font-monospace"><?= htmlspecialchars($pay['reference_no'] ?: '-') ?></td>
                <td class="text-end fw-bold font-monospace text-success"><?= FormatHelper::currency($pay['amount']) ?></td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $pay['id'] ?>&mode=preview" target="_blank" class="btn-logout" title="Receipt PDF">
                      <i data-lucide="file-text"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $pay['id'] ?>" class="btn-logout" title="View Receipt">
                      <i data-lucide="eye"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Bank Transfer Info & Notes -->
  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
      <div class="ios-card p-4">
        <h4 class="card-title-clean mb-2">Deposit / Remittance Account</h4>
        <p class="text-muted small mb-3">All payments for this invoice are credited to your primary account:</p>
        <div class="p-3 bg-light" style="border-radius: var(--radius-sm); border: var(--border-subtle);">
          <div><strong>Bank:</strong> <?= htmlspecialchars($primaryBank['bank_name']) ?></div>
          <div><strong>Branch:</strong> <?= htmlspecialchars($primaryBank['branch']) ?></div>
          <div><strong>Account Name:</strong> <?= htmlspecialchars($primaryBank['account_name']) ?></div>
          <div><strong>Account Number:</strong> <?= htmlspecialchars($primaryBank['account_number']) ?></div>
        </div>
      </div>
    </div>

    <?php if (!empty($invoice['notes'])): ?>
      <div class="col-12 col-lg-6">
        <div class="ios-card p-4 h-100">
          <h4 class="card-title-clean mb-2">Invoice Notes</h4>
          <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Record Payment Modal -->
  <?php if (!in_array($invoice['status'], ['paid', 'cancelled'], true)): ?>
    <div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--radius-lg); border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
          <div class="modal-header border-0 pb-0">
            <div>
              <h5 class="modal-title fw-bold" id="recordPaymentModalLabel">Record Payment</h5>
              <span class="text-muted small">Apply payment to Invoice <strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form action="<?= BASE_URL ?>/index.php?page=payment_store" method="POST" enctype="multipart/form-data">
            <?= AuthHelper::csrfInput() ?>
            <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
            <input type="hidden" name="client_id" value="<?= $invoice['client_id'] ?>">
            <input type="hidden" name="redirect_to" value="billing">
            <input type="hidden" name="payment_method" value="bank_transfer">

            <div class="modal-body pt-3">
              <!-- Invoice Summary Box -->
              <div class="p-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm);">
                <div class="d-flex justify-content-between mb-1 small text-muted">
                  <span>Client:</span>
                  <strong class="text-dark"><?= htmlspecialchars($invoice['company_name']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-muted">
                  <span>Total Amount:</span>
                  <span class="font-monospace fw-semibold"><?= FormatHelper::currency($invoice['total_amount']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1 small text-muted">
                  <span>Already Paid:</span>
                  <span class="font-monospace text-success fw-semibold"><?= FormatHelper::currency($invoice['paid_amount']) ?></span>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top">
                  <span class="fw-bold text-dark">Remaining Balance Due:</span>
                  <span class="fw-bold font-monospace fs-6 text-danger"><?= FormatHelper::currency($invoice['balance_due']) ?></span>
                </div>
              </div>

              <!-- Credited Account (One Central Account) -->
              <div class="mb-3">
                <label class="form-label-ios">Credited Business Account</label>
                <div class="p-2 px-3 bg-light d-flex align-items-center gap-2" style="border-radius: var(--radius-sm); border: 1px solid #e2e8f0; font-size: 0.88rem; font-weight: 600;">
                  <i data-lucide="building-2" style="width: 16px; height: 16px; color: #059669;"></i>
                  <span><?= htmlspecialchars($primaryBank['bank_name']) ?> &bull; <?= htmlspecialchars($primaryBank['branch']) ?> (<?= htmlspecialchars($primaryBank['account_number']) ?>)</span>
                </div>
                <span class="text-muted small" style="font-size: 0.75rem;">Payment automatically credited to your central business bank account.</span>
              </div>

              <div class="row g-3">
                <!-- Payment Date -->
                <div class="col-6">
                  <label for="modal_payment_date" class="form-label-ios">Payment Date <span class="text-danger">*</span></label>
                  <input type="date" id="modal_payment_date" name="payment_date" value="<?= date('Y-m-d') ?>" class="form-control-ios" required>
                </div>

                <!-- Amount -->
                <div class="col-6">
                  <label for="modal_amount" class="form-label-ios">Amount Received <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" min="0.01" max="<?= (float)$invoice['balance_due'] ?>" id="modal_amount" name="amount" value="<?= (float)$invoice['balance_due'] ?>" class="form-control-ios font-monospace fw-bold" required>
                </div>

                <!-- Reference No -->
                <div class="col-12">
                  <label for="modal_reference_no" class="form-label-ios">Transaction Reference / Slip No. (Optional)</label>
                  <input type="text" id="modal_reference_no" name="reference_no" class="form-control-ios" placeholder="e.g. TXN-982314 or Deposit Ref">
                </div>

                <!-- Proof Attachment -->
                <div class="col-12">
                  <label for="modal_proof_file" class="form-label-ios">Upload Bank Slip / Proof (Optional)</label>
                  <input type="file" id="modal_proof_file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="form-control-ios" style="padding: 6px 12px; font-size: 0.85rem;">
                </div>

                <!-- Notes -->
                <div class="col-12">
                  <label for="modal_notes" class="form-label-ios">Internal Notes (Optional)</label>
                  <input type="text" id="modal_notes" name="notes" class="form-control-ios" placeholder="e.g. Received via online banking">
                </div>
              </div>
            </div>

            <div class="modal-footer border-0 pt-0">
              <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn-ios-primary" style="background: #059669; border-color: #059669;">
                <i data-lucide="check"></i>
                <span>Confirm & Record Payment</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const shareBtn = document.getElementById('sharePdfBtn');
  if (shareBtn) {
    shareBtn.addEventListener('click', async () => {
      const shareUrl = "<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $invoice['id'] ?>&mode=preview";
      const shareTitle = "Curtiss Invoice <?= htmlspecialchars($invoice['invoice_number']) ?>";
      const shareText = "Invoice from Curtiss for <?= htmlspecialchars($invoice['company_name']) ?> (Total: <?= FormatHelper::currency($invoice['total_amount']) ?>)";

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
      alert('Invoice PDF link copied to clipboard!');
    }).catch(() => {
      prompt('Copy this link:', text);
    });
  }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
