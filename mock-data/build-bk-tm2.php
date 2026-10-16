<?php
/**
 * Builds the "bk-tm2" layout (Immigration Division 2 home page) with Elementor FREE widgets:
 *   - Elementor global colours / fonts / container width (the design tokens of the original CSS)
 *   - Header + Footer templates for "Ultimate Addons for Elementor Lite" (plugin: header-footer-elementor)
 *   - the Home page (Elementor containers + core widgets + Essential Addons Lite post grid)
 *   - a little Additional CSS for the header menu colours (the free menu widget has no colour controls)
 *
 * Requires: seed.php already run (pages, menu, categories, mock posts). Safe to re-run: it rebuilds the same items.
 * Text is placeholder except the section titles / service labels, which come from the design.
 *
 *   docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/build-bk-tm2.php --path=/var/www/html
 */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/* ---------- design tokens (from imm-system.css) ---------- */
$C = ['navy' => '#0A1E3C', 'navy7' => '#12294B', 'navy9' => '#061428', 'gold' => '#B8934A', 'gold2' => '#D4B26A', 'crimson' => '#8C1D2C',
      'paper' => '#EFF2F6', 'card' => '#FFFFFF', 'rule' => '#D9DFE7', 'ink' => '#16202E', 'muted' => '#5E6B7C', 'white' => '#FFFFFF'];
$SANS = 'Sarabun'; $SERIF = 'Noto Serif Thai';

/* ---------- builders ---------- */
$n = 0;
$uid = function () use (&$n) { return substr(md5('bk-tm2-' . (++$n)), 0, 7); };
$px  = fn($v, $u = 'px') => ['unit' => $u, 'size' => $v, 'sizes' => []];
$dim = fn($t, $r = null, $b = null, $l = null) => ['unit' => 'px', 'top' => (string) $t, 'right' => (string) ($r ?? $t), 'bottom' => (string) ($b ?? $t), 'left' => (string) ($l ?? $r ?? $t), 'isLinked' => false];
$gap = fn($v) => ['column' => (string) $v, 'row' => (string) $v, 'isLinked' => true, 'unit' => 'px'];
$box = function ($c) use ($px) { return ['content_width' => 'boxed', 'boxed_width' => $px(1180)]; };
$el  = function (array $s, array $kids = [], bool $inner = false) use ($uid) { return ['id' => $uid(), 'elType' => 'container', 'settings' => $s, 'elements' => $kids, 'isInner' => $inner]; };
$wd  = function (string $type, array $s) use ($uid) { return ['id' => $uid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $s, 'elements' => []]; };
$ty  = fn($fam, $size, $w = '400', $lh = null) => ['typography_typography' => 'custom', 'typography_font_family' => $fam, 'typography_font_size' => $px($size), 'typography_font_weight' => $w] + ($lh ? ['typography_line_height' => $px($lh, 'em')] : []);
$link = fn($u = '#') => ['url' => $u, 'is_external' => '', 'nofollow' => '', 'custom_attributes' => ''];

$head = fn($t, $tag, $color, $size, $align = 'left', $fam = null, $w = '700') => $wd('heading', ['title' => $t, 'header_size' => $tag, 'align' => $align, 'title_color' => $color] + $ty($fam ?? $GLOBALS['__SERIF'], $size, $w, 1.3));
$text = fn($html, $color, $size = 16, $align = 'left', $w = '400') => $wd('text-editor', ['editor' => $html, 'text_color' => $color, 'align' => $align] + $ty($GLOBALS['__SANS'], $size, $w, 1.6));
$icon = fn($v, $color, $size = 26, $align = 'left') => $wd('icon', ['selected_icon' => ['value' => $v, 'library' => 'fa-solid'], 'primary_color' => $color, 'size' => $px($size), 'align' => $align]);
$btn  = function ($label, $url, $fg, $bg, $bd, $align = 'left') use ($wd, $ty, $link, $dim, $SANS) {
    return $wd('button', ['text' => $label, 'link' => $link($url), 'align' => $align, 'button_text_color' => $fg, 'background_color' => $bg,
        'border_border' => 'solid', 'border_width' => $dim(1), 'border_color' => $bd, 'border_radius' => $dim(6), 'text_padding' => $dim(10, 22)] + $ty($SANS, 15, '600'));
};
$GLOBALS['__SANS'] = $SANS; $GLOBALS['__SERIF'] = $SERIF;

