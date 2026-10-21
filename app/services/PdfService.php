<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Professional PDF Document Generator Service
 * High-Definition Chromium Vector Engine with Dompdf Fallback
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService {
    /**
     * Locate available Chromium / Edge headless binary
     */
    public static function findChromiumBinary(): ?string {
        $candidates = [
            'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/usr/bin/microsoft-edge',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Compile HTML to PDF using Chromium Headless (or Dompdf fallback)
     */
    public static function compilePdf(string $html): string {
        $bin = self::findChromiumBinary();

        if ($bin !== null) {
            $tempDir = __DIR__ . '/../../storage/temp_pdf';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $userDataDir = $tempDir . '/user_data';
            if (!is_dir($userDataDir)) {
                mkdir($userDataDir, 0755, true);
            }

            $uniqueId = uniqid('pdf_', true);
            $htmlPath = $tempDir . '/' . $uniqueId . '.html';
            $pdfPath = $tempDir . '/' . $uniqueId . '.pdf';

            file_put_contents($htmlPath, $html);

            $fileUrl = 'file:///' . str_replace('\\', '/', $htmlPath);

            $cmd = '"' . $bin . '" --headless=new --no-sandbox --disable-gpu --disable-software-rasterizer --disable-extensions --user-data-dir="' . $userDataDir . '" --run-all-compositor-stages-before-draw --no-pdf-header-footer --print-to-pdf="' . $pdfPath . '" "' . $fileUrl . '" 2>&1';
            exec($cmd, $output, $returnCode);

            if (file_exists($pdfPath) && filesize($pdfPath) > 500) {
                $content = file_get_contents($pdfPath);
                @unlink($htmlPath);
                @unlink($pdfPath);
                return (string)$content;
            }

            // Cleanup if failed
            @unlink($htmlPath);
            @unlink($pdfPath);
        }

        // Dompdf Fallback (Zero GD dependency when using JPEG logo)
        $dompdf = self::createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * Get base64 encoded logo string (prefers JPEG for 100% GD-free Dompdf compatibility)
     */
    public static function getLogoBase64(): string {
        $jpgPath = __DIR__ . '/../../public/assets/images/logo.jpg';
        if (file_exists($jpgPath)) {
            return 'data:image/jpeg;base64,' . base64_encode((string)file_get_contents($jpgPath));
        }

        $pngPath = __DIR__ . '/../../public/assets/images/logo.png';
        if (file_exists($pngPath)) {
            return 'data:image/png;base64,' . base64_encode((string)file_get_contents($pngPath));
        }

        return '';
    }

    /**
     * Generate HTML layout for clean, executive A4 portrait invoice
     */
    public static function buildInvoiceHtml(array $invoice, array $items, bool $isInteractive = false): string {
        $currency = 'LKR&nbsp;';
        $companyEmail = 'trycurtiss@gmail.com';
        $companyWeb = 'www.trycurtiss.com';
        $companyPhone = '0761407875';

        // Bank remittance details
        $bankName = 'Commercial Bank';
        $bankBranch = 'Kurunegala City Office';
        $accName = 'G L DASUN SUSARA SENARATHNE';
        $accNo = '8018526390';

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT bank_name, branch, account_name, account_number FROM bank_accounts WHERE is_primary = 1 AND status = 'active' LIMIT 1");
            $bRow = $stmt->fetch();
            if ($bRow) {
                $bankName = $bRow['bank_name'] ?: $bankName;
                $bankBranch = $bRow['branch'] ?: $bankBranch;
                $accName = $bRow['account_name'] ?: $accName;
                $accNo = $bRow['account_number'] ?: $accNo;
            }
        } catch (Throwable $e) {
            // Keep defaults
        }

        $logoBase64 = self::getLogoBase64();

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

  @page {
    size: A4 portrait;
    margin: 0;
  }

  * {
    box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  html, body {
    margin: 0;
    padding: 0;
    width: 210mm;
    height: 297mm;
    background: #ffffff;
    color: #1f2937;
    font-size: 10pt;
    line-height: 1.5;
  }

  .sheet-container {
    width: 210mm;
    height: 297mm;
    min-height: 297mm;
    max-height: 297mm;
    position: relative;
    overflow: hidden;
    background: #ffffff;
    margin: 0 auto;
    box-sizing: border-box;
  }

  .page-content {
    padding: 18mm 16mm 0 16mm;
    position: relative;
    z-index: 2;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
  }

  /* Two Column Header Table */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
    table-layout: fixed;
  }

  .header-table td {
    vertical-align: top;
  }

  .brand-logo {
    height: 38px;
    width: auto;
    display: block;
    margin-bottom: 8px;
  }

  .company-info {
    font-size: 9.5pt;
    color: #374151;
    line-height: 1.5;
  }

  .company-name {
    font-weight: 600;
    color: #111827;
  }

  .invoice-title {
    text-align: right;
    font-size: 28pt;
    font-weight: 900;
    color: #111827;
    letter-spacing: -0.03em;
    line-height: 1;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  .meta-text {
    text-align: right;
    font-size: 9.5pt;
    color: #1f2937;
    line-height: 1.55;
  }

  .meta-text strong {
    font-weight: 700;
    color: #111827;
  }

  /* Billed to */
  .billed-to-wrap {
    margin-bottom: 26px;
    font-size: 9.5pt;
    line-height: 1.5;
    color: #374151;
  }

  .billed-to-title {
    font-weight: 800;
    color: #111827;
    margin-bottom: 4px;
    font-size: 10pt;
  }

  .client-company {
    color: #111827;
    font-weight: 600;
  }

  /* Table */
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    table-layout: fixed;
  }

  .items-table thead th {
    background-color: #e5e7eb;
    color: #374151;
    font-size: 9.5pt;
    font-weight: 600;
    padding: 11px 16px;
    text-align: left;
    border: none;
  }

  .items-table thead th.text-center {
    text-align: center;
  }

  .items-table thead th.text-right {
    text-align: right;
  }

  .items-table tbody td {
    padding: 13px 14px;
    font-size: 9.5pt;
    color: #374151;
    border: none;
    vertical-align: middle;
  }

  .items-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
  }

  .col-price,
  .col-amount,
  .total-row-table td {
    white-space: nowrap !important;
  }

  .total-row-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2px;
    margin-bottom: 24px;
    table-layout: fixed;
  }

  .total-row-table td {
    padding: 12px 14px;
    border-top: 1.5px solid #e5e7eb;
    font-size: 11pt;
    font-weight: 800;
    color: #111827;
  }

  /* Payment & Bank Details */
  .payment-section {
    font-size: 9.5pt;
    color: #374151;
    line-height: 1.6;
    margin-bottom: 14px;
  }

  .payment-section strong {
    color: #111827;
    font-weight: 700;
  }

  .bank-details-box {
    margin-top: 6px;
    margin-bottom: 14px;
    padding: 8px 14px;
    background: #f8fafc;
    border-left: 3px solid #111827;
    border-radius: 0 4px 4px 0;
    font-size: 9pt;
    color: #374151;
    line-height: 1.5;
    display: inline-block;
    min-width: 340px;
  }

  .note-section {
    font-size: 9.5pt;
    color: #374151;
  }

  .note-section strong {
    color: #111827;
    font-weight: 700;
  }

  /* Bottom Waves Graphic */
  .bottom-waves-wrap {
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    width: 100%;
    line-height: 0;
    z-index: 1;
    pointer-events: none;
  }

  /* Interactive Screen & Print Styles */
  @media screen {
    body {
      background: #e2e8f0;
      padding: 24px 12px;
      height: auto;
      min-height: 100vh;
      width: auto;
    }
    .top-bar {
      max-width: 210mm;
      margin: 0 auto 16px auto;
      background: #0f172a;
      color: #fff;
      padding: 10px 18px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .top-bar a, .top-bar button {
      background: #fff;
      color: #0f172a;
      border: none;
      padding: 6px 14px;
      font-weight: 700;
      font-size: 8.5pt;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      margin-left: 6px;
    }
    .sheet-container {
      border-radius: 8px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    }
  }

  @media print {
    html, body {
      width: 100% !important;
      height: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      background: #ffffff !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .top-bar {
      display: none !important;
    }
    .sheet-container {
      width: 100% !important;
      height: 100% !important;
      min-height: 100% !important;
      max-height: none !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      page-break-after: avoid !important;
      page-break-inside: avoid !important;
    }
    .bottom-waves-wrap {
      position: fixed !important;
      bottom: 0 !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      line-height: 0 !important;
    }
  }
</style>
</head>
<body>

<?php if ($isInteractive): ?>
<div class="top-bar">
  <div><strong>Invoice <?= htmlspecialchars($invoice['invoice_number']) ?></strong> &bull; <?= htmlspecialchars($invoice['company_name']) ?></div>
  <div>
    <button onclick="window.print()">Print Invoice</button>
    <a href="<?= BASE_URL ?>/index.php?page=invoice_pdf&id=<?= $invoice['id'] ?>&mode=download">Download PDF</a>
    <button onclick="window.close()">Close</button>
  </div>
</div>
<?php endif; ?>

<div class="sheet-container">
  <div class="page-content">
    <!-- Top Header Table -->
    <table class="header-table">
      <tr>
        <td style="width: 55%;">
          <?php if (!empty($logoBase64)): ?>
            <img src="<?= $logoBase64 ?>" class="brand-logo" alt="Curtiss">
          <?php endif; ?>
          <div class="company-info">
            <div class="company-name">Curtiss Software Solutions</div>
            <div>Susara Senarathne</div>
            <div>0761407875</div>
            <div>trycurtiss@gmail.com</div>
          </div>
        </td>
        <td style="width: 45%; text-align: right;">
          <div class="invoice-title">INVOICE</div>
          <div class="meta-text">
            <div><strong>Date:</strong> <?= date('d F, Y', strtotime($invoice['issue_date'])) ?></div>
            <div><strong>NO.</strong> <?= htmlspecialchars($invoice['invoice_number']) ?></div>
          </div>
        </td>
      </tr>
    </table>

    <!-- Billed to -->
    <div class="billed-to-wrap">
      <div class="billed-to-title">Billed to:</div>
      <div class="client-company"><?= htmlspecialchars($invoice['company_name']) ?></div>
      <?php if (!empty($invoice['contact_person']) && $invoice['contact_person'] !== $invoice['company_name']): ?>
        <div><?= htmlspecialchars($invoice['contact_person']) ?></div>
      <?php endif; ?>
      <?php if (!empty($invoice['address'])): ?>
        <div><?= nl2br(htmlspecialchars($invoice['address'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($invoice['email'])): ?>
        <div><?= htmlspecialchars($invoice['email']) ?></div>
      <?php endif; ?>
      <?php if (empty($invoice['email']) && !empty($invoice['phone'])): ?>
        <div><?= htmlspecialchars($invoice['phone']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Items Table -->
    <table class="items-table">
      <thead>
        <tr>
          <th style="width: 44%;">Item</th>
          <th style="width: 12%; text-align: center;">Quantity</th>
          <th style="width: 22%; text-align: right;" class="col-price">Price</th>
          <th style="width: 22%; text-align: right;" class="col-amount">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): 
          $qty = (float)($item['quantity'] ?? 1);
          $qtyDisp = ($qty == (int)$qty) ? (int)$qty : number_format($qty, 2);
          $price = (float)($item['unit_price'] ?? $item['line_total']);
          $total = (float)$item['line_total'];
        ?>
          <tr>
            <td><?= htmlspecialchars($item['description']) ?></td>
            <td style="text-align: center;"><?= $qtyDisp ?></td>
            <td style="text-align: right;" class="col-price"><?= $currency . number_format($price, 2) ?></td>
            <td style="text-align: right;" class="col-amount"><?= $currency . number_format($total, 2) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Total Row Table -->
    <table class="total-row-table">
      <?php if ((float)$invoice['discount'] > 0): ?>
        <tr>
          <td style="width: 66%; text-align: right; font-size: 9.5pt; font-weight: normal; color: #6b7280; padding: 4px 14px;">Discount</td>
          <td style="width: 34%; text-align: right; font-size: 9.5pt; font-weight: normal; color: #dc2626; padding: 4px 14px;" class="col-amount">-<?= $currency . number_format((float)$invoice['discount'], 2) ?></td>
        </tr>
      <?php endif; ?>
      <?php if ((float)$invoice['tax'] > 0): ?>
        <tr>
          <td style="width: 66%; text-align: right; font-size: 9.5pt; font-weight: normal; color: #6b7280; padding: 4px 14px;">Tax</td>
          <td style="width: 34%; text-align: right; font-size: 9.5pt; font-weight: normal; padding: 4px 14px;" class="col-amount"><?= $currency . number_format((float)$invoice['tax'], 2) ?></td>
        </tr>
      <?php endif; ?>
      <tr>
        <td style="width: 66%; text-align: right;">Total</td>
        <td style="width: 34%; text-align: right;" class="col-amount"><?= $currency . number_format((float)$invoice['total_amount'], 2) ?></td>
      </tr>
    </table>

    <!-- Payment & Bank Info -->
    <div class="payment-section">
      <div style="margin-bottom: 6px;"><strong>Payment method:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $invoice['payment_method'] ?? 'Bank Transfer'))) ?></div>
      <div class="bank-details-box">
        <div style="font-weight: 700; color: #111827; margin-bottom: 3px;">Bank Details:</div>
        <div><strong>Bank:</strong> <?= htmlspecialchars($bankName) ?> (<?= htmlspecialchars($bankBranch) ?>)</div>
        <div><strong>Account Name:</strong> <?= htmlspecialchars($accName) ?></div>
        <div><strong>Account Number:</strong> <?= htmlspecialchars($accNo) ?></div>
      </div>
    </div>

    <!-- Note -->
    <div class="note-section">
      <strong>Note:</strong> <?= !empty($invoice['notes']) ? htmlspecialchars($invoice['notes']) : 'Thank you for choosing us!' ?>
    </div>
  </div>

  <!-- Bottom Wave Graphic (Locked flush to bottom of A4 page) -->
  <div class="bottom-waves-wrap">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 280" preserveAspectRatio="none" style="width: 100%; height: 180px; display: block; margin: 0; padding: 0;">
      <path d="M 0,280 L 0,80 C 130,80 260,135 400,205 C 500,250 600,275 720,280 L 0,280 Z" fill="#d1d5db" />
      <path d="M 0,280 C 160,280 300,255 450,210 C 620,160 800,185 1000,85 L 1000,280 Z" fill="#2d3139" />
    </svg>
  </div>
</div>

</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Create Dompdf instance with optimized A4 options (Fallback engine)
     */
    public static function createDompdf(): Dompdf {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 150);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        return $dompdf;
    }

    /**
     * Stream PDF directly to browser (Preview or Download)
     */
    public static function streamInvoicePdf(array $invoice, array $items, bool $download = false): void {
        $html = self::buildInvoiceHtml($invoice, $items, false);
        $pdfBinary = self::compilePdf($html);
        $filename = 'Curtiss_Invoice_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $invoice['invoice_number']) . '.pdf';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdfBinary;
        exit;
    }

    /**
     * Save PDF to local storage and return absolute file path
     */
    public static function saveInvoicePdf(array $invoice, array $items): string {
        $html = self::buildInvoiceHtml($invoice, $items, false);
        $pdfBinary = self::compilePdf($html);

        $storageDir = __DIR__ . '/../../storage/invoices';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'Invoice_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $invoice['invoice_number']) . '.pdf';
        $fullPath = $storageDir . '/' . $filename;
        file_put_contents($fullPath, $pdfBinary);

        return $fullPath;
    }

    /**
     * Build HTML for professional A4 Payment Receipt
     */
    public static function buildReceiptHtml(array $payment): string {
        $companyEmail = 'trycurtiss@gmail.com';
        $companyWeb = 'www.trycurtiss.com';
        $companyPhone = '076 140 7875';
        $companyAddress = '79, Dambakanda Estate, Boyagane, Kurunegala';
        $currency = 'LKR ';

        $logoBase64 = self::getLogoBase64();

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt <?= htmlspecialchars($payment['receipt_number']) ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

  @page {
    size: a4 portrait;
    margin: 14mm 15mm 14mm 15mm;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  body { background: #ffffff; color: #0f172a; font-size: 9.5pt; line-height: 1.45; }
  table.layout-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
  table.layout-table td { vertical-align: top; }
  .brand-logo { height: 38px; width: auto; max-width: 230px; display: block; margin-bottom: 5px; }
  .brand-tagline { font-size: 9.5pt; font-weight: 700; color: #0f172a; letter-spacing: -0.01em; margin-bottom: 3px; }
  .brand-meta { font-size: 7.5pt; color: #64748b; line-height: 1.45; }
  .receipt-title { text-align: right; font-size: 24pt; font-weight: 800; color: #0f172a; line-height: 1; text-transform: uppercase; }
  .receipt-num { text-align: right; font-size: 11pt; font-weight: 700; color: #64748b; margin-top: 5px; }
  .header-divider { border-bottom: 1.5px solid #e2e8f0; margin-bottom: 18px; }
  .card-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; }
  .card-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 5px; }
  .client-name { font-size: 13pt; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
  .client-info-line { font-size: 8.5pt; color: #475569; line-height: 1.45; }
  .receipt-badge { border: 1.5px solid #0f172a; border-radius: 10px; padding: 16px; background: #f8fafc; margin-bottom: 22px; text-align: center; }
  .rec-amount-label { font-size: 8pt; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
  .rec-amount-val { font-size: 24pt; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
  .items-table { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
  .items-table th { background-color: #f1f5f9; color: #334155; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; padding: 10px 14px; border-top: 1px solid #e2e8f0; border-bottom: 1.5px solid #cbd5e1; }
  .items-table td { padding: 14px; font-size: 9pt; border-bottom: 1px solid #e2e8f0; }
  .footer-divider { border-top: 1.5px solid #0f172a; margin-top: 20px; margin-bottom: 6px; }
  .footer-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; color: #94a3b8; }
</style>
</head>
<body>

<table class="layout-table" style="margin-bottom: 14px;">
  <tr>
    <td style="width: 56%;">
      <?php if (!empty($logoBase64)): ?>
        <img src="<?= $logoBase64 ?>" class="brand-logo" alt="Curtiss">
      <?php endif; ?>
      <div class="brand-tagline">Curtiss Software Solutions</div>
      <div class="brand-meta">
        <?= htmlspecialchars($companyAddress) ?><br>
        <?= htmlspecialchars($companyEmail) ?> &bull; <?= htmlspecialchars($companyPhone) ?> &bull; <?= htmlspecialchars($companyWeb) ?>
      </div>
    </td>
    <td style="width: 44%;">
      <div class="receipt-title">Payment Receipt</div>
      <div class="receipt-num">#<?= htmlspecialchars($payment['receipt_number']) ?></div>
      <div style="text-align: right; margin-top: 6px;">
        <span style="display: inline-block; padding: 3px 10px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; border-radius: 9999px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
          <?= strtoupper(htmlspecialchars($payment['status'])) ?>
        </span>
      </div>
    </td>
  </tr>
</table>

<div class="header-divider"></div>

<table class="layout-table">
  <tr>
    <td style="width: 50%; padding-right: 8px;">
      <div class="card-box">
        <div class="card-label">Received From</div>
        <div class="client-name"><?= htmlspecialchars($payment['company_name']) ?></div>
        <div class="client-info-line"><strong>Attn:</strong> <?= htmlspecialchars($payment['contact_person']) ?></div>
        <div class="client-info-line"><strong>Client Code:</strong> <?= htmlspecialchars($payment['client_code']) ?></div>
        <div class="client-info-line"><strong>Phone:</strong> <?= htmlspecialchars($payment['phone']) ?></div>
        <?php if (!empty($payment['address'])): ?>
          <div class="client-info-line"><strong>Address:</strong> <?= nl2br(htmlspecialchars($payment['address'])) ?></div>
        <?php endif; ?>
      </div>
    </td>
    <td style="width: 50%; padding-left: 8px;">
      <div class="card-box">
        <div class="card-label">Receipt Details</div>
        <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
          <tr>
            <td style="color: #64748b; padding: 3px 0;">Receipt Number</td>
            <td style="font-weight: 700; color: #0f172a; text-align: right;"><?= htmlspecialchars($payment['receipt_number']) ?></td>
          </tr>
          <tr>
            <td style="color: #64748b; padding: 3px 0;">Payment Date</td>
            <td style="font-weight: 700; color: #0f172a; text-align: right;"><?= FormatHelper::date($payment['payment_date'], 'd M Y') ?></td>
          </tr>
          <tr>
            <td style="color: #64748b; padding: 3px 0;">Payment Method</td>
            <td style="font-weight: 700; color: #0f172a; text-align: right;"><?= ucwords(str_replace('_', ' ', $payment['payment_method'])) ?></td>
          </tr>
          <?php if (!empty($payment['reference_no'])): ?>
            <tr>
              <td style="color: #64748b; padding: 3px 0;">Transaction Ref</td>
              <td style="font-weight: 700; color: #0f172a; text-align: right;"><?= htmlspecialchars($payment['reference_no']) ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </td>
  </tr>
</table>

<div class="receipt-badge">
  <div class="rec-amount-label">Total Amount Received</div>
  <div class="rec-amount-val"><?= $currency . number_format((float)$payment['amount'], 2) ?></div>
</div>

<table class="items-table">
  <thead>
    <tr>
      <th style="text-align: left;">Payment Description / Allocation</th>
      <th style="text-align: right; width: 160px;">Received Amount</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>
        <?php if (!empty($payment['invoice_number'])): ?>
          <strong style="color: #0f172a; font-size: 10pt;">Payment for Invoice #<?= htmlspecialchars($payment['invoice_number']) ?></strong>
          <div style="font-size: 8.5pt; color: #64748b; margin-top: 3px;">
            Invoice Total: <?= $currency . number_format((float)$payment['invoice_total'], 2) ?> &bull; 
            Remaining Balance: <?= $currency . number_format((float)($payment['invoice_balance'] ?? 0), 2) ?>
          </div>
        <?php else: ?>
          <strong style="color: #0f172a; font-size: 10pt;"><?= htmlspecialchars($payment['custom_description'] ?: 'Direct Client Payment') ?></strong>
        <?php endif; ?>
        <?php if (!empty($payment['notes'])): ?>
          <div style="font-size: 8.5pt; color: #475569; margin-top: 4px;">
            <strong>Notes:</strong> <?= htmlspecialchars($payment['notes']) ?>
          </div>
        <?php endif; ?>
      </td>
      <td style="text-align: right; font-weight: 800; font-size: 11pt; color: #0f172a;">
        <?= $currency . number_format((float)$payment['amount'], 2) ?>
      </td>
    </tr>
  </tbody>
</table>

<div class="footer-divider"></div>
<table class="footer-table">
  <tr>
    <td style="text-align: left;">
      <strong style="color: #475569;">Curtiss Software Solutions</strong> &bull; <?= htmlspecialchars($companyWeb) ?> &bull; <?= htmlspecialchars($companyEmail) ?> &bull; <?= htmlspecialchars($companyPhone) ?>
    </td>
    <td style="text-align: right;">
      Official Payment Receipt &bull; Electronically Generated
    </td>
  </tr>
</table>

</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Stream Receipt PDF
     */
    public static function streamReceiptPdf(array $payment, bool $download = false): void {
        $html = self::buildReceiptHtml($payment);
        $pdfBinary = self::compilePdf($html);
        $filename = 'Curtiss_Receipt_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $payment['receipt_number']) . '.pdf';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdfBinary;
        exit;
    }

    /**
     * Save Receipt PDF to local storage and return absolute file path
     */
    public static function saveReceiptPdf(array $payment): string {
        $html = self::buildReceiptHtml($payment);
        $pdfBinary = self::compilePdf($html);

        $storageDir = __DIR__ . '/../../storage/receipts';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $filename = 'Receipt_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $payment['receipt_number']) . '.pdf';
        $fullPath = $storageDir . '/' . $filename;
        file_put_contents($fullPath, $pdfBinary);

        return $fullPath;
    }

    /**
     * Stream Financial Report PDF
     */
    public static function streamReportPdf(string $startDate, string $endDate, array $monthlyRevenue, array $clientOutstanding): void {
        $currency = 'LKR ';
        $companyEmail = 'trycurtiss@gmail.com';
        $companyWeb = 'www.trycurtiss.com';
        $companyPhone = '076 140 7875';
        $totalRevenue = array_sum(array_column($monthlyRevenue, 'total_revenue'));
        $totalOutstanding = array_sum(array_column($clientOutstanding, 'total_outstanding'));

        $logoBase64 = self::getLogoBase64();

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Financial Report</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
  @page { margin: 15mm 15mm; size: a4 portrait; }
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  body { color: #0f172a; font-size: 9.5pt; line-height: 1.45; }
  .header-tbl { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
  .tbl { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .tbl th { background: #f1f5f9; color: #334155; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.08em; padding: 9px 12px; border-bottom: 1.5px solid #cbd5e1; }
  .tbl td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
  .card-box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; background: #f8fafc; margin-bottom: 22px; }
</style>
</head>
<body>
  <table class="header-tbl">
    <tr>
      <td style="vertical-align: middle;">
        <?php if (!empty($logoBase64)): ?>
          <img src="<?= $logoBase64 ?>" style="height: 34px; max-width: 220px; margin-bottom: 4px;" alt="Curtiss">
        <?php endif; ?>
        <div style="font-size: 9pt; font-weight: 700; color: #0f172a;">Curtiss Software Solutions</div>
        <div style="color: #64748b; font-size: 8pt;">Financial Performance & Receivables Report</div>
      </td>
      <td style="text-align: right; vertical-align: middle;">
        <div style="font-weight: 800; font-size: 11pt; color: #0f172a;">Period: <?= htmlspecialchars($startDate) ?> to <?= htmlspecialchars($endDate) ?></div>
        <div style="color: #64748b; font-size: 8pt;">Generated on <?= date('d M Y, h:i A') ?></div>
      </td>
    </tr>
  </table>

  <div class="card-box">
    <table style="width: 100%;">
      <tr>
        <td style="width: 50%;">
          <div style="font-size: 8pt; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.06em;">Total Revenue Collected</div>
          <div style="font-size: 18pt; font-weight: 800; color: #0f172a; margin-top: 2px;"><?= $currency . number_format((float)$totalRevenue, 2) ?></div>
        </td>
        <td style="width: 50%; text-align: right;">
          <div style="font-size: 8pt; text-transform: uppercase; color: #dc2626; font-weight: 700; letter-spacing: 0.06em;">Total Outstanding Receivables</div>
          <div style="font-size: 18pt; font-weight: 800; color: #dc2626; margin-top: 2px;"><?= $currency . number_format((float)$totalOutstanding, 2) ?></div>
        </td>
      </tr>
    </table>
  </div>

  <h3 style="font-size: 10pt; font-weight: 800; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">Monthly Revenue Breakdown</h3>
  <table class="tbl">
    <thead>
      <tr>
        <th style="text-align: left;">Month</th>
        <th style="text-align: center; width: 120px;">Transactions</th>
        <th style="text-align: right; width: 160px;">Revenue (LKR)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($monthlyRevenue)): ?>
        <?php foreach ($monthlyRevenue as $m): ?>
          <tr>
            <td><strong><?= date('F Y', strtotime($m['month_label'] . '-01')) ?></strong></td>
            <td style="text-align: center; color: #64748b;"><?= (int)$m['transaction_count'] ?></td>
            <td style="text-align: right; font-weight: 700; color: #0f172a;"><?= number_format((float)$m['total_revenue'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No transactions recorded.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3 style="font-size: 10pt; font-weight: 800; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; color: #0f172a;">Outstanding Receivables Aging by Client</h3>
  <table class="tbl">
    <thead>
      <tr>
        <th style="text-align: left;">Client Name</th>
        <th style="text-align: center; width: 80px;">Code</th>
        <th style="text-align: right; width: 120px;">Invoiced</th>
        <th style="text-align: right; width: 120px;">Paid</th>
        <th style="text-align: right; width: 120px;">Outstanding</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($clientOutstanding)): ?>
        <?php foreach ($clientOutstanding as $c): ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['company_name']) ?></strong> (<?= htmlspecialchars($c['phone']) ?>)</td>
            <td style="text-align: center; color: #64748b;"><?= htmlspecialchars($c['client_code']) ?></td>
            <td style="text-align: right; color: #475569;"><?= number_format((float)$c['total_invoiced'], 2) ?></td>
            <td style="text-align: right; color: #059669; font-weight: 600;"><?= number_format((float)$c['total_paid'], 2) ?></td>
            <td style="text-align: right; font-weight: 800; color: #dc2626;"><?= number_format((float)$c['total_outstanding'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">No outstanding client receivables.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div style="border-top: 1px solid #e2e8f0; margin-top: 30px; padding-top: 10px; font-size: 7.5pt; color: #94a3b8; display: flex; justify-content: space-between;">
    <div>Curtiss Software Solutions &bull; <?= htmlspecialchars($companyWeb) ?> &bull; <?= htmlspecialchars($companyEmail) ?> &bull; <?= htmlspecialchars($companyPhone) ?></div>
    <div>Report Generated Electronically</div>
  </div>
</body>
</html>
        <?php
        $html = ob_get_clean();
        $pdfBinary = self::compilePdf($html);
        $filename = 'Curtiss_Financial_Report_' . $startDate . '_to_' . $endDate . '.pdf';

        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdfBinary;
        exit;
    }
}
