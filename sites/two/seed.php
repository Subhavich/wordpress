<?php
/**
 * SITE TWO (division2.immigration.go.th) - realistic PLACEHOLDER data shaped like the audited blog:
 *   - 17 categories (real names and ids), 78 posts in the real per-category counts, date windows, body lengths, image/file/empty mix (invented Thai text)
 *   - 9 pages (Home, News and the poster-style static pages), a main menu, an optional logo (mount a folder at /assets)
 * Everything is tagged _mock_data=1 and keyed (two-post-N ...), so it is safe to re-run.
 *
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/two/seed.php --path=/var/www/html
 */
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

mt_srand(2026);
$admin = get_users(['role' => 'administrator', 'number' => 1])[0]->ID;
$stats = ['trashed' => 0, 'cats' => 0, 'posts' => 0, 'pages' => 0, 'media' => 0, 'skipped' => 0];

/* ---------- 0. move previously imported (scraped) posts to the Trash; media is left alone ---------- */
foreach (get_posts(['post_type' => 'post', 'post_status' => 'publish', 'meta_key' => '_source_url', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
    wp_trash_post($id);
    $stats['trashed']++;
}

/* ---------- helpers ---------- */
function mock_find($key, $type = 'any') {
    $q = get_posts(['post_type' => $type, 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1]);
    return $q ? (int) $q[0] : 0;
}

// Minimal PNG writer used if GD is missing (solid colour).
function mock_png_solid($w, $h, $rgb) {
    $row = "\0" . str_repeat(chr($rgb[0]) . chr($rgb[1]) . chr($rgb[2]), $w);
    $raw = str_repeat($row, $h);
    $chunk = fn($t, $d) => pack('N', strlen($d)) . $t . $d . pack('N', crc32($t . $d));
    return "\x89PNG\r\n\x1a\n" . $chunk('IHDR', pack('NNCCCCC', $w, $h, 8, 2, 0, 0, 0)) . $chunk('IDAT', gzcompress($raw, 6)) . $chunk('IEND', '');
}

function mock_image_bytes($w, $h, $rgb, $label) {
    if (!function_exists('imagecreatetruecolor')) return mock_png_solid($w, $h, $rgb);
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    $fg = imagecolorallocate($im, max(0, $rgb[0] - 70), max(0, $rgb[1] - 70), max(0, $rgb[2] - 70));
    $tx = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    imagesetthickness($im, 3);
    imageline($im, 0, 0, $w, $h, $fg);
    imageline($im, $w, 0, 0, $h, $fg);
    imagerectangle($im, 4, 4, $w - 5, $h - 5, $fg);
    $text = $label . '  ' . $w . 'x' . $h;
    $fw = imagefontwidth(5) * strlen($text); $fh = imagefontheight(5);
    imagefilledrectangle($im, (int) (($w - $fw) / 2) - 12, (int) (($h - $fh) / 2) - 8, (int) (($w + $fw) / 2) + 12, (int) (($h + $fh) / 2) + 8, $fg);
    imagestring($im, 5, (int) (($w - $fw) / 2), (int) (($h - $fh) / 2), $text, $tx);
    ob_start(); imagepng($im, null, 6); $png = ob_get_clean(); imagedestroy($im);
    return $png;
}

function mock_attach($key, $bytes, $filename, $mime, $title, $parent = 0) {
    if ($id = mock_find($key, 'attachment')) return $id;
    $up = wp_upload_bits($filename, null, $bytes);
    if (!empty($up['error'])) { echo "upload failed $filename: {$up['error']}\n"; return 0; }
    $id = wp_insert_attachment(['post_mime_type' => $mime, 'post_title' => $title, 'post_status' => 'inherit', 'post_parent' => $parent], $up['file'], $parent);
    if (is_wp_error($id)) return 0;
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $up['file']));
    update_post_meta($id, '_mock_key', $key);
    update_post_meta($id, '_mock_data', 1);
    return $id;
}

