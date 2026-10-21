<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var string $nextInv */
/** @var array $clients */
/** @var ?int $selectedClientId */
/** @var ?string $error */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>New Invoice</h1>
      <p>Draft or issue a commercial invoice</p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=billing" class="btn-ios-secondary">
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

  <form id="invoiceForm" action="<?= BASE_URL ?>/index.php?page=billing_store" method="POST" autocomplete="off">
    <?= AuthHelper::csrfInput() ?>

    <!-- Invoice Header Card -->
    <div class="ios-card p-4 mb-4">
      <div class="row g-3">
        <!-- Client Selector -->
        <div class="col-12 col-md-6">
          <label for="client_id" class="form-label-ios">Select Client <span class="text-danger">*</span></label>
          <select id="client_id" name="client_id" class="form-control-ios" required>
            <option value="">-- Choose Client --</option>
            <?php foreach ($clients as $c): ?>
              <option 
                value="<?= $c['id'] ?>" 
                data-name="<?= htmlspecialchars($c['company_name'], ENT_QUOTES) ?>"
                data-code="<?= htmlspecialchars($c['client_code'], ENT_QUOTES) ?>"
                data-contact="<?= htmlspecialchars($c['contact_person'] ?? '', ENT_QUOTES) ?>"
                data-phone="<?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES) ?>"
                data-whatsapp="<?= htmlspecialchars($c['whatsapp'] ?? '', ENT_QUOTES) ?>"
                data-email="<?= htmlspecialchars($c['email'] ?? '', ENT_QUOTES) ?>"
                data-system="<?= htmlspecialchars($c['curtiss_system'] ?? '', ENT_QUOTES) ?>"
                <?= $selectedClientId === (int)$c['id'] ? 'selected' : '' ?>
              >
                <?= htmlspecialchars($c['company_name']) ?> (ID: <?= htmlspecialchars($c['client_code']) ?> &bull; <?= htmlspecialchars($c['contact_person']) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Dynamic Client Preview Card -->
          <div id="clientPreviewCard" class="mt-2 p-3 bg-light rounded-3 d-none border" style="font-size: 0.85rem;">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong id="cpName" class="text-dark"></strong>
              <span id="cpCode" class="badge badge-ios-subtle font-monospace"></span>
            </div>
            <div class="row g-2 text-muted">
              <div class="col-6 col-sm-4"><i data-lucide="user" style="width:12px; height:12px; display:inline-block; vertical-align:-1px;"></i> <span id="cpContact">-</span></div>
              <div class="col-6 col-sm-4"><i data-lucide="phone" style="width:12px; height:12px; display:inline-block; vertical-align:-1px;"></i> <span id="cpPhone">-</span></div>
              <div class="col-12 col-sm-4"><i data-lucide="server" style="width:12px; height:12px; display:inline-block; vertical-align:-1px;"></i> <span id="cpSystem">-</span></div>
            </div>
          </div>
        </div>

        <!-- Invoice Number -->
        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label-ios">Invoice Number</label>
          <input 
            type="text" 
            name="invoice_number" 
            value="<?= htmlspecialchars($nextInv) ?>" 
            class="form-control-ios font-monospace" 
            style="font-weight: 700; letter-spacing: 0.05em;" 
            required
          >
        </div>

        <!-- Status -->
        <div class="col-12 col-md-6 col-lg-3">
          <label for="status" class="form-label-ios">Invoice Status</label>
          <select id="status" name="status" class="form-control-ios">
            <option value="unpaid" selected>Finalize & Issue (Unpaid)</option>
            <option value="draft">Save as Draft</option>
          </select>
        </div>

        <!-- Dates & Terms -->
        <div class="col-12 col-md-4">
          <label for="issue_date" class="form-label-ios">Issue Date <span class="text-danger">*</span></label>
          <input 
            type="date" 
            id="issue_date" 
            name="issue_date" 
            value="<?= date('Y-m-d') ?>" 
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
            value="<?= date('Y-m-d', strtotime('+14 days')) ?>" 
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
            value="Due on receipt" 
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
          <span class="text-muted small">Add unlimited rows with live calculation</span>
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
            <tr class="item-row">
              <td>
                <input 
                  type="text" 
                  name="items[0][description]" 
                  class="form-control-ios item-desc" 
                  placeholder="e.g. Curtiss POS Software License & Setup" 
                  required
                >
                <input type="hidden" name="items[0][quantity]" value="1.00">
              </td>
              <td>
                <input 
                  type="number" 
                  step="0.01" 
                  name="items[0][unit_price]" 
                  value="0.00" 
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
            placeholder="Payment instructions, bank wire remarks, or project delivery notes..."
          ></textarea>
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
                value="0.00" 
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
                value="0.00" 
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
            <span>Create & Generate Invoice</span>
          </button>
        </div>
      </div>
    </div>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let rowIndex = 1;

  // Dynamic Client Info Preview
  const clientSelect = document.getElementById('client_id');
  const previewCard = document.getElementById('clientPreviewCard');
  const cpName = document.getElementById('cpName');
  const cpCode = document.getElementById('cpCode');
  const cpContact = document.getElementById('cpContact');
  const cpPhone = document.getElementById('cpPhone');
  const cpSystem = document.getElementById('cpSystem');

  function updateClientPreview() {
    const opt = clientSelect.selectedOptions[0];
    if (opt && opt.value) {
      cpName.textContent = opt.dataset.name || '';
      cpCode.textContent = 'ID: ' + (opt.dataset.code || '');
      cpContact.textContent = opt.dataset.contact ? opt.dataset.contact : 'No contact specified';
      cpPhone.textContent = opt.dataset.phone || opt.dataset.whatsapp || 'No contact phone';
      cpSystem.textContent = opt.dataset.system ? opt.dataset.system : 'No system assigned';
      previewCard.classList.remove('d-none');
    } else {
      previewCard.classList.add('d-none');
    }
  }

  clientSelect.addEventListener('change', updateClientPreview);
  updateClientPreview();

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

  // Add row
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
