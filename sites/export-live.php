<?php
/**
 * Builds the Elementor files you IMPORT into a LIVE site (no custom code runs there), from the page you built locally.
 *
 *   <site>.page.kit.zip     a one-page Elementor kit: import it with Elementor's own importer (Tools > Import/Export Kit, or its REST routes). Creates the page.
 *   <site>.template.json    the same page as an Elementor template (Templates > Saved Templates > Import): the manual fallback.
 *   design-system.kit.zip   (foundry/kit/) the Global Colors / Fonts kit: import it ONCE per live site, before the page.
 *
 * The page is re-aimed at the live site with the ids that `sites/two/import-live.py --structure` wrote to <pack>/live-ids-<site>.json:
 *   logo + portrait -> the live Media Library, local links -> live links (?page_id=), News category -> the live category, menu -> "main-menu".
 *
 *   docker compose run --rm -u 33 -v "${PWD}/sites:/sites:ro" -v "<pack folder>:/pack" -e SITE_KEY=one -e LIVE_IDS=/pack/live-ids-<site>.json -e KIT_OUT=/pack/kit \
 *       --entrypoint wp wpcli eval-file /sites/export-live.php --path=/var/www/html
 *   SITE_KEY = one (admin-division2, page /site-one/) | two (division2, page /site-two/)
 */
wp_set_current_user(1);
$key = getenv('SITE_KEY') ?: 'two';
$cfg = [
    'one' => ['mock' => 'one-page-site', 'title' => 'Site one', 'slug' => 'site-one', 'file' => 'site-one',
              'links' => ['/site-one-structure' => ['page', '126'], '/site-one-commanders' => ['page', '127']], 'news' => null],
    'two' => ['mock' => 'two-page-site', 'title' => 'Site two', 'slug' => 'site-two', 'file' => 'site-two',
              'links' => ['/org-structure' => ['page', '299']], 'news' => '202'],
][$key] ?? null;
if (!$cfg) { echo "SITE_KEY must be one or two.\n"; return; }
$idsFile = getenv('LIVE_IDS') ?: '';
if (!is_file($idsFile)) { echo "Missing LIVE_IDS file ($idsFile). Run import-live.py with --structure first.\n"; return; }
$live = json_decode(file_get_contents($idsFile), true);
$site = rtrim($live['site'], '/'); $local = untrailingslashit(home_url());
$out = getenv('KIT_OUT') ?: '/pack/kit'; if (!is_dir($out)) mkdir($out, 0775, true);