// a full-width coloured band with a boxed inner column
$band = function ($bg, array $kids, $id = '', $pad = 64) use ($el, $dim, $px, $gap, $C) {
    $inner = $el(['content_width' => 'boxed', 'boxed_width' => $px(1180), 'flex_direction' => 'column', 'flex_gap' => $gap(26)], $kids, true);
    return $el(['content_width' => 'full', 'flex_direction' => 'column', 'background_background' => 'classic', 'background_color' => $bg, 'padding' => $dim($pad, 24), '_element_id' => $id], [$inner]);
};
// section header: gold tick + title + rule + optional link
$secHead = function ($title, $color, $linkText = '', $linkUrl = '#') use ($el, $wd, $head, $text, $px, $gap, $C, $link) {
    $kids = [
        $wd('html', ['html' => '<span style="display:block;width:5px;height:28px;background:' . $C['gold'] . ';border-radius:2px"></span>']),
        $head($title, 'h2', $color, 28),
        $wd('html', ['html' => '<span style="display:block;height:1px;background:' . ($color === $C['white'] ? 'rgba(212,178,106,.45)' : $C['rule']) . ';width:100%"></span>', '_flex_size' => 'grow']),
    ];
    if ($linkText) $kids[] = $wd('text-editor', ['editor' => '<a href="' . esc_url($linkUrl) . '" style="color:' . ($color === $C['white'] ? $C['gold2'] : $C['gold']) . ';font-weight:600">' . $linkText . ' ›</a>', 'typography_typography' => 'custom', 'typography_font_family' => $GLOBALS['__SANS'], 'typography_font_size' => $px(15)]);
    return $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_gap' => $gap(14), 'flex_wrap' => 'nowrap'], $kids, true);
};
$card = fn($bg, $border) => ['background_background' => 'classic', 'background_color' => $bg, 'border_border' => 'solid', 'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true], 'border_color' => $border, 'border_radius' => ['unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true],
    'box_shadow_box_shadow_type' => 'yes', 'box_shadow_box_shadow' => ['horizontal' => 0, 'vertical' => 4, 'blur' => 18, 'spread' => 0, 'color' => 'rgba(10,30,60,.10)']];
$cols = fn($d, $t, $m) => ['width' => $px($d, '%'), 'width_tablet' => $px($t, '%'), 'width_mobile' => $px($m, '%')];

/* ---------- placeholder portrait image ---------- */
function bk_png($w, $h, $rgb, $label) {
    $im = imagecreatetruecolor($w, $h); $bg = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]); $fg = imagecolorallocate($im, max(0, $rgb[0] - 60), max(0, $rgb[1] - 60), max(0, $rgb[2] - 60)); $tx = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg); imageline($im, 0, 0, $w, $h, $fg); imageline($im, $w, 0, 0, $h, $fg);
    $t = "$label {$w}x{$h}"; imagestring($im, 5, (int) (($w - imagefontwidth(5) * strlen($t)) / 2), (int) ($h / 2 - 8), $t, $tx);
    ob_start(); imagepng($im); $b = ob_get_clean(); imagedestroy($im); return $b;
}
$portraitId = 0;
$ex = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'img-portrait', 'fields' => 'ids', 'numberposts' => 1]);
if ($ex) { $portraitId = (int) $ex[0]; } else {
    $up = wp_upload_bits('mock-portrait.png', null, bk_png(800, 600, [120, 134, 150], 'PORTRAIT'));
    if (empty($up['error'])) {
        $portraitId = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Mock portrait', 'post_status' => 'inherit'], $up['file']);
        wp_update_attachment_metadata($portraitId, wp_generate_attachment_metadata($portraitId, $up['file']));
        update_post_meta($portraitId, '_mock_key', 'img-portrait'); update_post_meta($portraitId, '_mock_data', 1);
    }
}
$portraitUrl = $portraitId ? wp_get_attachment_url($portraitId) : '';
$logoId = (int) get_theme_mod('custom_logo');
$logoUrl = $logoId ? wp_get_attachment_url($logoId) : '';
$pageUrl = fn($slug) => ($p = get_page_by_path($slug)) ? get_permalink($p) : '#';
$cat = get_term_by('slug', 'cat-202', 'category');

/* ================= HOME PAGE ================= */
$home = [];

