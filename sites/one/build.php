<?php
/**
 * SITE ONE (admin-division2.immigration.go.th, "main บก.") - design admin-tm2. build.
 *
 * Builds ONE page, /site-one/ (change the slug with SITE_SLUG=...): header + sections + footer on the Elementor Canvas template, so it does
 * not use the global header/footer and can live next to site two in the same WordPress.
 *
 * Shared foundry components: Header, Footer, Status band, Online services (cards only), Leadership, Videos (real YouTube links).
 * Site-one-only sections (they exist only in admin-tm2) are built here from the foundry building blocks: text hero, press releases and arrest results
 * (with empty states), downloads, contact card + Contact Form 7 form, units list. If site two ever needs one of them, move it into the foundry as a component.
 *
 * Run foundry/build-foundry.php first, then sites/one/seed.php.
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/one/build.php --path=/var/www/html
 */
$libDir = getenv('FOUNDRY_DIR') ?: '/foundry';
require $libDir . '/lib.php';

$byKey = fn($key) => ($q = get_posts(['post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1])) ? (int) $q[0] : 0;
$sitePageId = $byKey('one-page-site');
if (!$sitePageId) { echo "Site-one page not found. Run sites/one/seed.php first.\n"; return; }
$structureUrl = ($id = $byKey('one-page-structure')) ? get_permalink($id) : '#';

/* ======================= SECTIONS (order follows admin-tm2) ======================= */
$body = [];
/* ---- site-one-only sections, built from the foundry building blocks ---- */
// Text hero: arrival marker, two-line title, intro, two action buttons.
$sHero = $section('Hero', 'primary', [
    $stack('Hero > content', 16, [
        $row('Hero > arrival marker', 8, [$icon('Hero > marker icon', 'fas fa-map-marker-alt', 'goldbright', 16, 16, 16), $text('Hero > marker text', '<p>ท่าอากาศยานสุวรรณภูมิ</p>', 'goldbright', 'h4label')], ['flex_justify_content' => 'center']),
        $heading('Hero > title', 'ฝ่ายอำนวยการ<br>กองบังคับการตรวจคนเข้าเมือง ๒', 'h1', 'white', 'herotitle', 'center'),
        $text('Hero > intro', '<p>สนับสนุนการปฏิบัติราชการของหน่วยงานในสังกัด ด้านกำลังพล งบประมาณ พัสดุ และงานสารบรรณ พร้อมให้บริการข้อมูลข่าวสารแก่ประชาชนตามหลักธรรมาภิบาล</p>', 'softtext', 'bodylg', 'center'),
        $row('Hero > actions', 14, [
            $button('Hero > button: online services', 'ใช้บริการออนไลน์', '#eservice', 'primary', 'secondary', 'secondary', 'primary', 'goldbright', 'center'),
            $button('Hero > button: contact an officer', 'ติดต่อเจ้าหน้าที่', '#contact', 'white', null, 'goldbright', 'primary', 'white', 'center'),
        ], ['flex_wrap' => 'wrap', 'flex_justify_content' => 'center']),
    ], ['content_width' => 'boxed', 'boxed_width' => $px(860), 'flex_align_items' => 'center']),
], '', [18, 16, 14], [80, 60, 48]);

// Empty-state list section (press releases, arrest results): heading row + a dashed notice + a quiet button.
$sEmpty = fn($name, $title, $bg, $msg, $cta, $icoName) => $section($name, $bg, [
    $secHead($name, $title, false, 'ดูทั้งหมด'),
    $el("$name > empty state", $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'border_border' => 'dashed', 'border_width' => $dim(2), 'border_radius' => $radius(10)],
        $C('border_color', 'rule'), $gapR(10, 10, 8), $padR($dim(34, 20), $dim(30, 18), $dim(26, 16))), [
        $icon("$name > empty icon", $icoName, 'secondary', 34, 32, 30, 'center'),
        $text("$name > empty message", "<p>$msg</p>", 'muted', 'body', 'center'),
        $button("$name > archive button", $cta, '#', 'primary', null, 'primary', 'white', 'primary', 'center'),
    ], true),
], '', [20, 18, 16], [44, 36, 30]);

// News list section: a native Essential Addons Post Grid when the category has posts, otherwise the empty-state notice above.
$sNews = function ($name, $title, $bg, $slug, $emptyMsg, $emptyCta, $emptyIcon) use ($section, $secHead, $wd, $M, $C, $T, $radius, $sEmpty) {
    $cat = get_term_by('slug', $slug, 'category');
    if (!$cat || (int) $cat->count < 1) return $sEmpty($name, $title, $bg, $emptyMsg, $emptyCta, $emptyIcon);
    return $section($name, $bg, [
        $secHead($name, $title, false, 'ดูทั้งหมด', get_category_link($cat->term_id)),
        $wd("$name > post grid (Essential Addons)", 'eael-post-grid', $M(
            ['eael_post_grid_preset_style' => 'one', 'layout_mode' => 'grid', 'eael_post_grid_columns' => 'eael-col-3', 'eael_post_grid_columns_tablet' => 'eael-col-2', 'eael_post_grid_columns_mobile' => 'eael-col-1',
             'post_type' => 'post', 'posts_per_page' => 3, 'category_ids' => [(string) $cat->term_id], 'eael_show_title' => 'yes', 'eael_show_excerpt' => 'yes', 'eael_excerpt_length' => 18,
             'eael_show_read_more_button' => 'yes', 'read_more_button_text' => 'อ่านรายละเอียด', 'eael_show_meta' => 'yes', 'eael_show_author' => '', 'eael_show_date' => 'yes', 'image_size' => 'medium_large',
             'enable_postgrid_image_ratio' => 'yes', 'postgrid_image_ratio' => ['unit' => 'px', 'size' => 0.62, 'sizes' => []], 'eael_post_grid_border_radius' => $radius(10)],
            $C('eael_post_grid_bg_color', 'white'), $C('eael_post_grid_title_color', 'primary'), $C('eael_post_grid_title_hover_color', 'secondary'), $C('eael_post_grid_excerpt_color', 'muted'),
            $C('eael_post_grid_meta_color', 'muted'), $C('read_more_btn_text_color', 'secondary'), $T('eael_post_grid_title_typography_typography', 'h3card'), $T('eael_post_grid_excerpt_typography_typography', 'small'))),
    ], '', [20, 18, 16], [44, 36, 30]);
};

// Downloads: two cards of document links, from sites/one/docs.json (real documents). Each link is a placeholder "#doc/<key>" that
// sites/export-live.php replaces with the live file URL; "view all" is "#downloads" (the live Downloads page).
$docsCfg = json_decode((string) @file_get_contents(is_file(__DIR__ . '/docs.json') ? __DIR__ . '/docs.json' : '/sites/one/docs.json'), true) ?: ['announce' => ['title' => 'ประกาศและเอกสารเผยแพร่', 'show' => 6, 'items' => []], 'important' => ['title' => 'เอกสารสำคัญ', 'items' => []]];
$docIcon = ['pdf' => 'far fa-file-pdf', 'image' => 'far fa-image', 'article' => 'far fa-newspaper'];
$docCol = fn($title, array $items) => $el("Downloads > column: $title", $M(['content_width' => 'full', 'flex_direction' => 'column'], $card('white', 'rule'), $gapR(14, 14, 12), $padR($dim(24, 26), $dim(22, 22), $dim(20, 18))), [
    $heading("Downloads > $title > title", $title, 'h3', 'primary', 'h3card'),
    $divider("Downloads > $title > rule", 'rule'),
    $iconList("Downloads > $title > document links", array_map(fn($d) => [$d['title'], $docIcon[$d['kind'] ?? 'pdf'] ?? 'far fa-file-pdf', '#doc/' . $d['key']], $items), 'text', 'secondary', 'secondary', 'body'),
    $linkButton("Downloads > $title > view all", 'ดูทั้งหมด ›', '#downloads', 'secondary', 'primary'),
], true);
$sDocs = $section('Downloads', 'white', [
    $secHead('Downloads', 'เอกสารดาวน์โหลด', false),
    $grid('Downloads > columns grid', [2, 1, 1], [1, 2, 2], [24, 20, 16], [
        $docCol($docsCfg['announce']['title'], array_slice($docsCfg['announce']['items'], 0, (int) $docsCfg['announce']['show'])),
        $docCol($docsCfg['important']['title'], $docsCfg['important']['items']),
    ]),
], 'docs', [24, 20, 18], [56, 44, 34]);

// Contact: the commander's contact card + the contact-back form (Contact Form 7, found by its title).
$sContact = $section('Contact', 'paper', [
    $secHead('Contact', 'ติดต่อและข้อเสนอแนะ', false),
    $grid('Contact > columns grid', [2, 1, 1], [1, 2, 2], [24, 20, 16], [
        $el('Contact > commander card', $M(['content_width' => 'full', 'flex_direction' => 'column'], $card('white', 'rule'), $gapR(0, 0, 0)), [
            $image('Contact > commander photo', $portraitUrl, $portraitId),
            $stack('Contact > commander details', 6, [
                $text('Contact > commander lead', '<p>หากไม่ได้รับความสะดวกในการติดต่อราชการ หรือมีข้อเสนอแนะและเรื่องร้องเรียน ติดต่อได้โดยตรงที่</p>', 'muted', 'body'),
                $heading('Contact > commander name', 'ยศ ชื่อ นามสกุล (placeholder)', 'h4', 'primary', 'h3card'),
                $text('Contact > commander position', '<p>ผู้บังคับการตรวจคนเข้าเมือง 2</p>', 'muted', 'body'),
                $text('Contact > commander phone', '<p><strong>โทร. 0 2134 0858 ต่อ 40858</strong></p>', 'text', 'body'),
            ], ['padding' => $dim(20, 24)]),
        ], true),
        $el('Contact > form card', $M(['content_width' => 'full', 'flex_direction' => 'column'], $card('white', 'rule'), $gapR(12, 12, 10), $padR($dim(26, 28), $dim(24, 24), $dim(20, 18))), [
            $heading('Contact > form title', 'แบบฟอร์มติดต่อกลับ', 'h2', 'primary', 'h3card'),
            $text('Contact > form intro', '<p>กรอกข้อมูลเพื่อให้เจ้าหน้าที่ติดต่อกลับภายในเวลาราชการ</p>', 'muted', 'body'),
            $wd('Contact > contact-back form (Contact Form 7)', 'shortcode', ['shortcode' => '[contact-form-7 title="Contact-back form"]']),
        ], true),
    ]),
], 'contact', [24, 20, 18], [56, 44, 34]);

// Units: a grid of crest tiles, one per sub-unit.
$units = ['กองบังคับการตรวจคนเข้าเมือง 2', 'ตม. ขาเข้า ด่าน ตม. ทอ. สุวรรณภูมิ', 'ตม. ขาออก ด่าน ตม. ทอ. สุวรรณภูมิ', 'ฝ่ายตรวจลงตรา ด่าน ตม. ทอ. สุวรรณภูมิ', 'ฝ่ายพิธีการเข้าเมือง ด่าน ตม. ทอ. สุวรรณภูมิ', 'กองกำกับการสืบสวนปราบปราม บก.ตม.2',
          'ด่าน ตม. ทอ. กรุงเทพ', 'ด่าน ตม. ทอ. ภูเก็ต', 'ด่าน ตม. ทอ. เชียงใหม่', 'ด่าน ตม. ทอ. หาดใหญ่', 'ฝ่ายอำนวยการ บก.ตม.2'];
$sUnits = $section('Units', 'white', [
    $secHead('Units', 'หน่วยงานในสังกัด', false),
    $grid('Units > tiles grid', [6, 3, 2], $rowsFor(count($units), [6, 3, 2]), [16, 14, 12], array_map(fn($u, $i) => $el('Unit tile ' . ($i + 1) . ': ' . $u, $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center'], $card('white', 'rule'), $gapR(10, 10, 8), $padR($dim(20, 12), $dim(18, 12), $dim(16, 10))), [
        $icon('Unit tile ' . ($i + 1) . ' > crest icon', 'fas fa-shield-alt', 'secondary', 34, 32, 30, 'center'),
        $text('Unit tile ' . ($i + 1) . ' > name', "<p>$u</p>", 'text', 'small', 'center'),
    ], true), $units, array_keys($units))),
], '', [24, 20, 18], [56, 44, 34]);

