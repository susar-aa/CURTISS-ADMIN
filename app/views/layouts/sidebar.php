<?php
declare(strict_types=1);
/** @var string $activeNav */
$currentNav = $activeNav ?? 'dashboard';
$user = AuthHelper::getCurrentUser();
?>
<aside class="desktop-sidebar">
  <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="sidebar-brand">
    <img src="<?= BASE_URL ?>/public/assets/images/logo.png" alt="Curtiss" style="height: 32px; max-width: 140px; object-fit: contain;">
  </a>

  <ul class="sidebar-menu">
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="nav-link-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=clients" class="nav-link-item <?= $currentNav === 'clients' ? 'active' : '' ?>">
        <i data-lucide="users"></i>
        <span>Clients</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=billing" class="nav-link-item <?= $currentNav === 'billing' ? 'active' : '' ?>">
        <i data-lucide="file-text"></i>
        <span>Billing</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=payments" class="nav-link-item <?= $currentNav === 'payments' ? 'active' : '' ?>">
        <i data-lucide="credit-card"></i>
        <span>Payments</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=reminders" class="nav-link-item <?= $currentNav === 'reminders' ? 'active' : '' ?>">
        <i data-lucide="bell"></i>
        <span>Reminders</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=reports" class="nav-link-item <?= $currentNav === 'reports' ? 'active' : '' ?>">
        <i data-lucide="bar-chart-3"></i>
        <span>Reports</span>
      </a>
    </li>
    <li>
      <a href="<?= BASE_URL ?>/index.php?page=settings" class="nav-link-item <?= $currentNav === 'settings' ? 'active' : '' ?>">
        <i data-lucide="settings"></i>
        <span>Settings</span>
      </a>
    </li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-profile-bar">
      <div class="user-info">
        <span class="user-name"><?= htmlspecialchars($user['full_name'] ?? 'Admin') ?></span>
        <span class="user-role"><?= htmlspecialchars($user['role'] ?? 'Admin') ?></span>
      </div>
      <a href="<?= BASE_URL ?>/index.php?page=logout" class="btn-logout" title="Sign Out">
        <i data-lucide="log-out"></i>
      </a>
    </div>
  </div>
</aside>
