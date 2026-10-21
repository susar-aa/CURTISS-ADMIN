<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $invoices */
/** @var int $totalInvoices */
/** @var int $totalPages */
/** @var int $currentPage */
/** @var string $search */
/** @var string $status */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Billing & Invoices</h1>
      <p><?= number_format($totalInvoices) ?> billing record<?= $totalInvoices === 1 ? '' : 's' ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <form action="<?= BASE_URL ?>/index.php?page=billing_generate_recurring" method="POST" class="d-inline" onsubmit="return confirm('Generate invoices for all subscriptions currently due?');">
        <?= AuthHelper::csrfInput() ?>
        <input type="hidden" name="redirect_to" value="billing">
        <button type="submit" class="btn-ios-secondary" style="padding: 10px 16px;">
          <i data-lucide="refresh-cw"></i>
          <span>Run Subscription Billing</span>
        </button>
      </form>
      <a href="<?= BASE_URL ?>/index.php?page=billing_create" class="btn-ios-primary" style="width: auto;">
        <i data-lucide="plus"></i>
        <span>Create Invoice</span>
      </a>
    </div>
  </div>

  <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #ecfdf5; color: #047857;">
      <i data-lucide="check-circle" style="width: 16px; height: 16px; display: inline-block; vertical-align: -3px;"></i>
      <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['flash_info'])): ?>
    <div class="alert alert-info py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #f0f9ff; color: #0369a1;">
      <i data-lucide="info" style="width: 16px; height: 16px; display: inline-block; vertical-align: -3px;"></i>
      <?= htmlspecialchars($_SESSION['flash_info']); unset($_SESSION['flash_info']); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['billing_error'])): ?>
    <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <i data-lucide="alert-triangle" style="width: 16px; height: 16px; display: inline-block; vertical-align: -3px;"></i>
      <?= htmlspecialchars($_SESSION['billing_error']); unset($_SESSION['billing_error']); ?>
    </div>
  <?php endif; ?>

  <!-- Filter & Search Card -->
  <div class="ios-card mb-4 p-3">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-center">
      <input type="hidden" name="page" value="billing">

      <div class="col-12 col-md-6 col-lg-7">
        <div class="position-relative">
          <input 
            type="text" 
            name="q" 
            value="<?= htmlspecialchars($search ?? '') ?>" 
            class="form-control-ios ps-5" 
            placeholder="Search by invoice number, company, or client code..."
          >
          <div class="position-absolute top-50 translate-middle-y text-muted" style="left: 16px;">
            <i data-lucide="search" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3 col-lg-3">
        <select name="status" class="form-control-ios">
          <option value="">All Statuses</option>
          <option value="unpaid" <?= ($status ?? '') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
          <option value="partially_paid" <?= ($status ?? '') === 'partially_paid' ? 'selected' : '' ?>>Partially Paid</option>
          <option value="paid" <?= ($status ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
          <option value="draft" <?= ($status ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
        <button type="submit" class="btn-ios-primary" style="padding: 10px 16px;">
          Filter
        </button>
        <?php if (!empty($search) || !empty($status)): ?>
          <a href="<?= BASE_URL ?>/index.php?page=billing" class="btn-ios-secondary" title="Clear Filters" style="padding: 10px 14px;">
            <i data-lucide="x"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Invoices Table -->
  <div class="ios-card p-0 overflow-hidden">
    <?php if (!empty($invoices)): ?>
      <div class="table-responsive">
        <table class="ios-table">
          <thead>
            <tr>
              <th style="width: 120px;">Invoice #</th>
              <th>Client</th>
              <th>Issue Date</th>
              <th>Due Date</th>
              <th class="text-end">Total Amount</th>
              <th class="text-end">Balance Due</th>
              <th>Status</th>
              <th class="text-end" style="width: 130px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($invoices as $inv): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=billing_view&id=<?= $inv['id'] ?>" class="badge badge-ios-black font-monospace text-decoration-none" style="letter-spacing: 0.05em;">
                    <?= htmlspecialchars($inv['invoice_number']) ?>
                  </a>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $inv['client_id'] ?>" class="fw-bold text-decoration-none text-dark d-block">
                    <?= htmlspecialchars($inv['company_name']) ?>
                  </a>
                  <span class="text-muted small">ID: <?= htmlspecialchars($inv['client_code']) ?></span>
                </td>
                <td class="text-muted small"><?= FormatHelper::date($inv['issue_date']) ?></td>
                <td class="text-muted small"><?= FormatHelper::date($inv['due_date']) ?></td>
                <td class="text-end fw-semibold"><?= FormatHelper::currency($inv['total_amount']) ?></td>
                <td class="text-end fw-bold" style="color: <?= (float)$inv['balance_due'] > 0 ? 'var(--c-danger)' : 'inherit' ?>;">
                  <?= FormatHelper::currency($inv['balance_due']) ?>
                </td>
                <td><?= FormatHelper::statusBadge($inv['status']) ?></td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <?php if (!in_array($inv['status'], ['paid', 'cancelled'], true)): ?>
                      <a href="<?= BASE_URL ?>/index.php?page=payment_create&invoice_id=<?= $inv['id'] ?>" class="btn-logout" title="Record Payment" style="color: #059669;">
                        <i data-lucide="check-circle-2"></i>
                      </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/index.php?page=billing_view&id=<?= $inv['id'] ?>" class="btn-logout" title="View Details">
                      <i data-lucide="eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $inv['id'] ?>&mode=preview" target="_blank" class="btn-logout" title="Preview PDF">
                      <i data-lucide="file-text"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $inv['id'] ?>&mode=download" class="btn-logout" title="Download PDF">
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
            Page <?= $currentPage ?> of <?= $totalPages ?> (Total <?= $totalInvoices ?>)
          </span>
          <div class="d-flex gap-2">
            <?php if ($currentPage > 1): ?>
              <a href="<?= BASE_URL ?>/index.php?page=billing&p=<?= $currentPage - 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="btn-ios-secondary">
                Previous
              </a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= BASE_URL ?>/index.php?page=billing&p=<?= $currentPage + 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>" class="btn-ios-secondary">
                Next
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon-wrap"><i data-lucide="file-text"></i></div>
        <p class="empty-text">No invoices found matching the selected filters.</p>
        <a href="<?= BASE_URL ?>/index.php?page=billing_create" class="btn-ios-primary mt-3" style="width: auto;">+ Create New Invoice</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
