<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Targeting Triggers module: sanitize (save), server gate (front) and render attributes.
 * Shape: settings.targeting_triggers = {
 *   enabled, event(onload|onscroll|onexit|oninactivity|''), mode, period_hours,
 *   start_delay, close_delay, scroll_offset, scroll_unit, idle_delay,
 *   activity, from, to, roles[], area,
 *   cats_all, cats[], cats_archive, tags_all, tags[], tags_archive,
 *   posts_all, posts[], pages_all, pages[], url_keywords
 * }
 * No event selected (or the switch off) = module inactive, so popups behave exactly as before.
 */
class WpPopPop_Targeting_Triggers {

    const MODULE_KEY = 'acc-targeting-triggers';

    public static function events() {
        return array('onload', 'onscroll', 'onexit', 'oninactivity');
    }

    public static function modes() {
        return array('every-time', 'once-only', 'once-per-period');
    }

    public static function areas() {
        return array('sitewide', 'homepage', 'posts', 'pages', 'url');
    }

    public static function defaults() {
        return array(
            'enabled'       => true,
            'event'         => '',
            'mode'          => 'every-time',
            'period_hours'  => 24,
            'start_delay'   => 0,
            'close_delay'   => 0,
            'scroll_offset' => 600,
            'scroll_unit'   => 'px',
            'idle_delay'    => 15,
            'activity'      => 'always',
            'from'          => '',
            'to'            => '',
            'roles'         => array('all'),
            'area'          => 'sitewide',
            'cats_all'      => true,
            'cats'          => array(),
            'cats_archive'  => false,
            'tags_all'      => true,
            'tags'          => array(),
            'tags_archive'  => false,
            'posts_all'     => true,
            'posts'         => array(),
            'pages_all'     => true,
            'pages'         => array(),
            'url_keywords'  => '',
        );
    }

    /* ---------------------------------------------------------- field helpers */

    private static function flag($v) {
        return in_array($v, array(true, 1, '1', 'true'), true);
    }

    private static function num($v, $min, $max, $def) {
        if (!is_scalar($v) || !is_numeric($v)) {
            return $def;
        }
        return max($min, min($max, (int) round((float) $v)));
    }

    private static function pick($v, $allowed, $def) {
        return (is_string($v) && in_array($v, $allowed, true)) ? $v : $def;
    }

    private static function ids($v) {
        $out = array();
        if (!is_array($v)) {
            return $out;
        }
        foreach (array_slice($v, 0, 5000) as $x) {
            if (is_scalar($x)) {
                $n = absint($x);
                if ($n) {
                    $out[$n] = $n;
                }
            }
        }
        return array_values($out);
    }

