<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Reports Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController {
    private Report $reportModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->reportModel = new Report();
    }

    /**
     * Show financial & subscription reports
     */
    public function index(): void {
        $startDate = (string)($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = (string)($_GET['end_date'] ?? date('Y-m-d'));

        $monthlyRevenue = $this->reportModel->getMonthlyRevenue($startDate, $endDate);
        $clientOutstanding = $this->reportModel->getClientOutstandingReport();
        $subscriptionAnalytics = $this->reportModel->getSubscriptionAnalytics();

        $pageTitle = 'Reports & Analytics';
        $activeNav = 'reports';

        require __DIR__ . '/../views/reports/index.php';
    }

    /**
     * Export payment history as CSV
     */
    public function exportCsv(): void {
        $startDate = (string)($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = (string)($_GET['end_date'] ?? date('Y-m-d'));

        $records = $this->reportModel->getPaymentHistoryExport($startDate, $endDate);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Curtiss_Payments_' . $startDate . '_to_' . $endDate . '.csv"');

        $output = fopen('php://output', 'w');
        // BOM for Excel UTF-8 compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['Receipt Number', 'Client Code', 'Company Name', 'Payment Date', 'Payment Method', 'Reference', 'Amount (LKR)', 'Invoice', 'Status']);

        foreach ($records as $r) {
            fputcsv($output, [
                $r['receipt_number'],
                $r['client_code'],
                $r['company_name'],
                $r['payment_date'],
                ucwords(str_replace('_', ' ', $r['payment_method'])),
                $r['reference_no'] ?: '-',
                number_format((float)$r['amount'], 2, '.', ''),
                $r['invoice_number'] ?: 'Direct',
                ucfirst($r['status'])
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export financial summary as A4 PDF
     */
    public function exportPdf(): void {
        require_once __DIR__ . '/../services/PdfService.php';
        $startDate = (string)($_GET['start_date'] ?? date('Y-01-01'));
        $endDate = (string)($_GET['end_date'] ?? date('Y-m-d'));

        $monthlyRevenue = $this->reportModel->getMonthlyRevenue($startDate, $endDate);
        $clientOutstanding = $this->reportModel->getClientOutstandingReport();

        PdfService::streamReportPdf($startDate, $endDate, $monthlyRevenue, $clientOutstanding);
    }
}