/* ---------- 1. categories (names/ids mirror the audited division2 site) ---------- */
$cats = [ // cid => [name, posts to create]
    201 => ['ตัวชี้วัดที่ 9 การเปิดเผยข้อมูล', 6], 229 => ['คุณธรรม ความโปร่งใส และการป้องกันการทุจริต', 4],
    212 => ['ข่าวประชาสัมพันธ์ทั่วไป', 4], 202 => ['ผลการปฏิบัติ', 4], 216 => ['ตัวชี้วัดที่ 10 การป้องกันการทุจริต', 3],
    224 => ['ภารกิจผู้บังคับบัญชา', 3], 198 => ['มาตรฐาน ITA', 2], 214 => ['ผลการปฏิบัติ ITA', 2],
    223 => ['ประชุมบริหาร บก.ตม.2', 2], 227 => ['ข้อมูลพื้นฐานของหน่วยงาน', 2], 210 => ['กฏหมาย ระเบียบ', 2],
    203 => ['วีดีโอแนะนำ', 1], 205 => ['ประกาศจัดซื้อ จัดจ้าง', 1], 206 => ['บริการออนไลน์ และข้อมูลความรู้', 1],
    166 => ['เกี่ยวกับเรา', 0], 172 => ['ติดต่อเรา', 0], 189 => ['ผู้บังคับบัญชา', 0], // static pages live in WP pages
];
$catId = [];
foreach ($cats as $cid => [$name]) {
    $t = get_term_by('slug', 'cat-' . $cid, 'category') ?: get_term_by('name', $name, 'category');
    if (!$t) {
        $r = wp_insert_term($name, 'category', ['slug' => 'cat-' . $cid]);
        if (is_wp_error($r)) { echo "cat fail $cid: " . $r->get_error_message() . "\n"; continue; }
        $t = get_term($r['term_id'], 'category'); $stats['cats']++;
    }
    $catId[$cid] = (int) $t->term_id;
}

/* ---------- 2. placeholder media ---------- */
$land = [];
$colors = [[120, 144, 156], [141, 110, 99], [102, 139, 120], [128, 115, 150], [150, 125, 90], [90, 130, 160]];
foreach ($colors as $i => $rgb) {
    $n = $i + 1;
    $land[] = mock_attach("img-land-$n", mock_image_bytes(1200, 630, $rgb, "PLACEHOLDER $n"), "mock-landscape-$n.png", 'image/png', "Mock landscape $n");
}
$poster = [
    mock_attach('img-poster-1', mock_image_bytes(1055, 1491, [96, 125, 139], 'POSTER 1'), 'mock-poster-1.png', 'image/png', 'Mock poster 1'),
    mock_attach('img-poster-2', mock_image_bytes(1055, 1491, [121, 134, 203], 'POSTER 2'), 'mock-poster-2.png', 'image/png', 'Mock poster 2'),
];
$pdfBytes = "%PDF-1.1\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";
$pdf = mock_attach('file-pdf-1', $pdfBytes, 'mock-document.pdf', 'application/pdf', 'Mock document (placeholder PDF)');
$url = fn($id) => $id ? wp_get_attachment_url($id) : '';

/* ---------- 3. posts: realistic PLACEHOLDER mirroring the audited division2 blog ---------- */
// Shape taken from the audit of the real site (counts, date windows, body length, images, files, empty bodies, views).
// All titles and text below are INVENTED placeholders; nothing is copied from the real site. Safe to re-run (keys two-post-N).
// First-generation mock posts (key post-N) are moved to the Trash, not deleted.
foreach (get_posts(['post_type' => 'post', 'post_status' => 'publish', 'meta_key' => '_mock_data', 'meta_value' => 1, 'numberposts' => -1]) as $old) {
    if (preg_match('/^post-\d+$/', (string) get_post_meta($old->ID, '_mock_key', true))) { wp_trash_post($old->ID); $stats['trashed']++; }
}
wp_update_user(['ID' => $admin, 'display_name' => 'Admin']);   // the real site shows the author "Admin" on every post

