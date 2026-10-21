<?php
/**
 * FOUNDRY LIBRARY - shared by every site build. Include it with:   require '/foundry/lib.php';
 * It defines (in the including scope) design tokens, small builders, and the SHARED COMPONENTS. It does not write anything to the database
 * except the placeholder portrait image it may create once.
 *
 * Rules every component follows
 *   - Each section = OUTER full-width container (background + vertical padding) + INNER container (content width "boxed", 1280 px).
 *   - Colours and type sizes reference Elementor Global Colors / Global Fonts (set by foundry/build-foundry.php).
 *   - Card rows are Grid containers with their own tablet / mobile columns.
 *   - Every layer has a readable name ("Section > part"). No custom CSS, no HTML widgets.
 *
 * SHARED COMPONENTS (the contract between the foundry and the sites; each returns Elementor element arrays):
 *   $cHeader(array $o)                         -> [utility bar, masthead]   (use as the UAE "header" template)
 *   $cFooter(array $o)                         -> [footer]                  (use as the UAE "footer" template)
 *   $cStatusBand(array $items)                 -> section   items: [[name, icon, label, value], ...] (3 recommended)
 *   $cServices($title, $intro, $shortcuts, $passes) -> section  shortcuts: [[en, icon, label]] (may be []), passes: [[en, code, name, cta]]
 *   $cLeadership($title, $linkText, $linkUrl, $roles, $photoUrl, $photoId, $placeholderName) -> section   roles: [[en, label]]
 *   $cVideos($title, $linkText, $count)        -> section
 *   $cShell($name, $bgId)                      -> section   empty section shell (outer + inner 1280 + heading row) to start a new section
 *   $cTodo($name, $note, $bgId)                -> section   clearly-labelled placeholder for a section a site still has to build
 * Building blocks: $section, $secHead, $grid, $stack, $row, $card, $heading, $text, $icon, $button, $linkButton, $divider, $iconList, $image, $wd, $el, $M, $C, $T, $rp, $px, $dim, $gapR, $padR.
 * Persistence helper: $save($post_type, $key, $title, $data, $meta, $extra) with protection for hand edits ($force / $skipped).
 *
 * CHANGING THIS FILE changes every site that pulls it: keep component signatures stable, add options instead of renaming, bump foundry/VERSION.
 */
$SANS = 'Sarabun'; $SERIF = 'Noto Serif Thai';

/* ======================= design tokens ======================= */
$HEX = ['primary' => '#0A1E3C', 'secondary' => '#B8934A', 'text' => '#16202E', 'accent' => '#8C1D2C', 'navy700' => '#12294B', 'navy900' => '#061428',
        'goldbright' => '#D4B26A', 'paper' => '#EFF2F6', 'muted' => '#5E6B7C', 'rule' => '#D9DFE7', 'white' => '#FFFFFF', 'softtext' => '#C7D0DC',
        'faint' => '#8E9AA9', 'bannerdark' => '#22262D'];
$COLOR_NAMES = ['primary' => 'Primary navy', 'secondary' => 'Secondary gold', 'text' => 'Text ink', 'accent' => 'Accent crimson', 'navy700' => 'Navy 700', 'navy900' => 'Navy 900',
        'goldbright' => 'Gold bright', 'paper' => 'Paper', 'muted' => 'Muted text', 'rule' => 'Rule line', 'white' => 'White', 'softtext' => 'Soft text on navy', 'faint' => 'Faint text', 'bannerdark' => 'Banner dark'];
// type presets: id => [title, family, weight, size desktop/tablet/mobile, line-height]
$TYPE = [
    'h2section' => ['Section title', $SERIF, '700', [28, 26, 22], 1.3], 'h3card' => ['Card title', $SERIF, '600', [19, 18, 17], 1.3],
    'h4label' => ['Small heading', $SANS, '700', [15, 15, 14], 1.4], 'brand' => ['Brand name', $SERIF, '700', [17, 17, 15], 1.3],
    'brandsub' => ['Brand sub line', $SANS, '600', [11, 11, 10], 1.3], 'bodylg' => ['Body large', $SANS, '400', [17, 16, 16], 1.6],
    'body' => ['Body', $SANS, '400', [16, 16, 15], 1.6], 'small' => ['Body small', $SANS, '400', [14, 14, 13], 1.6],
    'btn' => ['Button', $SANS, '600', [15, 15, 15], 1.2], 'navitem' => ['Menu item', $SANS, '500', [15, 15, 16], 1.3],
    'statlabel' => ['Status label', $SANS, '500', [13, 13, 12], 1.3], 'statval' => ['Status value', $SANS, '700', [17, 17, 16], 1.3],
    'lanelabel' => ['Shortcut label', $SANS, '600', [14, 14, 13], 1.4],
];

