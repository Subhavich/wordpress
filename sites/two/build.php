<?php
/**
 * SITE TWO (division2.immigration.go.th, "second บก.") - design bk-tm2. Builds the Home page + Header + Footer.
 *
 * Shared sections come from the foundry components (foundry/lib.php): header, footer, status band, online services, leadership, videos.
 * Sections only this site has are written here: hero banner, banner caption, gift-refusal policy, news grid.
 * Run foundry/build-foundry.php first (Elementor globals) and sites/two/seed.php (pages, menu, posts).
 *
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/two/build.php --path=/var/www/html
 */
$libDir = getenv('FOUNDRY_DIR') ?: '/foundry';
require $libDir . '/lib.php';
$newsCat = get_term_by('slug', 'cat-202', 'category');

/* ======================= HOME PAGE ======================= */
$home = [];

// 1. Hero banner placeholder  (site two only)
$home[] = $section('Hero banner', 'bannerdark', [
    $stack('Hero banner > content', 12, [
        $icon('Hero banner > marker icon', 'fas fa-map-marker-alt', 'goldbright', 34, 32, 28, 'center'),
        $text('Hero banner > size note', '<p><strong>Official banner area</strong> &middot; recommended size 1440 &times; 460 px</p>', 'white', 'bodylg', 'center'),
        $text('Hero banner > hint', '<p>Replace this container with an Image or Image Carousel widget.</p>', 'faint', 'small', 'center'),
    ], ['flex_align_items' => 'center', 'flex_justify_content' => 'center'] + $rp('min_height', $px(300), $px(240), $px(180))),
], 'hero', [12, 12, 12], [56, 44, 32]);

// 2. Banner caption  (site two only)
$home[] = $section('Banner caption', 'paper', [
    $text('Banner caption > text', '<p>Placeholder banner caption. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.</p>', 'muted', 'body', 'center'),
], '', [12, 12, 12], [18, 16, 14]);

// 3. Status band  (SHARED component)
$home[] = $cStatusBand([
    ['Office hours', 'far fa-clock', 'เวลาราชการ', 'Mon-Fri 08:30-16:30'],
    ['Hotline', 'fas fa-phone-alt', 'สายด่วนตรวจคนเข้าเมือง', 'Hotline 0-0000 (24 h)'],
    ['TDAC', 'far fa-id-card', 'บัตรขาเข้าดิจิทัล TDAC', 'Register before travel'],
]);