/* ---- the page, in the order of admin-tm2 ---- */
$body[] = $sHero;
$body[] = $cStatusBand([                                                                                         // SHARED
    ['Office hours', 'far fa-clock', 'เวลาราชการ', 'จันทร์–ศุกร์ 08.30–16.30 น.'],
    ['Registry office', 'far fa-envelope', 'งานสารบรรณ', 'saraban_imm2@police.go.th'],
    ['Location', 'fas fa-map-marker-alt', 'ที่ตั้งสำนักงาน', 'ถนนสุวรรณภูมิ สาย 4'],
]);
$body[] = $cServices('บริการออนไลน์', 'Placeholder intro. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.', [],   // SHARED, cards-only variant
    [['E-extension', 'E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง'], ['90-day report (TM.47)', 'ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['Lodger notice (TM.30)', 'ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งที่พักอาศัย']]);
$body[] = $cLeadership('ผู้บังคับบัญชา', 'โครงสร้างหน่วยงาน', $structureUrl,                                            // SHARED
    [['National Police Chief', 'ผู้บัญชาการตำรวจแห่งชาติ'], ['Immigration Bureau Commander', 'ผู้บัญชาการสำนักงานตรวจคนเข้าเมือง'], ['Division Commander', 'ผู้บังคับการตรวจคนเข้าเมือง 2']], $portraitUrl, $portraitId);
$body[] = $sNews('Press releases', 'ข่าวประชาสัมพันธ์', 'white', 'press-releases', 'ยังไม่มีข่าวประชาสัมพันธ์เผยแพร่ในขณะนี้<br>ข่าวใหม่จะแสดงที่นี่ทันทีเมื่อเจ้าหน้าที่เผยแพร่', 'ดูข่าวย้อนหลัง', 'far fa-paper-plane');
$body[] = $sNews('Arrest results', 'ผลการจับกุม', 'paper', 'arrest-results', 'ยังไม่มีรายงานผลการจับกุมเผยแพร่ในขณะนี้', 'ดูรายงานย้อนหลัง', 'fas fa-shield-alt');
$body[] = $cVideos('วีดิทัศน์แนะนำ', 'ช่อง Immigration Bureau', 4, [['Police Care — กฎหมายเพื่อประชาชน', 'https://www.youtube.com/watch?v=Naip6sFsP84'], ['บัตรขาเข้าดิจิทัล TDAC คืออะไร', 'https://www.youtube.com/watch?v=QSEoVloM5A0']]);   // SHARED
$body[] = $sDocs;
$body[] = $sContact;
$body[] = $sUnits;

/* ======================= THE PAGE: header + sections + footer (SHARED components, site-one content) ======================= */
$header = $cHeader(['nameTh' => 'ฝ่ายอำนวยการ บก.ตม.2', 'nameEn' => 'ADMINISTRATION SUB-DIVISION', 'menu' => 'site-one-menu']);
$footer = $cFooter(['nameTh' => 'ฝ่ายอำนวยการ บก.ตม.2', 'nameEn' => 'ADMINISTRATION SUB-DIVISION', 'col1Title' => 'ลิงก์ด่วน', 'col1' => ['หน้าแรก', 'ข่าวประชาสัมพันธ์', 'โครงสร้างหน่วยงาน', 'ผู้บังคับบัญชา'],
                    'col2Title' => 'มาตรฐาน ITA', 'col2' => ['ITA 2567', 'ITA 2568', 'ITA 2569', 'แจ้งร้องเรียนการทุจริต']]);
$page = array_merge($header, $body, $footer);
$slug = get_post_field('post_name', $sitePageId);
$pageId = $save('page', 'one-page-site', 'Site one: admin-division2', $page, ['_wp_page_template' => 'elementor_canvas', '_elementor_template_type' => 'wp-page'], ['ID' => $sitePageId, 'post_name' => $slug]);

// Essential Addons builds each page's CSS/JS from a per-page list of the EA widgets it uses (the Elementor editor keeps that list up to date on save).
// A programmatic build has to write it itself, or a widget such as the Post Grid renders unstyled. Delete the generated files so they are rebuilt.
$ea = ['simple-menu']; if (strpos(wp_json_encode($page), 'eael-post-grid') !== false) $ea[] = 'post-grid';
update_post_meta($pageId, '_eael_widget_elements', $ea);
foreach (glob(wp_upload_dir()['basedir'] . "/essential-addons-elementor/eael-$pageId.*") ?: [] as $f) @unlink($f);

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
if ($skipped) echo "KEPT YOUR EDITS, not overwritten: " . implode('; ', $skipped) . " (set BK_FORCE=1 to rebuild from scratch)\n";
echo "built site ONE: page id=$pageId | " . get_permalink($pageId) . "\n";
echo 'sections: ' . count($page) . ' (' . count(array_filter($page, fn($s) => strpos($s['settings']['_title'], 'TODO') === 0)) . ' TODO to build)' . "\n";
echo 'edit in Elementor: ' . admin_url("post.php?post=$pageId&action=elementor") . "\n";