/* ======================= tiny helpers ======================= */
$n = 0;
$uid = function () use (&$n) { return substr(md5('bk-tm2-native-' . (++$n)), 0, 7); };
$px  = fn($v, $u = 'px') => ['unit' => $u, 'size' => $v, 'sizes' => []];
$fr  = fn($v) => ['unit' => 'fr', 'size' => $v, 'sizes' => []];
$dim = fn($t, $r = null, $b = null, $l = null) => ['unit' => 'px', 'top' => (string) $t, 'right' => (string) ($r ?? $t), 'bottom' => (string) ($b ?? $t), 'left' => (string) ($l ?? $r ?? $t), 'isLinked' => false];
$gapv = fn($v) => ['column' => (string) $v, 'row' => (string) $v, 'isLinked' => true, 'unit' => 'px'];
$rp  = fn($key, $d, $t, $m) => [$key => $d, $key . '_tablet' => $t, $key . '_mobile' => $m];           // responsive trio
$col = fn($id) => 'globals/colors?id=' . $id;
$typ = fn($id) => 'globals/typography?id=' . $id;
$M   = function (...$parts) { $o = []; foreach ($parts as $p) foreach ($p as $k => $v) { if ($k === '__globals__') $o['__globals__'] = array_merge($o['__globals__'] ?? [], $v); else $o[$k] = $v; } return $o; };
$C   = fn($key, $id) => [$key => $HEX[$id], '__globals__' => [$key => $col($id)]];                      // a colour bound to a Global Color
$T   = fn($key, $id) => [$key => 'custom', '__globals__' => [$key => $typ($id)]];                      // typography bound to a Global Font
$link = fn($u = '#') => ['url' => $u, 'is_external' => '', 'nofollow' => '', 'custom_attributes' => ''];
$radius = fn($v) => ['unit' => 'px', 'top' => (string) $v, 'right' => (string) $v, 'bottom' => (string) $v, 'left' => (string) $v, 'isLinked' => true];

$el = function (string $name, array $s, array $kids = [], bool $inner = false) use ($uid) { return ['id' => $uid(), 'elType' => 'container', 'settings' => ['_title' => $name] + $s, 'elements' => $kids, 'isInner' => $inner]; };
$wd = function (string $name, string $type, array $s) use ($uid) { return ['id' => $uid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => ['_title' => $name] + $s, 'elements' => []]; };

