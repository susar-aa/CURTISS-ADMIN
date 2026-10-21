<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents(__DIR__ . '/../public/assets/images/logo.jpg'));

$svgWaves = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 260" preserveAspectRatio="none" style="width: 100%; height: 160px; display: block;">
  <!-- Light gray background hill on left -->
  <path d="M 0,260 L 0,110 C 120,110 240,150 360,210 C 420,240 480,255 540,260 Z" fill="#d1d5db" />
  <!-- Dark wave sweeping up to the right -->
  <path d="M 90,260 C 240,250 380,210 520,180 C 680,145 840,195 1000,105 L 1000,260 Z" fill="#2d3139" />
</svg>
SVG;

$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice NO. 000001</title>
<style>
  @page {
    size: a4 portrait;
    margin: 0;
  }

  * {
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  }

  body {
    margin: 0;
    padding: 0;
    color: #1f2937;
    font-size: 10pt;
    line-height: 1.5;
    background: #ffffff;
    position: relative;
    height: 100%;
  }

  .page-container {
    padding: 22mm 24mm 0 24mm;
  }

  /* Two Column Header */
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
  }

  .header-table td {
    vertical-align: top;
  }

  .brand-logo {
    height: 36px;
    width: auto;
    display: block;
    margin-bottom: 8px;
  }

  .company-info {
    font-size: 9pt;
    color: #374151;
    line-height: 1.45;
  }

  .company-name {
    font-weight: 600;
    color: #111827;
  }

  .invoice-title {
    text-align: right;
    font-size: 26pt;
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
    line-height: 1.5;
  }

  .meta-text strong {
    font-weight: 700;
    color: #111827;
  }

  /* Billed to */
  .billed-to-wrap {
    margin-bottom: 26px;
    font-size: 9.5pt;
    line-height: 1.45;
    color: #374151;
  }

  .billed-to-title {
    font-weight: 800;
    color: #111827;
    margin-bottom: 4px;
    font-size: 10pt;
  }

  .client-company {
    color: #1f2937;
    font-weight: 600;
  }

  /* Table */
  .items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
  }

  .items-table thead th {
    background-color: #e5e7eb;
    color: #374151;
    font-size: 9.5pt;
    font-weight: 600;
    padding: 10px 14px;
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

  .total-row-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 4px;
    margin-bottom: 30px;
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
          <div><strong>Date:</strong> 21 October, 2026</div>
          <div><strong>NO.</strong> 000001</div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Billed to -->
  <div class="billed-to-wrap">
    <div class="billed-to-title">Billed to:</div>
    <div class="client-company">Kandy Kids</div>
    <div>Attn: Mahasen Wimalarathne</div>
    <div>No 04 George E De Silva Mawatha, Kandy, Sri Lanka, 20000</div>
    <div>94mahasen@gmail.com &bull; +94 71 753 2698</div>
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
        <td>Curtiss Point Of Sales (POS) System</td>
        <td style="text-align: center;">1</td>
        <td style="text-align: right;">$500</td>
        <td style="text-align: right;">$500</td>
      </tr>
      <tr>
        <td>Hosting & Cloud Service Fee</td>
        <td style="text-align: center;">1</td>
        <td style="text-align: right;">$45</td>
        <td style="text-align: right;">$45</td>
      </tr>
    </tbody>
  </table>

  <!-- Total Row -->
  <table class="total-row-table">
    <tr>
      <td style="width: 70%; text-align: right;">Total</td>
      <td style="width: 30%; text-align: right;">$545</td>
    </tr>
  </table>

  <!-- Payment & Bank Info -->
  <div class="payment-section">
    <div><strong>Payment method:</strong> Bank Transfer</div>
    <div class="bank-details-box">
      <div>Bank: Commercial Bank of Ceylon</div>
      <div>Branch: Kurunegala City Office</div>
      <div>Account Name: G L DASUN SUSARA SENARATHNE</div>
      <div>Account Number: 8018526390</div>
      <div>Reference: INV-00001</div>
    </div>
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

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$pdf = $dompdf->output();
file_put_contents(__DIR__ . '/template_test.pdf', $pdf);
echo "User template test generated: " . strlen($pdf) . " bytes\n";