    private static function date($v) {
        if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return '';
        }
        return $v;
    }

    private static function roles($v) {
        $valid = array_merge(array('all', 'visitor'), array_keys(self::roles_list()));
        $out   = array();
        if (is_array($v)) {
            foreach (array_slice($v, 0, 100) as $r) {
                $r = is_string($r) ? sanitize_key($r) : '';
                if ($r !== '' && in_array($r, $valid, true) && !in_array($r, $out, true)) {
                    $out[] = $r;
                }
            }
        }
        if (empty($out) || in_array('all', $out, true)) {
            return array('all');
        }
        return $out;
    }

    private static function keywords($v) {
        $out = array();
        foreach (preg_split('/[\r\n,]+/', is_string($v) ? $v : '') as $line) {
            $line = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', wp_strip_all_tags($line)));
            if ($line !== '') {
                $out[] = substr($line, 0, 200);
            }
            if (count($out) >= 50) {
                break;
            }
        }
        return implode("\n", $out);
    }

    /** Backend AJAX whitelist. Returns a clean array. */
    public static function sanitize($raw) {
        $in = json_decode(wp_json_encode($raw), true);
        if (!is_array($in)) {
            $in = array();
        }
        $d = self::defaults();
        $g = function ($k) use ($in, $d) {
            return array_key_exists($k, $in) ? $in[$k] : $d[$k];
        };

        $unit   = self::pick($g('scroll_unit'), array('px', '%'), 'px');
        $act    = self::pick($g('activity'), array('always', 'period'), 'always');
        $from   = self::date($g('from'));
        $to     = self::date($g('to'));
        if ($from !== '' && $to !== '' && $from > $to) {
            $tmp  = $from;
            $from = $to;
            $to   = $tmp;
        }

        return array(
            'enabled'       => self::flag($g('enabled')),
            'event'         => self::pick($g('event'), self::events(), ''),
            'mode'          => self::pick($g('mode'), self::modes(), 'every-time'),
            'period_hours'  => self::num($g('period_hours'), 1, 8760, 24),
            'start_delay'   => self::num($g('start_delay'), 0, 3600, 0),
            'close_delay'   => self::num($g('close_delay'), 0, 3600, 0),
            'scroll_offset' => self::num($g('scroll_offset'), 0, $unit === '%' ? 100 : 100000, 600),
            'scroll_unit'   => $unit,
            'idle_delay'    => self::num($g('idle_delay'), 1, 3600, 15),
            'activity'      => $act,
            'from'          => $from,
            'to'            => $to,
            'roles'         => self::roles($g('roles')),
            'area'          => self::pick($g('area'), self::areas(), 'sitewide'),
            'cats_all'      => self::flag($g('cats_all')),
            'cats'          => self::ids($g('cats')),
            'cats_archive'  => self::flag($g('cats_archive')),
            'tags_all'      => self::flag($g('tags_all')),
            'tags'          => self::ids($g('tags')),
            'tags_archive'  => self::flag($g('tags_archive')),
            'posts_all'     => self::flag($g('posts_all')),
            'posts'         => self::ids($g('posts')),
            'pages_all'     => self::flag($g('pages_all')),
            'pages'         => self::ids($g('pages')),
            'url_keywords'  => self::keywords($g('url_keywords')),
        );
    }

    /* ---------------------------------------------------------- runtime */

    /** Settings > Module switch (default on). A module that is switched off never affects the front end. */
    private static function module_active() {
        if (!class_exists('WpPopPop_Admin_Settings')) {
            require_once WPPOPPOP_PATH . 'includes/admin/class-admin-settings.php';
        }
        /* Not registered: the bundled package is seeded on the first admin load, so stay on until then. A deleted module is off. */
        if (class_exists('WpPopPop_Modules') && !WpPopPop_Modules::get(self::MODULE_KEY)) {
            $orphans = WpPopPop_Modules::orphans();
            return !isset($orphans[self::MODULE_KEY]);
        }
        return WpPopPop_Admin_Settings::module_enabled(self::MODULE_KEY);
    }

    /** Clean, active config or null (module off, absent, switched off or no event chosen). */
    private static function active($settings) {
        if (!is_array($settings) || !isset($settings['targeting_triggers']) || !is_array($settings['targeting_triggers'])) {
            return null;
        }
        $t = self::sanitize($settings['targeting_triggers']);
        if (!$t['enabled'] || $t['event'] === '' || !self::module_active()) {
            return null;
        }
        return $t;
    }

    /** Front render attributes for the runtime (public/js/front/front-targeting-triggers.js). */
    public static function frontend($settings) {
        $res = array('attrs' => '');
        $t   = self::active($settings);
        if ($t === null) {
            return $res;
        }
        $a = ' data-tt-event="' . esc_attr($t['event']) . '" data-tt-mode="' . esc_attr($t['mode']) . '"';
        if ($t['mode'] === 'once-per-period') {
            $a .= ' data-tt-period="' . (int) $t['period_hours'] . '"';
        }
        if ($t['event'] === 'onload') {
            $a .= ' data-tt-start="' . (int) $t['start_delay'] . '" data-tt-close="' . (int) $t['close_delay'] . '"';
        } elseif ($t['event'] === 'onscroll') {
            $a .= ' data-tt-offset="' . (int) $t['scroll_offset'] . '" data-tt-unit="' . esc_attr($t['scroll_unit']) . '"';
        } elseif ($t['event'] === 'onexit') {
            $sens = isset($settings['exit_intent_sensitivity']) ? self::num($settings['exit_intent_sensitivity'], 1, 200, 20) : 20;
            $a   .= ' data-tt-sens="' . (int) $sens . '"';
        } elseif ($t['event'] === 'oninactivity') {
            $a .= ' data-tt-idle="' . (int) $t['idle_delay'] . '"';
        }
        $res['attrs'] = $a;
        return $res;
    }

    /** Server gate: activity period, user roles, site area. Popups without the module are untouched. */
    public static function is_eligible($settings) {
        $t = self::active($settings);
        if ($t === null) {
            return true;
        }
        return self::activity_ok($t) && self::roles_ok($t) && self::area_ok($t);
    }

    private static function activity_ok($t) {
        if ($t['activity'] !== 'period') {
            return true;
        }
        $today = current_time('Y-m-d');
        if ($t['from'] !== '' && $today < $t['from']) {
            return false;
        }
        if ($t['to'] !== '' && $today > $t['to']) {
            return false;
        }
        return true;
    }

    private static function roles_ok($t) {
        if (in_array('all', $t['roles'], true)) {
            return true;
        }
        if (!is_user_logged_in()) {
            return in_array('visitor', $t['roles'], true);
        }
        $user = wp_get_current_user();
        foreach ((array) $user->roles as $role) {
            if (in_array($role, $t['roles'], true)) {
                return true;
            }
        }
        return false;
    }

    private static function area_ok($t) {
        switch ($t['area']) {
            case 'homepage':
                return is_front_page();
            case 'pages':
                if (!is_page()) {
                    return false;
                }
                return $t['pages_all'] || empty($t['pages']) || in_array((int) get_queried_object_id(), $t['pages'], true);
            case 'posts':
                return self::posts_ok($t);
            case 'url':
                return self::url_ok($t);
        }
        return true;
    }

    /** "All" (or an empty selection) = no filter. Active filters are OR-ed. */
    private static function posts_ok($t) {
        $cats_on  = !$t['cats_all'] && !empty($t['cats']);
        $tags_on  = !$t['tags_all'] && !empty($t['tags']);
        $posts_on = !$t['posts_all'] && !empty($t['posts']);

        if (is_singular('post')) {
            if (!$cats_on && !$tags_on && !$posts_on) {
                return true;
            }
            $id = (int) get_queried_object_id();
            return ($posts_on && in_array($id, $t['posts'], true))
                || ($cats_on && has_category($t['cats'], $id))
                || ($tags_on && has_tag($t['tags'], $id));
        }
        if ($t['cats_archive'] && is_category()) {
            return !$cats_on || is_category($t['cats']);
        }
        if ($t['tags_archive'] && is_tag()) {
            return !$tags_on || is_tag($t['tags']);
        }
        return false;
    }

    private static function url_ok($t) {
        $kw = array_filter(array_map('trim', explode("\n", $t['url_keywords'])));
        if (empty($kw)) {
            return true;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? strtolower(rawurldecode((string) wp_unslash($_SERVER['REQUEST_URI']))) : '';
        foreach ($kw as $k) {
            if (strpos($uri, strtolower($k)) !== false) {
                return true;
            }
        }
        return false;
    }

    /* ---------------------------------------------------------- builder data (dynamic lists) */

    private static function limit() {
        return max(1, (int) apply_filters('wppoppop_tt_list_limit', 500));
    }

    /** slug => label, straight from WordPress (stays in sync with plugins that add roles). */
    public static function roles_list() {
        $out = array();
        if (function_exists('wp_roles')) {
            foreach (wp_roles()->get_names() as $slug => $name) {
                $out[$slug] = translate_user_role($name);
            }
        }
        return $out;
    }

    /** term id => name */
    public static function terms_list($taxonomy) {
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => self::limit(), 'orderby' => 'name', 'order' => 'ASC'));
        $out   = array();
        if (is_array($terms)) {
            foreach ($terms as $t) {
                $out[(int) $t->term_id] = $t->name;
            }
        }
        return $out;
    }

    /** post id => "Title (ID: n, Status: s)" */
    public static function posts_list($type) {
        $rows = get_posts(array(
            'post_type'        => $type,
            'post_status'      => array('publish', 'future', 'draft', 'pending', 'private'),
            'numberposts'      => self::limit(),
            'orderby'          => $type === 'page' ? 'title' : 'date',
            'order'            => $type === 'page' ? 'ASC' : 'DESC',
            'suppress_filters' => true,
        ));
        $out = array();
        foreach ($rows as $p) {
            $title = get_the_title($p);
            $title = ($title === '') ? __('(no title)', 'wppoppop') : $title;
            $out[(int) $p->ID] = sprintf('%1$s (ID: %2$d, Status: %3$s)', $title, (int) $p->ID, $p->post_status);
        }
        return $out;
    }

    /** Builder bridge: window.WpPopPopTargetingData feeds the module's Inspector UI (admin.js) while the module is installed. */
    public static function localize_builder() {
        if (!class_exists('WpPopPop_Modules') || !WpPopPop_Modules::is_installed(self::MODULE_KEY) || !wp_script_is('wppoppop-builder-ext-js', 'enqueued')) {
            return;
        }
        $pairs = function ($items) {
            $out = array();
            foreach ($items as $k => $v) {
                $out[] = array((string) $k, (string) $v);
            }
            return $out;
        };
        wp_localize_script('wppoppop-builder-ext-js', 'WpPopPopTargetingData', array(
            'roles' => $pairs(array('all' => __('All roles', 'wppoppop'), 'visitor' => __('Visitors (logged out)', 'wppoppop')) + self::roles_list()),
            'cats'  => $pairs(self::terms_list('category')),
            'tags'  => $pairs(self::terms_list('post_tag')),
            'posts' => $pairs(self::posts_list('post')),
            'pages' => $pairs(self::posts_list('page')),
            'i18n'  => array(
                'All roles' => __('All roles', 'wppoppop'),
                'Visitors (logged out)' => __('Visitors (logged out)', 'wppoppop'),
                'Search...' => __('Search...', 'wppoppop'),
                'Nothing found.' => __('Nothing found.', 'wppoppop'),
                'Popup Targeting' => __('Popup Targeting', 'wppoppop'),
                'Turn the entire Targeting Triggers setting on or off' => __('Turn the entire Targeting Triggers setting on or off', 'wppoppop'),
                'Enable Targeting Triggers' => __('Enable Targeting Triggers', 'wppoppop'),
                'OnLoad' => __('OnLoad', 'wppoppop'),
                'OnScroll' => __('OnScroll', 'wppoppop'),
                'OnExit' => __('OnExit', 'wppoppop'),
                'OnInactivity' => __('OnInactivity', 'wppoppop'),
                'Choose when the popup appears. Until one is selected the popup keeps its default behavior.' => __('Choose when the popup appears. Until one is selected the popup keeps its default behavior.', 'wppoppop'),
                'Target Properties' => __('Target Properties', 'wppoppop'),
                'Periodicity' => __('Periodicity', 'wppoppop'),
                'Every time' => __('Every time', 'wppoppop'),
                'Once only' => __('Once only', 'wppoppop'),
                'Once per period' => __('Once per period', 'wppoppop'),
                'Show again after' => __('Show again after', 'wppoppop'),
                'hrs' => __('hrs', 'wppoppop'),
                'Start delay' => __('Start delay', 'wppoppop'),
                'sec' => __('sec', 'wppoppop'),
                'Close delay' => __('Close delay', 'wppoppop'),
                'Close delay 0 keeps the popup open until it is closed.' => __('Close delay 0 keeps the popup open until it is closed.', 'wppoppop'),
                'Scrolling offset' => __('Scrolling offset', 'wppoppop'),
                'Scrolling offset unit' => __('Scrolling offset unit', 'wppoppop'),
                'Inactivity delay' => __('Inactivity delay', 'wppoppop'),
                'Activity' => __('Activity', 'wppoppop'),
                'Always' => __('Always', 'wppoppop'),
                'Certain period' => __('Certain period', 'wppoppop'),
                'From' => __('From', 'wppoppop'),
                'End' => __('End', 'wppoppop'),
                'User Roles' => __('User Roles', 'wppoppop'),
                'Site Area' => __('Site Area', 'wppoppop'),
                'Sitewide' => __('Sitewide', 'wppoppop'),
                'Homepage' => __('Homepage', 'wppoppop'),
                'Posts' => __('Posts', 'wppoppop'),
                'Pages' => __('Pages', 'wppoppop'),
                'URL' => __('URL', 'wppoppop'),
                'Categories' => __('Categories', 'wppoppop'),
                'All' => __('All', 'wppoppop'),
                'Specific' => __('Specific', 'wppoppop'),
                'Also show on category archive pages' => __('Also show on category archive pages', 'wppoppop'),
                'Tags' => __('Tags', 'wppoppop'),
                'Also show on tag archive pages' => __('Also show on tag archive pages', 'wppoppop'),
                'A post matches when it is in any selected category, tag or post list.' => __('A post matches when it is in any selected category, tag or post list.', 'wppoppop'),
                'All pages' => __('All pages', 'wppoppop'),
                'Specific pages' => __('Specific pages', 'wppoppop'),
                'URL contains' => __('URL contains', 'wppoppop'),
                'One per line. The popup shows when the page address contains any of them. Empty = every URL.' => __('One per line. The popup shows when the page address contains any of them. Empty = every URL.', 'wppoppop'),
            ),
        ));
    }
}