$profile = [ // cid => [count, from, to, avg body chars, max images, share with a file, share with empty body, title patterns (%d = running number)]
    201 => [16, '2024-02-14', '2024-09-09', 1670, 3, .50, .06, ['ตัวชี้วัดที่ 9 การเปิดเผยข้อมูล: O%d ข้อมูลตามเกณฑ์การประเมิน ITA', 'ตัวชี้วัดที่ 9: เอกสารประกอบการเปิดเผยข้อมูลสาธารณะ ชุดที่ %d']],
    229 => [11, '2026-07-01', '2026-07-31', 1472, 0, .18, .10, ['คุณธรรม ความโปร่งใส: แนวทางป้องกันการทุจริต หัวข้อที่ %d', 'มาตรการส่งเสริมความโปร่งใสในการปฏิบัติงาน ฉบับที่ %d']],
    212 => [10, '2024-01-23', '2026-04-20', 810, 3, .20, .00, ['ประชาสัมพันธ์: ข้อมูลการให้บริการประชาชน ครั้งที่ %d', 'ข่าวประชาสัมพันธ์ทั่วไป: การเปลี่ยนแปลงการให้บริการ ฉบับที่ %d']],
    202 => [10, '2025-11-11', '2026-09-18', 3148, 10, .00, .00, ['ผลการปฏิบัติ: การดำเนินกิจกรรมของหน่วยงาน ครั้งที่ %d/2569', 'ผลการปฏิบัติงานประจำเดือน: รายงานฉบับที่ %d']],
    216 => [8, '2024-02-14', '2024-02-14', 50, 1, .25, .50, ['ตัวชี้วัดที่ 10 การป้องกันการทุจริต: O%d']],
    224 => [8, '2026-04-24', '2026-09-18', 3450, 8, .00, .00, ['ภารกิจผู้บังคับบัญชา: ตรวจเยี่ยมการปฏิบัติงานของหน่วย ครั้งที่ %d/2569', 'ภารกิจผู้บังคับบัญชา: ร่วมกิจกรรมของหน่วยงาน ครั้งที่ %d']],
    198 => [4, '2024-01-01', '2026-07-10', 4451, 2, .00, .00, ['มาตรฐาน ITA: การประเมินคุณธรรมและความโปร่งใส ประจำปีงบประมาณ 256%d']],
    214 => [3, '2024-03-11', '2025-03-25', 774, 2, .33, .00, ['ผลการปฏิบัติ ITA: สรุปผลการดำเนินงาน ครั้งที่ %d']],
    223 => [3, '2026-04-20', '2026-04-20', 0, 1, .00, .00, ['ประชุมบริหาร บก.ตม.2 ครั้งที่ %d/2569']],
    227 => [3, '2026-07-10', '2026-07-10', 232, 1, .00, .00, ['ข้อมูลพื้นฐานของหน่วยงาน: หัวข้อที่ %d']],
    210 => [2, '2024-03-06', '2025-04-29', 3137, 1, 1.00, .00, ['กฎหมายและระเบียบ: สรุปสาระสำคัญ ฉบับที่ %d']],
];
$TH = ['(ข้อความตัวอย่างสำหรับทดสอบเลย์เอาต์ ไม่ใช่เนื้อหาจริงของหน่วยงาน)', 'หน่วยงานดำเนินการตามแผนปฏิบัติราชการประจำปีงบประมาณอย่างต่อเนื่องและเป็นไปตามเป้าหมายที่กำหนด',
       'เจ้าหน้าที่ปฏิบัติหน้าที่ด้วยความซื่อสัตย์สุจริต โปร่งใส และสามารถตรวจสอบได้ในทุกขั้นตอน', 'การประชุมครั้งนี้มีวัตถุประสงค์เพื่อติดตามผลการดำเนินงานและแก้ไขปัญหาอุปสรรคร่วมกัน',
       'ผู้บังคับบัญชามอบนโยบายให้ข้าราชการตำรวจในสังกัดให้บริการประชาชนอย่างเต็มกำลังความสามารถ', 'ขอเชิญชวนประชาชนและผู้ประกอบการตรวจสอบข้อมูลและขั้นตอนการให้บริการได้ที่เว็บไซต์ของหน่วยงาน',
       'ที่ประชุมรับทราบรายงานความก้าวหน้าและมีมติให้ดำเนินการในส่วนที่เกี่ยวข้องต่อไป', 'การเผยแพร่ข้อมูลเป็นไปตามหลักเกณฑ์ที่กำหนดเพื่อให้ประชาชนเข้าถึงข้อมูลได้โดยสะดวก',
       'หน่วยงานให้ความสำคัญกับการป้องกันและปราบปรามการทุจริตตามมาตรฐานคุณธรรมและความโปร่งใส', 'ผลการดำเนินงานในช่วงที่ผ่านมาอยู่ในเกณฑ์ที่น่าพอใจ และจะพัฒนาให้ดียิ่งขึ้นอย่างต่อเนื่อง',
       'ประชาชนสามารถติดต่อสอบถามข้อมูลเพิ่มเติมได้ในวันและเวลาราชการตามช่องทางที่กำหนด', 'เอกสารที่เกี่ยวข้องแนบมาพร้อมประกาศนี้เพื่อประกอบการพิจารณาของผู้สนใจ'];
