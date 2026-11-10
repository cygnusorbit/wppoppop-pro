<?php
/* WPPOPPOP_AB_FILTERS */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A/B Campaigns list query: status filter (all|active|inactive|trash), search and pagination.
 * Reads status / s / paged from the request and returns a ready-to-render state array.
 */
class WpPopPop_Admin_AB_Query {

    const VIEWS = ['all', 'active', 'inactive', 'trash'];

    public static function current_status() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
        return in_array($status, self::VIEWS, true) ? $status : 'all';
    }

    private static function status_sql($status) {
        $col = "COALESCE(LOWER(status), 'active')";
        switch ($status) {
            case 'active':
                return "{$col} NOT IN ('draft','paused','inactive','trash')";
            case 'inactive':
                return "{$col} IN ('draft','paused','inactive')";
            case 'trash':
                return "{$col} = 'trash'";
            default:
                return "{$col} <> 'trash'";
        }
    }

    public static function counts() {
        global $wpdb;
        $counts = ['all' => 0, 'active' => 0, 'inactive' => 0, 'trash' => 0];
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results("SELECT COALESCE(LOWER(status), 'active') AS st, COUNT(*) AS n FROM {$wpdb->prefix}wppoppop_campaigns GROUP BY COALESCE(LOWER(status), 'active')");
        foreach ((array) $rows as $row) {
            $n  = (int) $row->n;
            $st = (string) $row->st;
            if ($st === 'trash') {
                $counts['trash'] += $n;
                continue;
            }
            $counts['all'] += $n;
            if (in_array($st, ['draft', 'paused', 'inactive'], true)) {
                $counts['inactive'] += $n;
            } else {
                $counts['active'] += $n;
            }
        }
        return $counts;
    }

    public static function run() {
        global $wpdb;
        $table  = $wpdb->prefix . 'wppoppop_campaigns';
        $status = self::current_status();
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $search = isset($_GET['s']) ? trim(sanitize_text_field(wp_unslash($_GET['s']))) : '';
        $paged  = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $per    = max(1, (int) apply_filters('wppoppop_ab_per_page', 10));

        $where  = self::status_sql($status);
        $params = [];
        if ($search !== '') {
            $like    = '%' . $wpdb->esc_like($search) . '%';
            $where  .= ' AND (title LIKE %s OR uid LIKE %s)';
            $params  = [$like, $like];
        }

        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql));

        $pages  = max(1, (int) ceil($total / $per));
        $paged  = min($paged, $pages);
        $offset = ($paged - 1) * $per;

        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
            array_merge($params, [$per, $offset])
        ));
        // phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        return [
            'items'    => is_array($items) ? $items : [],
            'total'    => $total,
            'pages'    => $pages,
            'paged'    => $paged,
            'per_page' => $per,
            'offset'   => $offset,
            'status'   => $status,
            'search'   => $search,
            'counts'   => self::counts(),
        ];
    }

    public static function url($state, $args = []) {
        $base = ['page' => 'wppoppop-ab'];
        $status = array_key_exists('status', $args) ? $args['status'] : $state['status'];
        $search = array_key_exists('s', $args) ? $args['s'] : $state['search'];
        if ($status && $status !== 'all') {
            $base['status'] = $status;
        }
        if ($search !== '' && $search !== null) {
            $base['s'] = $search;
        }
        if (!empty($args['paged']) && (int) $args['paged'] > 1) {
            $base['paged'] = (int) $args['paged'];
        }
        return add_query_arg($base, admin_url('admin.php'));
    }
}
