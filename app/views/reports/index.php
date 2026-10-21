<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $monthlyRevenue */
/** @var array $clientOutstanding */
/** @var array $subscriptionAnalytics */
/** @var string $startDate */
/** @var string $endDate */

$totalRevenueInPeriod = array_sum(array_column($monthlyRevenue, 'total_revenue'));
$totalOutstandingAll = array_sum(array_column($clientOutstanding, 'total_outstanding'));
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Reports & Analytics</h1>
      <p>Financial performance, receivable aging, and recurring run-rates</p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=report_export_pdf&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" target="_blank" class="btn-ios-secondary">
        <i data-lucide="file-text"></i>
        <span>Export PDF</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=report_export_csv&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn-ios-secondary">
        <i data-lucide="download"></i>
        <span>Export CSV</span>
      </a>
    </div>
  </div>

  <!-- Date Range Filter Card -->
  <div class="ios-card p-3 mb-4">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-center">
      <input type="hidden" name="page" value="reports">

      <div class="col-12 col-md-4">
        <label class="form-label-ios small">Start Date</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control-ios">
      </div>

      <div class="col-12 col-md-4">
        <label class="form-label-ios small">End Date</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control-ios">
      </div>

      <div class="col-12 col-md-4 d-flex align-items-end">
        <button type="submit" class="btn-ios-primary w-100" style="padding: 11px;">
          Generate Report
        </button>
      </div>
    </form>
  </div>

  <!-- Summary KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Revenue in Selected Period</span>
          <div class="metric-icon-wrap"><i data-lucide="trending-up"></i></div>
        </div>
        <div>
          <h2 class="metric-value"><?= FormatHelper::currency($totalRevenueInPeriod) ?></h2>
          <div class="metric-subtext"><?= FormatHelper::date($startDate) ?> to <?= FormatHelper::date($endDate) ?></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label" style="color: var(--c-danger);">Total Outstanding Receivables</span>
          <div class="metric-icon-wrap" style="background: var(--c-danger-soft); color: var(--c-danger);"><i data-lucide="wallet"></i></div>
        </div>
        <div>
          <h2 class="metric-value" style="color: var(--c-danger);"><?= FormatHelper::currency($totalOutstandingAll) ?></h2>
          <div class="metric-subtext">Across <?= count($clientOutstanding) ?> client account<?= count($clientOutstanding) === 1 ? '' : 's' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Monthly Breakdown & Subscription Analytics -->
  <div class="row g-4 mb-4">
    <!-- Monthly Revenue Table -->
    <div class="col-12 col-lg-7">
      <div class="ios-card h-100 p-0 overflow-hidden">
        <div class="p-3 border-bottom">
          <h3 class="card-title-clean">Monthly Revenue Breakdown</h3>
          <span class="text-muted small">Aggregated completed payments</span>
        </div>
        <?php if (!empty($monthlyRevenue)): ?>
          <div class="table-responsive">
            <table class="ios-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th class="text-center">Transactions</th>
                  <th class="text-end">Revenue</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($monthlyRevenue as $m): ?>
                  <tr>
                    <td class="fw-bold"><?= date('F Y', strtotime($m['month_label'] . '-01')) ?></td>
                    <td class="text-center"><span class="badge badge-ios-subtle"><?= (int)$m['transaction_count'] ?></span></td>
                    <td class="text-end fw-bold font-monospace"><?= FormatHelper::currency($m['total_revenue']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state py-4">
            <p class="empty-text">No payment records found in this period.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Subscription MRR Analytics -->
    <div class="col-12 col-lg-5">
      <div class="ios-card h-100 p-0 overflow-hidden">
        <div class="p-3 border-bottom">
          <h3 class="card-title-clean">Subscription Revenue by Frequency</h3>
          <span class="text-muted small">Recurring commitments</span>
        </div>
        <?php if (!empty($subscriptionAnalytics)): ?>
          <div class="table-responsive">
            <table class="ios-table">
              <thead>
                <tr>
                  <th>Billing Frequency</th>
                  <th class="text-center">Active</th>
                  <th class="text-end">Volume</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($subscriptionAnalytics as $sub): ?>
                  <tr>
                    <td class="fw-bold"><?= ucfirst(htmlspecialchars($sub['frequency'])) ?></td>
                    <td class="text-center"><span class="badge badge-ios-black"><?= (int)$sub['active_count'] ?></span></td>
                    <td class="text-end fw-bold font-monospace"><?= FormatHelper::currency($sub['active_volume']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state py-4">
            <p class="empty-text">No active subscriptions configured.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Outstanding Receivables per Client -->
  <div class="ios-card p-0 overflow-hidden">
    <div class="p-3 border-bottom">
      <h3 class="card-title-clean">Client Outstanding Receivables</h3>
      <span class="text-muted small">Clients with unsettled invoices requiring collection</span>
    </div>

    <?php if (!empty($clientOutstanding)): ?>
      <!-- Mobile Card List -->
      <div class="d-md-none p-3">
        <div class="d-flex flex-column gap-3">
          <?php foreach ($clientOutstanding as $row): ?>
            <div class="record-card-mobile">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $row['id'] ?>" class="fw-bold text-decoration-none text-dark">
                  <?= htmlspecialchars($row['company_name']) ?>
                </a>
                <span class="badge badge-ios-warning"><?= (int)$row['unpaid_invoices_count'] ?> Unpaid</span>
              </div>
              <div class="small text-muted mb-2">
                Code: <span class="font-monospace">#<?= htmlspecialchars($row['client_code']) ?></span> &bull; <?= htmlspecialchars($row['contact_person']) ?>
                <?php if (!empty($row['phone'])): ?>
                  &bull; <a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="text-muted"><?= htmlspecialchars($row['phone']) ?></a>
                <?php endif; ?>
              </div>
              <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                <div>
                  <div class="text-muted" style="font-size: 0.72rem;">Outstanding Due</div>
                  <div class="fw-bold font-monospace text-danger" style="font-size: 1.05rem;"><?= FormatHelper::currency($row['total_outstanding']) ?></div>
                </div>
                <a href="<?= BASE_URL ?>/index.php?page=payment_create&client_id=<?= $row['id'] ?>" class="btn-ios-secondary" style="padding: 6px 14px; font-size: 0.8rem;">
                  <i data-lucide="plus" style="width: 14px; height: 14px;"></i>
                  <span>Record Pay</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Desktop Table -->
      <div class="table-responsive d-none d-md-block">
        <table class="ios-table">
          <thead>
            <tr>
              <th>Client</th>
              <th>Phone</th>
              <th class="text-center">Unsettled Invoices</th>
              <th class="text-end">Total Invoiced</th>
              <th class="text-end">Amount Paid</th>
              <th class="text-end">Balance Due</th>
              <th class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clientOutstanding as $row): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $row['id'] ?>" class="fw-bold text-decoration-none text-dark d-block">
                    <?= htmlspecialchars($row['company_name']) ?>
                  </a>
                  <span class="text-muted small">ID: <?= htmlspecialchars($row['client_code']) ?> &bull; Attn: <?= htmlspecialchars($row['contact_person']) ?></span>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($row['phone']) ?></td>
                <td class="text-center"><span class="badge badge-ios-warning"><?= (int)$row['unpaid_invoices_count'] ?></span></td>
                <td class="text-end"><?= FormatHelper::currency($row['total_invoiced']) ?></td>
                <td class="text-end text-success"><?= FormatHelper::currency($row['total_paid']) ?></td>
                <td class="text-end fw-bold font-monospace text-danger"><?= FormatHelper::currency($row['total_outstanding']) ?></td>
                <td class="text-end">
                  <a href="<?= BASE_URL ?>/index.php?page=payment_create&client_id=<?= $row['id'] ?>" class="btn-ios-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                    <span>Record Pay</span>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state py-4">
        <div class="empty-icon-wrap"><i data-lucide="check-circle-2"></i></div>
        <p class="empty-text">Outstanding accounts are completely settled.</p>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
