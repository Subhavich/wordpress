<?php
/**
 * SITE ONE (admin-division2.immigration.go.th, "main บก.") - starter content.
 * Site one has no blog posts (like the real site), so this only creates what the layout needs. Everything is namespaced so site one and
 * site two can live in the SAME WordPress: page slugs are prefixed "site-one", the menu is "Site one menu", keys start with "one-".
 *   pages: the site page itself (slug site-one, or SITE_SLUG), News, Organisation structure, Commanders, Contact us
 *   menu:  "Site one menu" (slug site-one-menu), used by the site-one header
 * The front page is set to the site page only if no front page is set yet (so it never overrides site two). Safe to re-run; tagged _mock_data=1.
 *
 *   docker compose run --rm -u 33 -v "${PWD}/foundry:/foundry:ro" -v "${PWD}/sites:/sites:ro" [-v "<folder with logo_imm2_509x602.png>:/assets:ro"] --entrypoint wp wpcli eval-file /sites/one/seed.php --path=/var/www/html
 */
require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
$admin = get_users(['role' => 'administrator', 'number' => 1])[0]->ID;
$find = fn($key, $type) => ($q = get_posts(['post_type' => $type, 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => $key, 'fields' => 'ids', 'numberposts' => 1])) ? (int) $q[0] : 0;

$siteSlug = getenv('SITE_SLUG') ?: 'site-one';
// key => [slug, title, body]
$defs = ['site' => [$siteSlug, 'Site one: admin-division2', ''], 'news' => ["$siteSlug-news", 'News', ''], 'structure' => ["$siteSlug-structure", 'Organisation structure', 'p'],
         'commanders' => ["$siteSlug-commanders", 'Commanders', 'p'], 'contact' => ["$siteSlug-contact", 'Contact us', 'p']];
$pageId = [];
foreach ($defs as $k => [$slug, $title, $body]) {
    if ($id = $find("one-page-$k", 'page')) { $pageId[$k] = $id; continue; }
    $id = wp_insert_post(wp_slash(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $body ? '<p>Placeholder page. Replace with the real content (the original is a single poster image).</p>' : '', 'post_author' => $admin]), true);
    if (is_wp_error($id)) { echo "page fail $k\n"; continue; }
    update_post_meta($id, '_mock_key', "one-page-$k"); update_post_meta($id, '_mock_data', 1); $pageId[$k] = $id;
}
if (!(int) get_option('page_on_front')) { update_option('show_on_front', 'page'); update_option('page_on_front', $pageId['site']); }   // only when nothing else is the front page

if (!wp_get_nav_menu_object('Site one menu')) {
    $mid = wp_create_nav_menu('Site one menu');
    $add = fn($t, $k, $parent = 0) => wp_update_nav_menu_item($mid, 0, ['menu-item-title' => $t, 'menu-item-object' => 'page', 'menu-item-object-id' => $pageId[$k], 'menu-item-type' => 'post_type', 'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish']);
    $add('Home', 'site');
    $about = wp_update_nav_menu_item($mid, 0, ['menu-item-title' => 'About us', 'menu-item-url' => '#', 'menu-item-type' => 'custom', 'menu-item-status' => 'publish']);
    $add('Organisation structure', 'structure', $about); $add('Commanders', 'commanders', $about);
    $add('News', 'news'); $add('Contact', 'contact');
    echo "menu created\n";
}

$logoFile = '/assets/logo_imm2_509x602.png';
if (file_exists($logoFile) && !$find('one-logo', 'attachment') && !get_theme_mod('custom_logo')) {
    $up = wp_upload_bits('logo-imm2.png', null, file_get_contents($logoFile));
    if (empty($up['error'])) { $lid = wp_insert_attachment(['post_mime_type' => 'image/png', 'post_title' => 'Site logo', 'post_status' => 'inherit'], $up['file']); wp_update_attachment_metadata($lid, wp_generate_attachment_metadata($lid, $up['file'])); update_post_meta($lid, '_mock_key', 'one-logo'); update_post_meta($lid, '_mock_data', 1); set_theme_mod('custom_logo', $lid); echo "logo set\n"; }
}
flush_rewrite_rules();
echo "DONE site one seed: pages " . count($pageId) . ", site page id={$pageId['site']} (/{$siteSlug}/)\n";
