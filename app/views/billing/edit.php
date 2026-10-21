<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $invoice */
/** @var array $items */
/** @var ?string $error */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Edit Invoice</h1>
      <p><?= htmlspecialchars($invoice['invoice_number']) ?> &bull; <?= htmlspecialchars($invoice['company_name']) ?></p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=billing_view&id=<?= $invoice['id'] ?>" class="btn-ios-secondary">
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

  <form id="invoiceForm" action="<?= BASE_URL ?>/index.php?page=billing_update" method="POST" autocomplete="off">
    <?= AuthHelper::csrfInput() ?>
    <input type="hidden" name="id" value="<?= $invoice['id'] ?>">

    <!-- Invoice Header Card -->
    <div class="ios-card p-4 mb-4">
      <div class="row g-3">
        <!-- Client (Readonly in edit) -->
        <div class="col-12 col-md-6">
          <label class="form-label-ios">Client</label>
          <input 
            type="text" 
            class="form-control-ios bg-light" 
            value="<?= htmlspecialchars($invoice['company_name']) ?> (ID: <?= htmlspecialchars($invoice['client_code']) ?>)" 
            readonly 
            disabled
          >
        </div>

        <!-- Invoice Number -->
        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label-ios">Invoice Number</label>
          <input 
            type="text" 
            value="<?= htmlspecialchars($invoice['invoice_number']) ?>" 
            class="form-control-ios font-monospace bg-light" 
            readonly 
            disabled
          >
        </div>

        <!-- Status -->
        <div class="col-12 col-md-6 col-lg-3">
          <label for="status" class="form-label-ios">Status</label>
          <select id="status" name="status" class="form-control-ios">
            <option value="draft" <?= $invoice['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="unpaid" <?= $invoice['status'] === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
          </select>
        </div>

        <!-- Dates & Terms -->
        <div class="col-12 col-md-4">
          <label for="issue_date" class="form-label-ios">Issue Date <span class="text-danger">*</span></label>
          <input 
            type="date" 
            id="issue_date" 
            name="issue_date" 
            value="<?= htmlspecialchars($invoice['issue_date']) ?>" 
            class="form-control-ios" 
            required
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="due_date" class="form-label-ios">Due Date <span class="text-danger">*</span></label>
          <input 
            type="date" 
            id="due_date" 
            name="due_date" 
            value="<?= htmlspecialchars($invoice['due_date']) ?>" 
            class="form-control-ios" 
            required
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="payment_terms" class="form-label-ios">Payment Terms</label>
          <input 
            type="text" 
            id="payment_terms" 
            name="payment_terms" 
            value="<?= htmlspecialchars($invoice['payment_terms'] ?? 'Due on receipt') ?>" 
            class="form-control-ios"
          >
        </div>
      </div>
    </div>

    <!-- Line Items Card -->
    <div class="ios-card p-4 mb-4">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
          <h3 class="card-title-clean">Itemized Services & Products</h3>
          <span class="text-muted small">Update descriptions, quantities, and pricing</span>
        </div>
        <button type="button" id="addRowBtn" class="btn-ios-secondary" style="padding: 6px 14px;">
          <i data-lucide="plus"></i>
          <span>Add Row</span>
        </button>
      </div>

      <div class="table-responsive">
        <table class="ios-table" id="itemsTable">
          <thead>
            <tr>
              <th style="width: 65%;">Item Description</th>
              <th style="width: 25%;" class="text-end">Amount (LKR)</th>
              <th style="width: 10%;" class="text-center">Order / Actions</th>
            </tr>
          </thead>
          <tbody id="itemsBody">
            <?php foreach ($items as $idx => $item): ?>
              <tr class="item-row">
                <td>
                  <input 
                    type="text" 
                    name="items[<?= $idx ?>][description]" 
                    value="<?= htmlspecialchars($item['description']) ?>" 
                    class="form-control-ios item-desc" 
                    required
                  >
                  <input type="hidden" name="items[<?= $idx ?>][quantity]" value="1.00">
                </td>
                <td>
                  <input 
                    type="number" 
                    step="0.01" 
                    name="items[<?= $idx ?>][unit_price]" 
                    value="<?= (float)$item['unit_price'] ?>" 
                    class="form-control-ios text-end item-price font-monospace" 
                    placeholder="0.00"
                    required
                  >
                </td>
                <td class="text-center">
                  <div class="d-inline-flex gap-1 align-items-center">
                    <button type="button" class="btn-logout move-up-btn" title="Move Up" style="padding: 3px 5px;">
                      <i data-lucide="chevron-up" style="width: 15px; height: 15px;"></i>
                    </button>
                    <button type="button" class="btn-logout move-down-btn" title="Move Down" style="padding: 3px 5px;">
                      <i data-lucide="chevron-down" style="width: 15px; height: 15px;"></i>
                    </button>
                    <button type="button" class="btn-logout text-danger remove-row-btn" title="Remove Item" style="padding: 3px 5px;">
                      <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Calculations & Notes Row -->
    <div class="row g-4 mb-4">
      <div class="col-12 col-lg-7">
        <div class="ios-card p-4 h-100">
          <label for="notes" class="form-label-ios">Notes & Instructions (Appears on PDF)</label>
          <textarea 
            id="notes" 
            name="notes" 
            rows="5" 
            class="form-control-ios"
          ><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="col-12 col-lg-5">
        <div class="ios-card p-4">
          <h4 class="card-title-clean mb-3">Invoice Summary</h4>
          
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span class="fw-bold font-monospace" id="calcSubtotal">LKR 0.00</span>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-muted">Discount (LKR)</span>
            <div style="width: 130px;">
              <input 
                type="number" 
                step="0.01" 
                id="discountInput" 
                name="discount" 
                value="<?= (float)$invoice['discount'] ?>" 
                class="form-control-ios text-end py-1 px-2"
              >
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted">Tax / VAT (LKR)</span>
            <div style="width: 130px;">
              <input 
                type="number" 
                step="0.01" 
                id="taxInput" 
                name="tax" 
                value="<?= (float)$invoice['tax'] ?>" 
                class="form-control-ios text-end py-1 px-2"
              >
            </div>
          </div>

          <hr style="border-top: 2px solid #09090b; margin: 12px 0;">

          <div class="d-flex align-items-center justify-content-between mb-4">
            <span class="h5 mb-0 fw-bold">Grand Total</span>
            <span class="h4 mb-0 fw-bold font-monospace" id="calcGrandTotal">LKR 0.00</span>
          </div>

          <button type="submit" class="btn-ios-primary" style="padding-top: 14px; padding-bottom: 14px;">
            <i data-lucide="check"></i>
            <span>Update Invoice & Regenerate PDF</span>
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let rowIndex = <?= count($items) ?>;

  function reindexRows() {
    const rows = document.querySelectorAll('#itemsBody .item-row');
    rows.forEach((row, idx) => {
      row.querySelectorAll('input').forEach(input => {
        const name = input.getAttribute('name');
        if (name && name.startsWith('items[')) {
          input.setAttribute('name', name.replace(/items\[\d+\]/, `items[${idx}]`));
        }
      });
    });
  }

  function calculateTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.item-row');
    rows.forEach(row => {
      const price = parseFloat(row.querySelector('.item-price').value) || 0;
      subtotal += price;
    });

    const discount = parseFloat(document.getElementById('discountInput').value) || 0;
    const tax = parseFloat(document.getElementById('taxInput').value) || 0;
    const grandTotal = Math.max(0, subtotal - discount + tax);

    document.getElementById('calcSubtotal').textContent = 'LKR ' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('calcGrandTotal').textContent = 'LKR ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  document.getElementById('addRowBtn').addEventListener('click', () => {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
      <td>
        <input type="text" name="items[${rowIndex}][description]" class="form-control-ios item-desc" placeholder="Item description" required>
        <input type="hidden" name="items[${rowIndex}][quantity]" value="1.00">
      </td>
      <td>
        <input type="number" step="0.01" name="items[${rowIndex}][unit_price]" value="0.00" class="form-control-ios text-end item-price font-monospace" placeholder="0.00" required>
      </td>
      <td class="text-center">
        <div class="d-inline-flex gap-1 align-items-center">
          <button type="button" class="btn-logout move-up-btn" title="Move Up" style="padding: 3px 5px;">
            <i data-lucide="chevron-up" style="width: 15px; height: 15px;"></i>
          </button>
          <button type="button" class="btn-logout move-down-btn" title="Move Down" style="padding: 3px 5px;">
            <i data-lucide="chevron-down" style="width: 15px; height: 15px;"></i>
          </button>
          <button type="button" class="btn-logout text-danger remove-row-btn" title="Remove Item" style="padding: 3px 5px;">
            <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i>
          </button>
        </div>
      </td>
    `;
    tbody.appendChild(tr);
    rowIndex++;
    if (typeof lucide !== 'undefined') lucide.createIcons();
    bindEvents(tr);
    reindexRows();
    calculateTotals();
  });

  function bindEvents(row) {
    row.querySelector('.item-price').addEventListener('input', calculateTotals);

    // Row reordering
    row.querySelector('.move-up-btn').addEventListener('click', () => {
      const prev = row.previousElementSibling;
      if (prev && prev.classList.contains('item-row')) {
        prev.insertAdjacentElement('beforebegin', row);
        reindexRows();
        calculateTotals();
      }
    });

    row.querySelector('.move-down-btn').addEventListener('click', () => {
      const next = row.nextElementSibling;
      if (next && next.classList.contains('item-row')) {
        next.insertAdjacentElement('afterend', row);
        reindexRows();
        calculateTotals();
      }
    });

    row.querySelector('.remove-row-btn').addEventListener('click', () => {
      if (document.querySelectorAll('.item-row').length > 1) {
        row.remove();
        reindexRows();
        calculateTotals();
      } else {
        alert('An invoice must have at least one line item.');
      }
    });
  }

  document.querySelectorAll('.item-row').forEach(bindEvents);
  document.getElementById('discountInput').addEventListener('input', calculateTotals);
  document.getElementById('taxInput').addEventListener('input', calculateTotals);

  calculateTotals();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
