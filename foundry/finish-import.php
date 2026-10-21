<?php
/**
 * Post-import fix-up. A WordPress XML export carries posts, pages, media, menus' items, templates and Elementor page data,
 * but NOT site settings. Run this once after importing the export into another WordPress to apply those settings:
 *   - front page = "Home", posts page = "News"
 *   - the "Main menu" is assigned to the theme's header menu location
 *   - the site logo (attachment tagged _mock_key=logo) is set
 *   - the imported Elementor Kit (global colours / fonts) becomes the active kit
 *
 * It changes the target site's reading settings, menu locations, logo and Elementor globals, so run it on a new or staging
 * site, not on a site whose current settings you want to keep.
 *
 *   docker compose run --rm -u 33 -v "${PWD}/mock-data:/mock:ro" --entrypoint wp wpcli eval-file /mock/finish-import.php --path=/var/www/html
 */
$did = [];

// front page / posts page
$home = get_page_by_path('home'); $news = get_page_by_path('news');
if ($home) { update_option('show_on_front', 'page'); update_option('page_on_front', $home->ID); $did[] = "front page = #{$home->ID}"; }
if ($news) { update_option('page_for_posts', $news->ID); $did[] = "posts page = #{$news->ID}"; }

// menu location
$menu = wp_get_nav_menu_object('Main menu'); $locs = array_keys(get_registered_nav_menus());
if ($menu && $locs) { $set = get_theme_mod('nav_menu_locations', []); $set[$locs[0]] = $menu->term_id; set_theme_mod('nav_menu_locations', $set); $did[] = "menu '{$menu->name}' -> {$locs[0]}"; }

// logo
$logo = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_mock_key', 'meta_value' => 'logo', 'fields' => 'ids', 'numberposts' => 1]);
if ($logo) { set_theme_mod('custom_logo', (int) $logo[0]); $did[] = "logo = #{$logo[0]}"; }

// Elementor kit: pick the imported kit that carries the design's colours
$kits = get_posts(['post_type' => 'elementor_library', 'post_status' => 'any', 'meta_key' => '_elementor_template_type', 'meta_value' => 'kit', 'numberposts' => -1, 'fields' => 'ids']);
foreach ($kits as $k) {
    $s = get_post_meta($k, '_elementor_page_settings', true);
    if (is_array($s) && !empty($s['custom_colors']) && in_array('navy700', array_column($s['custom_colors'], '_id'), true)) {
        update_option('elementor_active_kit', $k); $did[] = "active Elementor kit = #$k"; break;
    }
}

if (class_exists('\Elementor\Plugin')) { \Elementor\Plugin::$instance->files_manager->clear_cache(); $did[] = 'Elementor CSS cache cleared'; }
flush_rewrite_rules();
echo "finish-import: " . ($did ? implode(' | ', $did) : 'nothing to do') . "\n";
