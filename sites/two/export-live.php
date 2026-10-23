<?php
/**
 * SITE TWO: builds the two Elementor files you IMPORT into the live site (no custom code runs there):
 *
 *   design-system.kit.zip   Elementor kit with ONLY the design system: 4+10 Global Colors, 4+13 Global Fonts, 1280 px content width
 *   site-two.live.json      the /site-two page as an Elementor template (header + 11 sections + footer, native widgets only),
 *                           re-aimed at the live site: logo/portrait -> the live Media Library, local links -> live links, News grid -> live category
 *
 * It needs the ids of the live site, which `import-live.py --structure` writes to <pack>/live-ids-<site>.json.
 *
 *   docker compose run --rm -u 33 -v "${PWD}/sites:/sites:ro" -v "<pack folder>:/pack" -e LIVE_IDS=/pack/live-ids-division2_immigration_go_th.json \
 *       --entrypoint wp wpcli eval-file /sites/two/export-live.php --path=/var/www/html
 * Output goes to <pack>/kit/.
 */
wp_set_current_user(1);
$idsFile = getenv('LIVE_IDS') ?: '/pack/live-ids-division2_immigration_go_th.json';
if (!is_file($idsFile)) { echo "Missing $idsFile (run import-live.py with --structure first).\n"; return; }
$live = json_decode(file_get_contents($idsFile), true);
$site = rtrim($live['site'], '/'); $local = untrailingslashit(home_url());
$out = (getenv('KIT_OUT') ?: '/pack/kit'); if (!is_dir($out)) mkdir($out, 0775, true);

/* ---- 1. the page, re-aimed at the live site ---- */
$pageId = get_posts(['post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'two-page-site', 'fields' => 'ids', 'numberposts' => 1])[0] ?? 0;
if (!$pageId) { echo "Site two page not found. Run the build first.\n"; return; }
$data = json_decode(get_post_meta($pageId, '_elementor_data', true), true);
$logo = $live['assets']['logo-imm2.png'] ?? null; $portrait = $live['assets']['portrait-placeholder.png'] ?? null;
if (!$logo || !$portrait) { echo "live-ids has no logo/portrait: run import-live.py with --structure.\n"; return; }
$newsCat = get_term_by('slug', 'cat-202', 'category');
$n = ['image' => 0, 'link' => 0, 'category' => 0, 'other' => 0];
$map = function ($x) use (&$map, $site, $local, $live, $logo, $portrait, $newsCat, &$n) {
    if (!is_array($x)) return $x;
    if (isset($x['url']) && is_string($x['url']) && array_key_exists('id', $x)) {                    // an Elementor media value {url, id}
        $file = basename((string) parse_url($x['url'], PHP_URL_PATH));                              // matched by file name (WordPress may add -1, -2)
        if (preg_match('~^logo-imm2(-\d+)?\.png$~', $file)) { $x['url'] = $logo['url']; $x['id'] = $logo['id']; $n['image']++; }
        elseif (preg_match('~^(mock-portrait|portrait-placeholder)(-\d+)?\.png$~', $file)) { $x['url'] = $portrait['url']; $x['id'] = $portrait['id']; $n['image']++; }
    }
    foreach ($x as $k => $v) {
        if (is_string($v) && strpos($v, $local) !== false) {
            if (rtrim($v, '/') === $local . '/org-structure') { $x[$k] = $site . '/?page_id=' . ($live['pages']['299'] ?? 0); $n['link']++; }
            elseif ($newsCat && rtrim($v, '/') === untrailingslashit(get_category_link($newsCat->term_id))) { $x[$k] = $site . '/?cat=' . ($live['cats']['202'] ?? 0); $n['link']++; }
            else { $x[$k] = str_replace($local, $site, $v); $n['other']++; }
        } elseif ($k === 'category_ids' && is_array($v)) { $x[$k] = array_map(fn($i) => (string) ($live['cats']['202'] ?? $i), $v); $n['category']++; }
        elseif (is_array($v)) $x[$k] = $map($v);
    }
    return $x;
};
$data = $map($data);
$left = preg_match_all('~localhost~i', json_encode($data), $m);
$tpl = ['content' => $data, 'page_settings' => ['template' => 'elementor_canvas'], 'version' => '0.4', 'title' => 'Site two: division2', 'type' => 'page'];
file_put_contents("$out/site-two.live.json", json_encode($tpl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "page template: site-two.live.json  (images {$n['image']}, key links {$n['link']}, news category {$n['category']}, other urls {$n['other']}, leftover 'localhost' strings: $left)\n";

/* ---- 2. the design-system kit (Global Colors, Global Fonts, content width only) ---- */
$req = new WP_REST_Request('POST', '/elementor/v1/import-export-customization/export');
$req->set_header('content_type', 'application/json');
$req->set_body(json_encode(['include' => ['settings'], 'kitInfo' => ['title' => 'Division 2 design system', 'description' => 'Global colours, global fonts and 1280 px content width for the division2 sites', 'source' => 'local']]));
$res = rest_do_request($req); $d = $res->get_data()['data'] ?? null;
if (!$d || empty($d['file'])) { echo 'Kit export failed: ' . json_encode($res->get_data()) . "\n"; return; }
file_put_contents("$out/design-system.kit.zip", base64_decode($d['file']));
echo 'design system kit: design-system.kit.zip (' . filesize("$out/design-system.kit.zip") . " bytes)\n";
