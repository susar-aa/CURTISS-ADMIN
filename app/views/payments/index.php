<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $payments */
/** @var int $totalPayments */
/** @var int $totalPages */
/** @var int $currentPage */
/** @var string $search */
/** @var string $method */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Payments & Receipts</h1>
      <p><?= number_format($totalPayments) ?> recorded transaction<?= $totalPayments === 1 ? '' : 's' ?></p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=payment_create" class="btn-ios-primary">
        <i data-lucide="plus"></i>
        <span>Record Payment</span>
      </a>
    </div>
  </div>

  <!-- Filters Card -->
  <div class="ios-card mb-4 p-3">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-center">
      <input type="hidden" name="page" value="payments">

      <div class="col-8 col-md-9 col-lg-10">
        <div class="position-relative">
          <input 
            type="text" 
            name="q" 
            value="<?= htmlspecialchars($search ?? '') ?>" 
            class="form-control-ios ps-5" 
            placeholder="Search by receipt #, company, invoice #, reference..."
          >
          <div class="position-absolute top-50 translate-middle-y text-muted" style="left: 16px;">
            <i data-lucide="search" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
      </div>

      <div class="col-4 col-md-3 col-lg-2 d-flex gap-2">
        <button type="submit" class="btn-ios-primary flex-fill justify-content-center" style="padding: 10px 14px;">
          Filter
        </button>
        <?php if (!empty($search)): ?>
          <a href="<?= BASE_URL ?>/index.php?page=payments" class="btn-ios-secondary" title="Clear Filters" style="padding: 10px 14px;">
            <i data-lucide="x"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Payments List Container -->
  <div class="ios-card p-0 overflow-hidden">
    <?php if (!empty($payments)): ?>
      <!-- 1. Mobile Card View (< 768px) -->
      <div class="d-md-none p-3 d-flex flex-column gap-3">
        <?php foreach ($payments as $p): ?>
          <div class="record-card-mobile">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $p['id'] ?>" class="badge badge-ios-black font-monospace text-decoration-none">
                <?= htmlspecialchars($p['receipt_number']) ?>
              </a>
              <?= FormatHelper::statusBadge($p['status']) ?>
            </div>

            <div class="d-flex align-items-baseline justify-content-between mb-1">
              <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $p['client_id'] ?>" class="fw-bold text-dark text-decoration-none" style="font-size: 1.05rem;">
                <?= htmlspecialchars($p['company_name']) ?>
              </a>
              <span class="fw-bold font-monospace" style="font-size: 1.1rem; color: #059669;">
                <?= FormatHelper::currency($p['amount']) ?>
              </span>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between text-muted small mb-3 gap-2">
              <span><?= FormatHelper::date($p['payment_date']) ?></span>
              <span>
                <?php if (!empty($p['invoice_number'])): ?>
                  Inv: <strong class="text-dark"><?= htmlspecialchars($p['invoice_number']) ?></strong>
                <?php else: ?>
                  Standalone
                <?php endif; ?>
              </span>
            </div>

            <div class="d-flex gap-2 pt-2 border-top">
              <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $p['id'] ?>" class="btn-ios-secondary flex-fill justify-content-center" style="padding: 7px 10px; font-size: 0.82rem;">
                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                <span>Receipt</span>
              </a>
              <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $p['id'] ?>&mode=preview" target="_blank" class="btn-ios-secondary flex-fill justify-content-center" style="padding: 7px 10px; font-size: 0.82rem;">
                <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                <span>PDF</span>
              </a>
              <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $p['id'] ?>&mode=download" class="btn-ios-secondary flex-fill justify-content-center" style="padding: 7px 10px; font-size: 0.82rem;">
                <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                <span>Download</span>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- 2. Desktop Table View (>= 768px) -->
      <div class="table-responsive d-none d-md-block">
        <table class="ios-table">
          <thead>
            <tr>
              <th style="width: 120px;">Receipt #</th>
              <th>Client</th>
              <th>Invoice</th>
              <th>Payment Date</th>
              <th>Credited Account</th>
              <th>Reference</th>
              <th class="text-end">Amount</th>
              <th>Status</th>
              <th class="text-end" style="width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $p['id'] ?>" class="badge badge-ios-black font-monospace text-decoration-none" style="letter-spacing: 0.05em;">
                    <?= htmlspecialchars($p['receipt_number']) ?>
                  </a>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $p['client_id'] ?>" class="fw-bold text-decoration-none text-dark d-block">
                    <?= htmlspecialchars($p['company_name']) ?>
                  </a>
                  <span class="text-muted small">ID: <?= htmlspecialchars($p['client_code']) ?></span>
                </td>
                <td>
                  <?php if (!empty($p['invoice_number'])): ?>
                    <a href="<?= BASE_URL ?>/index.php?page=billing_view&id=<?= $p['invoice_id'] ?>" class="text-decoration-none fw-medium text-dark">
                      <?= htmlspecialchars($p['invoice_number']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted small">Standalone</span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small"><?= FormatHelper::date($p['payment_date']) ?></td>
                <td class="small fw-semibold">
                  <i data-lucide="building-2" style="width: 14px; height: 14px; vertical-align: -2px; color: #059669; margin-right: 3px;"></i>
                  Commercial Bank
                </td>
                <td class="text-muted small font-monospace"><?= htmlspecialchars($p['reference_no'] ?: '-') ?></td>
                <td class="text-end fw-bold font-monospace"><?= FormatHelper::currency($p['amount']) ?></td>
                <td><?= FormatHelper::statusBadge($p['status']) ?></td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <a href="<?= BASE_URL ?>/index.php?page=payment_view&id=<?= $p['id'] ?>" class="btn-logout" title="View Receipt">
                      <i data-lucide="eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $p['id'] ?>&mode=preview" target="_blank" class="btn-logout" title="Preview Receipt PDF">
                      <i data-lucide="file-text"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=receipt_pdf&id=<?= $p['id'] ?>&mode=download" class="btn-logout" title="Download Receipt PDF">
                      <i data-lucide="download"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="d-flex align-items-center justify-content-between p-3 border-top">
          <span class="text-muted small">
            Page <?= $currentPage ?> of <?= $totalPages ?> (Total <?= $totalPayments ?>)
          </span>
          <div class="d-flex gap-2">
            <?php if ($currentPage > 1): ?>
              <a href="<?= BASE_URL ?>/index.php?page=payments&p=<?= $currentPage - 1 ?>&q=<?= urlencode($search) ?>&method=<?= urlencode($method) ?>" class="btn-ios-secondary">
                Previous
              </a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= BASE_URL ?>/index.php?page=payments&p=<?= $currentPage + 1 ?>&q=<?= urlencode($search) ?>&method=<?= urlencode($method) ?>" class="btn-ios-secondary">
                Next
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon-wrap"><i data-lucide="credit-card"></i></div>
        <p class="empty-text">No payment records found matching the criteria.</p>
        <a href="<?= BASE_URL ?>/index.php?page=payment_create" class="btn-ios-primary mt-3" style="width: auto;">+ Record First Payment</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
