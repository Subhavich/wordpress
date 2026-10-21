<?php
/**
 * FOUNDRY BUILD - the shared base for every site. Run it before a site's own build.
 *
 *  1. Elementor Kit: Global Colors, Global Fonts (with tablet / mobile sizes) and the 1280 content width.
 *  2. The shared COMPONENT LIBRARY, built twice from the same data:
 *       - live "Custom Blocks"  (Ultimate Addons for Elementor Lite, type Custom Block): embed with [hfe_template id="..."]; edit once, every page updates
 *       - Saved Templates       (Elementor, container type, category "Shared components"): insert as an independent copy
 *  3. A private "Component guide" page: visual page map of both sites, component matrix, live specimens, how-to, rules.
 *
 * Idempotent. Hand edits are protected (see $save in lib.php; BK_FORCE=1 rebuilds from scratch).
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" --entrypoint wp wpcli eval-file /foundry/build-foundry.php --path=/var/www/html
 */
$libDir = getenv('FOUNDRY_DIR') ?: '/foundry';
require $libDir . '/lib.php';
$version = trim((string) @file_get_contents($libDir . '/VERSION')) ?: 'dev';

/* ======================= 1. Elementor Kit (globals) ======================= */
$kitId = (int) get_option('elementor_active_kit');
if ($kitId) {
    $s = get_post_meta($kitId, '_elementor_page_settings', true); if (!is_array($s)) $s = [];
    $managed = array_flip(['system_colors', 'custom_colors', 'system_typography', 'custom_typography', 'container_width']);
    $before = array_intersect_key($s, $managed);
    $sys = ['primary', 'secondary', 'text', 'accent'];
    $s['system_colors'] = array_map(fn($id) => ['_id' => $id, 'title' => $COLOR_NAMES[$id], 'color' => $HEX[$id]], $sys);
    $s['custom_colors'] = array_values(array_map(fn($id) => ['_id' => $id, 'title' => $COLOR_NAMES[$id], 'color' => $HEX[$id]], array_diff(array_keys($HEX), $sys)));
    $s['system_typography'] = [['_id' => 'primary', 'title' => 'Primary', 'typography_typography' => 'custom', 'typography_font_family' => $SERIF, 'typography_font_weight' => '700'],
        ['_id' => 'secondary', 'title' => 'Secondary', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '600'],
        ['_id' => 'text', 'title' => 'Text', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '400'],
        ['_id' => 'accent', 'title' => 'Accent', 'typography_typography' => 'custom', 'typography_font_family' => $SANS, 'typography_font_weight' => '500']];
    $s['custom_typography'] = [];
    foreach ($TYPE as $id => [$title, $fam, $w, $sz, $lh]) $s['custom_typography'][] = ['_id' => $id, 'title' => $title, 'typography_typography' => 'custom', 'typography_font_family' => $fam, 'typography_font_weight' => $w,
        'typography_font_size' => $px($sz[0]), 'typography_font_size_tablet' => $px($sz[1]), 'typography_font_size_mobile' => $px($sz[2]), 'typography_line_height' => $px($lh, 'em')];
    $s['body_typography_typography'] = 'custom'; $s['body_typography_font_family'] = $SANS; $s['body_typography_font_size'] = $px(16);
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h) { $s[$h . '_typography_typography'] = 'custom'; $s[$h . '_typography_font_family'] = $SERIF; $s[$h . '_typography_font_weight'] = '700'; }
    $s['container_width'] = $px(1280);
    $storedKit = get_post_meta($kitId, '_bk_kit_hash', true);
    if (!$force && $storedKit && md5(serialize($before)) !== $storedKit) { $skipped[] = 'Elementor global colours / fonts (Site Settings)'; }
    else {
        update_post_meta($kitId, '_elementor_page_settings', $s);
        update_post_meta($kitId, '_bk_kit_hash', md5(serialize(array_intersect_key((array) get_post_meta($kitId, '_elementor_page_settings', true), $managed))));
    }
}
// no custom CSS: only clear CSS that earlier versions of the scripts wrote (your own CSS is left alone)
$css = (string) wp_get_custom_css();
if ($css !== '' && (strpos($css, 'bk-tm2') !== false || strpos($css, 'Essential Addons Lite post grid') !== false)) wp_update_custom_css_post('', ['stylesheet' => get_stylesheet()]);