/* ---- widgets ---- */
$heading = fn($name, $text, $tag, $color, $type, $align = 'left') => $wd($name, 'heading', $M(['title' => $text, 'header_size' => $tag, 'align' => $align], $C('title_color', $color), $T('typography_typography', $type)));
$text    = fn($name, $html, $color, $type, $align = 'left') => $wd($name, 'text-editor', $M(['editor' => $html, 'align' => $align], $C('text_color', $color), $T('typography_typography', $type)));
$icon    = fn($name, $ico, $color, $d, $t, $m, $align = 'left') => $wd($name, 'icon', $M(['selected_icon' => ['value' => $ico, 'library' => 'fa-solid'], 'view' => 'default', 'align' => $align], $rp('size', $px($d), $px($t), $px($m)), $C('primary_color', $color)));
$divider = fn($name, $color) => $wd($name, 'divider', $M(['style' => 'solid', 'weight' => $px(1), 'gap' => $px(0), 'width' => $px(100, '%'), '_flex_size' => 'grow', 'hide_mobile' => 'hidden-mobile'], $C('color', $color)));
$image   = fn($name, $url, $id) => $wd($name, 'image', ['image' => ['url' => $url, 'id' => $id], 'image_size' => 'medium_large', 'align' => 'center', 'width' => $px(100, '%')]);
$button  = function ($name, $label, $url, $fg, $bg, $bd, $hoverFg, $hoverBg, $align = 'left', $pad = null) use ($wd, $M, $C, $T, $link, $dim) {
    $s = ['text' => $label, 'link' => $link($url), 'align' => $align, 'text_padding' => $pad ?? $dim(10, 22), 'border_radius' => $dim(6)] + $M($C('button_text_color', $fg), $C('hover_color', $hoverFg), $T('typography_typography', 'btn'));
    $s = $M($s, $bg ? $C('background_color', $bg) : ['background_color' => 'rgba(0,0,0,0)']);   // an empty background would fall back to the Kit's accent colour
    if ($bd)      $s = $M($s, ['border_border' => 'solid', 'border_width' => $dim(1)], $C('border_color', $bd), $C('button_hover_border_color', $bd));
    if ($hoverBg) $s = $M($s, $C('button_background_hover_color', $hoverBg));
    return $wd($name, 'button', $s);
};
$linkButton = fn($name, $label, $url, $color, $hover) => $wd($name, 'button', $M(['text' => $label, 'link' => $link($url), 'align' => 'left', 'text_padding' => $dim(0), 'border_radius' => $dim(0), 'background_color' => 'rgba(0,0,0,0)', 'button_background_hover_color' => 'rgba(0,0,0,0)'], $C('button_text_color', $color), $C('hover_color', $hover), $T('typography_typography', 'btn')));
$iconList = fn($name, array $items, $textColor, $iconColor, $hoverColor, $type, $view = 'traditional') => $wd($name, 'icon-list', $M(
    ['view' => $view, 'icon_list' => array_map(fn($i, $k) => ['_id' => substr(md5($name . $k), 0, 7), 'text' => $i[0], 'selected_icon' => ['value' => $i[1], 'library' => 'fa-solid'], 'link' => $link($i[2] ?? '#')], $items, array_keys($items)),
     'space_between' => $px(8), 'icon_size' => $px(14)],
    $C('text_color', $textColor), $C('icon_color', $iconColor), $C('text_color_hover', $hoverColor), $T('icon_typography_typography', $type)));

/* ---- containers ---- */
// vertical rhythm / padding / gaps that change per device
$padR  = fn($d, $t, $m) => $rp('padding', $d, $t, $m);
$gapR  = fn($d, $t, $m) => $rp('flex_gap', $gapv($d), $gapv($t), $gapv($m));
// Layer 1 (outer): full width, background colour, vertical + side padding.   Layer 2 (inner): boxed 1280, content.
$section = function ($name, $bg, array $kids, $anchor = '', $gap = [28, 24, 20], $pad = [64, 48, 36]) use ($el, $M, $C, $dim, $px, $gapR, $padR) {
    $inner = $el("$name > inner (max 1280)", $M(['content_width' => 'boxed', 'boxed_width' => $px(1280), 'flex_direction' => 'column'], $gapR(...$gap)), $kids, true);
    $s = ['content_width' => 'full', 'flex_direction' => 'column', 'background_background' => 'classic', '_element_id' => $anchor];
    return $el("$name > outer (full width)", $M($s, $bg ? $C('background_color', $bg) : [], $padR($dim($pad[0], 24), $dim($pad[1], 20), $dim($pad[2], 16))), [$inner]);
};
$grid = fn($name, $cols, $rows, $gap, array $kids) => $el($name, $M(['container_type' => 'grid', 'content_width' => 'full'],
    $rp('grid_columns_grid', $fr($cols[0]), $fr($cols[1]), $fr($cols[2])), $rp('grid_rows_grid', $fr($rows[0]), $fr($rows[1]), $fr($rows[2])),
    $rp('grid_gaps', $gapv($gap[0]), $gapv($gap[1]), $gapv($gap[2]))), $kids, true);
