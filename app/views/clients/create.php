<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var string $nextCode */
/** @var ?string $error */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>New Client</h1>
      <p>Register a company or business client</p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=clients" class="btn-ios-secondary">
        <i data-lucide="arrow-left"></i>
        <span>Back to List</span>
      </a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="ios-card p-4">
    <form action="<?= BASE_URL ?>/index.php?page=client_store" method="POST" autocomplete="off">
      <?= AuthHelper::csrfInput() ?>

      <div class="row g-3">
        <!-- Client ID & Status Row -->
        <div class="col-12 col-md-4">
          <label class="form-label-ios">Client ID (5-Digit Sequential)</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0" style="border-radius: 12px 0 0 12px; font-weight: 700; color: #71717a;">#</span>
            <input 
              type="text" 
              name="client_code" 
              value="<?= htmlspecialchars($nextCode) ?>" 
              class="form-control-ios border-start-0 font-monospace" 
              style="border-radius: 0 12px 12px 0; font-weight: 700; letter-spacing: 0.05em;" 
              required
            >
          </div>
          <span class="text-muted small">Auto-generated sequential identifier.</span>
        </div>

        <div class="col-12 col-md-4">
          <label for="status" class="form-label-ios">Initial Status</label>
          <select id="status" name="status" class="form-control-ios">
            <option value="active" selected>Active</option>
            <option value="suspended">Suspended</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label for="installation_date" class="form-label-ios">Installation Date</label>
          <input 
            type="date" 
            id="installation_date" 
            name="installation_date" 
            value="<?= date('Y-m-d') ?>" 
            class="form-control-ios"
          >
        </div>

        <!-- Business Details -->
        <div class="col-12 col-md-6">
          <label for="company_name" class="form-label-ios">Business / Company Name <span class="text-danger">*</span></label>
          <input 
            type="text" 
            id="company_name" 
            name="company_name" 
            class="form-control-ios" 
            placeholder="e.g. Apex Engineering Ltd" 
            required 
            autofocus
          >
        </div>

        <div class="col-12 col-md-6">
          <label for="contact_person" class="form-label-ios">Contact Person <span class="text-danger">*</span></label>
          <input 
            type="text" 
            id="contact_person" 
            name="contact_person" 
            class="form-control-ios" 
            placeholder="e.g. John Doe (Managing Director)" 
            required
          >
        </div>

        <!-- Contact Channels -->
        <div class="col-12 col-md-4">
          <label for="phone" class="form-label-ios">Phone Number <span class="text-danger">*</span></label>
          <input 
            type="tel" 
            id="phone" 
            name="phone" 
            class="form-control-ios" 
            placeholder="e.g. 0771234567" 
            required
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="whatsapp" class="form-label-ios">WhatsApp Number</label>
          <input 
            type="tel" 
            id="whatsapp" 
            name="whatsapp" 
            class="form-control-ios" 
            placeholder="e.g. 94771234567"
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="email" class="form-label-ios">Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            class="form-control-ios" 
            placeholder="e.g. contact@business.com"
          >
        </div>

        <!-- Curtiss System Information -->
        <div class="col-12 col-md-6">
          <label for="curtiss_system" class="form-label-ios">Purchased Curtiss System</label>
          <input 
            type="text" 
            id="curtiss_system" 
            name="curtiss_system" 
            list="curtiss_system_list" 
            class="form-control-ios" 
            placeholder="Select or type system..."
          >
          <datalist id="curtiss_system_list">
            <option value="Curtiss POS">
            <option value="Curtiss ERP">
            <option value="Curtiss Salon">
            <option value="Curtiss Pharmacy">
            <option value="Curtiss Auto POS">
            <option value="Curtiss Custom Web Application">
          </datalist>
        </div>

        <div class="col-12 col-md-6">
          <label for="system_url" class="form-label-ios">System URL / Domain</label>
          <input 
            type="url" 
            id="system_url" 
            name="system_url" 
            class="form-control-ios" 
            placeholder="https://client.trycurtiss.com"
          >
        </div>

        <!-- Address -->
        <div class="col-12">
          <label for="address" class="form-label-ios">Physical Business Address</label>
          <input 
            type="text" 
            id="address" 
            name="address" 
            class="form-control-ios" 
            placeholder="e.g. 124 Main Street, Colombo 03"
          >
        </div>

        <!-- Notes -->
        <div class="col-12">
          <label for="notes" class="form-label-ios">Internal Notes & Special Instructions</label>
          <textarea 
            id="notes" 
            name="notes" 
            rows="3" 
            class="form-control-ios" 
            placeholder="Technical details, billing preferences, key contacts..."
          ></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="col-12 mt-4 pt-2 d-flex justify-content-end">
          <div class="form-submit-actions">
            <a href="<?= BASE_URL ?>/index.php?page=clients" class="btn-ios-secondary">Cancel</a>
            <button type="submit" class="btn-ios-primary" style="padding-left: 28px; padding-right: 28px;">
              <i data-lucide="check"></i>
              <span>Save Client</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
