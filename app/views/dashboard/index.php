<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $currentUser */
/** @var array $metrics */
/** @var array $recentPayments */
/** @var array $upcomingDue */
?>

<main class="main-content">
  <!-- Top Bar / Greeting Header -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Overview</h1>
      <p><?= date('l, F j, Y') ?> &bull; <?= APP_COMPANY ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge badge-ios-black px-3 py-2">
        <i data-lucide="shield-check" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i>
        <?= htmlspecialchars($currentUser['role'] ?? 'Admin') ?>
      </span>
    </div>
  </div>

  <!-- Metric KPI Cards Grid -->
  <div class="row g-3 mb-4">
    <!-- Total Clients -->
    <div class="col-6 col-lg-4">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Total Clients</span>
          <div class="metric-icon-wrap">
            <i data-lucide="users"></i>
          </div>
        </div>
        <div>
          <h2 class="metric-value"><?= number_format($metrics['total_clients']) ?></h2>
          <div class="metric-subtext">Registered organizations</div>
        </div>
      </div>
    </div>

    <!-- Active Clients -->
    <div class="col-6 col-lg-4">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Active Clients</span>
          <div class="metric-icon-wrap">
            <i data-lucide="user-check"></i>
          </div>
        </div>
        <div>
          <h2 class="metric-value"><?= number_format($metrics['active_clients']) ?></h2>
          <div class="metric-subtext">With running services</div>
        </div>
      </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-12 col-lg-4">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Monthly Revenue</span>
          <div class="metric-icon-wrap">
            <i data-lucide="trending-up"></i>
          </div>
        </div>
        <div>
          <h2 class="metric-value"><?= FormatHelper::currency($metrics['monthly_revenue']) ?></h2>
          <div class="metric-subtext">Collected in <?= date('F Y') ?></div>
        </div>
      </div>
    </div>

    <!-- Outstanding Amount -->
    <div class="col-12 col-lg-4">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Outstanding Balance</span>
          <div class="metric-icon-wrap">
            <i data-lucide="wallet"></i>
          </div>
        </div>
        <div>
          <h2 class="metric-value"><?= FormatHelper::currency($metrics['outstanding']) ?></h2>
          <div class="metric-subtext">Unsettled receivables</div>
        </div>
      </div>
    </div>

    <!-- Upcoming Payments -->
    <div class="col-6 col-lg-4">
      <a href="<?= BASE_URL ?>/index.php?page=reminders" class="text-decoration-none">
        <div class="ios-card metric-card">
          <div class="metric-header">
            <span class="metric-label">Upcoming Due</span>
            <div class="metric-icon-wrap">
              <i data-lucide="calendar"></i>
            </div>
          </div>
          <div>
            <h2 class="metric-value"><?= number_format($metrics['upcoming_count']) ?></h2>
            <div class="metric-subtext">Due within 30 days &rarr;</div>
          </div>
        </div>
      </a>
    </div>

    <!-- Overdue Payments -->
    <div class="col-6 col-lg-4">
      <a href="<?= BASE_URL ?>/index.php?page=reminders" class="text-decoration-none">
        <div class="ios-card metric-card">
          <div class="metric-header">
            <span class="metric-label" style="color: var(--c-danger);">Overdue</span>
            <div class="metric-icon-wrap" style="background: var(--c-danger-soft); color: var(--c-danger);">
              <i data-lucide="alert-circle"></i>
            </div>
          </div>
          <div>
            <h2 class="metric-value" style="color: var(--c-danger);"><?= number_format($metrics['overdue_count']) ?></h2>
            <div class="metric-subtext" style="color: var(--c-danger);">View overdue timeline &rarr;</div>
          </div>
        </div>
      </a>
    </div>
  </div>

  <!-- Detailed Lists Section -->
  <div class="row g-4">
    <!-- Recent Payments -->
    <div class="col-12 col-lg-6">
      <div class="ios-card h-100">
        <div class="card-header-clean">
          <h3 class="card-title-clean">Recent Payments</h3>
          <span class="text-muted small">Latest completed transactions</span>
        </div>

        <?php if (!empty($recentPayments)): ?>
          <!-- Mobile Card List -->
          <div class="d-md-none p-3 pt-0">
            <div class="d-flex flex-column gap-2">
              <?php foreach ($recentPayments as $payment): ?>
                <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                  <div class="min-w-0 pe-2">
                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars($payment['company_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($payment['receipt_number']) ?> &bull; <?= FormatHelper::date($payment['payment_date']) ?></div>
                  </div>
                  <div class="text-end fw-bold font-monospace flex-shrink-0" style="color: #059669; font-size: 0.92rem;">
                    +<?= FormatHelper::currency($payment['amount']) ?>
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
                  <th>Receipt</th>
                  <th>Date</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentPayments as $payment): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($payment['company_name']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($payment['client_code']) ?></div>
                    </td>
                    <td><span class="badge badge-ios-subtle"><?= htmlspecialchars($payment['receipt_number']) ?></span></td>
                    <td class="text-muted small"><?= FormatHelper::date($payment['payment_date']) ?></td>
                    <td class="text-end fw-semibold"><?= FormatHelper::currency($payment['amount']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon-wrap">
              <i data-lucide="receipt"></i>
            </div>
            <p class="empty-text">No payment records logged yet.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Upcoming Due Dates -->
    <div class="col-12 col-lg-6">
      <div class="ios-card h-100">
        <div class="card-header-clean">
          <h3 class="card-title-clean">Upcoming Due Invoices</h3>
          <span class="text-muted small">Pending settlements</span>
        </div>

        <?php if (!empty($upcomingDue)): ?>
          <!-- Mobile Card List -->
          <div class="d-md-none p-3 pt-0">
            <div class="d-flex flex-column gap-2">
              <?php foreach ($upcomingDue as $due): ?>
                <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                  <div class="min-w-0 pe-2">
                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars($due['company_name']) ?></div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($due['invoice_number']) ?> &bull; Due <?= FormatHelper::date($due['due_date']) ?></div>
                  </div>
                  <div class="text-end flex-shrink-0">
                    <div class="fw-bold font-monospace" style="color: var(--c-danger); font-size: 0.92rem;">
                      <?= FormatHelper::currency($due['balance_due']) ?>
                    </div>
                    <div><?= FormatHelper::statusBadge($due['status']) ?></div>
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
                  <th>Invoice</th>
                  <th>Due Date</th>
                  <th class="text-end">Balance</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($upcomingDue as $due): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($due['company_name']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($due['client_code']) ?></div>
                    </td>
                    <td><span class="badge badge-ios-subtle"><?= htmlspecialchars($due['invoice_number']) ?></span></td>
                    <td>
                      <span class="text-muted small"><?= FormatHelper::date($due['due_date']) ?></span>
                      <div><?= FormatHelper::statusBadge($due['status']) ?></div>
                    </td>
                    <td class="text-end fw-semibold"><?= FormatHelper::currency($due['balance_due']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon-wrap">
              <i data-lucide="check-circle-2"></i>
            </div>
            <p class="empty-text">All client accounts are currently settled.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