// 1. hero banner slot + caption + status band
$home[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center', 'flex_gap' => $gap(12),
    'background_background' => 'classic', 'background_color' => '#22262d', 'min_height' => $px(380), 'padding' => $dim(40, 24)], [
    $icon('fas fa-map-marker-alt', $C['gold2'], 34, 'center'),
    $text('<strong>Official banner area</strong> &middot; recommended size 1440 &times; 460 px', '#E6EAF0', 18, 'center'),
    $text('Replace this container with an Image or Image Carousel widget.', '#9AA5B4', 14, 'center'),
]);
$home[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'background_background' => 'classic', 'background_color' => $C['paper'], 'padding' => $dim(18, 24)], [
    $text('Placeholder banner caption. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.', $C['muted'], 15, 'center'),
]);
$cell = fn($ico, $k, $v) => $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_gap' => $gap(14)] + $cols(31, 100, 100), [
    $icon($ico, $C['gold2'], 28), $text('<span style="color:' . $C['gold2'] . ';font-size:13px">' . $k . '</span><br><strong style="color:#fff;font-size:17px">' . $v . '</strong>', '#fff', 16),
], true);
$home[] = $band($C['navy7'], [$el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(18)], [
    $cell('far fa-clock', 'เวลาราชการ', 'Mon-Fri 08:30-16:30'),
    $cell('fas fa-phone-alt', 'สายด่วนตรวจคนเข้าเมือง', 'Hotline 0-0000 (24 h)'),
    $cell('far fa-id-card', 'บัตรขาเข้าดิจิทัล TDAC', 'Register before travel'),
], true)], 'status', 22);

// 2. online services (navy band): 6 "lanes" + 6 "passes"
$lanes = [['fas fa-shield-alt', 'แจ้งเหตุด่วน'], ['far fa-calendar-alt', 'จองคิวล่วงหน้า'], ['fas fa-star-of-life', 'สำนักงานตำรวจแห่งชาติ'],
          ['far fa-file-alt', 'บัตรขาเข้าดิจิทัล TDAC'], ['fas fa-mobile-alt', 'แอปพลิเคชันตรวจคนเข้าเมือง'], ['fas fa-search', 'ตรวจสอบสถานะคำร้อง']];
$laneEls = [];
foreach ($lanes as [$ico, $lab]) $laneEls[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_gap' => $gap(10), 'padding' => $dim(18, 10),
    'background_background' => 'classic', 'background_color' => $C['navy7'], 'border_border' => 'solid', 'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true], 'border_color' => 'rgba(212,178,106,.35)',
    'border_radius' => ['unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true]] + $cols(14.5, 30, 46), [
    $icon($ico, $C['gold2'], 30, 'center'), $text($lab, '#fff', 14, 'center', '600'),
], true);
$passes = [['TDAC', 'บัตรขาเข้าดิจิทัล', 'ลงทะเบียน'], ['ตม. 2', 'รายการเกี่ยวกับยานพาหนะ', 'แจ้งรายการ'], ['ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งเข้าพัก'],
           ['ONLINE APPOINTMENT', 'จองคิวออนไลน์', 'จองคิว'], ['ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง']];
$passEls = [];
foreach ($passes as [$code, $name, $cta]) $passEls[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(0), 'background_background' => 'classic', 'background_color' => $C['white'],
    'border_radius' => ['unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true], 'overflow' => 'hidden'] + $cols(31, 47, 100), [
    $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(4), 'background_background' => 'classic', 'background_color' => $C['navy9'], 'padding' => $dim(16, 20)], [
        $head($code, 'h4', $C['gold2'], 15, 'left', $GLOBALS['__SANS'], '700'), $head($name, 'h3', $C['white'], 19, 'left', $GLOBALS['__SERIF'], '600')], true),
    $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(12), 'padding' => $dim(18, 20)], [
        $text('Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod tempor.', $C['muted'], 15), $btn($cta, '#', $C['white'], $C['gold'], $C['gold'])], true),
], true);
$home[] = $band($C['navy'], [
    $secHead('บริการออนไลน์', $C['white']),
    $text('Placeholder intro. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.', '#C7D0DC', 17),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(16)], $laneEls, true),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(20)], $passEls, true),
], 'eservice', 72);