$stack = fn($name, $gap, array $kids, array $extra = []) => $el($name, $M(['content_width' => 'full', 'flex_direction' => 'column'], $gapR($gap, $gap, $gap), $extra), $kids, true);
$row   = fn($name, $gap, array $kids, array $extra = []) => $el($name, $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center'], $gapR($gap, $gap, $gap), $extra), $kids, true);
$card  = fn($bg, $border) => $M(['background_background' => 'classic', 'border_border' => 'solid', 'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true], 'border_radius' => $radius(10), 'overflow' => 'hidden',
    'box_shadow_box_shadow_type' => 'yes', 'box_shadow_box_shadow' => ['horizontal' => 0, 'vertical' => 4, 'blur' => 18, 'spread' => 0, 'color' => 'rgba(10,30,60,.10)']], $C('background_color', $bg), $C('border_color', $border));

// section heading row: gold accent bar + title + rule + "view all" link
$secHead = function ($name, $title, $dark, $linkText = '', $linkUrl = '#') use ($el, $M, $C, $dim, $heading, $divider, $linkButton, $gapR, $rp) {
    $kids = [
        $el("$name > title with gold bar", $M(['content_width' => 'full', 'flex_direction' => 'column', 'border_border' => 'solid', 'border_width' => $dim(0, 0, 0, 5), 'padding' => $dim(0, 0, 0, 14)], $C('border_color', 'secondary')),
            [$heading("$name > title", $title, 'h2', $dark ? 'white' : 'primary', 'h2section')], true),
        $divider("$name > rule", $dark ? 'secondary' : 'rule'),
    ];
    if ($linkText) $kids[] = $linkButton("$name > link: $linkText", $linkText . ' ›', $linkUrl, $dark ? 'goldbright' : 'secondary', $dark ? 'white' : 'primary');
    return $el("$name > heading row", $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center'], $gapR(14, 12, 10)), $kids, true);
};

/* ======================= media + links used by the layouts ======================= */
$portrait = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'img-portrait', 'fields' => 'ids', 'numberposts' => 1]);
$portraitId = $portrait ? (int) $portrait[0] : 0; $portraitUrl = $portraitId ? wp_get_attachment_url($portraitId) : '';
if (!$portraitId && function_exists('imagecreatetruecolor')) {
    require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
    $im = imagecreatetruecolor(800, 600); imagefilledrectangle($im, 0, 0, 800, 600, imagecolorallocate($im, 120, 134, 150)); $fg = imagecolorallocate($im, 60, 74, 90);
    imageline($im, 0, 0, 800, 600, $fg); imageline($im, 800, 0, 0, 600, $fg); imagestring($im, 5, 330, 292, 'PORTRAIT 800x600', imagecolorallocate($im, 255, 255, 255));
    ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im);
    $up = wp_upload_bits('mock-portrait.png', null, $png);
    if (empty($up['error'])) { $portraitId = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Mock portrait', 'post_status' => 'inherit'], $up['file']); wp_update_attachment_metadata($portraitId, wp_generate_attachment_metadata($portraitId, $up['file'])); update_post_meta($portraitId, '_mock_key', 'img-portrait'); update_post_meta($portraitId, '_mock_data', 1); $portraitUrl = $up['url']; }
}
$logoId = (int) get_theme_mod('custom_logo'); $logoUrl = $logoId ? wp_get_attachment_url($logoId) : '';
$pageUrl = fn($slug) => ($p = get_page_by_path($slug)) ? get_permalink($p) : '#';

/* ======================= persistence helper (protects hand edits) ======================= */
// After each write we remember a hash of what we wrote (_bk_build_hash). If the stored data no longer matches it, someone edited the item in
// Elementor, so it is NOT overwritten. Set the environment variable BK_FORCE=1 to rebuild from scratch.
$force = (bool) getenv('BK_FORCE'); $skipped = [];
$save = function ($post_type, $key, $title, $data, $meta = [], $extra = []) use ($force, &$skipped) {
    $q = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1]);
    $args = array_merge(['post_type' => $post_type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => ''], $extra);
    if ($q) {
        $id = (int) $q[0]; $stored = get_post_meta($id, '_bk_build_hash', true);
        if (!$force && $stored && md5((string) get_post_meta($id, '_elementor_data', true)) !== $stored) { $skipped[] = $title; return $id; }
        wp_update_post(['ID' => $id] + $args);
    } else { $id = wp_insert_post($args); }
    update_post_meta($id, '_mock_key', $key); update_post_meta($id, '_mock_data', 1);
    update_post_meta($id, '_elementor_edit_mode', 'builder'); update_post_meta($id, '_elementor_template_type', 'wp-post');
    update_post_meta($id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
    update_post_meta($id, '_elementor_data', wp_slash(wp_json_encode($data)));
    update_post_meta($id, '_bk_build_hash', md5((string) get_post_meta($id, '_elementor_data', true)));
    foreach ($meta as $k => $v) update_post_meta($id, $k, $v);
    return $id;
};