// 4. Online services  (SHARED component, variant with shortcuts)
$home[] = $cServices('บริการออนไลน์', 'Placeholder intro. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.',
    [['Emergency report', 'fas fa-shield-alt', 'แจ้งเหตุด่วน'], ['Queue booking', 'far fa-calendar-alt', 'จองคิวล่วงหน้า'], ['National Police Office', 'fas fa-star-of-life', 'สำนักงานตำรวจแห่งชาติ'],
     ['TDAC arrival card', 'far fa-file-alt', 'บัตรขาเข้าดิจิทัล TDAC'], ['Immigration app', 'fas fa-mobile-alt', 'แอปพลิเคชันตรวจคนเข้าเมือง'], ['Application status', 'fas fa-search', 'ตรวจสอบสถานะคำร้อง']],
    [['TDAC', 'TDAC', 'บัตรขาเข้าดิจิทัล', 'ลงทะเบียน'], ['Vehicle report (TM.2)', 'ตม. 2', 'รายการเกี่ยวกับยานพาหนะ', 'แจ้งรายการ'], ['Lodger notice (TM.30)', 'ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งเข้าพัก'],
     ['Online appointment', 'ONLINE APPOINTMENT', 'จองคิวออนไลน์', 'จองคิว'], ['90-day report (TM.47)', 'ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['E-extension', 'E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง']]);

// 5. Leadership  (SHARED component)
$home[] = $cLeadership('ผู้บังคับบัญชา', 'โครงสร้างหน่วยงาน', $pageUrl('org-structure'),
    [['National Police Chief', 'ผู้บัญชาการตำรวจแห่งชาติ'], ['Immigration Bureau Commander', 'ผู้บัญชาการสำนักงานตรวจคนเข้าเมือง'], ['Division 2 Commander', 'ผู้บังคับการตรวจคนเข้าเมือง 2']], $portraitUrl, $portraitId);

// 6. Gift-refusal policy (ITA)  (site two only)
$sheet = fn($en, $th) => $el("Policy document: $en", $M(['content_width' => 'full', 'flex_direction' => 'column', 'flex_align_items' => 'center', 'flex_justify_content' => 'center'], $card('white', 'rule'), $gapR(10, 10, 10), $padR($dim(26, 18), $dim(22, 16), $dim(20, 14))), [
    $icon("Policy document: $en > icon", 'far fa-file-alt', 'secondary', 38, 34, 30, 'center'), $text("Policy document: $en > label", "<p>$th</p>", 'primary', 'lanelabel', 'center')], true);
$home[] = $section('Gift-refusal policy', 'paper', [
    $grid('Gift-refusal policy > columns', [2, 1, 1], [1, 2, 2], [36, 28, 24], [
        $stack('Gift-refusal policy > text column', 14, [
            $heading('Gift-refusal policy > title', 'นโยบายงดรับและงดให้ของขวัญ', 'h2', 'primary', 'h2section'),
            $text('Gift-refusal policy > text', '<p>Placeholder text. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>', 'text', 'bodylg'),
            $button('Gift-refusal policy > button', 'อ่านประกาศฉบับเต็ม', '#', 'primary', null, 'primary', 'white', 'primary')], ['flex_justify_content' => 'center']),
        $grid('Gift-refusal policy > documents grid', [2, 2, 1], [1, 1, 2], [16, 14, 12], [$sheet('Division level', 'ประกาศเจตนารมณ์ ระดับกองบังคับการ'), $sheet('Commander level', 'ประกาศเจตนารมณ์ ผู้บังคับการ')]),
    ]),
], 'ita');

// 7. News (Essential Addons Lite post grid, category "ผลการปฏิบัติ")  (site two only)
$chips = [];
foreach ([['All', 'ทั้งหมด', true], ['Press releases', 'ข่าวประชาสัมพันธ์', false], ['Arrest results', 'ผลการจับกุม', false], ['ITA activities', 'กิจกรรม ITA', false]] as [$en, $th, $on])
    $chips[] = $button("Filter chip: $en", $th, '#', $on ? 'white' : 'primary', $on ? 'primary' : null, 'primary', 'white', 'secondary');
$home[] = $section('News', 'white', [
    $secHead('News', 'ผลการปฏิบัติ', false, 'ดูทั้งหมด', $newsCat ? get_category_link($newsCat->term_id) : '#'),
    $el('News > filter chips', $M(['content_width' => 'full', 'flex_direction' => 'row', 'flex_wrap' => 'wrap'], $gapR(10, 10, 8)), $chips, true),
    $wd('News > post grid (Essential Addons)', 'eael-post-grid', $M(
        ['eael_post_grid_preset_style' => 'one', 'layout_mode' => 'grid', 'eael_post_grid_columns' => 'eael-col-3', 'eael_post_grid_columns_tablet' => 'eael-col-2', 'eael_post_grid_columns_mobile' => 'eael-col-1',
         'post_type' => 'post', 'posts_per_page' => 3, 'category_ids' => $newsCat ? [(string) $newsCat->term_id] : [], 'eael_show_title' => 'yes', 'eael_show_excerpt' => 'yes', 'eael_excerpt_length' => 16,
         'eael_show_read_more_button' => 'yes', 'read_more_button_text' => 'อ่านรายละเอียด', 'eael_show_meta' => 'yes', 'eael_show_author' => '', 'eael_show_date' => 'yes', 'image_size' => 'medium_large',
         'enable_postgrid_image_ratio' => 'yes', 'postgrid_image_ratio' => ['unit' => 'px', 'size' => 0.62, 'sizes' => []], 'eael_post_grid_border_radius' => $radius(10)],
        $C('eael_post_grid_bg_color', 'white'), $C('eael_post_grid_title_color', 'primary'), $C('eael_post_grid_title_hover_color', 'secondary'), $C('eael_post_grid_excerpt_color', 'muted'),
        $C('eael_post_grid_meta_color', 'muted'), $C('read_more_btn_text_color', 'secondary'), $T('eael_post_grid_title_typography_typography', 'h3card'), $T('eael_post_grid_excerpt_typography_typography', 'small'))),
], 'news');

// 8. Videos  (SHARED component)
$home[] = $cVideos('วีดิทัศน์แนะนำ', 'ช่อง Immigration Bureau', 4);

/* ======================= HEADER + FOOTER (SHARED components, site two content) ======================= */
$header = $cHeader();
$footer = $cFooter();

/* ======================= SAVE ======================= */
$homePage = get_page_by_path('home');
if (!$homePage) { echo "Home page not found. Run sites/two/seed.php first.\n"; return; }
$homeId = $save('page', 'page-home', 'Home', $home, ['_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page'], ['ID' => $homePage->ID, 'post_name' => 'home']);
$loc = ['ehf_target_include_locations' => ['rule' => ['basic-global'], 'specific' => []], 'ehf_target_exclude_locations' => ['rule' => [], 'specific' => []], 'ehf_target_user_roles' => ['all']];
$hdrId = $save('elementor-hf', 'hf-header', 'Header (bk-tm2)', $header, ['ehf_template_type' => 'type_header'] + $loc);
$ftrId = $save('elementor-hf', 'hf-footer', 'Footer (bk-tm2)', $footer, ['ehf_template_type' => 'type_footer'] + $loc);

/* ======================= STANDALONE SITE PAGE: /site-two/ =======================
 * The whole site on ONE page (header + sections + footer, Elementor Canvas template, so it does not depend on the global header/footer).
 * Lets both sites live side by side in one WordPress. Change the slug with SITE_SLUG=... or in the page's permalink. */
$existing = get_posts(['post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'two-page-site', 'fields' => 'ids', 'numberposts' => 1]);
$slug = getenv('SITE_SLUG') ?: ($existing ? get_post_field('post_name', $existing[0]) : 'site-two');   // a slug you changed in WordPress is kept
$sitePage = array_merge($cHeader(), $home, $cFooter());     // fresh header/footer elements; the body sections are reused as they are
$sitePageId = $save('page', 'two-page-site', 'Site two: division2', $sitePage, ['_wp_page_template' => 'elementor_canvas', '_elementor_template_type' => 'wp-page'], ['post_name' => $slug]);
echo "standalone page: " . get_permalink($sitePageId) . " (slug $slug)\n";

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
if ($skipped) echo "KEPT YOUR EDITS, not overwritten: " . implode('; ', $skipped) . " (set BK_FORCE=1 to rebuild from scratch)\n";
echo "built site TWO (native): home page id=$homeId | header id=$hdrId | footer id=$ftrId\n";
echo 'sections: home=' . count($home) . ', header=' . count($header) . ', footer=' . count($footer) . "\n";
echo 'edit in Elementor: ' . admin_url("post.php?post=$homeId&action=elementor") . "\n";
