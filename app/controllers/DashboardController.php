<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Dashboard Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController {
    private Dashboard $dashboardModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->dashboardModel = new Dashboard();
    }

    public function index(): void {
        $currentUser = AuthHelper::getCurrentUser();
        $metrics = $this->dashboardModel->getMetrics();
        $recentPayments = $this->dashboardModel->getRecentPayments(6);
        $upcomingDue = $this->dashboardModel->getUpcomingDueList(6);

        $pageTitle = 'Dashboard';
        $activeNav = 'dashboard';

        require __DIR__ . '/../views/dashboard/index.php';
    }
}