// 3. commanders
$execs = [['ผู้บัญชาการตำรวจแห่งชาติ'], ['ผู้บัญชาการสำนักงานตรวจคนเข้าเมือง'], ['ผู้บังคับการตรวจคนเข้าเมือง 2']];
$execEls = [];
foreach ($execs as [$role]) $execEls[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(0), 'overflow' => 'hidden'] + $card($C['white'], $C['rule']) + $cols(31, 47, 100), [
    $wd('image', ['image' => ['url' => $portraitUrl, 'id' => $portraitId], 'image_size' => 'medium_large', 'align' => 'center', 'width' => $px(100, '%')]),
    $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(4), 'padding' => $dim(16, 20)], [
        $head('ยศ ชื่อ นามสกุล (placeholder)', 'h4', $C['navy'], 18, 'left', $GLOBALS['__SERIF'], '700'), $text($role, $C['muted'], 15)], true),
], true);
$home[] = $band($C['white'], [
    $secHead('ผู้บังคับบัญชา', $C['navy'], 'โครงสร้างหน่วยงาน', $pageUrl('org-structure')),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(22)], $execEls, true),
], 'about', 64);

// 4. gift-refusal policy (ITA)
$sheet = fn($l) => $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_gap' => $gap(10), 'padding' => $dim(26, 18)] + $card($C['white'], $C['rule']) + $cols(46, 46, 100), [
    $icon('far fa-file-alt', $C['gold'], 38, 'center'), $text($l, $C['navy'], 15, 'center', '600')], true);
$home[] = $band($C['paper'], [$el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'flex_gap' => $gap(36)], [
    $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(14)] + $cols(52, 100, 100), [
        $head('นโยบายงดรับและงดให้ของขวัญ', 'h2', $C['navy'], 28), $text('Placeholder text. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.', $C['ink'], 17),
        $btn('อ่านประกาศฉบับเต็ม', '#', $C['navy'], 'transparent', $C['navy'])], true),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(16)] + $cols(40, 100, 100), [$sheet('ประกาศเจตนารมณ์ ระดับกองบังคับการ'), $sheet('ประกาศเจตนารมณ์ ผู้บังคับการ')], true),
], true)], 'ita', 64);

// 5. news (Essential Addons Lite post grid, category "ผลการปฏิบัติ")
$chips = [];
foreach ([['ทั้งหมด', true], ['ข่าวประชาสัมพันธ์', false], ['ผลการจับกุม', false], ['กิจกรรม ITA', false]] as [$l, $on])
    $chips[] = $btn($l, '#', $on ? $C['white'] : $C['navy'], $on ? $C['navy'] : 'transparent', $C['navy']);
$home[] = $band($C['white'], [
    $secHead('ผลการปฏิบัติ', $C['navy'], 'ดูทั้งหมด', $cat ? get_category_link($cat->term_id) : '#'),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(10)], $chips, true),
    $wd('eael-post-grid', ['eael_post_grid_preset_style' => 'one', 'eael_post_grid_columns' => 'eael-col-3', 'post_type' => 'post', 'posts_per_page' => 3,
        'category_ids' => $cat ? [(string) $cat->term_id] : [], 'eael_show_excerpt' => 'yes', 'eael_excerpt_length' => 16, 'eael_show_read_more_button' => 'yes', 'read_more_button_text' => 'อ่านรายละเอียด',
        'eael_show_meta' => 'yes', 'eael_show_author' => '', 'eael_show_date' => 'yes', 'image_size' => 'medium_large', 'eael_show_title' => 'yes', 'layout_mode' => 'grid']),
], 'news', 64);

// 6. videos
$vids = [];
for ($i = 1; $i <= 4; $i++) $vids[] = $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center', 'flex_gap' => $gap(12), 'min_height' => $px(190), 'padding' => $dim(20),
    'background_background' => 'classic', 'background_color' => $C['navy'], 'border_radius' => ['unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true]] + $cols(22.5, 46, 100), [
    $icon('far fa-play-circle', $C['gold2'], 46, 'center'), $text("Video title placeholder $i", '#fff', 15, 'center', '600')], true);
$home[] = $band($C['paper'], [$secHead('วีดิทัศน์แนะนำ', $C['navy'], 'ช่อง Immigration Bureau'), $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(18)], $vids, true)], 'videos', 64);