/* ======================= SHARED COMPONENTS ======================= */
$rowsFor = fn($n, $cols) => array_map(fn($c) => (int) ceil($n / $c), $cols);

// Status band: a row of icon + label + value cells.
$cStatusBand = function (array $items) use ($row, $icon, $stack, $text, $section, $grid) {
    $stat = fn($n, $ico, $label, $value) => $row("Status > item: $n", 14, [
        $icon("Status > $n > icon", $ico, 'goldbright', 28, 26, 24),
        $stack("Status > $n > text", 2, [$text("Status > $n > label", "<p>$label</p>", 'goldbright', 'statlabel'), $text("Status > $n > value", "<p>$value</p>", 'white', 'statval')]),
    ]);
    return $section('Status band', 'navy700', [
        $grid('Status band > items grid', [3, 3, 1], [1, 1, 3], [18, 16, 14], array_map(fn($i) => $stat(...$i), $items)),
    ], 'status', [12, 12, 12], [22, 20, 18]);
};

// Online services band: optional shortcut tiles + "pass" cards.
$cServices = function ($title, $intro, array $shortcuts, array $passes) use ($el, $M, $C, $radius, $gapR, $padR, $dim, $icon, $text, $card, $stack, $heading, $button, $secHead, $section, $grid, $rowsFor) {
    $laneEls = [];
    foreach ($shortcuts as [$en, $ico, $th]) $laneEls[] = $el("Shortcut: $en", $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center', 'border_border' => 'solid',
        'border_width' => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true], 'border_color' => 'rgba(212,178,106,.35)', 'border_radius' => $radius(10), 'background_background' => 'classic'],
        $C('background_color', 'navy700'), $gapR(10, 10, 8), $padR($dim(18, 10), $dim(16, 10), $dim(14, 8))), [
        $icon("Shortcut: $en > icon", $ico, 'goldbright', 30, 28, 26, 'center'), $text("Shortcut: $en > label", "<p>$th</p>", 'white', 'lanelabel', 'center')], true);
    $passEls = [];
    foreach ($passes as [$en, $code, $name, $cta]) $passEls[] = $el("Service card: $en", $M(['content_width' => 'full', 'flex_direction' => 'column'], $card('white', 'white'), $gapR(0, 0, 0)), [
        $stack("Service card: $en > header", 4, [$heading("Service card: $en > code", $code, 'h4', 'goldbright', 'h4label'), $heading("Service card: $en > name", $name, 'h3', 'white', 'h3card')],
            $M($C('background_color', 'navy900'), ['background_background' => 'classic', 'padding' => $dim(16, 20)])),
        $stack("Service card: $en > body", 12, [$text("Service card: $en > description", '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit sed do eiusmod tempor.</p>', 'muted', 'small'),
            $button("Service card: $en > button", $cta, '#', 'white', 'secondary', 'secondary', 'white', 'primary')], ['padding' => $dim(18, 20)]),
    ], true);
    $kids = [$secHead('Online services', $title, true), $text('Online services > intro', "<p>$intro</p>", 'softtext', 'bodylg')];
    if ($shortcuts) $kids[] = $grid('Online services > shortcuts grid', [6, 3, 2], $rowsFor(count($shortcuts), [6, 3, 2]), [16, 14, 12], $laneEls);
    $kids[] = $grid('Online services > service cards grid', [3, 2, 1], $rowsFor(count($passes), [3, 2, 1]), [20, 18, 16], $passEls);
    return $section('Online services', 'primary', $kids, 'eservice', [26, 24, 20], [72, 52, 40]);
};

