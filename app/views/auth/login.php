<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#09090b">
  <title>Sign In | Curtiss Client Manager</title>

  <!-- PWA Manifest -->
  <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.json">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/public/assets/images/favicon.png">
  <link rel="shortcut icon" href="<?= BASE_URL ?>/public/favicon.ico">
  <link rel="apple-touch-icon" href="<?= BASE_URL ?>/public/assets/images/favicon.png">

  <!-- Inter Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Curtiss iOS Design System -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= APP_VERSION ?>">
</head>
<body class="auth-page">

<div class="auth-card" style="max-width: 410px;">
  <div class="auth-brand mb-3">
    <img src="<?= BASE_URL ?>/public/assets/images/logo.png" alt="Curtiss" style="height: 42px; max-width: 190px; object-fit: contain; margin-bottom: 8px;">
    <p class="text-muted small mb-0">Client & Financial Management</p>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 px-3 small border-0 mb-3" style="border-radius: 12px; background: #fef2f2; color: #dc2626;">
      <i data-lucide="alert-circle" style="width: 15px; height: 15px; display: inline-block; vertical-align: -2px; margin-right: 4px;"></i>
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Auth Mode Segmented Control -->
  <div class="auth-mode-tabs mb-4">
    <button type="button" class="auth-mode-tab active" id="tabPinBtn" onclick="switchAuthMode('pin')">
      <i data-lucide="key-round" style="width: 15px; height: 15px;"></i>
      <span>6-Digit PIN</span>
    </button>
    <button type="button" class="auth-mode-tab" id="tabPasswordBtn" onclick="switchAuthMode('password')">
      <i data-lucide="lock" style="width: 15px; height: 15px;"></i>
      <span>Password</span>
    </button>
  </div>

  <!-- SECTION 1: 6-DIGIT PIN KEYPAD LOGIN (Default) -->
  <div id="pinAuthSection">
    <div class="text-center mb-2">
      <p class="text-muted small mb-1">Enter your 6-digit access PIN</p>
    </div>

    <form id="pinLoginForm" action="<?= BASE_URL ?>/index.php?page=login_pin_process" method="POST" autocomplete="off">
      <?= AuthHelper::csrfInput() ?>
      <input type="hidden" name="username" value="admin">
      <input type="hidden" name="pin" id="pinInput" value="">

      <!-- 6 PIN Dots Indicator -->
      <div class="pin-dots-container" id="pinDots">
        <div class="pin-dot" id="dot-0"></div>
        <div class="pin-dot" id="dot-1"></div>
        <div class="pin-dot" id="dot-2"></div>
        <div class="pin-dot" id="dot-3"></div>
        <div class="pin-dot" id="dot-4"></div>
        <div class="pin-dot" id="dot-5"></div>
      </div>

      <!-- Clean Keypad -->
      <div class="keypad-grid mb-3">
        <button type="button" class="keypad-btn" onclick="appendPin('1')">1</button>
        <button type="button" class="keypad-btn" onclick="appendPin('2')">2</button>
        <button type="button" class="keypad-btn" onclick="appendPin('3')">3</button>

        <button type="button" class="keypad-btn" onclick="appendPin('4')">4</button>
        <button type="button" class="keypad-btn" onclick="appendPin('5')">5</button>
        <button type="button" class="keypad-btn" onclick="appendPin('6')">6</button>

        <button type="button" class="keypad-btn" onclick="appendPin('7')">7</button>
        <button type="button" class="keypad-btn" onclick="appendPin('8')">8</button>
        <button type="button" class="keypad-btn" onclick="appendPin('9')">9</button>

        <button type="button" class="keypad-btn action-btn" onclick="clearPin()">Clear</button>
        <button type="button" class="keypad-btn" onclick="appendPin('0')">0</button>
        <button type="button" class="keypad-btn action-btn" onclick="deletePin()">
          <i data-lucide="delete" style="width: 20px; height: 20px;"></i>
        </button>
      </div>
    </form>
  </div>

  <!-- SECTION 2: USERNAME & PASSWORD LOGIN -->
  <div id="passwordAuthSection" style="display: none;">
    <form action="<?= BASE_URL ?>/index.php?page=login_process" method="POST" autocomplete="off">
      <?= AuthHelper::csrfInput() ?>

      <div class="mb-3">
        <label for="username" class="form-label-ios">Username</label>
        <input 
          type="text" 
          id="username" 
          name="username" 
          class="form-control-ios" 
          placeholder="Enter username" 
          value="admin"
          required
        >
      </div>

      <div class="mb-4">
        <label for="password" class="form-label-ios">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="form-control-ios" 
          placeholder="Enter password" 
          required
        >
      </div>

      <button type="submit" class="btn-ios-primary">
        <span>Sign In with Password</span>
      </button>
    </form>
  </div>

  <div class="text-center mt-3 pt-2 border-top">
    <span class="text-muted" style="font-size: 0.75rem;">Curtiss &copy; <?= date('Y') ?> &bull; Internal System</span>
  </div>
</div>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // Auth Mode Switching
  function switchAuthMode(mode) {
    const pinSec = document.getElementById('pinAuthSection');
    const pwdSec = document.getElementById('passwordAuthSection');
    const tabPin = document.getElementById('tabPinBtn');
    const tabPwd = document.getElementById('tabPasswordBtn');

    if (mode === 'password') {
      pinSec.style.display = 'none';
      pwdSec.style.display = 'block';
      tabPin.classList.remove('active');
      tabPwd.classList.add('active');
      const pwdInput = document.getElementById('password');
      if (pwdInput) pwdInput.focus();
    } else {
      pwdSec.style.display = 'none';
      pinSec.style.display = 'block';
      tabPwd.classList.remove('active');
      tabPin.classList.add('active');
      clearPin();
    }
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }

  // PIN Keypad Logic
  let currentPin = '';
  let isSubmitting = false;

  function updatePinDots() {
    for (let i = 0; i < 6; i++) {
      const dot = document.getElementById('dot-' + i);
      if (dot) {
        if (i < currentPin.length) {
          dot.classList.add('filled');
        } else {
          dot.classList.remove('filled');
        }
      }
    }
    const pinInput = document.getElementById('pinInput');
    if (pinInput) pinInput.value = currentPin;
  }

  function appendPin(digit) {
    if (isSubmitting || currentPin.length >= 6) return;
    currentPin += digit;
    updatePinDots();

    // Auto submit upon 6th digit
    if (currentPin.length === 6) {
      isSubmitting = true;
      setTimeout(() => {
        const form = document.getElementById('pinLoginForm');
        if (form) form.submit();
      }, 150);
    }
  }

  function deletePin() {
    if (isSubmitting || currentPin.length === 0) return;
    currentPin = currentPin.slice(0, -1);
    updatePinDots();
  }

  function clearPin() {
    if (isSubmitting) return;
    currentPin = '';
    updatePinDots();
  }

  // Physical Keyboard Listener
  document.addEventListener('keydown', function(e) {
    // Only capture keyboard for PIN when PIN section is active and not focused on input
    const pinSec = document.getElementById('pinAuthSection');
    if (!pinSec || pinSec.style.display === 'none') return;
    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;

    if (e.key >= '0' && e.key <= '9') {
      e.preventDefault();
      appendPin(e.key);
    } else if (e.key === 'Backspace') {
      e.preventDefault();
      deletePin();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      clearPin();
    }
  });
</script>
</body>
</html>
