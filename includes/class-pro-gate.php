<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared helpers for the Pro admin pages: the licence gate and the dashboard bar links.
 */
class WpPopPop_Pro_Gate {

    public static function unlocked() {
        return WpPopPop_Pro_License::is_unlocked();
    }

    /** Page shown instead of a Pro page while the licence is not active. Same look as the License page. */
    public static function locked_screen($title, $icon, $desc) {
        $locked_title = $title;
        $locked_icon  = $icon;
        $locked_desc  = $desc;
        include WPPOPPOP_PRO_PATH . 'templates/locked-page.php';
    }

    /** Inserts one [slug, label, icon] entry into the dashboard bar links after the given slug. */
    public static function insert_link($links, $after, $entry) {
        $out   = array();
        $added = false;
        foreach ((array) $links as $l) {
            $out[] = $l;
            if (!$added && isset($l[0]) && $l[0] === $after) {
                $out[]  = $entry;
                $added = true;
            }
        }
        if (!$added) {
            $out[] = $entry;
        }
        return $out;
    }
}