/* ================= HEADER ================= */
$header = [
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between', 'background_background' => 'classic', 'background_color' => $C['navy9'], 'padding' => $dim(7, 24)], [
        $el(['content_width' => 'boxed', 'boxed_width' => $px(1180), 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between', 'flex_gap' => $gap(18)], [
            $wd('text-editor', ['editor' => '<span style="color:#D4B26A">&#9742;</span> 02-000-0000 &nbsp;&nbsp; <span style="color:#D4B26A">&#9993;</span> info@example.invalid', 'text_color' => '#C7D0DC'] + $ty($SANS, 13)),
            $wd('text-editor', ['editor' => '<strong style="color:#D4B26A">ไทย</strong> &nbsp;|&nbsp; EN', 'text_color' => '#C7D0DC', 'align' => 'right'] + $ty($SANS, 13)),
        ], true),
    ]),
    $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'background_background' => 'classic', 'background_color' => $C['navy'], 'padding' => $dim(12, 24),
        'border_border' => 'solid', 'border_width' => ['unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '3', 'left' => '0', 'isLinked' => false], 'border_color' => $C['gold']], [
        $el(['content_width' => 'boxed', 'boxed_width' => $px(1180), 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between', 'flex_gap' => $gap(24), 'flex_wrap' => 'nowrap'], [
            $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'flex_gap' => $gap(14), 'flex_wrap' => 'nowrap'], [
                $wd('site-logo', ['custom_image' => ['url' => $logoUrl, 'id' => $logoId], 'site_logo_size_size' => 'medium', 'width' => $px(54, 'px'), 'align' => 'left', 'link_to' => 'default']),
                $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(0)], [
                    $head('กองบังคับการตรวจคนเข้าเมือง 2', 'div', $C['white'], 17, 'left', $SERIF, '700'),
                    $head('IMMIGRATION DIVISION 2', 'div', $C['gold2'], 11, 'left', $SANS, '600'),
                ], true),
            ], true),
            $wd('navigation-menu', ['menu' => 'main-menu', 'layout' => 'horizontal', 'navmenu_align' => 'right', 'submenu_icon' => 'arrow', 'dropdown' => 'tablet']),
        ], true),
    ]),
];

/* ================= FOOTER ================= */
$flist = function ($title, array $items) use ($el, $wd, $head, $gap, $C, $SANS, $SERIF, $ty, $link, $cols) {
    $rows = []; foreach ($items as $t) $rows[] = ['_id' => substr(md5($title . $t), 0, 7), 'text' => $t, 'selected_icon' => ['value' => 'fas fa-angle-right', 'library' => 'fa-solid'], 'link' => $link('#')];
    return $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(10)] + $cols(20, 48, 100), [
        $head($title, 'h4', $C['gold2'], 16, 'left', $SANS, '700'),
        $wd('icon-list', ['icon_list' => $rows, 'text_color' => '#C7D0DC', 'icon_color' => $C['gold2'], 'space_between' => ['unit' => 'px', 'size' => 8, 'sizes' => []]] + $ty($SANS, 15)),
    ], true);
};
$footer = [
    $el(['content_width' => 'full', 'flex_direction' => 'column', 'background_background' => 'classic', 'background_color' => $C['navy9'], 'padding' => $dim(56, 24, 24, 24)], [
        $el(['content_width' => 'boxed', 'boxed_width' => $px(1180), 'flex_direction' => 'column', 'flex_gap' => $gap(34)], [
            $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_gap' => $gap(26)], [
                $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(8)] + $cols(29, 100, 100), [
                    $wd('site-logo', ['custom_image' => ['url' => $logoUrl, 'id' => $logoId], 'width' => $px(60, 'px'), 'align' => 'left']),
                    $head('กองบังคับการตรวจคนเข้าเมือง 2', 'h3', $C['white'], 19, 'left', $SERIF, '700'),
                    $head('IMMIGRATION DIVISION 2', 'div', $C['gold2'], 11, 'left', $SANS, '600'),
                    $wd('text-editor', ['editor' => '123 Example Road, Sample District<br>Sample Province 10000', 'text_color' => '#C7D0DC'] + $ty($SANS, 15, '400', 1.6)),
                ], true),
                $flist('บริการผู้เดินทาง', ['บัตรขาเข้าดิจิทัล TDAC', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งอยู่เกิน 90 วัน', 'ขออยู่ต่อในราชอาณาจักร']),
                $flist('เกี่ยวกับหน่วยงาน', ['โครงสร้างและอำนาจหน้าที่', 'ผู้บังคับบัญชา', 'กฎหมายและระเบียบ', 'มาตรฐาน ITA']),
                $el(['content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => $gap(10)] + $cols(20, 48, 100), [
                    $head('ติดต่อและร้องเรียน', 'h4', $C['gold2'], 16, 'left', $SANS, '700'),
                    $wd('text-editor', ['editor' => 'Tel. 02-000-0000<br>Hotline 0-0000 (24 h)<br>info@example.invalid', 'text_color' => '#C7D0DC'] + $ty($SANS, 15, '400', 1.8)),
                    $wd('social-icons', ['social_icon_list' => [
                        ['_id' => 'a1b2c3d', 'social_icon' => ['value' => 'fab fa-facebook-f', 'library' => 'fa-brands'], 'link' => $link('#')],
                        ['_id' => 'b2c3d4e', 'social_icon' => ['value' => 'fab fa-x-twitter', 'library' => 'fa-brands'], 'link' => $link('#')],
                        ['_id' => 'c3d4e5f', 'social_icon' => ['value' => 'fab fa-youtube', 'library' => 'fa-brands'], 'link' => $link('#')]], 'shape' => 'circle', 'align' => 'left', 'icon_color' => 'custom', 'icon_primary_color' => $C['gold'], 'icon_secondary_color' => $C['navy9'],
                        'icon_size' => $px(14), 'icon_padding' => $px(0.8, 'em'), 'icon_spacing' => $px(8)]),
                ], true),
            ], true),
            $el(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_justify_content' => 'space-between', 'flex_gap' => $gap(12), 'border_border' => 'solid',
                'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false], 'border_color' => 'rgba(212,178,106,.25)', 'padding' => $dim(18, 0, 6, 0)], [
                $wd('copyright', ['shortcode' => 'Copyright © [hfe_current_year] Placeholder Division. All rights reserved.', 'align' => 'left', 'text_color' => '#8E9AA9'] + $ty($SANS, 13)),
                $wd('text-editor', ['editor' => 'Privacy policy · Website policy', 'text_color' => '#8E9AA9', 'align' => 'right'] + $ty($SANS, 13)),
            ], true),
        ], true),
    ]),
];

