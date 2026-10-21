<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $settings */
/** @var array $bankAccounts */
/** @var array $currentUser */
/** @var ?string $flashSuccess */
/** @var ?string $flashError */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Settings & Configuration</h1>
      <p>Company branding, bank remittal details, and administrative controls</p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=backup_db" class="btn-ios-secondary">
        <i data-lucide="database"></i>
        <span>Download Backup</span>
      </a>
    </div>
  </div>

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

  <!-- Mobile Account & Quick Links Card -->
  <div class="ios-card p-3 mb-4 d-lg-none">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="d-flex align-items-center gap-3">
        <div class="metric-icon-wrap" style="width: 44px; height: 44px; border-radius: 50%;">
          <i data-lucide="user"></i>
        </div>
        <div>
          <div class="fw-bold"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?></div>
          <div class="text-muted small"><?= htmlspecialchars($currentUser['email'] ?? '') ?> &bull; <?= htmlspecialchars($currentUser['role'] ?? 'Admin') ?></div>
        </div>
      </div>
      <a href="<?= BASE_URL ?>/index.php?page=logout" class="btn-ios-secondary text-danger" style="border-color: #fee2e2; background: #fef2f2; padding: 6px 12px; font-size: 0.8rem;">
        <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
        <span>Sign Out</span>
      </a>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
      <a href="<?= BASE_URL ?>/index.php?page=reminders" class="btn-ios-secondary justify-content-center">
        <i data-lucide="bell" style="width: 15px; height: 15px;"></i>
        <span>Reminders</span>
      </a>
      <a href="<?= BASE_URL ?>/index.php?page=reports" class="btn-ios-secondary justify-content-center">
        <i data-lucide="bar-chart-3" style="width: 15px; height: 15px;"></i>
        <span>Reports</span>
      </a>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Company Information Settings -->
    <div class="col-12 col-lg-7">
      <div class="ios-card p-4">
        <h3 class="card-title-clean mb-3">Company Profile & Branding</h3>
        <p class="text-muted small mb-4">These details appear on commercial invoices, receipts, and public payment pages.</p>

        <form action="<?= BASE_URL ?>/index.php?page=settings_company" method="POST" autocomplete="off">
          <?= AuthHelper::csrfInput() ?>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label for="company_name" class="form-label-ios">Company Name</label>
              <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? 'Curtiss') ?>" class="form-control-ios" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="company_email" class="form-label-ios">Finance Email</label>
              <input type="email" id="company_email" name="company_email" value="<?= htmlspecialchars($settings['company_email'] ?? 'trycurtiss@gmail.com') ?>" class="form-control-ios" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="company_phone" class="form-label-ios">Contact Phone</label>
              <input type="text" id="company_phone" name="company_phone" value="<?= htmlspecialchars($settings['company_phone'] ?? '0761407875') ?>" class="form-control-ios" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="company_website" class="form-label-ios">Official Website</label>
              <input type="text" id="company_website" name="company_website" value="<?= htmlspecialchars($settings['company_website'] ?? 'www.trycurtiss.com') ?>" class="form-control-ios" required>
            </div>

            <div class="col-12">
              <label for="company_address" class="form-label-ios">Registered Address</label>
              <textarea id="company_address" name="company_address" rows="2" class="form-control-ios"><?= htmlspecialchars($settings['company_address'] ?? '79, Dambakanda Estate, Boyagane, Kurunegala') ?></textarea>
            </div>

            <div class="col-12 text-end pt-2">
              <button type="submit" class="btn-ios-primary" style="width: auto; padding-left: 24px; padding-right: 24px;">
                <span>Save Company Details</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Password Management -->
    <!-- Admin Security: Password & 6-Digit PIN -->
    <div class="col-12 col-lg-5">
      <div class="ios-card p-4 h-100">
        <h3 class="card-title-clean mb-2">Admin Security</h3>
        <p class="text-muted small mb-3">Logged in as: <strong><?= htmlspecialchars($currentUser['username'] ?? 'admin') ?></strong></p>

        <!-- Mode switcher tabs -->
        <div class="auth-mode-tabs mb-4">
          <button type="button" class="auth-mode-tab active" id="secTabPinBtn" onclick="toggleSecMode('pin')">
            <i data-lucide="key-round" style="width: 14px; height: 14px;"></i>
            <span>6-Digit PIN</span>
          </button>
          <button type="button" class="auth-mode-tab" id="secTabPassBtn" onclick="toggleSecMode('password')">
            <i data-lucide="lock" style="width: 14px; height: 14px;"></i>
            <span>Password</span>
          </button>
        </div>

        <!-- 6-Digit PIN Form -->
        <div id="secPinSection">
          <form action="<?= BASE_URL ?>/index.php?page=settings_pin" method="POST" autocomplete="off">
            <?= AuthHelper::csrfInput() ?>

            <div class="mb-3">
              <label for="pin_current_password" class="form-label-ios">Current Password <span class="text-danger">*</span></label>
              <input type="password" id="pin_current_password" name="current_password" class="form-control-ios" placeholder="Enter password to verify" required>
            </div>

            <div class="mb-3">
              <label for="new_pin" class="form-label-ios">New 6-Digit PIN <span class="text-danger">*</span></label>
              <input 
                type="password" 
                id="new_pin" 
                name="new_pin" 
                class="form-control-ios font-monospace" 
                inputmode="numeric" 
                maxlength="6" 
                pattern="[0-9]{6}" 
                placeholder="6 digits (e.g. 123456)" 
                style="letter-spacing: 0.25em; font-size: 1.1rem;"
                required
              >
              <span class="text-muted" style="font-size: 0.75rem;">Must be exactly 6 numeric digits (0-9).</span>
            </div>

            <div class="mb-4">
              <label for="confirm_pin" class="form-label-ios">Confirm 6-Digit PIN <span class="text-danger">*</span></label>
              <input 
                type="password" 
                id="confirm_pin" 
                name="confirm_pin" 
                class="form-control-ios font-monospace" 
                inputmode="numeric" 
                maxlength="6" 
                pattern="[0-9]{6}" 
                placeholder="Repeat 6 digits" 
                style="letter-spacing: 0.25em; font-size: 1.1rem;"
                required
              >
            </div>

            <button type="submit" class="btn-ios-primary">
              <i data-lucide="check" style="width: 16px; height: 16px;"></i>
              <span>Save 6-Digit PIN</span>
            </button>
          </form>
        </div>

        <!-- Password Form -->
        <div id="secPasswordSection" style="display: none;">
          <form action="<?= BASE_URL ?>/index.php?page=settings_password" method="POST" autocomplete="off">
            <?= AuthHelper::csrfInput() ?>

            <div class="mb-3">
              <label for="current_password" class="form-label-ios">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control-ios" required>
            </div>

            <div class="mb-3">
              <label for="new_password" class="form-label-ios">New Password (Min 6 chars)</label>
              <input type="password" id="new_password" name="new_password" class="form-control-ios" required>
            </div>

            <div class="mb-4">
              <label for="confirm_password" class="form-label-ios">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control-ios" required>
            </div>

            <button type="submit" class="btn-ios-primary">
              <span>Change Password</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bank Account Management -->
  <div class="ios-card p-0 overflow-hidden mb-4">
    <div class="p-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <h3 class="card-title-clean">Bank Accounts & Public Remittance</h3>
        <span class="text-muted small">Manage bank accounts displayed to clients on the public payment page</span>
      </div>
      <div class="header-actions">
        <a href="<?= BASE_URL ?>/index.php?page=pay" target="_blank" class="btn-ios-secondary">
          <i data-lucide="eye"></i>
          <span>View Public Page</span>
        </a>
        <button type="button" class="btn-ios-primary" data-bs-toggle="modal" data-bs-target="#addBankModal">
          <i data-lucide="plus"></i>
          <span>Add Account</span>
        </button>
      </div>
    </div>

    <!-- Mobile Card View -->
    <div class="d-md-none p-3">
      <div class="d-flex flex-column gap-3">
        <?php foreach ($bankAccounts as $acc): ?>
          <div class="record-card-mobile">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fw-bold"><?= htmlspecialchars($acc['bank_name']) ?></span>
              <?php if (!empty($acc['is_primary'])): ?>
                <span class="badge badge-ios-black">Primary</span>
              <?php else: ?>
                <span class="badge badge-ios-subtle">Secondary</span>
              <?php endif; ?>
            </div>
            <div class="small text-muted mb-2">
              Branch: <?= htmlspecialchars($acc['branch']) ?><br>
              Account: <strong class="text-dark font-monospace"><?= htmlspecialchars($acc['account_number']) ?></strong><br>
              Name: <?= htmlspecialchars($acc['account_name']) ?>
            </div>
            <?php if (empty($acc['is_primary'])): ?>
              <div class="d-flex gap-2 pt-2 border-top">
                <form action="<?= BASE_URL ?>/index.php?page=settings_bank_primary" method="POST" class="flex-grow-1 m-0">
                  <?= AuthHelper::csrfInput() ?>
                  <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                  <button type="submit" class="btn-ios-secondary w-100 justify-content-center" style="font-size: 0.8rem; padding: 6px;">
                    <span>Set Primary</span>
                  </button>
                </form>
                <form action="<?= BASE_URL ?>/index.php?page=settings_bank_delete" method="POST" onsubmit="return confirm('Remove this bank account?');" class="m-0">
                  <?= AuthHelper::csrfInput() ?>
                  <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                  <button type="submit" class="btn-logout" title="Remove Account">
                    <i data-lucide="trash-2"></i>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Desktop Table -->
    <div class="table-responsive d-none d-md-block">
      <table class="ios-table">
        <thead>
          <tr>
            <th>Bank & Branch</th>
            <th>Account Name</th>
            <th>Account Number</th>
            <th>Status</th>
            <th class="text-end" style="width: 180px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bankAccounts as $acc): ?>
            <tr>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($acc['bank_name']) ?></div>
                <span class="text-muted small"><?= htmlspecialchars($acc['branch']) ?></span>
              </td>
              <td class="fw-medium"><?= htmlspecialchars($acc['account_name']) ?></td>
              <td class="font-monospace fw-bold"><?= htmlspecialchars($acc['account_number']) ?></td>
              <td>
                <?php if (!empty($acc['is_primary'])): ?>
                  <span class="badge badge-ios-black">Primary</span>
                <?php else: ?>
                  <span class="badge badge-ios-subtle">Secondary</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <?php if (empty($acc['is_primary'])): ?>
                    <form action="<?= BASE_URL ?>/index.php?page=settings_bank_primary" method="POST">
                      <?= AuthHelper::csrfInput() ?>
                      <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                      <button type="submit" class="btn-ios-secondary" style="padding: 4px 10px; font-size: 0.78rem;">
                        <span>Set Primary</span>
                      </button>
                    </form>

                    <form action="<?= BASE_URL ?>/index.php?page=settings_bank_delete" method="POST" onsubmit="return confirm('Remove this bank account?');">
                      <?= AuthHelper::csrfInput() ?>
                      <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                      <button type="submit" class="btn-logout" title="Remove Account">
                        <i data-lucide="trash-2"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Numbering & Database Restore Tools Row -->
  <div class="row g-4 mb-4">
    <!-- Numbering Prefixes -->
    <div class="col-12 col-lg-6">
      <div class="ios-card p-4 h-100">
        <h3 class="card-title-clean mb-2">Invoice & Receipt Numbering</h3>
        <p class="text-muted small mb-3">Configure document sequential prefixes.</p>

        <form action="<?= BASE_URL ?>/index.php?page=settings_numbering" method="POST">
          <?= AuthHelper::csrfInput() ?>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label for="invoice_prefix" class="form-label-ios">Invoice Prefix</label>
              <input type="text" id="invoice_prefix" name="invoice_prefix" value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-') ?>" class="form-control-ios font-monospace" required>
            </div>
            <div class="col-6">
              <label for="receipt_prefix" class="form-label-ios">Receipt Prefix</label>
              <input type="text" id="receipt_prefix" name="receipt_prefix" value="<?= htmlspecialchars($settings['receipt_prefix'] ?? 'REC-') ?>" class="form-control-ios font-monospace" required>
            </div>
          </div>
          <button type="submit" class="btn-ios-secondary w-100 justify-content-center">
            <span>Save Numbering Prefixes</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Database Restore Tool -->
    <div class="col-12 col-lg-6">
      <div class="ios-card p-4 h-100">
        <h3 class="card-title-clean mb-2 text-danger">Database Restore Tool</h3>
        <p class="text-muted small mb-3">Upload a previously downloaded SQL backup dump to restore data.</p>

        <form action="<?= BASE_URL ?>/index.php?page=settings_restore_db" method="POST" enctype="multipart/form-data" onsubmit="return confirm('WARNING: Restoring the database will overwrite existing records with the backup file. Proceed?');">
          <?= AuthHelper::csrfInput() ?>
          <div class="mb-3">
            <input type="file" name="sql_file" class="form-control-ios" accept=".sql" required>
          </div>
          <button type="submit" class="btn-ios-secondary w-100 justify-content-center text-danger border-danger">
            <i data-lucide="upload"></i>
            <span>Restore From SQL File</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<!-- Add Bank Account Modal -->
