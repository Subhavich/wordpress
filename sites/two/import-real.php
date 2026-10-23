<?php
/**
 * SITE TWO: imports the REAL blog from the backup (the import pack made by sites/two/extract-backup.py) into WordPress.
 *
 *   - 76 news/ITA articles as POSTS in their real categories (slug cat-<cid>) with generated tags, real titles, dates, view counts, bodies
 *   - 13 static articles (about, contact, commanders, laws...) as PAGES (pack field kind=page), no categories or tags
 *   - article images and attached PDF/DOCX/XLSX go into the Media Library; body links/<img> are rewritten to them
 *   - the first image of a post becomes its featured image (the original hid it, the new design's news cards show it)
 *   - the invented placeholder posts (keys two-post-N / post-N) are moved to the trash, so real and fake never mix
 *   - safe to re-run: an article that is already imported is skipped (meta _real_nid), media is looked up by _real_src
 *
 * Env: IMPORT_STATUS=publish|draft   (default publish; use draft on a live site, then review)
 *      KEEP_MOCK=1                   keep the placeholder posts
 *
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" -v "<pack folder>:/pack:ro" --entrypoint wp wpcli eval-file /sites/two/import-real.php --path=/var/www/html
 */
@ini_set('memory_limit', '1024M'); @set_time_limit(0);
require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
$pack = getenv('PACK_DIR') ?: '/pack';
if (!is_file("$pack/posts.json")) { echo "No import pack at $pack (run sites/two/extract-backup.py and mount the folder as /pack).\n"; return; }
$posts = json_decode(file_get_contents("$pack/posts.json"), true);
$status = getenv('IMPORT_STATUS') ?: 'publish';
$admin = get_users(['role' => 'administrator', 'number' => 1]); $adminId = $admin ? $admin[0]->ID : 1;
wp_set_current_user($adminId);                       // keeps iframes/tables in the body (kses would strip them for "no user")

/* categories: real names and ids from the old CMS (the seed creates the same ones) */
$catId = [];
foreach ($posts as $p) {
    if ($p['kind'] !== 'post' || isset($catId[$p['cid']]) || !$p['cid']) continue;
    $t = get_term_by('slug', 'cat-' . $p['cid'], 'category') ?: get_term_by('name', $p['category'], 'category');
    if (!$t) { $r = wp_insert_term($p['category'], 'category', ['slug' => 'cat-' . $p['cid']]); $t = is_wp_error($r) ? null : get_term($r['term_id'], 'category'); }
    if ($t) $catId[$p['cid']] = (int) $t->term_id;
}

/* the placeholder posts go to the trash */
$trashed = 0;
if (!getenv('KEEP_MOCK')) {
    foreach (get_posts(['post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => '_mock_key']) as $m) {
        $k = (string) get_post_meta($m->ID, '_mock_key', true);
        if (preg_match('/^(two-post-|post-)\d+/', $k)) { wp_trash_post($m->ID); $trashed++; }
    }
}

/* media library: one attachment per backup file, reused across posts */
$attach = function (string $kind, string $name, int $parent) use ($pack): ?int {
    $key = "$kind/$name";
    $q = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_real_src', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1]);
    if ($q) return (int) $q[0];
    $src = "$pack/" . ($kind === 'media' ? 'media' : 'files') . "/$name";
    if (!is_file($src)) return null;
    $tmp = wp_tempnam($name); copy($src, $tmp);
    $id = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], $parent);
    if (is_wp_error($id)) { @unlink($tmp); echo "  media fail $key: " . $id->get_error_message() . "\n"; return null; }
    update_post_meta($id, '_real_src', $key);
    return (int) $id;
};

