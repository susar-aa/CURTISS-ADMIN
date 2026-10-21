<?php
require_once __DIR__ . '/../app/services/PdfService.php';

$logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(__DIR__ . '/../public/assets/images/logo.jpg'));

$svgWaves = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 280" preserveAspectRatio="none" style="width: 100%; height: 180px; display: block;">
  <path d="M 0,280 L 0,110 C 120,110 240,150 360,210 C 430,245 500,265 580,280 Z" fill="#d1d5db" />
  <path d="M 80,280 C 230,265 370,220 520,185 C 680,150 830,190 1000,95 L 1000,280 Z" fill="#2d3139" />
</svg>
SVG;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice NO. 000001</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

  @page {
    size: a4 portrait;
    margin: 0;
  }

  * {
    box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  body {
    margin: 0;
    padding: 0;
    color: #1f2937;
    font-size: 10pt;
    line-height: 1.5;
    background: #ffffff;
    position: relative;
    height: 297mm;
    overflow: hidden;
  }

  .page-container {
    padding: 24mm 26mm 0 26mm;
  }

  /* Two Column Header */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 26px;
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
    margin-bottom: 28px;
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

  .items-table tbody td {
    padding: 13px 16px;
    font-size: 9.5pt;
    color: #374151;
    border: none;
    vertical-align: middle;
  }

  .items-table tbody tr {
    border-bottom: 1px solid #f3f4f6;
  }

  .total-row-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 2px;
    margin-bottom: 30px;
  }

  .total-row-table td {
    padding: 12px 16px;
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
    margin-bottom: 18px;
  }

  .payment-section strong {
    color: #111827;
    font-weight: 700;
  }

  .bank-details-box {
    margin-top: 4px;
    margin-bottom: 14px;
    padding-left: 12px;
    border-left: 2px solid #e5e7eb;
    font-size: 9pt;
    color: #4b5563;
  }

  .note-section {
    font-size: 9.5pt;
    color: #374151;
  }

  .note-section strong {
    color: #111827;
    font-weight: 700;
  }

  /* Bottom Waves */
  .bottom-waves-wrap {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    line-height: 0;
  }
</style>
</head>
<body>

<div class="page-container">
  <!-- Top Header Table -->
  <table class="header-table">
    <tr>
      <td style="width: 55%;">
        <img src="{$logoData}" class="brand-logo" alt="Curtiss">
        <div class="company-info">
          <div class="company-name">Curtiss Software Solutions</div>
          <div>Susara Senarathne</div>
          <div>0761407875</div>
          <div>trycurtiss@gmail.com</div>
        </div>
      </td>
      <td style="width: 45%; text-align: right;">
        <div class="invoice-title">Invoice</div>
        <div class="meta-text">
          <div><strong>Date:</strong> 02 June, 2030</div>
          <div><strong>NO.</strong> 000001</div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Billed to -->
  <div class="billed-to-wrap">
    <div class="billed-to-title">Billed to:</div>
    <div class="client-company">Studio Shodwe</div>
    <div>123 Anywhere St., Any City</div>
    <div>hello@reallygreatsite.com</div>
  </div>

  <!-- Items Table -->
  <table class="items-table">
    <thead>
      <tr>
        <th style="width: 45%;">Item</th>
        <th style="width: 15%; text-align: center;">Quantity</th>
        <th style="width: 20%; text-align: right;">Price</th>
        <th style="width: 20%; text-align: right;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Logo</td>
        <td style="text-align: center;">1</td>
        <td style="text-align: right;">$500</td>
        <td style="text-align: right;">$500</td>
      </tr>
      <tr>
        <td>Banner (2x6m)</td>
        <td style="text-align: center;">2</td>
        <td style="text-align: right;">$45</td>
        <td style="text-align: right;">$90</td>
      </tr>
      <tr>
        <td>Poster (1x2m)</td>
        <td style="text-align: center;">3</td>
        <td style="text-align: right;">$55</td>
        <td style="text-align: right;">$165</td>
      </tr>
    </tbody>
  </table>

  <!-- Total Row -->
  <table class="total-row-table">
    <tr>
      <td style="width: 70%; text-align: right;">Total</td>
      <td style="width: 30%; text-align: right;">$755</td>
    </tr>
  </table>

  <!-- Payment & Bank Info -->
  <div class="payment-section">
    <div><strong>Payment method:</strong> Cash</div>
  </div>

  <!-- Note -->
  <div class="note-section">
    <strong>Note:</strong> Thank you for choosing us!
  </div>
</div>

<!-- Bottom Wave Graphic -->
<div class="bottom-waves-wrap">
  {$svgWaves}
</div>

</body>
</html>
HTML;

$pdf = PdfService::compilePdf($html);
file_put_contents(__DIR__ . '/edge_template_test.pdf', $pdf);
echo "Edge template PDF generated: " . strlen($pdf) . " bytes\n";