/* ======================= 2. component library ======================= */
$lanes = [['Emergency report', 'fas fa-shield-alt', 'แจ้งเหตุด่วน'], ['Queue booking', 'far fa-calendar-alt', 'จองคิวล่วงหน้า'], ['National Police Office', 'fas fa-star-of-life', 'สำนักงานตำรวจแห่งชาติ'],
          ['TDAC arrival card', 'far fa-file-alt', 'บัตรขาเข้าดิจิทัล TDAC'], ['Immigration app', 'fas fa-mobile-alt', 'แอปพลิเคชันตรวจคนเข้าเมือง'], ['Application status', 'fas fa-search', 'ตรวจสอบสถานะคำร้อง']];
$passesTwo = [['TDAC', 'TDAC', 'บัตรขาเข้าดิจิทัล', 'ลงทะเบียน'], ['Vehicle report (TM.2)', 'ตม. 2', 'รายการเกี่ยวกับยานพาหนะ', 'แจ้งรายการ'], ['Lodger notice (TM.30)', 'ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งเข้าพัก'],
              ['Online appointment', 'ONLINE APPOINTMENT', 'จองคิวออนไลน์', 'จองคิว'], ['90-day report (TM.47)', 'ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['E-extension', 'E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง']];
$passesOne = [['E-extension', 'E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง'], ['90-day report (TM.47)', 'ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['Lodger notice (TM.30)', 'ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งที่พักอาศัย']];
$intro = 'Placeholder intro. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.';
$roles = [['National Police Chief', 'ผู้บัญชาการตำรวจแห่งชาติ'], ['Immigration Bureau Commander', 'ผู้บัญชาการสำนักงานตรวจคนเข้าเมือง'], ['Division Commander', 'ผู้บังคับการตรวจคนเข้าเมือง']];

$components = [   // key => [title, description, data (array of top-level containers)]
    'shell'    => ['Section shell', 'The empty skeleton for any new section: outer full-width container, inner 1280 container, heading row and a content slot.', [$cShell('New section', 'white')]],
    'status'   => ['Status band', 'Three icon + label + value cells (office hours, hotline, TDAC). Grid 3 / 3 / 1 columns.', [$cStatusBand([['Office hours', 'far fa-clock', 'เวลาราชการ', 'Mon-Fri 08:30-16:30'], ['Hotline', 'fas fa-phone-alt', 'สายด่วนตรวจคนเข้าเมือง', 'Hotline 0-0000 (24 h)'], ['TDAC', 'far fa-id-card', 'บัตรขาเข้าดิจิทัล TDAC', 'Register before travel']])]],
    'services' => ['Online services band (with shortcuts)', 'Navy band with 6 shortcut tiles and the service cards. Used by site two. Shortcuts 6 / 3 / 2, cards 3 / 2 / 1.', [$cServices('บริการออนไลน์', $intro, $lanes, $passesTwo)]],
    'cards'    => ['Online services band (cards only)', 'The same band without the shortcut tiles. The variant site one needs.', [$cServices('บริการออนไลน์', $intro, [], $passesOne)]],
    'leaders'  => ['Leadership', 'Photo cards with a name and a position. Grid 3 / 3 / 1.', [$cLeadership('ผู้บังคับบัญชา', 'โครงสร้างหน่วยงาน', '#', $roles, $portraitUrl, $portraitId)]],
    'videos'   => ['Videos', 'Play-icon cards under a section heading. Grid 4 / 2 / 1.', [$cVideos('วีดิทัศน์แนะนำ', 'ช่อง Immigration Bureau', 4)]],
    'todo'     => ['TODO placeholder', 'A dashed, clearly-labelled box for a section that a site still has to build. Replace it with the real section.', [$cTodo('Section name', 'Describe what this section should contain, then build it here.', 'paper')]],
];
$blkId = []; $tplId = [];
$catTerm = term_exists('Shared components', 'elementor_library_category') ?: wp_insert_term('Shared components', 'elementor_library_category');
foreach ($components as $key => [$title, $desc, $data]) {
    $blkId[$key] = $save('elementor-hf', "fdy-blk-$key", "[Shared] $title", $data, ['ehf_template_type' => 'custom']);                               // live block
    $tplId[$key] = $save('elementor_library', "fdy-tpl-$key", "[Shared] $title", $data, ['_elementor_template_type' => 'container']);              // Saved Template (copy)
    wp_set_object_terms($tplId[$key], 'container', 'elementor_library_type');
    if (!is_wp_error($catTerm)) wp_set_object_terms($tplId[$key], (int) (is_array($catTerm) ? $catTerm['term_id'] : $catTerm), 'elementor_library_category');
}

/* ======================= 3. the Component guide page ======================= */
$edit = fn($id) => admin_url("post.php?post=$id&action=elementor");
$tag = fn($name, $label, $bg, $fg) => $el($name, $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_align_items' => 'center', 'border_radius' => $radius(6), 'background_background' => 'classic'], $C('background_color', $bg), $padR($dim(8, 12), $dim(8, 12), $dim(8, 12))),
    [$text("$name > label", "<p>$label</p>", $fg, 'small')], true);
// one block of the page map
$mapBox = function ($label, $kind, $note = '') use ($el, $M, $C, $dim, $radius, $gapR, $padR, $text) {
    [$bg, $fg, $tagText] = ['shared' => ['secondary', 'primary', 'shared'], 'one' => ['navy700', 'white', 'site one'], 'two' => ['primary', 'white', 'site two'], 'todo' => ['paper', 'muted', 'to build']][$kind];
    $s = ['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'flex_justify_content' => 'space-between', 'border_radius' => $radius(8), 'background_background' => 'classic'];
    if ($kind === 'todo') $s += ['border_border' => 'dashed', 'border_width' => $dim(2)];
    return $el("Map block: $label", $M($s, $C('background_color', $bg), $kind === 'todo' ? $C('border_color', 'secondary') : [], $gapR(8, 8, 8), $padR($dim(12, 16), $dim(12, 14), $dim(10, 12))), [
        $text("Map block: $label > name", "<p><strong>$label</strong></p>", $fg, 'small'), $text("Map block: $label > tag", '<p>' . ($note ?: $tagText) . '</p>', $fg, 'statlabel', 'right')], true);
};
$mapTwo = [['Header: utility bar + masthead', 'shared', 'shared · global header'], ['Hero banner', 'two'], ['Banner caption', 'two'], ['Status band', 'shared'], ['Online services + shortcuts', 'shared'], ['Leadership', 'shared'],
           ['Gift-refusal policy', 'two'], ['News grid + filter chips', 'two'], ['Videos', 'shared'], ['Footer', 'shared', 'shared · global footer']];
$mapOne = [['Header: utility bar + masthead', 'shared', 'shared · global header'], ['Text hero with action buttons', 'todo'], ['Status band', 'shared'], ['Online services (cards only)', 'shared'], ['Leadership', 'shared'],
           ['Press releases with empty state', 'todo'], ['Arrest results with empty state', 'todo'], ['Videos', 'shared'], ['Downloads (two columns)', 'todo'], ['Contact card + contact-back form', 'todo'], ['Units list (11 units)', 'todo'], ['Footer', 'shared', 'shared · global footer']];
$mapCol = fn($name, $title, array $items) => $stack($name, 10, array_merge([$heading("$name > title", $title, 'h3', 'primary', 'h3card')], array_map(fn($i) => $mapBox($i[0], $i[1], $i[2] ?? ''), $items)));

$matrix = [ // component, site one, site two, where it lives
    ['Header: utility bar + masthead', 'yes', 'yes', 'UAE header template, built by $cHeader (global on the site)'],
    ['Footer', 'yes', 'yes', 'UAE footer template, built by $cFooter (global on the site)'],
    ['Status band', 'yes', 'yes', '[Shared] Status band'],
    ['Online services band', 'cards only', 'with shortcuts', '[Shared] Online services (two variants)'],
    ['Leadership', 'yes', 'yes', '[Shared] Leadership'],
    ['Videos', 'yes', 'yes', '[Shared] Videos'],
    ['Section heading row', 'yes', 'yes', 'inside every section ($secHead)'],
    ['New section skeleton', 'yes', 'yes', '[Shared] Section shell'],
    ['Hero banner + caption', 'no', 'yes', 'site two only: sites/two'],
    ['Gift-refusal policy', 'no', 'yes', 'site two only: sites/two'],
    ['News grid + filter chips', 'no', 'yes', 'site two only: sites/two'],
    ['Text hero with action buttons', 'yes', 'no', 'site one only: to build in sites/one'],
    ['News with empty state', 'yes', 'no', 'site one only: to build in sites/one'],
    ['Downloads, contact card + form, units list', 'yes', 'no', 'site one only: to build in sites/one'],
];
$mCell = fn($name, $html, $w, $bold = false, $color = 'text') => $el($name, ['content_width' => 'full', 'flex_direction' => 'column', 'padding' => $dim(10, 12)] + $rp('width', $px($w, '%'), $px($w, '%'), $px(100, '%')), [$text("$name > text", $bold ? "<p><strong>$html</strong></p>" : "<p>$html</p>", $color, 'small')], true);
$mRow = function ($r, $head = false) use ($el, $M, $C, $dim, $mCell, $text) {
    $mark = fn($v) => $v === 'yes' ? '✔ yes' : ($v === 'no' ? '— no' : '✔ ' . $v);
    $nm = 'Matrix row: ' . $r[0];
    $s = ['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap', 'flex_align_items' => 'center', 'border_border' => 'solid', 'border_width' => $dim(0, 0, 1, 0)];
    if ($head) $s += ['background_background' => 'classic'];
    return $el($nm, $M($s, $C('border_color', 'rule'), $head ? $C('background_color', 'paper') : []), [
        $mCell("$nm > component", $r[0], 34, true, 'primary'), $mCell("$nm > site one", $head ? $r[1] : $mark($r[1]), 14, $head), $mCell("$nm > site two", $head ? $r[2] : $mark($r[2]), 14, $head), $mCell("$nm > where", $r[3], 38, $head, 'muted')], true);
};
$matrixRows = array_merge([$mRow(['Component', 'Site one', 'Site two', 'Where it lives'], true)], array_map(fn($r) => $mRow($r), $matrix));

$specimens = [];
foreach ($components as $key => [$title, $desc]) {
    $specimens[] = $el("Specimen card: $title", $M(['content_width' => 'full', 'flex_direction' => 'column', 'background_background' => 'classic'], $card('white', 'rule'), $gapR(0, 0, 0)), [
        $stack("Specimen card: $title > info", 8, [
            $row("Specimen card: $title > title row", 12, [$heading("Specimen card: $title > title", "[Shared] $title", 'h3', 'primary', 'h3card'),
                $button("Specimen card: $title > edit master", 'Edit live block', $edit($blkId[$key]), 'white', 'secondary', 'secondary', 'white', 'primary', 'left', $dim(8, 16)),
                $button("Specimen card: $title > edit template", 'Edit template copy', $edit($tplId[$key]), 'primary', null, 'primary', 'white', 'primary', 'left', $dim(8, 16))], ['flex_wrap' => 'wrap']),
            $text("Specimen card: $title > description", "<p>$desc</p>", 'muted', 'body'),
            $text("Specimen card: $title > shortcode", '<p>Embed live with the Shortcode widget: <code>[hfe_template id="' . $blkId[$key] . '"]</code></p>', 'text', 'small')], ['padding' => $dim(18, 20)]),
        $wd("Specimen card: $title > live specimen", 'shortcode', ['shortcode' => '[hfe_template id="' . $blkId[$key] . '"]']),
    ], true);
}

$step = fn($name, $title, $html) => $stack($name, 10, [$heading("$name > title", $title, 'h3', 'primary', 'h3card'), $text("$name > text", $html, 'text', 'body')], $M($card('white', 'rule'), ['padding' => $dim(20, 22)]));

$guide = [
    $section('Guide > intro', 'white', [
        $heading('Guide > title', 'Foundry component guide', 'h2', 'primary', 'h2section'),
        $text('Guide > intro text', '<p>This installation is the <strong>foundry</strong> for the two Immigration Division 2 websites: <strong>site one</strong> (admin-division2) and <strong>site two</strong> (division2). It holds the design system (Global Colors and Fonts, the outer full-width + inner 1280 rule), the shared components and this guide. Each website is a fork that builds its own pages from these pieces. Foundry version <strong>' . $version . '</strong>.</p>', 'text', 'bodylg'),
        $grid('Guide > legend', [4, 2, 1], [1, 2, 4], [10, 10, 8], [$mapBox('Shared by both sites', 'shared', 'build from the foundry'), $mapBox('Site one only', 'one'), $mapBox('Site two only', 'two'), $mapBox('Still to build', 'todo')]),
    ], 'guide-top', [18, 16, 14], [56, 44, 32]),
    $section('Guide > page map', 'paper', [
        $secHead('Guide > page map', 'Page map: what each site is made of', false),
        $text('Guide > page map intro', '<p>Top to bottom, as each home page is (or will be) laid out. Gold blocks are shared: build them from the foundry, never copy-paste between sites.</p>', 'muted', 'body'),
        $grid('Guide > page map columns', [2, 2, 1], [1, 1, 2], [24, 20, 18], [$mapCol('Page map > site one', 'Site one: admin-division2', $mapOne), $mapCol('Page map > site two', 'Site two: division2', $mapTwo)]),
    ], 'guide-map'),
    $section('Guide > matrix', 'white', [$secHead('Guide > matrix', 'Component matrix', false), $stack('Guide > matrix table', 0, $matrixRows, $M($card('white', 'rule'), ['overflow' => 'hidden']))], 'guide-matrix'),
    $section('Guide > live components', 'paper', [
        $secHead('Guide > live components', 'Shared components: live specimens', false),
        $text('Guide > live intro', '<p>Each specimen is the real <strong>Custom Block</strong>, embedded with a shortcode. Edit the master block and every page that embeds it changes. The <strong>template copy</strong> button opens the Saved Template, the independent copy you insert and then customise.</p>', 'muted', 'body'),
        $stack('Guide > specimens', 28, $specimens),
    ], 'guide-components'),
    $section('Guide > how to use', 'white', [
        $secHead('Guide > how to use', 'How to use the components', false),
        $grid('Guide > how to use cards', [3, 1, 1], [1, 3, 3], [20, 18, 16], [
            $step('How to > embed live', '1. Embed live', '<p><strong>Edit once, every page updates.</strong></p><ol><li>Edit the page in Elementor.</li><li>Add a <strong>Shortcode</strong> widget.</li><li>Paste the block shortcode shown on its specimen card.</li><li>To change it everywhere: UAE → Header &amp; Footer, open the block with Elementor and edit it.</li></ol><p>A live block cannot be edited inside the page. Use a copy for page-specific changes.</p>'),
            $step('How to > insert a copy', '2. Insert as a copy', '<p><strong>Independent: change anything.</strong></p><ol><li>In the editor click the folder icon (Add Template).</li><li>Open <strong>My Templates</strong> and choose a [Shared] template.</li><li>Click Insert.</li></ol><p>All copies live in Templates → Saved Templates, category <strong>Shared components</strong>.</p>'),
            $step('How to > start a section', '3. Start a new section', '<ol><li>Insert <strong>[Shared] Section shell</strong>.</li><li>Rename the layers and keep the <em>outer (full width)</em> + <em>inner (max 1280)</em> pair.</li><li>Pick the outer background from Global Colors.</li><li>Put cards in a Grid container and set the columns for desktop, tablet and mobile.</li></ol>'),
        ]),
    ], 'guide-how'),
    $section('Guide > rules', 'paper', [
        $secHead('Guide > rules', 'Rules for both sites', false),
        $grid('Guide > rules cards', [3, 1, 1], [1, 3, 3], [20, 18, 16], [
            $step('Rules > structure', 'Structure', '<ul><li>Every section: one outer full-width container (background, vertical padding) and one inner container, boxed at 1280 px, holding the content.</li><li>Card rows are Grid containers with their own columns per device.</li><li>Name every layer: <em>Section &gt; part</em>.</li></ul>'),
            $step('Rules > design tokens', 'Design tokens', '<ul><li>Use Global Colors and Global Fonts only (Elementor → Site Settings). Never hard-code a colour or a font size.</li><li>No custom CSS and no HTML widgets.</li><li>Tablet and mobile sizes live in the Global Fonts.</li></ul>'),
            $step('Rules > two devs', 'Working as two devs', '<ul><li><strong>foundry/</strong> is shared: change it through a pull request to the foundry, then both sites pull it.</li><li><strong>sites/one</strong> and <strong>sites/two</strong> belong to one dev each.</li><li>Need something new in both sites? Add it as a foundry component, not in a site folder.</li></ul>'),
        ]),
    ], 'guide-rules'),
];
$guideId = $save('page', 'fdy-guide', 'Component guide', $guide, ['_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page'], ['post_name' => 'component-guide', 'post_status' => 'private']);

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
if ($skipped) echo "KEPT YOUR EDITS, not overwritten: " . implode('; ', $skipped) . " (set BK_FORCE=1 to rebuild from scratch)\n";
echo "foundry $version built: kit #$kitId | " . count($components) . " live blocks + " . count($components) . " templates | guide page #$guideId (private)\n";
echo 'blocks: ' . json_encode($blkId) . "\n";
echo 'guide: ' . get_permalink($guideId) . ' | edit: ' . $edit($guideId) . "\n";