// Leadership: photo cards with a name and a position.
$cLeadership = function ($title, $linkText, $linkUrl, array $roles, $photoUrl, $photoId, $placeholderName = 'ยศ ชื่อ นามสกุล (placeholder)') use ($el, $M, $card, $gapR, $image, $stack, $heading, $text, $dim, $secHead, $section, $grid, $rowsFor) {
    $execEls = [];
    foreach ($roles as [$en, $th]) $execEls[] = $el("Commander: $en", $M(['content_width' => 'full', 'flex_direction' => 'column'], $card('white', 'rule'), $gapR(0, 0, 0)), [
        $image("Commander: $en > photo", $photoUrl, $photoId),
        $stack("Commander: $en > info", 4, [$heading("Commander: $en > name", $placeholderName, 'h4', 'primary', 'h3card'), $text("Commander: $en > position", "<p>$th</p>", 'muted', 'body')], ['padding' => $dim(16, 20)]),
    ], true);
    return $section('Commanders', 'white', [
        $secHead('Commanders', $title, false, $linkText, $linkUrl),
        $grid('Commanders > cards grid', [3, 3, 1], $rowsFor(count($roles), [3, 3, 1]), [22, 18, 16], $execEls),
    ], 'about');
};

// Videos: a row of play-icon cards.
$cVideos = function ($title, $linkText, $count = 4) use ($el, $M, $C, $radius, $gapR, $padR, $dim, $rp, $px, $icon, $text, $secHead, $section, $grid, $rowsFor) {
    $vidEls = [];
    for ($i = 1; $i <= $count; $i++) $vidEls[] = $el("Video card $i", $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center', 'border_radius' => $radius(10), 'background_background' => 'classic'],
        $C('background_color', 'primary'), $gapR(12, 12, 10), $padR($dim(20), $dim(18), $dim(16)), $rp('min_height', $px(190), $px(170), $px(150))), [
        $icon("Video card $i > play icon", 'far fa-play-circle', 'goldbright', 46, 42, 38, 'center'), $text("Video card $i > title", "<p>Video title placeholder $i</p>", 'white', 'lanelabel', 'center')], true);
    return $section('Videos', 'paper', [$secHead('Videos', $title, false, $linkText), $grid('Videos > cards grid', [4, 2, 1], $rowsFor($count, [4, 2, 1]), [18, 16, 14], $vidEls)], 'videos');
};

