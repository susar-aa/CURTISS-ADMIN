<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var string $nextRec */
/** @var array $clients */
/** @var ?array $invoice */
/** @var ?int $preselectedClientId */
/** @var ?string $error */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Record Payment</h1>
      <p>Log received funds and issue official receipt</p>
    </div>
    <div>
      <a href="<?= BASE_URL ?>/index.php?page=payments" class="btn-ios-secondary">
        <i data-lucide="arrow-left"></i>
        <span>Cancel</span>
      </a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="ios-card p-4">
    <form action="<?= BASE_URL ?>/index.php?page=payment_store" method="POST" enctype="multipart/form-data" autocomplete="off">
      <?= AuthHelper::csrfInput() ?>

      <?php if (!empty($invoice)): ?>
        <input type="hidden" name="invoice_id" value="<?= $invoice['id'] ?>">
        <!-- Invoice Applied Notice -->
        <div class="alert py-3 px-3 mb-4" style="border-radius: 16px; background-color: #f8f8fa; border: 1px solid #e4e4e7;">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="fw-bold">Payment for Invoice #<?= htmlspecialchars($invoice['invoice_number']) ?></span>
            <span class="badge badge-ios-black"><?= FormatHelper::currency($invoice['total_amount']) ?> Total</span>
          </div>
          <div class="d-flex justify-content-between text-muted small">
            <span>Client: <?= htmlspecialchars($invoice['company_name']) ?></span>
            <span>Remaining Balance Due: <strong class="text-danger"><?= FormatHelper::currency($invoice['balance_due']) ?></strong></span>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-3">
        <!-- Receipt Number & Date -->
        <div class="col-12 col-md-4">
          <label class="form-label-ios">Receipt Number (5-Digit Sequential)</label>
          <input 
            type="text" 
            value="<?= htmlspecialchars($nextRec) ?>" 
            class="form-control-ios font-monospace bg-light" 
            style="font-weight: 700; letter-spacing: 0.05em;" 
            readonly 
            disabled
          >
          <span class="text-muted small">Auto-generated sequential receipt ID.</span>
        </div>

        <div class="col-12 col-md-4">
          <label for="payment_date" class="form-label-ios">Payment Date <span class="text-danger">*</span></label>
          <input 
            type="date" 
            id="payment_date" 
            name="payment_date" 
            value="<?= date('Y-m-d') ?>" 
            class="form-control-ios" 
            required
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="amount" class="form-label-ios">Amount Received (LKR) <span class="text-danger">*</span></label>
          <input 
            type="number" 
            step="0.01" 
            id="amount" 
            name="amount" 
            value="<?= !empty($invoice) ? (float)$invoice['balance_due'] : '' ?>" 
            class="form-control-ios font-monospace" 
            style="font-weight: 700;" 
            placeholder="0.00" 
            required 
            autofocus
          >
        </div>

        <!-- Client Selector -->
        <div class="col-12 col-md-6">
          <label for="client_id" class="form-label-ios">Client Organization <span class="text-danger">*</span></label>
          <select id="client_id" name="client_id" class="form-control-ios" required <?= !empty($invoice) ? 'style="pointer-events: none; background: #f8f8fa;"' : '' ?>>
            <option value="">-- Choose Client --</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $preselectedClientId === (int)$c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['company_name']) ?> (ID: <?= htmlspecialchars($c['client_code']) ?> &bull; <?= htmlspecialchars($c['contact_person']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (!empty($invoice)): ?>
            <input type="hidden" name="client_id" value="<?= $invoice['client_id'] ?>">
          <?php endif; ?>
        </div>

        <!-- Credited Business Account -->
        <div class="col-12 col-md-6">
          <label class="form-label-ios">Credited Business Account</label>
          <div class="form-control-ios bg-light d-flex align-items-center gap-2" style="font-weight: 600;">
            <i data-lucide="building-2" style="width: 16px; height: 16px; color: #059669;"></i>
            <span>Commercial Bank &bull; Kurunegala (8018526390)</span>
          </div>
          <input type="hidden" name="payment_method" value="bank_transfer">
          <?php if (!empty($invoice)): ?>
            <input type="hidden" name="redirect_to" value="billing">
          <?php endif; ?>
          <span class="text-muted small">Fixed central account. All incoming payments credit here.</span>
        </div>

        <!-- Reference Number -->
        <div class="col-12 col-md-6">
          <label for="reference_no" class="form-label-ios">Transaction Reference / Cheque No.</label>
          <input 
            type="text" 
            id="reference_no" 
            name="reference_no" 
            class="form-control-ios" 
            placeholder="e.g. Bank Ref #TXN9817234 or Cheque #004512"
          >
          <span class="text-muted small">Checked against duplicate records.</span>
        </div>

        <!-- Payment Proof Attachment -->
        <div class="col-12 col-md-6">
          <label for="proof_file" class="form-label-ios">Upload Payment Slip / Proof (Optional)</label>
          <input 
            type="file" 
            id="proof_file" 
            name="proof_file" 
            class="form-control-ios" 
            accept="image/*,.pdf"
          >
          <span class="text-muted small">Supported: JPG, PNG, PDF (Max 5MB).</span>
        </div>

        <!-- Custom Receipt Description (For standalone payments) -->
        <div class="col-12">
          <label for="custom_description" class="form-label-ios">Receipt Description / Service Allocation</label>
          <input 
            type="text" 
            id="custom_description" 
            name="custom_description" 
            class="form-control-ios" 
            placeholder="e.g. Monthly Retainer / Annual POS Maintenance Subscription"
            value="<?= !empty($invoice) ? ('Settlement for Invoice #' . $invoice['invoice_number']) : '' ?>"
          >
        </div>

        <!-- Notes -->
        <div class="col-12">
          <label for="notes" class="form-label-ios">Internal Remarks / Cashier Notes</label>
          <textarea 
            id="notes" 
            name="notes" 
            rows="3" 
            class="form-control-ios" 
            placeholder="Additional details regarding deposit date, clearing status, or bank teller..."
          ></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="col-12 mt-4 pt-2 d-flex justify-content-end gap-2">
          <a href="<?= BASE_URL ?>/index.php?page=payments" class="btn-ios-secondary">Cancel</a>
          <button type="submit" class="btn-ios-primary" style="width: auto; padding-left: 28px; padding-right: 28px;">
            <i data-lucide="check"></i>
            <span>Record Payment & Issue Receipt</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