/* ================= SAVE ================= */
function bk_save($post_type, $key, $title, $data, $meta = [], $extra = []) {
    $q = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1]);
    $args = ['post_type' => $post_type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => ''] + $extra;
    if ($q) { $id = (int) $q[0]; wp_update_post(['ID' => $id] + $args); } else { $id = wp_insert_post($args); }
    update_post_meta($id, '_mock_key', $key); update_post_meta($id, '_mock_data', 1);
    update_post_meta($id, '_elementor_edit_mode', 'builder'); update_post_meta($id, '_elementor_template_type', 'wp-post');
    update_post_meta($id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
    update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    foreach ($meta as $k => $v) update_post_meta($id, $k, $v);
    return $id;
}
$homePage = get_page_by_path('home');
if (!$homePage) { echo "Home page not found. Run seed.php first.\n"; return; }
$homeId = bk_save('page', 'page-home', 'Home', $home, ['_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page'], ['ID' => $homePage->ID, 'post_name' => 'home']);
$hdrId = bk_save('elementor-hf', 'hf-header', 'Header (bk-tm2)', $header, ['ehf_template_type' => 'type_header', 'ehf_target_include_locations' => ['rule' => ['basic-global'], 'specific' => []], 'ehf_target_exclude_locations' => ['rule' => [], 'specific' => []], 'ehf_target_user_roles' => ['all']]);
$ftrId = bk_save('elementor-hf', 'hf-footer', 'Footer (bk-tm2)', $footer, ['ehf_template_type' => 'type_footer', 'ehf_target_include_locations' => ['rule' => ['basic-global'], 'specific' => []], 'ehf_target_exclude_locations' => ['rule' => [], 'specific' => []], 'ehf_target_user_roles' => ['all']]);

