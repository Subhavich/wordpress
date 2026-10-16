<?php
/**
 * Seeds placeholder ("mock") content that mirrors the structure of the Division 2 blog:
 *   - 14 categories, one per post
 *   - ~37 posts in the audited mix (text+images, text only, poster-only, text+file)
 *   - static pages (vision, commanders, structure ...) that are a single poster image
 *   - a main menu, a blank Home page, a News (blog index) page
 *   - optional site logo, if a file is mounted at /assets/logo_imm2_509x602.png
 *
 * Everything created is tagged with the meta key _mock_data=1 so it can be found and removed later.
 * Safe to run repeatedly (each item has a _mock_key; existing ones are skipped).
 *
 * Run (from the repo root):
 *   docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/seed.php --path=/var/www/html
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

/* ---------- 3. posts ---------- */
$L = ['Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore.',
      'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo.',
      'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
      'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim.',
      'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.',
      'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur.',
      'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.',
      'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum.'];
$paras = function ($n) use ($L) { $o = ''; for ($i = 0; $i < $n; $i++) $o .= '<p>' . $L[mt_rand(0, 7)] . ' ' . $L[mt_rand(0, 7)] . "</p>\n"; return $o; };
$fig = fn($id) => $id ? '<figure class="wp-block-image"><img src="' . esc_url($url($id)) . '" alt="Placeholder image"/></figure>' . "\n" : '';
$pickType = function () { $r = mt_rand(1, 100); return $r <= 68 ? 'images' : ($r <= 80 ? 'text' : ($r <= 90 ? 'poster' : 'file')); };
$pickDate = function () {
    $r = mt_rand(1, 100); $y = $r <= 2 ? 2022 : ($r <= 4 ? 2023 : ($r <= 44 ? 2024 : ($r <= 54 ? 2025 : 2026)));
    $m = mt_rand(1, $y === 2026 ? 9 : 12); $d = mt_rand(1, 28);
    return sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $m, $d, mt_rand(8, 16), mt_rand(0, 59));
};

$n = 0;
foreach ($cats as $cid => [$name, $count]) {
    for ($k = 1; $k <= $count; $k++) {
        $n++;
        $key = "post-$n";
        if (mock_find($key, 'post')) { $stats['skipped']++; continue; }
        $type = $cid === 205 || $cid === 206 ? 'file' : ($cid === 203 ? 'text' : $pickType());
        $body = '';
        if ($cid === 203) $body .= '<p><em>Video placeholder: the original category held short intro videos (YouTube embed).</em></p>' . "\n";
        switch ($type) {
            case 'poster': $body .= $fig($poster[$n % 2]); break;
            case 'file':   $body .= $paras(1) . '<p><a href="' . esc_url($url($pdf)) . '">Download placeholder document (PDF)</a></p>' . "\n"; break;
            case 'text':   $body .= '<h2>Heading placeholder</h2>' . "\n" . $paras(mt_rand(2, 4)); break;
            default:       // text + images (the most common type; ~10 images on long posts)
                $body .= $paras(2);
                $imgs = mt_rand(1, 4); for ($i = 0; $i < $imgs; $i++) $body .= $fig($land[($n + $i) % 6]);
                $body .= $paras(1);
        }
        if (mt_rand(1, 100) <= 10) $body .= '<table><thead><tr><th>Column A</th><th>Column B</th><th>Column C</th></tr></thead><tbody><tr><td>Sample</td><td>Sample</td><td>Sample</td></tr><tr><td>Sample</td><td>Sample</td><td>Sample</td></tr></tbody></table>' . "\n";
        $date = $pickDate();
        $pid = wp_insert_post(wp_slash([
            'post_type' => 'post', 'post_status' => 'publish', 'post_title' => sprintf('Mock post %02d (%s)', $n, $name),
            'post_name' => "mock-post-$n", 'post_content' => $body, 'post_date' => $date, 'post_date_gmt' => get_gmt_from_date($date),
            'post_author' => $admin, 'post_category' => [$catId[$cid]],
        ]), true);
        if (is_wp_error($pid)) { echo "post fail $n: " . $pid->get_error_message() . "\n"; continue; }
        update_post_meta($pid, '_mock_key', $key);
        update_post_meta($pid, '_mock_data', 1);
        update_post_meta($pid, '_mock_type', $type);
        update_post_meta($pid, '_mock_views', $n % 7 === 0 ? mt_rand(1000, 6000) : mt_rand(1, 300));
        if ($type === 'images') set_post_thumbnail($pid, $land[$n % 6]);
        if ($type === 'poster') set_post_thumbnail($pid, $poster[$n % 2]);
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
    $contact = $add(['menu-item-title' => 'Contact', 'menu-item-url' => '#', 'menu-item-type' => 'custom']);
    $pg('Contact us', 'contact-us', $contact); $pg('Corruption complaint', 'corruption-complaint', $contact);
    $locs = array_keys(get_registered_nav_menus());
    if ($locs) { $set = get_theme_mod('nav_menu_locations', []); $set[$locs[0]] = $mid; set_theme_mod('nav_menu_locations', $set); }
    echo "menu created, location: " . ($locs[0] ?? 'none registered') . "\n";
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
