<?php
/**
 * Plugin Name:       Weave Cache Purge Helper
 * Plugin URI:        https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * Description:       Fork of Cache Purge Helper for Weave Digital Use. Adds additional WordPress, BB, ACF, and WP-Umbrella hooks to trigger cache purges in the correct order.
 * Version:           1.4.0
 * Author:            Gareth Bissland, Paul Stoute, Jordan Trask, Jeff Cleverley
 * Author URI:        https://weave.co.nz
 * Requires PHP:      7.2
 * Requires at least: 5.0
 * Tested up to:      6.8
 *
 * @link              https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * @since             1.0.1
 * Original Author:   Paul Stoute, Jordan Trask, Jeff Cleverley
 * Original Plugin URI: https://github.com/managingwp/cache-purge-helper
 * @package           weave-cache-purge-helper
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Log to WordPress Debug Log
 * 
 * Simplified to only check for WC_PHP_DEBUG constant
 */
function wcph_write_log($log) {
    if (defined('WC_PHP_DEBUG') && WC_PHP_DEBUG === true) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

/**
 * Cache Purge Function
 * Handles all cache purging operations in the correct sequence
 */
function wcph_direct_purge() {
    $called_action_hook = current_filter();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - initiated on ' . $called_action_hook);
    
    // Default WordPress Cache Purge
    wp_cache_flush();
    
    // FIRST: Clear Beaver Builder cache if it's active
    if (defined('FL_BUILDER_VERSION') && class_exists('FLBuilderModel')) {
        wcph_write_log('wcph - Clearing Beaver Builder cache.');
        FLBuilderModel::delete_asset_cache_for_all_posts();
        wcph_write_log('wcph - Beaver Builder cache cleared.');
    }
    
    // SECOND: Clear Nginx/Litespeed cache
    $purge_executed = false;
    
    // Check and Purge Nginx Helper Cache
    if ( function_exists( 'is_plugin_active' ) && is_plugin_active('nginx-helper/nginx-helper.php') ) {
        global $nginx_purger;
        if ( isset( $nginx_purger ) && is_object( $nginx_purger ) ) {
            wcph_write_log('wcph - nginx-helper plugin detected, purging cache.');
            $nginx_purger->purge_all();
            $purge_executed = true; // Mark purge as executed
        }
    }
    
    // Check and Purge LiteSpeed Cache (if no other purge executed)
    if ( !$purge_executed && function_exists('litespeed_purge_all') ) {
        wcph_write_log('wcph - litespeed-cache plugin detected, purging cache.');
        do_action('litespeed_purge_all');
        $purge_executed = true; // Mark purge as executed
    }
    
    // If no supported cache plugin was detected, log a message
    if ( !$purge_executed ) {
        wcph_write_log('wcph - No supported cache plugin detected. No cache purge executed.');
    } else {
        wcph_write_log('wcph - cache purge completed.');
    }
}

// WP-Umbrella Integration
if (file_exists(WP_PLUGIN_DIR . '/wp-health')) {
    include_once WP_PLUGIN_DIR . '/wp-health/src/Helpers/GodTransient.php';
    include_once WP_PLUGIN_DIR . '/wp-health/src/God/ErrorHandler.php';

    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    if (class_exists("\WPUmbrella\Core\Collections\CacheCollectionItem") &&
       is_plugin_active('wp-health/wp-health.php')) {

        class WPUmbrellaNginxHelper implements \WPUmbrella\Core\Collections\CacheCollectionItem
        {
            public static function isAvailable()
            {
                if (!function_exists('is_plugin_active')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                
                // Check for Nginx Helper OR LiteSpeed Cache
                return is_plugin_active('nginx-helper/nginx-helper.php') || 
                       is_plugin_active('litespeed-cache/litespeed-cache.php') ||
                       class_exists('\Nginx_Helper');
            }
        
            public function clear()
            {
                wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - WP Umbrella triggered cache clear');
                wcph_direct_purge();
                wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - WP Umbrella cache clear completed');
            }
        }
        
        // Override WP-Umbrella's cache compatibilities to remove their buggy GlobalNginx class
        add_filter('wp_umbrella_cache_compatibilities', 'wpu_add_cache_compatibilities', 20); // Higher priority
        function wpu_add_cache_compatibilities($classes)
        {
            // Remove WP-Umbrella's buggy GlobalNginx class if present
            $classes = array_filter($classes, function($class) {
                return $class !== "\WPUmbrella\Thirds\Cache\GlobalNginx";
            });
            
            // Add our properly implemented version
            $classes[] = '\WPUmbrellaNginxHelper';
            
            wcph_write_log('wcph - WP-Umbrella cache compatibilities updated, removed buggy GlobalNginx class');
            
            return $classes;
        }
    }
}

/**
 * Initialize Plugin Hooks
 */
function wcph_init_hooks() {
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    
    // Critical system events
    add_action('upgrader_process_complete', 'wcph_direct_purge', 10, 0);
    add_action('activated_plugin', 'wcph_direct_purge', 10, 0);
    add_action('deactivated_plugin', 'wcph_direct_purge', 10, 0);
    add_action('switch_theme', 'wcph_direct_purge', 10, 0);
    
    // Beaver Builder events
    if ( defined('FL_BUILDER_VERSION') ) {
        add_action('fl_builder_cache_cleared', 'wcph_direct_purge', 10, 0);
        add_action('fl_builder_after_save_layout', 'wcph_direct_purge', 10, 0);
        add_action('fl_builder_after_save_user_template', 'wcph_direct_purge', 10, 0);
    }
    
    // ACF Options Page Purge
    if ( function_exists('acf') ) {
        add_action('acf/save_post', function($post_id) {
            if ($post_id === 'options') {
                wcph_write_log('wcph - ACF options page saved, clearing Beaver Builder cache.');
                if (defined('FL_BUILDER_VERSION') && class_exists('FLBuilderModel')) {
                    FLBuilderModel::delete_asset_cache_for_all_posts();
                    wcph_write_log('wcph - Beaver Builder cache cleared.');
                }
                wcph_direct_purge();
            }
        });
    }
}

add_action('init', 'wcph_init_hooks');

// REST API hooks
add_action('rest_after_insert_post', function($post, $request, $creating) {
    $post_type = $post->post_type;
    $action_type = $creating ? 'created' : 'updated';
    
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - REST API post ' . $action_type . ': ' . $post_type . ' (ID: ' . $post->ID . ')');
    wcph_direct_purge();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - REST API cache clear completed');
}, 10, 3);

/**
 * Browser cache-busting for Beaver Builder's generated layout files
 *
 * Beaver Builder versions a cached layout file by its post's modified time, so a
 * file it regenerates for any other reason keeps the same URL. Servers send
 * uploads with a long browser cache (about a year on GridPane), so a returning
 * visitor keeps the old file. It bites hardest on dynamic global rows such as
 * mega menus: their selectors carry a hash of the row's settings, so editing the
 * global row, or changing the site URL, leaves the page asking for selectors the
 * visitor's cached file does not have.
 *
 * reBusted cannot catch these. It rewrites versions on wp_enqueue_scripts, and
 * Beaver Builder enqueues partial layout files later, while rendering. These
 * filters run as each tag is printed, so late files are covered too.
 */
function wcph_bb_asset_mtime_src($src) {
    static $cache = null;

    if (!is_string($src) || false === strpos($src, '/bb-plugin/cache/')) {
        return $src;
    }
    if (null === $cache) {
        $cache = class_exists('FLBuilderModel') ? FLBuilderModel::get_cache_dir() : false;
    }
    if (empty($cache['path']) || empty($cache['url'])) {
        return $src;
    }

    // Compare paths only, so an http/https or host mismatch does not matter.
    $src_path  = wp_parse_url($src, PHP_URL_PATH);
    $cache_url = trailingslashit(wp_parse_url($cache['url'], PHP_URL_PATH));
    if (!$src_path || 0 !== strpos($src_path, $cache_url)) {
        return $src;
    }

    // Cache files sit flat in the cache folder. Anything with a further slash is
    // not one of them, which also means nothing in the URL can steer the stat.
    $name = substr($src_path, strlen($cache_url));
    if ('' === $name || false !== strpos($name, '/')) {
        return $src;
    }

    $file = $cache['path'] . $name;
    if (!is_file($file)) {
        return $src;
    }

    return add_query_arg('wcph', filemtime($file), $src);
}
add_filter('style_loader_src', 'wcph_bb_asset_mtime_src', 20);
add_filter('script_loader_src', 'wcph_bb_asset_mtime_src', 20);

/**
 * Manual test function for cache purge
 */
add_action('init', function() {
    if (isset($_GET['test_wcph_purge']) && current_user_can('manage_options')) {
        // A nonce so a link on another site cannot make an administrator's browser purge the cache.
        // The Plugins row link below carries it.
        check_admin_referer('wcph_test_purge');
        wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - Manual test of cache purge system initiated');
        wcph_direct_purge();
        echo '<div style="background: #fff; border: 1px solid #008000; padding: 20px; margin: 20px; font-family: sans-serif;">
            <h2>Cache Purge Test Completed</h2>
            <p>The cache purge test has been initiated. Check your debug log for results.</p>
            <p><a href="javascript:history.back()">Go Back</a></p>
        </div>';
        exit;
    }
});

/**
 * "Test purge" link on the Plugins row, carrying the nonce the test hook checks.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    if (current_user_can('manage_options')) {
        $url = wp_nonce_url(add_query_arg('test_wcph_purge', '1', home_url('/')), 'wcph_test_purge');
        $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Test purge', 'weave-cache-purge-helper') . '</a>';
    }
    return $links;
});

// Initialize the updater on init hook to avoid translation loading issues
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'github-updater.php';
    
    // Initialize the updater on init hook
    add_action('init', function() {
        Weave_Cache_Purge_Updater::init(__FILE__);
    });
}