$pageId = get_posts(['post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $cfg['mock'], 'fields' => 'ids', 'numberposts' => 1])[0] ?? 0;
if (!$pageId) { echo "Local page {$cfg['mock']} not found. Run the site build first.\n"; return; }
$data = json_decode(get_post_meta($pageId, '_elementor_data', true), true);
$logo = $live['assets']['logo-imm2.png'] ?? null; $portrait = $live['assets']['portrait-placeholder.png'] ?? null;
if (!$logo || !$portrait) { echo "live-ids has no logo/portrait: both must be in <pack>/site-assets, then run import-live.py with --structure.\n"; return; }
$newsCat = $cfg['news'] ? get_term_by('slug', 'cat-' . $cfg['news'], 'category') : null;
$n = ['image' => 0, 'link' => 0, 'category' => 0, 'menu' => 0, 'doc' => 0, 'other' => 0];
$map = function ($x) use (&$map, $site, $local, $live, $logo, $portrait, $cfg, $newsCat, &$n) {
    if (!is_array($x)) return $x;
    if (isset($x['url']) && is_string($x['url']) && array_key_exists('id', $x)) {                    // an Elementor media value {url, id}
        $file = basename((string) parse_url($x['url'], PHP_URL_PATH));                              // matched by file name (WordPress may add -1, -2)
        if (preg_match('~^logo-imm2(-\d+)?\.png$~', $file)) { $x['url'] = $logo['url']; $x['id'] = $logo['id']; $n['image']++; }
        elseif (preg_match('~^(mock-portrait|portrait-placeholder)(-\d+)?\.png$~', $file)) { $x['url'] = $portrait['url']; $x['id'] = $portrait['id']; $n['image']++; }
    }
    foreach ($x as $k => $v) {
        if (is_string($v) && preg_match('~^#doc/([\w-]+)$~', $v, $dm)) { $x[$k] = $live['docs'][$dm[1]]['url'] ?? '#'; $n['doc']++; continue; }          // Downloads section: file links
        if ($v === '#downloads') { $x[$k] = isset($live['downloads_page']) ? $site . '/?page_id=' . $live['downloads_page'] : '#'; $n['doc']++; continue; }
        if (is_string($v) && strpos($v, $local) !== false) {
            $path = '/' . trim((string) parse_url($v, PHP_URL_PATH), '/'); $done = false;
            foreach ($cfg['links'] as $p => [$kind, $nid]) if (rtrim($path, '/') === $p) { $x[$k] = $site . '/?page_id=' . ($live['pages'][$nid] ?? 0); $n['link']++; $done = true; }
            if (!$done && $newsCat && rtrim($v, '/') === untrailingslashit(get_category_link($newsCat->term_id))) { $x[$k] = $site . '/?cat=' . ($live['cats'][$cfg['news']] ?? 0); $n['link']++; $done = true; }
            if (!$done) { $x[$k] = str_replace($local, $site, $v); $n['other']++; }
        } elseif ($k === 'eael_simple_menu_menu' && is_string($v)) { $x[$k] = 'main-menu'; $n['menu']++; }
        elseif ($k === 'category_ids' && is_array($v) && $cfg['news']) { $x[$k] = array_map(fn($i) => (string) ($live['cats'][$cfg['news']] ?? $i), $v); $n['category']++; }
        elseif (is_array($v)) $x[$k] = $map($v);
    }
    return $x;
};
$data = $map($data);
$left = preg_match_all('~localhost~i', json_encode($data), $m);
$settings = ['template' => 'elementor_canvas'];

/* 1. the page as an Elementor TEMPLATE (manual fallback) */
file_put_contents("$out/{$cfg['file']}.template.json", json_encode(['content' => $data, 'page_settings' => $settings, 'version' => '0.4', 'title' => $cfg['title'], 'type' => 'page'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

/* 2. the page as a ONE-PAGE KIT: take Elementor's own export for the manifest shape, keep only this page */
$req = new WP_REST_Request('POST', '/elementor/v1/import-export-customization/export');
$req->set_header('content_type', 'application/json'); $req->set_body(json_encode(['include' => ['content'], 'kitInfo' => ['title' => $cfg['title'], 'description' => 'One page for the ' . $cfg['title'] . ' live site', 'source' => 'local']]));
$d = rest_do_request($req)->get_data()['data'] ?? null;
if (!$d || empty($d['file'])) { echo "Kit export failed.\n"; return; }
$tmp = wp_tempnam('kit.zip'); file_put_contents($tmp, base64_decode($d['file']));
$zin = new ZipArchive(); $zin->open($tmp); $manifest = json_decode($zin->getFromName('manifest.json'), true); $zin->close();
$entry = $manifest['content']['page'][(string) $pageId] ?? null;
if (!$entry) { echo "Page $pageId is not in Elementor's export (is it published?).\n"; return; }
$entry['title'] = $cfg['title']; $entry['url'] = $site . '/' . $cfg['slug'] . '/'; $entry['terms'] = [];
foreach ($manifest['content'] as $type => $_) $manifest['content'][$type] = [];
$manifest['content']['page'] = [(string) $pageId => $entry];
$manifest['taxonomies'] = []; $manifest['wp-content'] = []; $manifest['site'] = $site; $manifest['title'] = $cfg['title']; $manifest['name'] = sanitize_title($cfg['title']);
$kit = "$out/{$cfg['file']}.page.kit.zip"; @unlink($kit);
$zo = new ZipArchive(); $zo->open($kit, ZipArchive::CREATE);
$zo->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$zo->addFromString("content/page/$pageId.json", json_encode(['content' => $data, 'settings' => $settings, 'metadata' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$zo->close(); @unlink($tmp);
echo "page: {$cfg['file']}.page.kit.zip (" . filesize($kit) . " bytes) + {$cfg['file']}.template.json | images {$n['image']}, key links {$n['link']}, news category {$n['category']}, menu {$n['menu']}, document links {$n['doc']}, other urls {$n['other']}, leftover 'localhost': $left\n";
