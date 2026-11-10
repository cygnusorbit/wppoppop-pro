<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Popup Positions module: sanitize (save) + render bits (front).
 * Shape: settings.popup_position = { position: slug, top:int, right:int, bottom:int, left:int }
 * Padding is the gap (px) kept between the popup and that screen edge; 0 = stick to the edge.
 */
class WpPopPop_Popup_Position {

    const DEFAULT_POSITION = 'center';
    const MAX_PAD          = 1000;
    const DEFAULT_PAD      = 50; // WPPOPPOP_PP_DEFAULT_PAD

    /** slug => array(vertical, horizontal) */
    private static function map() {
        return array(
            'top-left'      => array('top', 'left'),
            'top-center'    => array('top', 'center'),
            'top-right'     => array('top', 'right'),
            'middle-left'   => array('middle', 'left'),
            'center'        => array('middle', 'center'),
            'middle-right'  => array('middle', 'right'),
            'bottom-left'   => array('bottom', 'left'),
            'bottom-center' => array('bottom', 'center'),
            'bottom-right'  => array('bottom', 'right'),
        );
    }

    public static function positions() {
        return array_keys(self::map());
    }

    public static function defaults() {
        return array('position' => self::DEFAULT_POSITION, 'top' => self::DEFAULT_PAD, 'right' => self::DEFAULT_PAD, 'bottom' => self::DEFAULT_PAD, 'left' => self::DEFAULT_PAD);
    }

    private static function pad($v) {
        if (!is_scalar($v) || !is_numeric($v)) {
            return self::DEFAULT_PAD;
        }
        return max(0, min(self::MAX_PAD, (int) round((float) $v)));
    }

    private static function slug($v) {
        $v = is_string($v) ? $v : '';
        return in_array($v, self::positions(), true) ? $v : self::DEFAULT_POSITION;
    }

    /** Backend AJAX whitelist. Returns a clean array. */
    public static function sanitize($raw) {
        $in = json_decode(wp_json_encode($raw), true);
        if (!is_array($in)) {
            $in = array();
        }
        return array(
            'position' => self::slug(isset($in['position']) ? $in['position'] : ''),
            'top'      => self::pad(isset($in['top']) ? $in['top'] : self::DEFAULT_PAD),
            'right'    => self::pad(isset($in['right']) ? $in['right'] : self::DEFAULT_PAD),
            'bottom'   => self::pad(isset($in['bottom']) ? $in['bottom'] : self::DEFAULT_PAD),
            'left'     => self::pad(isset($in['left']) ? $in['left'] : self::DEFAULT_PAD),
        );
    }

    /**
     * Front render bits. Popups saved before this module existed return an empty result
     * so their rendering is untouched. Re-sanitizes so staged / imported data is safe too.
     */
    public static function frontend($settings) {
        $res = array('css' => '', 'pos' => '');
        if (!is_array($settings) || !isset($settings['popup_position']) || !is_array($settings['popup_position'])) {
            return $res;
        }
        $p   = self::sanitize($settings['popup_position']);
        $map = self::map();
        $v   = $map[$p['position']][0];
        $h   = $map[$p['position']][1];

        $align = array('top' => 'flex-start', 'middle' => 'center', 'bottom' => 'flex-end', 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end');

        $res['pos'] = $p['position'];
        $res['css'] = 'align-items:' . $align[$v] . ';justify-content:' . $align[$h] . ';box-sizing:border-box;padding:'
            . $p['top'] . 'px ' . $p['right'] . 'px ' . $p['bottom'] . 'px ' . $p['left'] . 'px;';
        return $res;
    }
}
