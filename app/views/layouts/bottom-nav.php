<?php
declare(strict_types=1);
/** @var string $activeNav */
$currentNav = $activeNav ?? 'dashboard';
?>
<nav class="mobile-bottom-nav">
  <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="mobile-nav-item <?= $currentNav === 'dashboard' ? 'active' : '' ?>">
    <i data-lucide="layout-dashboard"></i>
    <span>Home</span>
  </a>
  <a href="<?= BASE_URL ?>/index.php?page=clients" class="mobile-nav-item <?= $currentNav === 'clients' ? 'active' : '' ?>">
    <i data-lucide="users"></i>
    <span>Clients</span>
  </a>
  <a href="<?= BASE_URL ?>/index.php?page=billing" class="mobile-nav-item <?= $currentNav === 'billing' ? 'active' : '' ?>">
    <i data-lucide="file-text"></i>
    <span>Billing</span>
  </a>
  <a href="<?= BASE_URL ?>/index.php?page=payments" class="mobile-nav-item <?= $currentNav === 'payments' ? 'active' : '' ?>">
    <i data-lucide="credit-card"></i>
    <span>Pay</span>
  </a>
  <a href="<?= BASE_URL ?>/index.php?page=settings" class="mobile-nav-item <?= $currentNav === 'settings' ? 'active' : '' ?>">
    <i data-lucide="settings"></i>
    <span>Settings</span>
  </a>
</nav>
