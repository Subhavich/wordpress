<?php
/**
 * SITE ONE: SIMULATED news for the two categories the real site has but never filled:
 *   ข่าวประชาสัมพันธ์ (press releases, slug press-releases) and ผลการจับกุม (arrest results, slug arrest-results).
 * 6 invented posts each, so the layout can be seen with data. Every card's excerpt says it is sample data ("ข้อมูลตัวอย่าง"),
 * every post carries _mock_data=1. LOCAL / TEST USE ONLY: do not run this on a live site.  Safe to re-run (posts are found by _mock_key).
 *
 *   docker compose run --rm -u 33 -v "${PWD}/sites:/sites:ro" --entrypoint wp wpcli eval-file /sites/one/seed-news.php --path=/var/www/html
 *   remove them again:  wp post delete $(wp post list --meta_key=_mock_data --meta_value=1 --post_type=post --format=ids) --force   (only the one-news-* ones are made here)
 */
require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
$admin = get_users(['role' => 'administrator', 'number' => 1])[0]->ID;
$find = fn($key) => ($q = get_posts(['post_type' => 'post', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1])) ? (int) $q[0] : 0;

$cats = ['press-releases' => 'ข่าวประชาสัมพันธ์', 'arrest-results' => 'ผลการจับกุม'];
$catId = [];
foreach ($cats as $slug => $name) {
    $t = get_term_by('slug', $slug, 'category') ?: null;
    if (!$t) { $r = wp_insert_term($name, 'category', ['slug' => $slug]); $t = is_wp_error($r) ? null : get_term($r['term_id'], 'category'); }
    if ($t) $catId[$slug] = (int) $t->term_id;
}

// [category slug, title, body paragraphs, date]
$news = [
    ['press-releases', 'ประชาสัมพันธ์ช่องทางการติดต่อราชการทางออนไลน์ของฝ่ายอำนวยการ', ['ฝ่ายอำนวยการ บก.ตม.2 ขอประชาสัมพันธ์ช่องทางการติดต่อราชการ ทั้งทางโทรศัพท์ จดหมายอิเล็กทรอนิกส์ และแบบฟอร์มติดต่อกลับบนเว็บไซต์ เพื่ออำนวยความสะดวกแก่ประชาชนและหน่วยงานที่เกี่ยวข้อง'], '2026-10-20'],
    ['press-releases', 'ประกาศเวลาทำการและการให้บริการประชาชนในช่วงวันหยุดนักขัตฤกษ์', ['แจ้งให้ทราบว่าในช่วงวันหยุดนักขัตฤกษ์ หน่วยงานจะเปิดให้บริการเฉพาะงานเร่งด่วน ส่วนงานบริการอื่นจะเปิดทำการตามปกติในวันทำการถัดไป'], '2026-10-12'],
    ['press-releases', 'ขอเชิญตอบแบบสำรวจความพึงพอใจต่อการให้บริการของหน่วยงาน ประจำปีงบประมาณ พ.ศ. 2569', ['หน่วยงานขอเชิญผู้ใช้บริการร่วมตอบแบบสำรวจความพึงพอใจ เพื่อนำผลไปปรับปรุงคุณภาพการให้บริการให้ดียิ่งขึ้น'], '2026-10-05'],
    ['press-releases', 'แจ้งปรับปรุงช่องทางการรับเรื่องร้องเรียนและข้อเสนอแนะ', ['เพื่อให้การรับเรื่องร้องเรียนเป็นไปอย่างรวดเร็วและโปร่งใส หน่วยงานได้ปรับปรุงช่องทางการรับเรื่อง และจะแจ้งผลการดำเนินการกลับให้ผู้ร้องทราบ'], '2026-09-28'],
    ['press-releases', 'อบรมเชิงปฏิบัติการเพื่อเพิ่มประสิทธิภาพงานสารบรรณอิเล็กทรอนิกส์', ['ฝ่ายอำนวยการจัดอบรมเชิงปฏิบัติการให้แก่ข้าราชการตำรวจและเจ้าหน้าที่ในสังกัด เพื่อพัฒนาทักษะการใช้งานระบบสารบรรณอิเล็กทรอนิกส์'], '2026-09-18'],
    ['press-releases', 'ประชาสัมพันธ์การใช้งานระบบแจ้งที่พักอาศัยของคนต่างด้าวออนไลน์', ['ขอเชิญผู้ประกอบการที่พักและเจ้าบ้านใช้งานระบบแจ้งที่พักอาศัยของคนต่างด้าวทางออนไลน์ ซึ่งสะดวก รวดเร็ว และลดภาระการเดินทาง'], '2026-09-09'],
    ['arrest-results', 'สรุปผลการตรวจสอบและดำเนินคดีผู้ฝ่าฝืนกฎหมายคนเข้าเมือง ประจำสัปดาห์', ['เจ้าหน้าที่ตรวจสอบและดำเนินการกับผู้ฝ่าฝืนกฎหมายคนเข้าเมืองตามอำนาจหน้าที่ รายละเอียดจำนวนและผลการดำเนินการเป็นข้อมูลตัวอย่างสำหรับทดสอบการแสดงผล'], '2026-10-22'],
    ['arrest-results', 'ผลการปฏิบัติการร่วมกับหน่วยงานที่เกี่ยวข้องในพื้นที่รับผิดชอบ', ['การปฏิบัติการร่วมระหว่างหน่วยงานเพื่อป้องกันและปราบปรามการกระทำผิด เนื้อหาในข่าวนี้เป็นข้อความตัวอย่างสำหรับทดสอบการแสดงผล'], '2026-10-14'],
    ['arrest-results', 'ตรวจสอบสถานประกอบการและที่พักอาศัยของคนต่างด้าวตามแผนประจำเดือน', ['เจ้าหน้าที่ออกตรวจสอบตามแผนประจำเดือนเพื่อให้การแจ้งที่พักและการอยู่ในราชอาณาจักรเป็นไปตามกฎหมาย ข้อมูลตัวเลขเป็นตัวอย่างเท่านั้น'], '2026-10-06'],
    ['arrest-results', 'สถิติผลการปฏิบัติงานด้านการป้องกันและปราบปราม ประจำไตรมาส', ['สรุปสถิติผลการปฏิบัติงานด้านการป้องกันและปราบปรามประจำไตรมาส ข้อมูลในโพสต์นี้เป็นข้อมูลตัวอย่างสำหรับทดสอบการแสดงผลเท่านั้น'], '2026-09-30'],
    ['arrest-results', 'ผลการจัดการคดีที่เกี่ยวกับการแจ้งข้อมูลที่พักอาศัยไม่ถูกต้อง', ['หน่วยงานดำเนินการตามอำนาจหน้าที่ต่อกรณีที่พบการแจ้งข้อมูลไม่ถูกต้อง รายละเอียดเป็นข้อความตัวอย่าง'], '2026-09-21'],
    ['arrest-results', 'รายงานผลการตรวจสอบเอกสารเดินทางของผู้เดินทางเข้าออกประเทศ', ['สรุปผลการตรวจสอบเอกสารเดินทางตามภารกิจของหน่วยงาน เนื้อหาและตัวเลขเป็นข้อมูลตัวอย่างสำหรับทดสอบการแสดงผล'], '2026-09-11'],
];