// Header: utility bar + masthead (logo, brand name, Essential Addons Simple Menu).
$cHeader = function (array $o = []) use ($section, $el, $M, $C, $T, $dim, $px, $rp, $gapR, $iconList, $text, $row, $stack, $heading, $wd, $logoUrl, $logoId) {
    $o += ['phone' => '02-000-0000', 'tel' => 'tel:020000000', 'email' => 'info@example.invalid', 'nameTh' => 'กองบังคับการตรวจคนเข้าเมือง 2', 'nameEn' => 'IMMIGRATION DIVISION 2', 'menu' => 'main-menu', 'logoUrl' => $logoUrl, 'logoId' => $logoId];
    $header = [
        $section('Utility bar', 'navy900', [
            $el('Utility bar > row', $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between'], $gapR(12, 10, 6), ['flex_justify_content_mobile' => 'center']), [
                $iconList('Utility bar > contact', [[$o['phone'], 'fas fa-phone-alt', $o['tel']], [$o['email'], 'fas fa-envelope', 'mailto:' . $o['email']]], 'softtext', 'goldbright', 'goldbright', 'small', 'inline'),
                $text('Utility bar > language', '<p>ไทย &nbsp;|&nbsp; EN</p>', 'softtext', 'small', 'right'),
            ], true),
        ], '', [0, 0, 0], [7, 7, 7]),
        $section('Masthead', 'primary', [
            $el('Masthead > row', $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'nowrap', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between'], $gapR(24, 16, 12)), [
                $row('Masthead > brand', 14, [
                    $wd('Masthead > logo', 'site-logo', $M(['custom_image' => ['url' => $o['logoUrl'], 'id' => $o['logoId']], 'site_logo_size_size' => 'medium', 'align' => 'left', 'link_to' => 'default'], $rp('width', $px(54), $px(48), $px(40)))),
                    $stack('Masthead > brand text', 0, [$heading('Masthead > name (Thai)', $o['nameTh'], 'div', 'white', 'brand'), $heading('Masthead > name (English)', $o['nameEn'], 'div', 'goldbright', 'brandsub')]),
                ], ['flex_wrap' => 'nowrap']),
                $wd('Masthead > main menu (Essential Addons Simple Menu)', 'eael-simple-menu', $M(
                    ['eael_simple_menu_menu' => $o['menu'], 'eael_simple_menu_preset' => 'preset-1', 'eael_simple_menu_layout' => 'horizontal', 'eael_simple_menu_dropdown' => 'tablet', 'eael_simple_menu_item_alignment' => 'eael-simple-menu-align-right',
                     'eael_simple_menu_item_padding' => $dim(10, 12), '_flex_size' => 'grow', 'eael_simple_menu_hamburger_disable_selected_menu' => 'hide',
                     'eael_simple_menu_hamburger_alignment' => 'right', 'eael_simple_menu_hamburger_size' => $px(26)],
                    $C('eael_simple_menu_hamburger_bg', 'navy700'),
                    $C('eael_simple_menu_item_color', 'white'), $C('eael_simple_menu_item_color_hover', 'goldbright'), $C('eael_simple_menu_item_color_active', 'goldbright'), $T('eael_simple_menu_item_typography_typography', 'navitem'),
                    $C('eael_simple_menu_background', 'primary'), $C('eael_simple_menu_item_background', 'primary'), $C('eael_simple_menu_item_background_hover', 'navy700'), $C('eael_simple_menu_item_background_active', 'primary'),
                    $C('eael_simple_menu_item_indicator_color', 'goldbright'), $C('eael_simple_menu_dropdown_item_background', 'navy700'),
                    $C('eael_simple_menu_dropdown_background', 'navy700'), $C('eael_simple_menu_dropdown_item_color', 'white'), $C('eael_simple_menu_dropdown_item_color_hover', 'goldbright'), $C('eael_simple_menu_dropdown_item_background_hover', 'primary'),
                    $C('eael_simple_menu_hamburger_icon_color', 'white'), $C('eael_simple_menu_hamburger_top_level_item_background', 'navy700'), $C('eael_simple_menu_hamburger_top_level_item_color', 'white'),
                    $C('eael_simple_menu_hamburger_top_level_item_color_hover', 'goldbright'), $C('eael_simple_menu_hamburger_dropdown_item_background', 'navy900'), $C('eael_simple_menu_hamburger_dropdown_item_color', 'white'),
                    $C('eael_simple_menu_hamburger_dropdown_item_color_hover', 'goldbright'))),
            ], true),
        ], '', [0, 0, 0], [12, 12, 12]),
    ];
    // gold line under the masthead = native border on the masthead outer container
    $header[1]['settings'] = $M($header[1]['settings'], ['border_border' => 'solid', 'border_width' => $dim(0, 0, 3, 0)], $C('border_color', 'secondary'));
    return $header;
};

