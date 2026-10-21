<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $client */
/** @var array $stats */
/** @var array $subscriptions */
/** @var array $payments */
/** @var array $invoices */
/** @var array $activities */
/** @var string $tab */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
?>

<main class="main-content">
  <!-- Profile Header -->
  <div class="mobile-header">
    <div class="header-greeting">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge badge-ios-black font-monospace" style="letter-spacing: 0.05em; font-size: 0.8rem;">
          #<?= htmlspecialchars($client['client_code']) ?>
        </span>
        <?= FormatHelper::statusBadge($client['status']) ?>
        <?php if ($client['is_archived']): ?>
          <span class="badge badge-ios-warning">Archived</span>
        <?php endif; ?>
      </div>
      <h1><?= htmlspecialchars($client['company_name']) ?></h1>
      <p>Contact: <?= htmlspecialchars($client['contact_person']) ?> &bull; Client since <?= FormatHelper::date($client['created_at']) ?></p>
    </div>

    <div class="d-flex align-items-center gap-2">
      <a href="<?= BASE_URL ?>/index.php?page=client_edit&id=<?= $client['id'] ?>" class="btn-ios-secondary">
        <i data-lucide="pencil"></i>
        <span>Edit</span>
      </a>

      <!-- Archive / Restore Action -->
      <form action="<?= BASE_URL ?>/index.php?page=client_archive" method="POST" onsubmit="return confirm('<?= $client['is_archived'] ? 'Restore this client?' : 'Archive this client? All records will be preserved.' ?>');">
        <?= AuthHelper::csrfInput() ?>
        <input type="hidden" name="id" value="<?= $client['id'] ?>">
        <button type="submit" class="btn-ios-secondary" title="<?= $client['is_archived'] ? 'Restore' : 'Archive' ?>">
          <i data-lucide="<?= $client['is_archived'] ? 'rotate-ccw' : 'archive' ?>"></i>
          <span><?= $client['is_archived'] ? 'Restore' : 'Archive' ?></span>
        </button>
      </form>

      <button type="button" class="btn-ios-primary" style="width: auto;" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
        <i data-lucide="plus"></i>
        <span>Add Subscription</span>
      </button>
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

  <!-- Summary KPI Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Active Services</span>
          <div class="metric-icon-wrap"><i data-lucide="layers"></i></div>
        </div>
        <div>
          <h3 class="metric-value"><?= (int)$stats['active_subs'] ?></h3>
          <div class="metric-subtext">Total: <?= (int)$stats['total_subs'] ?> subscriptions</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Monthly Run-Rate</span>
          <div class="metric-icon-wrap"><i data-lucide="repeat"></i></div>
        </div>
        <div>
          <h3 class="metric-value"><?= FormatHelper::currency($stats['monthly_run_rate']) ?></h3>
          <div class="metric-subtext">Recurring MRR</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label">Total Invoiced</span>
          <div class="metric-icon-wrap"><i data-lucide="file-text"></i></div>
        </div>
        <div>
          <h3 class="metric-value"><?= FormatHelper::currency($stats['total_invoiced']) ?></h3>
          <div class="metric-subtext"><?= (int)$stats['total_invoices'] ?> invoices generated</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-lg-3">
      <div class="ios-card metric-card">
        <div class="metric-header">
          <span class="metric-label" style="color: <?= $stats['total_outstanding'] > 0 ? 'var(--c-danger)' : 'inherit' ?>;">Outstanding</span>
          <div class="metric-icon-wrap" style="<?= $stats['total_outstanding'] > 0 ? 'background: var(--c-danger-soft); color: var(--c-danger);' : '' ?>">
            <i data-lucide="wallet"></i>
          </div>
        </div>
        <div>
          <h3 class="metric-value" style="color: <?= $stats['total_outstanding'] > 0 ? 'var(--c-danger)' : 'inherit' ?>;">
            <?= FormatHelper::currency($stats['total_outstanding']) ?>
          </h3>
          <div class="metric-subtext">Paid: <?= FormatHelper::currency($stats['total_paid']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- iOS-inspired Tab Navigation -->
  <div class="ios-card p-2 mb-4">
    <div class="d-flex flex-wrap gap-1">
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>&tab=overview" 
         class="btn-ios-secondary <?= $tab === 'overview' ? 'bg-dark text-white' : '' ?>" style="border: none;">
        <i data-lucide="info"></i>
        <span>Overview</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>&tab=subscriptions" 
         class="btn-ios-secondary <?= $tab === 'subscriptions' ? 'bg-dark text-white' : '' ?>" style="border: none;">
        <i data-lucide="layers"></i>
        <span>Subscriptions (<?= count($subscriptions) ?>)</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>&tab=payments" 
         class="btn-ios-secondary <?= $tab === 'payments' ? 'bg-dark text-white' : '' ?>" style="border: none;">
        <i data-lucide="credit-card"></i>
        <span>Payments (<?= count($payments) ?>)</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>&tab=receipts" 
         class="btn-ios-secondary <?= $tab === 'receipts' ? 'bg-dark text-white' : '' ?>" style="border: none;">
        <i data-lucide="receipt"></i>
        <span>Invoices (<?= count($invoices) ?>)</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>&tab=activity" 
         class="btn-ios-secondary <?= $tab === 'activity' ? 'bg-dark text-white' : '' ?>" style="border: none;">
        <i data-lucide="activity"></i>
        <span>Activity Log</span>
      </a>
    </div>
  </div>

  <!-- TAB CONTENT -->

  <!-- 1. Overview Tab -->
  <?php if ($tab === 'overview'): ?>
    <div class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="ios-card h-100">
          <div class="card-header-clean">
            <h3 class="card-title-clean">Contact & Business Profile</h3>
          </div>
          <table class="ios-table">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 140px;">Company</td>
                <td class="fw-semibold"><?= htmlspecialchars($client['company_name']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Contact Person</td>
                <td><?= htmlspecialchars($client['contact_person']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Phone</td>
                <td>
                  <a href="tel:<?= htmlspecialchars($client['phone']) ?>" class="text-decoration-none text-dark fw-medium">
                    <i data-lucide="phone" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px;"></i>
                    <?= htmlspecialchars($client['phone']) ?>
                  </a>
                </td>
              </tr>
              <tr>
                <td class="text-muted">WhatsApp</td>
                <td>
                  <?php if (!empty($client['whatsapp'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/\D/', '', $client['whatsapp']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-success fw-medium">
                      <i data-lucide="message-square" style="width: 14px; height: 14px; display: inline-block; vertical-align: -2px;"></i>
                      <?= htmlspecialchars($client['whatsapp']) ?> (Chat on WhatsApp)
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td class="text-muted">Email</td>
                <td>
                  <?php if (!empty($client['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($client['email']) ?>" class="text-decoration-none text-dark">
                      <?= htmlspecialchars($client['email']) ?>
                    </a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td class="text-muted">Address</td>
                <td><?= nl2br(htmlspecialchars($client['address'] ?: '-')) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="ios-card h-100">
          <div class="card-header-clean">
            <h3 class="card-title-clean">Curtiss System Deployment</h3>
          </div>
          <table class="ios-table">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 140px;">System</td>
                <td class="fw-semibold"><?= htmlspecialchars($client['curtiss_system'] ?: 'None Specified') ?></td>
              </tr>
              <tr>
                <td class="text-muted">Live Domain / URL</td>
                <td>
                  <?php if (!empty($client['system_url'])): ?>
                    <a href="<?= htmlspecialchars($client['system_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-ios-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                      <i data-lucide="external-link" style="width: 12px; height: 12px;"></i>
                      <span>Launch App</span>
                    </a>
                    <span class="text-muted small ms-2"><?= htmlspecialchars($client['system_url']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td class="text-muted">Installed Date</td>
                <td><?= FormatHelper::date($client['installation_date']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">Account Status</td>
                <td><?= FormatHelper::statusBadge($client['status']) ?></td>
              </tr>
              <tr>
                <td class="text-muted">System Notes</td>
                <td><?= nl2br(htmlspecialchars($client['notes'] ?: 'No notes recorded.')) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- 2. Subscriptions Tab -->
  <?php if ($tab === 'subscriptions'): ?>
    <div class="ios-card p-0 overflow-hidden">
      <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <div>
          <h3 class="card-title-clean">Client Subscriptions & Recurring Plans</h3>
          <span class="text-muted small">Manage recurring billing, renewal due dates, and cycle amounts</span>
        </div>
        <button type="button" class="btn-ios-primary" style="width: auto; padding: 8px 16px; font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
          <i data-lucide="plus"></i>
          <span>Add Subscription</span>
        </button>
      </div>

      <?php if (!empty($subscriptions)): ?>
        <div class="table-responsive">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Service / Plan Name</th>
                <th>Frequency</th>
                <th>Amount</th>
                <th>Start Date</th>
                <th>Next Due Date</th>
                <th>Status</th>
                <th class="text-end" style="width: 160px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subscriptions as $sub): ?>
                <tr>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($sub['plan_name']) ?></div>
                    <span class="text-muted small">ID: SUB-<?= str_pad((string)$sub['id'], 4, '0', STR_PAD_LEFT) ?></span>
                  </td>
                  <td>
                    <span class="badge badge-ios-subtle"><?= ucfirst(htmlspecialchars($sub['frequency'])) ?></span>
                  </td>
                  <td class="fw-semibold">
                    <?= FormatHelper::currency($sub['amount']) ?>
                  </td>
                  <td class="text-muted small">
                    <?= FormatHelper::date($sub['start_date']) ?>
                  </td>
                  <td>
                    <div class="fw-medium"><?= FormatHelper::date($sub['next_due_date']) ?></div>
                    <span class="badge <?= $sub['computed_status']['badge'] ?>" style="font-size: 0.68rem;">
                      <?= htmlspecialchars($sub['computed_status']['label']) ?>
                    </span>
                  </td>
                  <td>
                    <?= FormatHelper::statusBadge($sub['status']) ?>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <!-- Renew Button -->
                      <button 
                        type="button" 
                        class="btn-ios-secondary" 
                        style="padding: 4px 10px; font-size: 0.78rem;" 
                        data-bs-toggle="modal" 
                        data-bs-target="#renewModal<?= $sub['id'] ?>"
                        title="Renew Subscription"
                      >
                        <i data-lucide="rotate-cw" style="width: 13px; height: 13px;"></i>
                        <span>Renew</span>
                      </button>

                      <!-- Edit Button -->
                      <button 
                        type="button" 
                        class="btn-logout" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editModal<?= $sub['id'] ?>"
                        title="Edit Plan"
                      >
                        <i data-lucide="pencil"></i>
                      </button>
                    </div>

                    <!-- Renew Modal for this subscription -->
                    <div class="modal fade text-start" id="renewModal<?= $sub['id'] ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 24px; border: var(--border-subtle);">
                          <form action="<?= BASE_URL ?>/index.php?page=subscription_renew" method="POST">
                            <?= AuthHelper::csrfInput() ?>
                            <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">

                            <div class="modal-header border-0 pb-0">
                              <h5 class="modal-title fw-bold">Renew Subscription</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body pt-3">
                              <p class="text-muted small mb-3">
                                Renewing <strong><?= htmlspecialchars($sub['plan_name']) ?></strong> (<?= ucfirst($sub['frequency']) ?> &bull; <?= FormatHelper::currency($sub['amount']) ?>).
                              </p>
                              
                              <div class="mb-3">
                                <label class="form-label-ios">Current Due Date</label>
                                <input type="text" class="form-control-ios bg-light" value="<?= FormatHelper::date($sub['next_due_date']) ?>" readonly disabled>
                              </div>

                              <div class="mb-3">
                                <label for="custom_due_date_<?= $sub['id'] ?>" class="form-label-ios">New Next Due Date (Leave blank to advance by <?= ucfirst($sub['frequency']) ?>)</label>
                                <input 
                                  type="date" 
                                  id="custom_due_date_<?= $sub['id'] ?>" 
                                  name="custom_due_date" 
                                  class="form-control-ios"
                                >
                              </div>
                            </div>

                            <div class="modal-footer border-0 pt-0">
                              <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn-ios-primary" style="width: auto;">Confirm Renewal</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                    <!-- Edit Modal for this subscription -->
                    <div class="modal fade text-start" id="editModal<?= $sub['id'] ?>" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 24px; border: var(--border-subtle);">
                          <form action="<?= BASE_URL ?>/index.php?page=subscription_update" method="POST">
                            <?= AuthHelper::csrfInput() ?>
                            <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                            <input type="hidden" name="client_id" value="<?= $client['id'] ?>">

                            <div class="modal-header border-0 pb-0">
                              <h5 class="modal-title fw-bold">Edit Subscription</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body pt-3">
                              <div class="mb-3">
                                <label class="form-label-ios">Plan Name</label>
                                <input type="text" name="plan_name" value="<?= htmlspecialchars($sub['plan_name']) ?>" class="form-control-ios" required>
                              </div>

                              <div class="row g-2 mb-3">
                                <div class="col-6">
                                  <label class="form-label-ios">Frequency</label>
                                  <select name="frequency" class="form-control-ios">
                                    <option value="monthly" <?= $sub['frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                    <option value="yearly" <?= $sub['frequency'] === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                    <option value="one_time" <?= $sub['frequency'] === 'one_time' ? 'selected' : '' ?>>One-Time</option>
                                    <option value="custom" <?= $sub['frequency'] === 'custom' ? 'selected' : '' ?>>Custom</option>
                                  </select>
                                </div>
                                <div class="col-6">
                                  <label class="form-label-ios">Amount (LKR)</label>
                                  <input type="number" step="0.01" name="amount" value="<?= (float)$sub['amount'] ?>" class="form-control-ios" required>
                                </div>
                              </div>

                              <div class="row g-2 mb-3">
                                <div class="col-6">
                                  <label class="form-label-ios">Start Date</label>
                                  <input type="date" name="start_date" value="<?= htmlspecialchars($sub['start_date']) ?>" class="form-control-ios" required>
                                </div>
                                <div class="col-6">
                                  <label class="form-label-ios">Next Due Date</label>
                                  <input type="date" name="next_due_date" value="<?= htmlspecialchars($sub['next_due_date'] ?? '') ?>" class="form-control-ios">
                                </div>
                              </div>

                              <div class="mb-3">
                                <label class="form-label-ios">Status</label>
                                <select name="status" class="form-control-ios">
                                  <option value="active" <?= $sub['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                  <option value="suspended" <?= $sub['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                  <option value="cancelled" <?= $sub['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                  <option value="expired" <?= $sub['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                                </select>
                              </div>
                            </div>

                            <div class="modal-footer border-0 pt-0">
                              <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Cancel</button>
                              <button type="submit" class="btn-ios-primary" style="width: auto;">Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon-wrap"><i data-lucide="layers"></i></div>
          <p class="empty-text">No subscriptions configured for this client yet.</p>
          <button type="button" class="btn-ios-primary mt-3" style="width: auto;" data-bs-toggle="modal" data-bs-target="#addSubscriptionModal">
            + Add First Subscription
          </button>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- 3. Payments Tab -->
  <?php if ($tab === 'payments'): ?>
    <div class="ios-card p-0 overflow-hidden">
      <div class="p-3 border-bottom">
        <h3 class="card-title-clean">Payment History</h3>
        <span class="text-muted small">Recorded transactions and financial settlements</span>
      </div>

      <?php if (!empty($payments)): ?>
        <div class="table-responsive">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Receipt #</th>
                <th>Invoice</th>
                <th>Date</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td><span class="badge badge-ios-subtle"><?= htmlspecialchars($p['receipt_number']) ?></span></td>
                  <td><?= htmlspecialchars($p['invoice_number'] ?: 'Direct Payment') ?></td>
                  <td class="text-muted small"><?= FormatHelper::date($p['payment_date']) ?></td>
                  <td><span class="badge badge-ios-gray"><?= ucwords(str_replace('_', ' ', $p['payment_method'])) ?></span></td>
                  <td class="text-muted small"><?= htmlspecialchars($p['reference_no'] ?: '-') ?></td>
                  <td class="text-end fw-semibold"><?= FormatHelper::currency($p['amount']) ?></td>
                  <td><?= FormatHelper::statusBadge($p['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon-wrap"><i data-lucide="credit-card"></i></div>
          <p class="empty-text">No payment history recorded for this client.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- 4. Receipts / Invoices Tab -->
  <?php if ($tab === 'receipts'): ?>
    <div class="ios-card p-0 overflow-hidden">
      <div class="p-3 border-bottom">
        <h3 class="card-title-clean">Client Invoices</h3>
        <span class="text-muted small">Issued billing statements and documents</span>
      </div>

      <?php if (!empty($invoices)): ?>
        <div class="table-responsive">
          <table class="ios-table">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($invoices as $inv): ?>
                <tr>
                  <td><span class="badge badge-ios-black"><?= htmlspecialchars($inv['invoice_number']) ?></span></td>
                  <td class="text-muted small"><?= FormatHelper::date($inv['issue_date']) ?></td>
                  <td class="text-muted small"><?= FormatHelper::date($inv['due_date']) ?></td>
                  <td class="text-end fw-semibold"><?= FormatHelper::currency($inv['total_amount']) ?></td>
                  <td class="text-end text-muted small"><?= FormatHelper::currency($inv['paid_amount']) ?></td>
                  <td class="text-end fw-bold"><?= FormatHelper::currency($inv['total_amount'] - $inv['paid_amount']) ?></td>
                  <td><?= FormatHelper::statusBadge($inv['status']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon-wrap"><i data-lucide="file-text"></i></div>
          <p class="empty-text">No invoices issued for this client yet.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- 5. Activity Log Tab -->
  <?php if ($tab === 'activity'): ?>
    <div class="ios-card p-0 overflow-hidden">
      <div class="p-3 border-bottom">
        <h3 class="card-title-clean">Audit Activity Log</h3>
        <span class="text-muted small">Recorded chronological timeline of client changes and financial events</span>
      </div>

      <?php if (!empty($activities)): ?>
        <div class="p-3">
          <div class="d-flex flex-column gap-3">
            <?php foreach ($activities as $act): ?>
              <div class="d-flex align-items-start gap-3 p-3" style="background-color: var(--c-surface); border-radius: var(--radius-sm); border: var(--border-subtle);">
                <div class="metric-icon-wrap" style="width: 32px; height: 32px; min-width: 32px;">
                  <i data-lucide="check" style="width: 15px; height: 15px;"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="fw-bold small"><?= htmlspecialchars($act['action']) ?></span>
                    <span class="text-muted small"><?= FormatHelper::date($act['created_at'], 'M d, Y h:i A') ?></span>
                  </div>
                  <p class="mb-0 text-muted small"><?= htmlspecialchars($act['description']) ?></p>
                  <?php if (!empty($act['staff_name'])): ?>
                    <span class="text-muted" style="font-size: 0.72rem;">By: <?= htmlspecialchars($act['staff_name']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon-wrap"><i data-lucide="activity"></i></div>
          <p class="empty-text">No audit events logged yet.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Add Subscription Modal -->
<div class="modal fade" id="addSubscriptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border: var(--border-subtle);">
      <form action="<?= BASE_URL ?>/index.php?page=subscription_store" method="POST">
        <?= AuthHelper::csrfInput() ?>
        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Add Service Subscription</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body pt-3">
          <div class="mb-3">
            <label for="sub_plan_name" class="form-label-ios">Plan / Service Name <span class="text-danger">*</span></label>
            <input 
              type="text" 
              id="sub_plan_name" 
              name="plan_name" 
              class="form-control-ios" 
              placeholder="e.g. Monthly Maintenance & Hosting" 
              required
            >
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="sub_frequency" class="form-label-ios">Billing Frequency</label>
              <select id="sub_frequency" name="frequency" class="form-control-ios">
                <option value="monthly" selected>Monthly</option>
                <option value="yearly">Yearly</option>
                <option value="one_time">One-Time</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div class="col-6">
              <label for="sub_amount" class="form-label-ios">Amount (LKR) <span class="text-danger">*</span></label>
              <input 
                type="number" 
                step="0.01" 
                id="sub_amount" 
                name="amount" 
                class="form-control-ios" 
                placeholder="0.00" 
                required
              >
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="sub_start_date" class="form-label-ios">Start Date <span class="text-danger">*</span></label>
              <input 
                type="date" 
                id="sub_start_date" 
                name="start_date" 
                value="<?= date('Y-m-d') ?>" 
                class="form-control-ios" 
                required
              >
            </div>
            <div class="col-6">
              <label for="sub_next_due_date" class="form-label-ios">Next Due Date (Optional)</label>
              <input 
                type="date" 
                id="sub_next_due_date" 
                name="next_due_date" 
                class="form-control-ios" 
                placeholder="Auto-calculated if blank"
              >
            </div>
          </div>

          <div class="mb-3">
            <label for="sub_status" class="form-label-ios">Status</label>
            <select id="sub_status" name="status" class="form-control-ios">
              <option value="active" selected>Active</option>
              <option value="suspended">Suspended</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-ios-primary" style="width: auto; padding-left: 24px; padding-right: 24px;">
            Save Subscription
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