<div class="modal fade" id="addBankModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border: var(--border-subtle);">
      <form action="<?= BASE_URL ?>/index.php?page=settings_bank_add" method="POST">
        <?= AuthHelper::csrfInput() ?>

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold">Add Bank Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body pt-3">
          <div class="mb-3">
            <label for="bank_name" class="form-label-ios">Bank Name <span class="text-danger">*</span></label>
            <input type="text" id="bank_name" name="bank_name" placeholder="e.g. Commercial Bank" class="form-control-ios" required>
          </div>

          <div class="mb-3">
            <label for="branch" class="form-label-ios">Branch</label>
            <input type="text" id="branch" name="branch" placeholder="e.g. Kurunegala City Office" class="form-control-ios">
          </div>

          <div class="mb-3">
            <label for="account_name" class="form-label-ios">Account Name <span class="text-danger">*</span></label>
            <input type="text" id="account_name" name="account_name" placeholder="e.g. G L DASUN SUSARA SENARATHNE" class="form-control-ios" required>
          </div>

          <div class="mb-3">
            <label for="account_number" class="form-label-ios">Account Number <span class="text-danger">*</span></label>
            <input type="text" id="account_number" name="account_number" placeholder="e.g. 8018526390" class="form-control-ios font-monospace" required>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn-ios-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-ios-primary" style="width: auto;">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function toggleSecMode(mode) {
    const pinSec = document.getElementById('secPinSection');
    const pwdSec = document.getElementById('secPasswordSection');
    const tabPin = document.getElementById('secTabPinBtn');
    const tabPwd = document.getElementById('secTabPassBtn');

    if (mode === 'password') {
      pinSec.style.display = 'none';
      pwdSec.style.display = 'block';
      tabPin.classList.remove('active');
      tabPwd.classList.add('active');
    } else {
      pwdSec.style.display = 'none';
      pinSec.style.display = 'block';
      tabPwd.classList.remove('active');
      tabPin.classList.add('active');
    }
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