$thaiParas = function ($target) use ($TH) {
    $out = []; $len = 0;
    while ($len < $target) { $p = $TH[mt_rand(0, count($TH) - 1)] . ' ' . $TH[mt_rand(0, count($TH) - 1)]; $out[] = $p; $len += mb_strlen($p); }
    return $out;
};
$gauss = function () { $u = mt_rand(1, 10000) / 10001; $v = mt_rand(1, 10000) / 10001; return sqrt(-2 * log($u)) * cos(2 * M_PI * $v); };
$fig = fn($id) => $id ? '<figure class="wp-block-image"><img src="' . esc_url($url($id)) . '" alt="ภาพประกอบตัวอย่าง"/></figure>' . "\n" : '';

$n = 0;
foreach ($profile as $cid => [$count, $from, $to, $avg, $maxImgs, $fileShare, $emptyShare, $patterns]) {
    for ($k = 1; $k <= $count; $k++) {
        $n++; $key = "two-post-$n";
        if (mock_find($key, 'post')) { $stats['skipped']++; continue; }
        $ts = mt_rand(strtotime($from . ' 08:00:00'), strtotime($to . ' 16:00:00')); $date = date('Y-m-d H:i:s', $ts);
        $isEmpty = mt_rand(1, 1000) / 1000 <= $emptyShare;
        $body = ''; $type = 'text';
        if ($isEmpty) {
            if ($maxImgs > 0 && mt_rand(0, 1)) { $body = $fig($poster[$n % 2]); $type = 'poster'; } else { $type = 'empty'; }
        } else {
            $target = $avg > 0 ? min(8900, max(60, (int) ($avg * (0.4 + mt_rand(0, 120) / 100)))) : 0;
            $paras = $target ? $thaiParas($target) : [];
            $imgN = $maxImgs > 0 ? mt_rand(1, $maxImgs) : 0;
            $ims = []; for ($i = 0; $i < $imgN; $i++) $ims[] = $fig($land[($n + $i) % 6]);
            $every = max(1, (int) ceil(max(1, count($paras)) / max(1, $imgN)));
            foreach ($paras as $pi => $p) {
                if ($pi === 1 && $target > 1500) $body .= '<h2>หัวข้อย่อยตัวอย่าง</h2>' . "\n";
                $body .= '<p>' . $p . "</p>\n";
                if ($ims && ($pi + 1) % $every === 0) $body .= array_shift($ims);
            }
            $body .= implode('', $ims);
            if (mt_rand(1, 100) <= 8) $body .= '<table><thead><tr><th>รายการ</th><th>จำนวน</th><th>หมายเหตุ</th></tr></thead><tbody><tr><td>ตัวอย่าง</td><td>0</td><td>-</td></tr><tr><td>ตัวอย่าง</td><td>0</td><td>-</td></tr></tbody></table>' . "\n";
            if (mt_rand(1, 1000) / 1000 <= $fileShare) $body .= '<p><a href="' . esc_url($url($pdf)) . '">ดาวน์โหลดเอกสารประกอบ (ไฟล์ PDF ตัวอย่าง)</a></p>' . "\n";
            $type = $imgN ? ($target ? 'images' : 'poster') : ($body && strpos($body, '.pdf') !== false ? 'file' : 'text');
        }
        $title = sprintf($patterns[($k - 1) % count($patterns)], $cid === 198 ? 66 + $k : $k);
        $pid = wp_insert_post(wp_slash([
            'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => "two-post-$n", 'post_content' => $body,
            'post_date' => $date, 'post_date_gmt' => get_gmt_from_date($date), 'post_author' => $admin, 'post_category' => [$catId[$cid]],
        ]), true);
        if (is_wp_error($pid)) { echo "post fail $n: " . $pid->get_error_message() . "\n"; continue; }
        update_post_meta($pid, '_mock_key', $key); update_post_meta($pid, '_mock_data', 1); update_post_meta($pid, '_mock_type', $type);
        update_post_meta($pid, '_mock_views', max(1, min(6280, (int) round(exp(4.4 + 1.6 * $gauss())))));   // real site: median 81, max 6,280 (WordPress has no view counter; kept as a custom field)
        if (strpos($body, '<img') !== false) set_post_thumbnail($pid, $type === 'poster' ? $poster[$n % 2] : $land[$n % 6]);
        $stats['posts']++;
    }
}