$tint = [[16, 42, 82], [34, 60, 110], [12, 70, 92], [70, 52, 24], [52, 40, 82], [24, 78, 70]];
$made = 0;
foreach ($news as $i => [$cat, $title, $paras, $date]) {
    $key = 'one-news-' . ($i + 1);
    if ($find($key)) continue;
    $when = $date . ' 09:' . sprintf('%02d', 10 + $i) . ':00';
    $excerpt = 'ข้อมูลตัวอย่าง: ' . $paras[0];
    $id = wp_insert_post(wp_slash(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => $title, 'post_author' => $admin, 'post_date' => $when, 'post_date_gmt' => get_gmt_from_date($when),
        'post_category' => [$catId[$cat]], 'post_excerpt' => $excerpt, 'post_content' => '<p>' . implode('</p><p>', array_merge($paras, ['(ข้อมูลตัวอย่างสำหรับทดสอบการแสดงผล ไม่ใช่ข่าวจริง)'])) . '</p>']));
    if (is_wp_error($id)) { echo "fail $key\n"; continue; }
    update_post_meta($id, '_mock_key', $key); update_post_meta($id, '_mock_data', 1);
    // placeholder cover image (GD): a navy gradient with the size printed on it
    if (function_exists('imagecreatetruecolor')) {
        $w = 1200; $h = 740; $im = imagecreatetruecolor($w, $h); [$r, $g, $b] = $tint[$i % 6];
        for ($y = 0; $y < $h; $y++) { $k = $y / $h; imagefilledrectangle($im, 0, $y, $w, $y, imagecolorallocate($im, (int) ($r + 40 * $k), (int) ($g + 40 * $k), (int) ($b + 50 * $k))); }
        imagestring($im, 5, 520, 360, 'NEWS COVER 1200x740', imagecolorallocate($im, 255, 255, 255));
        ob_start(); imagepng($im); $png = ob_get_clean(); imagedestroy($im);
        $up = wp_upload_bits("mock-news-cover-" . ($i + 1) . '.png', null, $png);
        if (empty($up['error'])) { $att = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Mock news cover ' . ($i + 1), 'post_status' => 'inherit'], $up['file'], $id); wp_update_attachment_metadata($att, wp_generate_attachment_metadata($att, $up['file'])); update_post_meta($att, '_mock_data', 1); set_post_thumbnail($id, $att); }
    }
    $made++;
}
foreach ($catId as $slug => $cid) { clean_term_cache($cid, 'category'); $t = get_term($cid, 'category'); echo "  {$t->name}: {$t->count} posts\n"; }
echo "DONE simulated news: $made posts created\n";