$converted = 0; $made = 0; $skipped = 0; $imgs = 0; $docs = 0; $feat = 0; $byNid = [];
foreach ($posts as $p) {
    $kind = $p['kind'];
    $ex = get_posts(['post_type' => ['post', 'page'], 'post_status' => 'any', 'meta_key' => '_real_nid', 'meta_value' => $p['nid'], 'fields' => 'ids', 'numberposts' => 1]);
    if ($ex) {
        $byNid[$p['nid']] = (int) $ex[0]; $skipped++;
        if (get_post_type($ex[0]) !== $kind) {                       // imported by an older version as a post: turn it into a page
            wp_update_post(['ID' => $ex[0], 'post_type' => $kind, 'post_name' => ($kind === 'page' ? 'page-' : 'news-') . $p['nid']]);
            if ($kind === 'page') { wp_set_object_terms($ex[0], [], 'category'); wp_set_object_terms($ex[0], [], 'post_tag'); }
            $converted++;
        }
        continue;
    }
    $when = ($p['date'] ?: '2024-01-01') . sprintf(' 08:%02d:00', $p['nid'] % 60);   // the CMS stored no time of day
    $id = wp_insert_post(['post_type' => $kind, 'post_status' => 'draft', 'post_title' => $p['title'], 'post_content' => '', 'post_author' => $adminId,
        'post_date' => $when, 'post_date_gmt' => get_gmt_from_date($when), 'post_name' => ($kind === 'page' ? 'page-' : 'news-') . $p['nid']]
        + ($kind === 'post' ? ['post_category' => isset($catId[$p['cid']]) ? [$catId[$p['cid']]] : [], 'tags_input' => $p['tags']] : []), true);
    if (is_wp_error($id)) { echo "  post fail nid{$p['nid']}: " . $id->get_error_message() . "\n"; continue; }
    $body = $p['body_html']; $first = null;
    $body = preg_replace_callback('~@media/([^"\')\s]+)~', function ($m) use ($attach, $id, &$imgs, &$first) {
        $a = $attach('media', $m[1], $id); if (!$a) return $m[0]; $imgs++; $first = $first ?: $a; return wp_get_attachment_url($a);
    }, $body);
    $body = preg_replace_callback('~@file/([^"\')\s]+)~', function ($m) use ($attach, $id, &$docs) {
        $a = $attach('file', $m[1], $id); if (!$a) return $m[0]; $docs++; return wp_get_attachment_url($a);
    }, $body);
    wp_update_post(['ID' => $id, 'post_content' => wp_slash($body), 'post_status' => $status]);
    update_post_meta($id, '_real_nid', $p['nid']); update_post_meta($id, '_real_views', $p['views']); update_post_meta($id, '_mock_views', $p['views']);
    if ($first) { set_post_thumbnail($id, $first); $feat++; }
    $byNid[$p['nid']] = $id; $made++;
}

/* second pass: links between articles / categories that the crawler had rewritten */
$firstPage = [];
foreach ($posts as $p) if ($p['kind'] === 'page' && isset($byNid[$p['nid']])) $firstPage += [$p['cid'] => $byNid[$p['nid']]];
$relinked = 0;
foreach ($byNid as $nid => $id) {
    $c = get_post_field('post_content', $id);
    $n = preg_replace_callback('~@(post|cat)/(\d+)~', function ($m) use ($byNid, $catId, $firstPage) {
        if ($m[1] === 'post') return isset($byNid[(int) $m[2]]) ? get_permalink($byNid[(int) $m[2]]) : '#';
        if (isset($catId[(int) $m[2]])) return get_category_link($catId[(int) $m[2]]);
        return isset($firstPage[(int) $m[2]]) ? get_permalink($firstPage[(int) $m[2]]) : '#';       // a category that became pages
    }, $c);
    if ($n !== $c) { wp_update_post(['ID' => $id, 'post_content' => wp_slash($n)]); $relinked++; }
}
if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); }
echo "real import: $made created ($skipped already there, $converted converted post->page), $imgs images + $docs files in the media library, $feat featured images, $relinked posts re-linked, $trashed placeholder posts trashed, status=$status\n";
foreach (get_categories(['hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC']) as $c) printf("  %-58s %d\n", $c->name, $c->count);