/* ---------- 4. static pages (the originals are single-poster pages) ---------- */
$pageDefs = [
    'home' => ['Home', ''], 'news' => ['News', ''],
    'vision-mission' => ['Vision / mission', 'poster'], 'commanders' => ['Commanders', 'poster'],
    'org-structure' => ['Organisation structure', 'poster'], 'powers-responsibilities' => ['Powers and areas of responsibility', 'poster'],
    'staffing' => ['Staffing', 'poster'], 'corruption-complaint' => ['Corruption complaint channel', 'poster-text'],
    'contact-us' => ['Contact us', 'contact'],
];
$pageId = [];
foreach ($pageDefs as $slug => [$title, $kind]) {
    if ($ex = mock_find("page-$slug", 'page')) { $pageId[$slug] = $ex; continue; }
    $content = '';
    if ($kind === 'poster' || $kind === 'poster-text') $content = $fig($poster[count($pageId) % 2]);
    if ($kind === 'poster-text') $content = '<p>' . $L[0] . '</p>' . "\n" . $content;
    if ($kind === 'contact') $content = '<p><strong>Example Division</strong><br>123 Example Road, Sample District, Sample Province 10000<br>Phone: 02-000-0000<br>Email: info@example.invalid</p>' . "\n" . '<p><em>Replace with real details. A Contact Form 7 form (name, surname, phone, email, message, privacy consent) goes below.</em></p>';
    $id = wp_insert_post(wp_slash(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_author' => $admin]), true);
    if (is_wp_error($id)) { echo "page fail $slug\n"; continue; }
    update_post_meta($id, '_mock_key', "page-$slug"); update_post_meta($id, '_mock_data', 1);
    $pageId[$slug] = $id; $stats['pages']++;
}
update_option('show_on_front', 'page');
update_option('page_on_front', $pageId['home']);
update_option('page_for_posts', $pageId['news']);

/* ---------- 5. main menu (mirrors the original navigation) ---------- */
$menu = wp_get_nav_menu_object('Main menu') ?: null;
if (!$menu) {
    $mid = wp_create_nav_menu('Main menu');
    $add = function ($args) use ($mid) { return wp_update_nav_menu_item($mid, 0, $args + ['menu-item-status' => 'publish']); };
    $pg = fn($t, $slug, $parent = 0) => $add(['menu-item-title' => $t, 'menu-item-object' => 'page', 'menu-item-object-id' => $pageId[$slug], 'menu-item-type' => 'post_type', 'menu-item-parent-id' => $parent]);
    $ct = fn($cid, $parent = 0) => $add(['menu-item-title' => $cats[$cid][0], 'menu-item-object' => 'category', 'menu-item-object-id' => $catId[$cid], 'menu-item-type' => 'taxonomy', 'menu-item-parent-id' => $parent]);
    $pg('Home', 'home');
    $about = $add(['menu-item-title' => 'About us', 'menu-item-url' => '#', 'menu-item-type' => 'custom']);
    foreach (['vision-mission' => 'Vision / mission', 'commanders' => 'Commanders', 'org-structure' => 'Organisation structure', 'powers-responsibilities' => 'Powers and areas', 'staffing' => 'Staffing'] as $s => $t) $pg($t, $s, $about);
    $ct(210);
    $news = $add(['menu-item-title' => 'News', 'menu-item-url' => get_permalink($pageId['news']), 'menu-item-type' => 'custom']);
    foreach ([224, 223, 205, 212, 202] as $c) $ct($c, $news);
    $ita = $add(['menu-item-title' => 'ITA standards', 'menu-item-url' => '#', 'menu-item-type' => 'custom']);
    foreach ([198, 201, 216, 214, 229] as $c) $ct($c, $ita);
    $pg('Corruption complaint', 'corruption-complaint', $ita);
    $pg('Contact', 'contact-us');   // last item is a plain link: a dropdown on the right-most item would stick out past the page edge
    $locs = array_keys(get_registered_nav_menus());
    if ($locs) { $set = get_theme_mod('nav_menu_locations', []); $set[$locs[0]] = $mid; set_theme_mod('nav_menu_locations', $set); }
    echo "menu created, location: " . ($locs[0] ?? 'none registered') . "\n";
} else {
    // menus made by an earlier version of this script had "Contact" as a dropdown: make it a plain link and move "Corruption complaint" under "ITA standards"
    $mid = $menu->term_id; $byTitle = [];
    foreach (wp_get_nav_menu_items($mid) as $it) $byTitle[$it->title][] = $it;
    $contact = $byTitle['Contact'][0] ?? null; $ita = $byTitle['ITA standards'][0] ?? null; $complaint = $byTitle['Corruption complaint'][0] ?? null; $contactUs = $byTitle['Contact us'][0] ?? null;
    if ($contact && $contact->type === 'custom' && $ita && $complaint && $contactUs && (int) $complaint->menu_item_parent === (int) $contact->ID) {
        wp_update_nav_menu_item($mid, $contact->ID, ['menu-item-title' => 'Contact', 'menu-item-object' => 'page', 'menu-item-object-id' => $pageId['contact-us'], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish']);
        wp_update_nav_menu_item($mid, $complaint->ID, ['menu-item-title' => 'Corruption complaint', 'menu-item-object' => 'page', 'menu-item-object-id' => $pageId['corruption-complaint'], 'menu-item-type' => 'post_type', 'menu-item-parent-id' => $ita->ID, 'menu-item-status' => 'publish']);
        wp_trash_post($contactUs->ID);   // the redundant "Contact us" child; trashed, not deleted
        echo "menu updated: Contact is now a plain link\n";
    }
}

/* ---------- 6. logo (optional: mount the folder holding logo_imm2_509x602.png at /assets) ---------- */
$logoFile = '/assets/logo_imm2_509x602.png';
if (file_exists($logoFile)) {
    $logo = mock_attach('logo', file_get_contents($logoFile), 'logo-imm2.png', 'image/png', 'Site logo');
    if ($logo) set_theme_mod('custom_logo', $logo);
    echo "logo set (attachment $logo)\n";
} else {
    echo "no logo file mounted at $logoFile (skipped)\n";
}

flush_rewrite_rules();
$stats['media'] = count(get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_mock_data', 'meta_value' => 1, 'fields' => 'ids', 'numberposts' => -1]));
echo "\nDONE " . json_encode($stats) . "\n";
echo 'GD: ' . (function_exists('imagecreatetruecolor') ? 'yes' : 'NO (solid-colour placeholders used)') . "\n";
foreach (get_categories(['hide_empty' => false, 'orderby' => 'name']) as $c) printf("  %-58s %d\n", $c->name, $c->count);
echo 'home page id=' . $pageId['home'] . "\n";
