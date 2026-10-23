<?php
/**
 * SITE TWO (division2.immigration.go.th, design bk-tm2): the SECTION PLAN of the home page, with NO content elements yet.
 *
 * One page, /site-two-sections/ (Elementor Canvas), made only of the layout skeleton of the bk-tm2 wireframe:
 *   every band of the wireframe = ONE outer container (full width: the band's background colour + vertical padding)
 *                               + ONE inner container (Content Width "boxed" = 1280 px: where the content and its grids will go).
 * Backgrounds are Elementor Global Colors (set by foundry/build-foundry.php). Every layer is named "Section > outer (full width)" / "Section > inner (max 1280)".
 * The inner containers carry a temporary minimum height (desktop / tablet / mobile) that mirrors the height of that band in the wireframe,
 * so the page reads like the design. REMOVE the minimum height of an inner container when you put its real content in.
 *
 * Bands, in the order of bk-tm2.html (the "hero" of the wireframe is three bands: banner slot, caption, status strip):
 *   Utility bar | Masthead | Hero banner | Banner caption | Status band | Online services | Commanders | Gift-refusal policy | News | Videos | Footer
 *
 * Run foundry/build-foundry.php first (Elementor Global Colors / Fonts).
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/two/sections.php --path=/var/www/html
 */
$libDir = getenv('FOUNDRY_DIR') ?: '/foundry';
require $libDir . '/lib.php';

/* name, background (Global Color id), anchor id, outer vertical padding [desktop, tablet, mobile], inner minimum height [desktop, tablet, mobile], note */
$plan = [
    ['Utility bar',          'navy900',    '',         [7, 7, 7],       [27, 27, 27],     'phone + email, language, theme button'],
    ['Masthead',             'primary',    '',         [12, 12, 12],    [47, 47, 47],     'logo + name, main menu, buttons (gold line under it)'],
    ['Hero banner',          'bannerdark', '',         [0, 0, 0],       [409, 320, 220],  'official banner slot 1440 x 460'],
    ['Banner caption',       'paper',      '',         [14, 12, 12],    [23, 23, 40],     'one line of text under the banner'],
    ['Status band',          'navy700',    'status',   [22, 20, 18],    [40, 40, 120],    '3 cells: office hours, hotline, TDAC'],
    ['Online services',      'primary',    'eservice', [72, 52, 40],    [732, 640, 540],  'heading, intro, 6 shortcuts, 6 service cards'],
    ['Commanders',           'white',      'about',    [64, 48, 36],    [405, 360, 300],  'heading + 3 leader cards'],
    ['Gift-refusal policy',  'paper',      'ita',      [64, 48, 36],    [332, 300, 260],  'ITA teaser + 2 documents'],
    ['News',                 'white',      'news',     [64, 48, 36],    [517, 460, 380],  'heading, filter chips, 1 large + small news cards'],
    ['Videos',               'paper',      'videos',   [64, 48, 36],    [471, 400, 340],  'heading + 4 video cards'],
    ['Footer',               'navy900',    'contact',  [56, 48, 40],    [205, 260, 300],  'brand, 3 link columns, bottom bar'],
];

$sections = [];
foreach ($plan as [$name, $bg, $anchor, $pad, $min, $note]) {
    $s = $section($name, $bg, [], $anchor, [0, 0, 0], $pad);                                           // outer (full width) + inner (boxed 1280), no content
    $s['elements'][0]['settings'] = $M($s['elements'][0]['settings'], $rp('min_height', $px($min[0]), $px($min[1]), $px($min[2])));   // inner: temporary height of the wireframe band
    if ($name === 'Masthead') $s['settings'] = $M($s['settings'], ['border_border' => 'solid', 'border_width' => $dim(0, 0, 3, 0)], $C('border_color', 'secondary'));   // gold line under the masthead
    $sections[] = $s;
}

$existing = get_posts(['post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'two-page-sections', 'fields' => 'ids', 'numberposts' => 1]);
$slug = getenv('SECTIONS_SLUG') ?: ($existing ? get_post_field('post_name', $existing[0]) : 'site-two-sections');
$pageId = $save('page', 'two-page-sections', 'Site two: section plan', $sections, ['_wp_page_template' => 'elementor_canvas', '_elementor_template_type' => 'wp-page'], ['post_name' => $slug]);

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
if ($skipped) echo "KEPT YOUR EDITS, not overwritten: " . implode('; ', $skipped) . " (set BK_FORCE=1 to rebuild from scratch)\n";
echo 'built site TWO section plan: page id=' . $pageId . ' | ' . get_permalink($pageId) . "\n";
echo 'sections: ' . count($sections) . ' (' . implode(' | ', array_column($plan, 0)) . ")\n";
echo 'edit in Elementor: ' . admin_url("post.php?post=$pageId&action=elementor") . "\n";
