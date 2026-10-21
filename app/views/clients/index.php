<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $clients */
/** @var int $totalClients */
/** @var int $totalPages */
/** @var int $currentPage */
/** @var string $search */
/** @var string $status */
/** @var bool $archived */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1><?= $archived ? 'Archived Clients' : 'Clients' ?></h1>
      <p><?= number_format($totalClients) ?> registered business<?= $totalClients === 1 ? '' : 'es' ?></p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($archived): ?>
        <a href="<?= BASE_URL ?>/index.php?page=clients" class="btn-ios-secondary">
          <i data-lucide="arrow-left"></i>
          <span>Active Clients</span>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/index.php?page=clients&archived=1" class="btn-ios-secondary" title="View Archived">
          <i data-lucide="archive"></i>
          <span class="d-none d-md-inline">Archived</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?page=client_create" class="btn-ios-primary" style="width: auto;">
          <i data-lucide="plus"></i>
          <span>New Client</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filter & Search Card -->
  <div class="ios-card mb-4 p-3">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-2 align-items-center">
      <input type="hidden" name="page" value="clients">
      <?php if ($archived): ?>
        <input type="hidden" name="archived" value="1">
      <?php endif; ?>

      <div class="col-12 col-md-6 col-lg-7">
        <div class="position-relative">
          <input 
            type="text" 
            name="q" 
            value="<?= htmlspecialchars($search ?? '') ?>" 
            class="form-control-ios ps-5" 
            placeholder="Search by company name, client ID, or phone..."
          >
          <div class="position-absolute top-50 translate-middle-y text-muted" style="left: 16px;">
            <i data-lucide="search" style="width: 18px; height: 18px;"></i>
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3 col-lg-3">
        <select name="status" class="form-control-ios">
          <option value="">All Statuses</option>
          <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="suspended" <?= ($status ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>

      <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
        <button type="submit" class="btn-ios-primary" style="padding: 10px 16px;">
          Filter
        </button>
        <?php if (!empty($search) || !empty($status)): ?>
          <a href="<?= BASE_URL ?>/index.php?page=clients<?= $archived ? '&archived=1' : '' ?>" class="btn-ios-secondary" title="Clear Filters" style="padding: 10px 14px;">
            <i data-lucide="x"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Clients List Table -->
  <div class="ios-card p-0 overflow-hidden">
    <?php if (!empty($clients)): ?>
      <div class="table-responsive">
        <table class="ios-table">
          <thead>
            <tr>
              <th style="width: 100px;">ID</th>
              <th>Company & Contact</th>
              <th>Phone / WhatsApp</th>
              <th>System</th>
              <th>Subscriptions</th>
              <th>Status</th>
              <th class="text-end" style="width: 120px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clients as $c): ?>
              <tr>
                <td>
                  <span class="badge badge-ios-black font-monospace" style="letter-spacing: 0.05em;">
                    <?= htmlspecialchars($c['client_code']) ?>
                  </span>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $c['id'] ?>" class="fw-bold text-decoration-none text-dark d-block">
                    <?= htmlspecialchars($c['company_name']) ?>
                  </a>
                  <span class="text-muted small"><?= htmlspecialchars($c['contact_person']) ?></span>
                </td>
                <td>
                  <div><?= htmlspecialchars($c['phone']) ?></div>
                  <?php if (!empty($c['whatsapp'])): ?>
                    <span class="text-muted small">WA: <?= htmlspecialchars($c['whatsapp']) ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="fw-medium"><?= htmlspecialchars($c['curtiss_system'] ?: '-') ?></span>
                  <?php if (!empty($c['system_url'])): ?>
                    <div class="small">
                      <a href="<?= htmlspecialchars($c['system_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-muted text-decoration-none">
                        <i data-lucide="external-link" style="width: 12px; height: 12px; vertical-align: -1px;"></i>
                        <?= htmlspecialchars(parse_url($c['system_url'], PHP_URL_HOST) ?: $c['system_url']) ?>
                      </a>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-ios-subtle">
                    <?= (int)$c['active_subscriptions'] ?> Active
                  </span>
                </td>
                <td>
                  <?= FormatHelper::statusBadge($c['status']) ?>
                </td>
                <td class="text-end">
                  <div class="d-inline-flex gap-1">
                    <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $c['id'] ?>" class="btn-logout" title="View Profile">
                      <i data-lucide="eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/index.php?page=client_edit&id=<?= $c['id'] ?>" class="btn-logout" title="Edit Client">
                      <i data-lucide="pencil"></i>
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
            Page <?= $currentPage ?> of <?= $totalPages ?> (Total <?= $totalClients ?>)
          </span>
          <div class="d-flex gap-2">
            <?php if ($currentPage > 1): ?>
              <a href="<?= BASE_URL ?>/index.php?page=clients&p=<?= $currentPage - 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?><?= $archived ? '&archived=1' : '' ?>" class="btn-ios-secondary">
                Previous
              </a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
              <a href="<?= BASE_URL ?>/index.php?page=clients&p=<?= $currentPage + 1 ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?><?= $archived ? '&archived=1' : '' ?>" class="btn-ios-secondary">
                Next
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <div class="empty-icon-wrap">
          <i data-lucide="users"></i>
        </div>
        <p class="empty-text">No clients found matching the selected criteria.</p>
        <?php if (!empty($search) || !empty($status)): ?>
          <a href="<?= BASE_URL ?>/index.php?page=clients" class="btn-ios-secondary mt-3">Reset Filters</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/index.php?page=client_create" class="btn-ios-primary mt-3" style="width: auto;">+ Add First Client</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