// Footer: brand column, two link columns, contact column with social icons, bottom bar.
$cFooter = function (array $o = []) use ($section, $el, $M, $C, $dim, $px, $rp, $gapR, $iconList, $text, $stack, $heading, $wd, $grid, $link, $logoUrl, $logoId) {
    $o += ['nameTh' => 'กองบังคับการตรวจคนเข้าเมือง 2', 'nameEn' => 'IMMIGRATION DIVISION 2', 'address' => '123 Example Road, Sample District<br>Sample Province 10000',
           'col1Title' => 'บริการผู้เดินทาง', 'col1' => ['บัตรขาเข้าดิจิทัล TDAC', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งอยู่เกิน 90 วัน', 'ขออยู่ต่อในราชอาณาจักร'],
           'col2Title' => 'เกี่ยวกับหน่วยงาน', 'col2' => ['โครงสร้างและอำนาจหน้าที่', 'ผู้บังคับบัญชา', 'กฎหมายและระเบียบ', 'มาตรฐาน ITA'],
           'contactTitle' => 'ติดต่อและร้องเรียน', 'contact' => 'Tel. 02-000-0000<br>Hotline 0-0000 (24 h)<br>info@example.invalid',
           'copyright' => 'Copyright © [hfe_current_year] Placeholder Division. All rights reserved.', 'legal' => 'Privacy policy · Website policy', 'logoUrl' => $logoUrl, 'logoId' => $logoId];
    $flist = fn($name, $title, array $items) => $stack("Footer > column: $name", 10, [$heading("Footer > $name > heading", $title, 'h4', 'goldbright', 'h4label'), $iconList("Footer > $name > links", array_map(fn($t) => [$t, 'fas fa-angle-right'], $items), 'softtext', 'goldbright', 'white', 'body')]);
    return [
        $section('Footer', 'navy900', [
            $grid('Footer > columns grid', [4, 2, 1], [1, 2, 4], [28, 24, 22], [
                $stack('Footer > column: brand', 8, [
                    $wd('Footer > logo', 'site-logo', $M(['custom_image' => ['url' => $o['logoUrl'], 'id' => $o['logoId']], 'align' => 'left', 'link_to' => 'default'], $rp('width', $px(60), $px(56), $px(52)))),
                    $heading('Footer > name (Thai)', $o['nameTh'], 'h3', 'white', 'h3card'), $heading('Footer > name (English)', $o['nameEn'], 'div', 'goldbright', 'brandsub'),
                    $text('Footer > address', '<p>' . $o['address'] . '</p>', 'softtext', 'body')]),
                $flist('traveller services', $o['col1Title'], $o['col1']),
                $flist('about', $o['col2Title'], $o['col2']),
                $stack('Footer > column: contact', 10, [
                    $heading('Footer > contact > heading', $o['contactTitle'], 'h4', 'goldbright', 'h4label'),
                    $text('Footer > contact > details', '<p>' . $o['contact'] . '</p>', 'softtext', 'body'),
                    $wd('Footer > contact > social icons', 'social-icons', $M(['social_icon_list' => [
                        ['_id' => 'a1b2c3d', 'social_icon' => ['value' => 'fab fa-facebook-f', 'library' => 'fa-brands'], 'link' => $link('#')],
                        ['_id' => 'b2c3d4e', 'social_icon' => ['value' => 'fab fa-x-twitter', 'library' => 'fa-brands'], 'link' => $link('#')],
                        ['_id' => 'c3d4e5f', 'social_icon' => ['value' => 'fab fa-youtube', 'library' => 'fa-brands'], 'link' => $link('#')]],
                        'shape' => 'circle', 'align' => 'left', 'icon_color' => 'custom', 'icon_size' => $px(14), 'icon_padding' => $px(0.8, 'em'), 'icon_spacing' => $px(8)],
                        $C('icon_primary_color', 'secondary'), $C('icon_secondary_color', 'navy900')))]),
            ]),
            $el('Footer > bottom bar', $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between', 'border_border' => 'solid', 'border_width' => $dim(1, 0, 0, 0),
                'padding' => $dim(18, 0, 0, 0)], $C('border_color', 'secondary'), $gapR(12, 12, 8), ['flex_justify_content_mobile' => 'center']), [
                $wd('Footer > copyright', 'copyright', ['shortcode' => $o['copyright'], 'align' => 'left']),
                $text('Footer > legal links', '<p>' . $o['legal'] . '</p>', 'faint', 'small', 'right'),
            ], true),
        ], '', [34, 30, 26], [56, 48, 40]),
    ];
};

// Section shell: the empty skeleton every new section starts from (outer full width + inner 1280 + heading row + a content slot).
$cShell = function ($name = 'New section', $bg = 'white') use ($section, $secHead, $text, $stack) {
    return $section($name, $bg, [
        $secHead($name, 'Section title', $bg === 'primary' || $bg === 'navy700' || $bg === 'navy900', 'View all', '#'),
        $stack("$name > content", 16, [$text("$name > placeholder text", '<p>Put the section content here: a Grid container for cards, a text block, a form...</p>', $bg === 'primary' || $bg === 'navy700' || $bg === 'navy900' ? 'softtext' : 'muted', 'body')]),
    ]);
};

// TODO placeholder: a visibly unfinished section that tells the next dev what to build.
$cTodo = function ($name, $note, $bg = 'paper') use ($el, $M, $C, $dim, $gapR, $padR, $radius, $section, $heading, $text, $stack) {
    return $section("TODO: $name", $bg, [
        $el("TODO: $name > box", $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'border_border' => 'dashed', 'border_width' => $dim(2), 'border_radius' => $radius(10)], $C('border_color', 'secondary'), $gapR(8, 8, 8), $padR($dim(32, 20), $dim(28, 18), $dim(24, 16))), [
            $heading("TODO: $name > title", "TODO: $name", 'h3', 'primary', 'h3card', 'center'),
            $text("TODO: $name > note", "<p>$note</p>", 'muted', 'body', 'center'),
        ], true),
    ], '', [12, 12, 12], [40, 36, 30]);
};
