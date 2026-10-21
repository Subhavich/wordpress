<?php
/**
 * SITE ONE (admin-division2.immigration.go.th, "main บก.") - design admin-tm2. STARTER build.
 *
 * Builds ONE page, /site-one/ (change the slug with SITE_SLUG=...): header + sections + footer on the Elementor Canvas template, so it does
 * not use the global header/footer and can live next to site two in the same WordPress.
 *
 * Already here: Header, Footer, Status band, Online services (cards only), Leadership, Videos, all from the SHARED foundry components.
 * Left for the site-one dev: the dashed "TODO" sections (they exist only in admin-tm2). Replace each $cTodo(...) with the real section, built from the
 * foundry building blocks ($section, $grid, $stack, $heading, $text, $button, ...) or, if site two will need it too, a new foundry component.
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
$body[] = $cTodo('Text hero with action buttons', 'admin-tm2 opens with a text hero (arrival marker, title, intro, two buttons: "use online services" and "contact an officer") instead of a banner image.', 'paper');
$body[] = $cStatusBand([                                                                                         // SHARED
    ['Office hours', 'far fa-clock', 'เวลาราชการ', 'Mon-Fri 08:30-16:30'],
    ['Hotline', 'fas fa-phone-alt', 'สายด่วนตรวจคนเข้าเมือง', 'Hotline 0-0000 (24 h)'],
    ['Registry office', 'far fa-envelope', 'งานสารบรรณ', 'info@example.invalid'],
]);
$body[] = $cServices('บริการออนไลน์', 'Placeholder intro. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.', [],   // SHARED, cards-only variant
    [['E-extension', 'E-EXTENSION', 'ขออยู่ต่อในราชอาณาจักร', 'ยื่นคำร้อง'], ['90-day report (TM.47)', 'ตม. 47', 'แจ้งอยู่เกิน 90 วัน', 'แจ้งที่พักอาศัย'], ['Lodger notice (TM.30)', 'ตม. 30', 'แจ้งรับคนต่างด้าวเข้าพัก', 'แจ้งที่พักอาศัย']]);
$body[] = $cLeadership('ผู้บังคับบัญชา', 'โครงสร้างหน่วยงาน', $structureUrl,                                            // SHARED
    [['National Police Chief', 'ผู้บัญชาการตำรวจแห่งชาติ'], ['Immigration Bureau Commander', 'ผู้บัญชาการสำนักงานตรวจคนเข้าเมือง'], ['Division Commander', 'ผู้บังคับการตรวจคนเข้าเมือง 2']], $portraitUrl, $portraitId);
$body[] = $cTodo('Press releases (with empty state)', 'News list for "ข่าวประชาสัมพันธ์". When there are no posts it shows an empty-state block: "ยังไม่มีข่าวประชาสัมพันธ์เผยแพร่ในขณะนี้" with a link to the archive.', 'white');
$body[] = $cTodo('Arrest results (with empty state)', 'Same pattern for "ผลการจับกุม" (this category exists on site one, not on site two).', 'paper');
$body[] = $cVideos('วีดิทัศน์แนะนำ', 'ช่อง Immigration Bureau', 4);                                                    // SHARED
$body[] = $cTodo('Downloads (two columns)', 'Two columns of document links: "บริการออนไลน์และข้อมูลความรู้" (manuals, FAQ) and "เอกสารสำคัญ" (Immigration Act, gift policy, annual plan, annual report), each with a "view all" link.', 'white');
$body[] = $cTodo('Contact card + contact-back form', 'Left: the commander contact card (photo, name, position, phone). Right: contact-back form (name, surname, phone, email, message, privacy consent). Use Contact Form 7: Elementor\'s Form widget is Pro.', 'paper');
$body[] = $cTodo('Units list (11 units)', 'The list of sub-units (หน่วยงานในสังกัด), each with the crest: arrival, departure, visa, ceremony, investigation, and the airport checkpoints.', 'white');

/* ======================= THE PAGE: header + sections + footer (SHARED components, site-one content) ======================= */
$header = $cHeader(['nameTh' => 'ฝ่ายอำนวยการ บก.ตม.2', 'nameEn' => 'ADMINISTRATION SUB-DIVISION', 'menu' => 'site-one-menu']);
$footer = $cFooter(['nameTh' => 'ฝ่ายอำนวยการ บก.ตม.2', 'nameEn' => 'ADMINISTRATION SUB-DIVISION', 'col1Title' => 'ลิงก์ด่วน', 'col1' => ['หน้าแรก', 'ข่าวประชาสัมพันธ์', 'โครงสร้างหน่วยงาน', 'ผู้บังคับบัญชา'],
                    'col2Title' => 'มาตรฐาน ITA', 'col2' => ['ITA 2567', 'ITA 2568', 'ITA 2569', 'แจ้งร้องเรียนการทุจริต']]);
$page = array_merge($header, $body, $footer);
$slug = get_post_field('post_name', $sitePageId);
$pageId = $save('page', 'one-page-site', 'Site one: admin-division2', $page, ['_wp_page_template' => 'elementor_canvas', '_elementor_template_type' => 'wp-page'], ['ID' => $sitePageId, 'post_name' => $slug]);

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
if ($skipped) echo "KEPT YOUR EDITS, not overwritten: " . implode('; ', $skipped) . " (set BK_FORCE=1 to rebuild from scratch)\n";
echo "built site ONE starter: page id=$pageId | " . get_permalink($pageId) . "\n";
echo 'sections: ' . count($page) . ' (' . count(array_filter($page, fn($s) => strpos($s['settings']['_title'], 'TODO') === 0)) . ' TODO to build)' . "\n";
echo 'edit in Elementor: ' . admin_url("post.php?post=$pageId&action=elementor") . "\n";
