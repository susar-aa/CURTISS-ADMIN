<?php
declare(strict_types=1);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

/** @var array $client */
/** @var ?string $error */
?>

<main class="main-content">
  <!-- Top Bar -->
  <div class="mobile-header">
    <div class="header-greeting">
      <h1>Edit Client</h1>
      <p><?= htmlspecialchars($client['company_name']) ?> &bull; ID #<?= htmlspecialchars($client['client_code']) ?></p>
    </div>
    <div class="header-actions">
      <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>" class="btn-ios-secondary">
        <i data-lucide="arrow-left"></i>
        <span>Back to Profile</span>
      </a>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 px-3 small border-0 mb-4" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <div class="ios-card p-4">
    <form action="<?= BASE_URL ?>/index.php?page=client_update" method="POST" autocomplete="off">
      <?= AuthHelper::csrfInput() ?>
      <input type="hidden" name="id" value="<?= $client['id'] ?>">

      <div class="row g-3">
        <!-- Client ID & Status Row -->
        <div class="col-12 col-md-4">
          <label class="form-label-ios">Client ID</label>
          <input 
            type="text" 
            value="<?= htmlspecialchars($client['client_code']) ?>" 
            class="form-control-ios font-monospace bg-light" 
            readonly
            disabled
          >
          <span class="text-muted small">Fixed sequential identifier.</span>
        </div>

        <div class="col-12 col-md-4">
          <label for="status" class="form-label-ios">Client Status</label>
          <select id="status" name="status" class="form-control-ios">
            <option value="active" <?= $client['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $client['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="inactive" <?= $client['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-12 col-md-4">
          <label for="installation_date" class="form-label-ios">Installation Date</label>
          <input 
            type="date" 
            id="installation_date" 
            name="installation_date" 
            value="<?= htmlspecialchars($client['installation_date'] ?? '') ?>" 
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
            value="<?= htmlspecialchars($client['company_name']) ?>" 
            class="form-control-ios" 
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
            value="<?= htmlspecialchars($client['contact_person']) ?>" 
            class="form-control-ios" 
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
            value="<?= htmlspecialchars($client['phone']) ?>" 
            class="form-control-ios" 
            required
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="whatsapp" class="form-label-ios">WhatsApp Number</label>
          <input 
            type="tel" 
            id="whatsapp" 
            name="whatsapp" 
            value="<?= htmlspecialchars($client['whatsapp'] ?? '') ?>" 
            class="form-control-ios"
          >
        </div>

        <div class="col-12 col-md-4">
          <label for="email" class="form-label-ios">Email Address</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($client['email'] ?? '') ?>" 
            class="form-control-ios"
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
            value="<?= htmlspecialchars($client['curtiss_system'] ?? '') ?>" 
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
            value="<?= htmlspecialchars($client['system_url'] ?? '') ?>" 
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
            value="<?= htmlspecialchars($client['address'] ?? '') ?>" 
            class="form-control-ios"
          >
        </div>

        <!-- Notes -->
        <div class="col-12">
          <label for="notes" class="form-label-ios">Internal Notes</label>
          <textarea 
            id="notes" 
            name="notes" 
            rows="3" 
            class="form-control-ios"
          ><?= htmlspecialchars($client['notes'] ?? '') ?></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="col-12 mt-4 pt-2 d-flex justify-content-end">
          <div class="form-submit-actions">
            <a href="<?= BASE_URL ?>/index.php?page=client_view&id=<?= $client['id'] ?>" class="btn-ios-secondary">Cancel</a>
            <button type="submit" class="btn-ios-primary" style="padding-left: 28px; padding-right: 28px;">
              <i data-lucide="check"></i>
              <span>Update Changes</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
