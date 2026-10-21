<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Formatting & UI Helpers
 */

class FormatHelper {
    public static function currency(float|int|string|null $amount): string {
        $val = (float)($amount ?? 0);
        return 'LKR ' . number_format($val, 2, '.', ',');
    }

    public static function date(?string $dateStr, string $format = 'M d, Y'): string {
        if (empty($dateStr) || $dateStr === '0000-00-00') {
            return '-';
        }
        $timestamp = strtotime($dateStr);
        return $timestamp ? date($format, $timestamp) : '-';
    }

    public static function statusBadge(string $status): string {
        $statusLower = strtolower(trim($status));
        $map = [
            'active' => 'badge-ios-black',
            'paid' => 'badge-ios-black',
            'completed' => 'badge-ios-black',
            'partially_paid' => 'badge-ios-outline',
            'unpaid' => 'badge-ios-gray',
            'overdue' => 'badge-ios-danger',
            'suspended' => 'badge-ios-warning',
            'inactive' => 'badge-ios-subtle',
            'cancelled' => 'badge-ios-subtle',
            'draft' => 'badge-ios-subtle',
        ];

        $class = $map[$statusLower] ?? 'badge-ios-gray';
        $label = ucwords(str_replace('_', ' ', $status));

        return sprintf('<span class="badge %s">%s</span>', htmlspecialchars($class), htmlspecialchars($label));
    }
}