/* ---------- Elementor global design settings (the active Kit) ---------- */
$kitId = (int) get_option('elementor_active_kit');
if ($kitId) {
    $s = get_post_meta($kitId, '_elementor_page_settings', true); if (!is_array($s)) $s = [];
    $s['system_colors'] = [['_id' => 'primary', 'title' => 'Primary', 'color' => $C['navy']], ['_id' => 'secondary', 'title' => 'Secondary', 'color' => $C['gold']], ['_id' => 'text', 'title' => 'Text', 'color' => $C['ink']], ['_id' => 'accent', 'title' => 'Accent', 'color' => $C['crimson']]];
    $s['custom_colors'] = [['_id' => 'navy700', 'title' => 'Navy 700', 'color' => $C['navy7']], ['_id' => 'navy900', 'title' => 'Navy 900', 'color' => $C['navy9']], ['_id' => 'goldbright', 'title' => 'Gold bright', 'color' => $C['gold2']], ['_id' => 'paper', 'title' => 'Paper', 'color' => $C['paper']], ['_id' => 'muted', 'title' => 'Muted', 'color' => $C['muted']], ['_id' => 'rule', 'title' => 'Rule', 'color' => $C['rule']]];
    $s['system_typography'] = [['_id' => 'primary', 'title' => 'Primary', 'typography_typography' => 'custom', 'typography_font_family' => $SERIF, 'typography_font_weight' => '700'], ['_id' => 'secondary', 'title' => 'Secondary', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '600'], ['_id' => 'text', 'title' => 'Text', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '400'], ['_id' => 'accent', 'title' => 'Accent', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '500']];
    $s['body_typography_typography'] = 'custom'; $s['body_typography_font_family'] = $SANS; $s['body_typography_font_size'] = $px(16);
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h) { $s[$h . '_typography_typography'] = 'custom'; $s[$h . '_typography_font_family'] = $SERIF; $s[$h . '_typography_font_weight'] = '700'; }
    $s['container_width'] = $px(1180);
    update_post_meta($kitId, '_elementor_page_settings', $s);
}

/* ---------- Additional CSS: header menu colours (free menu widget has no colour controls) ---------- */
$css = <<<CSS
/* bk-tm2 header menu (UAE Lite navigation-menu widget colours come from Elementor globals, so override them here) */
.elementor-widget-navigation-menu .menu-item a.hfe-menu-item { color:#fff !important; font-family:'Sarabun',sans-serif; font-size:15px; font-weight:500; white-space:nowrap; padding:10px 12px !important; }
.elementor-widget-navigation-menu .menu-item a.hfe-menu-item:hover,
.elementor-widget-navigation-menu .menu-item.current-menu-item > a.hfe-menu-item { color:#D4B26A !important; }
.elementor-widget-navigation-menu .menu-item a.hfe-menu-item .sub-arrow svg { fill:#D4B26A; }
.elementor-widget-navigation-menu .sub-menu { background:#12294B !important; border-top:2px solid #B8934A; }
.elementor-widget-navigation-menu .sub-menu a.hfe-sub-menu-item { color:#fff !important; padding:10px 18px !important; font-size:14px; white-space:nowrap; }
.elementor-widget-navigation-menu .sub-menu a.hfe-sub-menu-item:hover { color:#D4B26A !important; background:#0A1E3C; }
.hfe-nav-menu__toggle, .hfe-nav-menu__toggle svg { color:#fff; fill:#fff; }
.elementor-widget-copyright .hfe-copyright-wrapper, .elementor-widget-copyright .hfe-copyright-wrapper a, .elementor-widget-copyright .hfe-copyright-wrapper span { color:#8E9AA9 !important; font-size:13px; }
.elementor-widget-navigation-menu { flex-grow:1; }
/* news grid (Essential Addons Lite post grid): uniform image ratio, card look from the design */
.eael-post-grid .eael-entry-media img, .eael-post-grid .eael-entry-thumbnail img { width:100%; aspect-ratio:16/10; object-fit:cover; display:block; }
.eael-post-grid .eael-grid-post-holder { border:1px solid #D9DFE7; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(10,30,60,.08); }
.eael-post-grid .eael-entry-title, .eael-post-grid .eael-entry-title a { font-family:'Noto Serif Thai',serif; font-size:18px; color:#0A1E3C; }
.eael-post-grid .eael-post-elements-readmore-btn { color:#B8934A; font-weight:600; }
CSS;
wp_update_custom_css_post($css, ['stylesheet' => get_stylesheet()]);

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
echo "built: home page id=$homeId | header id=$hdrId | footer id=$ftrId | kit id=$kitId\n";
echo 'elements: home=' . count($home) . ' sections, header=' . count($header) . ', footer=' . count($footer) . "\n";
echo 'edit in Elementor: ' . admin_url("post.php?post=$homeId&action=elementor") . "\n";
