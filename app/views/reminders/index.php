<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $obligations */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Due Dates & Reminders</h1>
      <p>Billing timeline, overdue obligations, and WhatsApp client notifications</p>
    </div>
    <div class="header-actions">
      <form action="<?= BASE_URL ?>/index.php?page=billing_generate_recurring" method="POST" class="d-inline" onsubmit="return confirm('Generate invoices for all subscriptions currently due?');">
        <?= AuthHelper::csrfInput() ?>
        <input type="hidden" name="redirect_to" value="reminders">
        <button type="submit" class="btn-ios-secondary">
          <i data-lucide="refresh-cw"></i>
          <span>Run Billing</span>
        </button>
      </form>
      <a href="<?= BASE_URL ?>/index.php?page=pay" target="_blank" class="btn-ios-secondary">
        <i data-lucide="external-link"></i>
        <span>Public Link</span>
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

  <!-- Summary KPI Badges -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label" style="color: var(--c-danger);">Overdue</span>
          <div class="metric-icon-wrap" style="background: var(--c-danger-soft); color: var(--c-danger);"><i data-lucide="alert-circle"></i></div>
        </div>
        <div>
          <h3 class="metric-value" style="color: var(--c-danger);"><?= count($obligations['overdue']) ?></h3>
          <div class="metric-subtext">Past due deadline</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label" style="color: var(--c-warning);">Due Today</span>
          <div class="metric-icon-wrap" style="background: var(--c-warning-soft); color: var(--c-warning);"><i data-lucide="clock"></i></div>
        </div>
        <div>
          <h3 class="metric-value" style="color: var(--c-warning);"><?= count($obligations['due_today']) ?></h3>
          <div class="metric-subtext">Requires attention today</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Due in 7 Days</span>
          <div class="metric-icon-wrap"><i data-lucide="calendar"></i></div>
        </div>
        <div>
          <h3 class="metric-value"><?= count($obligations['due_7_days']) ?></h3>
          <div class="metric-subtext">Upcoming this week</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Future Due</span>
          <div class="metric-icon-wrap"><i data-lucide="calendar-days"></i></div>
        </div>
        <div>
          <h3 class="metric-value"><?= count($obligations['upcoming']) ?></h3>
          <div class="metric-subtext">Next billing cycles</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Obligations Timeline Groups -->
  <?php
    $sections = [
      ['key' => 'overdue', 'title' => 'Overdue Obligations', 'badge' => 'badge-ios-danger', 'icon' => 'alert-triangle'],
      ['key' => 'due_today', 'title' => 'Due Today', 'badge' => 'badge-ios-warning', 'icon' => 'clock'],
      ['key' => 'due_7_days', 'title' => 'Due within 7 Days', 'badge' => 'badge-ios-black', 'icon' => 'calendar'],
      ['key' => 'upcoming', 'title' => 'Upcoming Later', 'badge' => 'badge-ios-subtle', 'icon' => 'calendar-days']
    ];
  ?>

  <?php foreach ($sections as $sec): ?>
    <?php $items = $obligations[$sec['key']] ?? []; ?>
    <div class="ios-card p-0 overflow-hidden mb-4">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="<?= $sec['icon'] ?>" style="width: 18px; height: 18px;"></i>
          <h3 class="card-title-clean"><?= $sec['title'] ?></h3>
        </div>
        <span class="badge <?= $sec['badge'] ?>"><?= count($items) ?> Items</span>
      </div>

      <?php if (!empty($items)): ?>
        <!-- 1. Mobile Cards (< 768px) -->
        <div class="d-md-none p-3 d-flex flex-column gap-3">
          <?php foreach ($items as $item): ?>
            <?php 
              $waPhone = !empty($item['whatsapp']) ? $item['whatsapp'] : $item['phone'];
              $waUrl = Reminder::buildWhatsAppLink(
                $waPhone,
                $item['company_name'],
                (float)$item['amount_due'],
                FormatHelper::date($item['due_date']),
                $item['invoice_number'],
                $sec['key']
              );
              $sendUrl = BASE_URL . '/index.php?page=reminder_send&client_id=' . $item['client_id'] . 
                         '&invoice_id=' . ($item['invoice_id'] ?? '') . 
                         '&sub_id=' . ($item['subscription_id'] ?? '') . 
                         '&type=' . $sec['key'] . 
                         '&wa_url=' . urlencode($waUrl);
            ?>
            <div class="record-card-mobile">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge badge-ios-subtle"><?= htmlspecialchars($item['item_type']) ?></span>
                <span class="fw-bold font-monospace" style="color: #09090b; font-size: 1.05rem;">
                  <?= FormatHelper::currency($item['amount_due']) ?>
                </span>
              </div>
              <div class="mb-2">
                <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $item['client_id'] ?>" class="fw-bold text-dark text-decoration-none" style="font-size: 1.02rem;">
                  <?= htmlspecialchars($item['company_name']) ?>
                </a>
                <div class="text-muted small">Inv: <strong><?= htmlspecialchars($item['invoice_number']) ?></strong> &bull; Due: <?= FormatHelper::date($item['due_date']) ?></div>
                <?php if ($sec['key'] === 'overdue'): ?>
                  <span class="text-danger small fw-semibold"><?= $item['days_diff'] ?> days overdue</span>
                <?php endif; ?>
              </div>
              <div class="pt-2 border-top">
                <a href="<?= $sendUrl ?>" target="_blank" class="btn-ios-secondary w-100 justify-content-center" style="color: #059669; border-color: rgba(5, 150, 105, 0.25); background: #ecfdf5; font-size: 0.82rem; padding: 8px;">
                  <i data-lucide="message-square" style="width: 14px; height: 14px;"></i>
                  <span>Send WhatsApp Reminder</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- 2. Desktop Table (>= 768px) -->
        <div class="table-responsive d-none d-md-block">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Client</th>
                <th>Type & Item</th>
                <th>Due Date</th>
                <th class="text-end">Amount Due</th>
                <th class="text-end" style="width: 220px;">WhatsApp Reminder</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <?php 
                  $waPhone = !empty($item['whatsapp']) ? $item['whatsapp'] : $item['phone'];
                  $waUrl = Reminder::buildWhatsAppLink(
                    $waPhone,
                    $item['company_name'],
                    (float)$item['amount_due'],
                    FormatHelper::date($item['due_date']),
                    $item['invoice_number'],
                    $sec['key']
                  );
                  $sendUrl = BASE_URL . '/index.php?page=reminder_send&client_id=' . $item['client_id'] . 
                             '&invoice_id=' . ($item['invoice_id'] ?? '') . 
                             '&sub_id=' . ($item['subscription_id'] ?? '') . 
                             '&type=' . $sec['key'] . 
                             '&wa_url=' . urlencode($waUrl);
                ?>
                <tr>
                  <td>
                    <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $item['client_id'] ?>" class="fw-bold text-decoration-none text-dark d-block">
                      <?= htmlspecialchars($item['company_name']) ?>
                    </a>
                    <span class="text-muted small">ID: <?= htmlspecialchars($item['client_code']) ?> &bull; <?= htmlspecialchars($item['phone']) ?></span>
                  </td>
                  <td>
                    <span class="badge badge-ios-subtle"><?= htmlspecialchars($item['item_type']) ?></span>
                    <span class="fw-semibold ms-1"><?= htmlspecialchars($item['invoice_number']) ?></span>
                  </td>
                  <td>
                    <span class="fw-medium"><?= FormatHelper::date($item['due_date']) ?></span>
                    <?php if ($sec['key'] === 'overdue'): ?>
                      <span class="text-danger small d-block"><?= $item['days_diff'] ?> days overdue</span>
                    <?php elseif ($sec['key'] === 'due_7_days'): ?>
                      <span class="text-muted small d-block">in <?= $item['days_diff'] ?> days</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end fw-bold font-monospace">
                    <?= FormatHelper::currency($item['amount_due']) ?>
                  </td>
                  <td class="text-end">
                    <a href="<?= $sendUrl ?>" target="_blank" class="btn-ios-secondary" style="color: #059669; border-color: rgba(5, 150, 105, 0.2); background: #ecfdf5;">
                      <i data-lucide="message-square" style="width: 14px; height: 14px;"></i>
                      <span>Send Reminder</span>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state py-3">
          <p class="empty-text small">No items in this category.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
