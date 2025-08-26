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
/**
 * Cache Purge Function
 * Handles all cache purging operations in the correct sequence
 */
function wcph_direct_purge() {
    $called_action_hook = current_filter();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - initiated on ' . $called_action_hook);
    
    // Determine if this is a full purge event
    $full_purge_hooks = array(
        'upgrader_process_complete',
        'activated_plugin',
        'deactivated_plugin',
        'switch_theme'
    );
    
    $is_full_purge = in_array($called_action_hook, $full_purge_hooks);
    
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
            $purge_executed = true;
        }
    }
    
    // Check and Purge LiteSpeed Cache (if no other purge executed)
    if ( !$purge_executed && function_exists('litespeed_purge_all') ) {
        wcph_write_log('wcph - litespeed-cache plugin detected, purging cache.');
        do_action('litespeed_purge_all');
        $purge_executed = true;
    }
    
    // If no supported cache plugin was detected, log a message
    if ( !$purge_executed ) {
        wcph_write_log('wcph - No supported cache plugin detected. No cache purge executed.');
    } else {
        wcph_write_log('wcph - cache purge completed.');
    }
    
    // THIRD: Clear BunnyCDN cache if enabled
    if (wcph_bunny_is_enabled()) {
        // Determine what to purge
        if ($is_full_purge) {
            // Full purge for system events
            wcph_bunny_purge_all();
        } else {
            // Check if this is a Beaver Builder save
            $bb_hooks = array(
                'fl_builder_cache_cleared',
                'fl_builder_after_save_layout',
                'fl_builder_after_save_user_template'
            );
            
            if (in_array($called_action_hook, $bb_hooks)) {
                // For BB saves, purge the current page URL
                $current_url = wcph_get_current_page_url();
                if ($current_url) {
                    wcph_bunny_purge_url($current_url);
                } else {
                    // Fallback to full purge if we can't determine the URL
                    wcph_write_log('wcph - Could not determine current page URL, doing full BunnyCDN purge');
                    wcph_bunny_purge_all();
                }
            }
        }
    }
}

/**
 * BunnyCDN Integration Functions
 * Only loads if WCPH_BUNNY_ENABLED is true
 */

/**
 * Check if BunnyCDN integration is enabled
 */
function wcph_bunny_is_enabled() {
    return defined('WCPH_BUNNY_ENABLED') && 
           WCPH_BUNNY_ENABLED === true && 
           defined('WCPH_BUNNY_API_KEY') && 
           defined('WCPH_BUNNY_PULL_ZONE_ID');
}

/**
 * Purge specific URL from BunnyCDN
 * 
 * @param string $url URL to purge
 * @return bool Success status
 */
function wcph_bunny_purge_url($url) {
    if (!wcph_bunny_is_enabled()) {
        return false;
    }
    
    $api_url = 'https://api.bunny.net/purge?url=' . urlencode($url) . '&async=false';
    
    $args = array(
        'headers' => array(
            'AccessKey' => WCPH_BUNNY_API_KEY,
            'Content-Type' => 'application/json',
        ),
        'body' => '',
        'method' => 'POST',
        'timeout' => 5,
    );
    
    wcph_write_log('wcph - BunnyCDN purging URL: ' . $url);
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        wcph_write_log('wcph - BunnyCDN purge failed: ' . $response->get_error_message());
        return false;
    }
    
    wcph_write_log('wcph - BunnyCDN purge successful for: ' . $url);
    return true;
}

/**
 * Purge entire BunnyCDN pull zone
 * 
 * @return bool Success status
 */
function wcph_bunny_purge_all() {
    if (!wcph_bunny_is_enabled()) {
        return false;
    }
    
    $api_url = 'https://api.bunny.net/pullzone/' . WCPH_BUNNY_PULL_ZONE_ID . '/purgeCache';
    
    $args = array(
        'headers' => array(
            'AccessKey' => WCPH_BUNNY_API_KEY,
            'Content-Type' => 'application/json',
        ),
        'body' => '',
        'method' => 'POST',
        'timeout' => 10,
    );
    
    wcph_write_log('wcph - BunnyCDN purging entire pull zone');
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        wcph_write_log('wcph - BunnyCDN full purge failed: ' . $response->get_error_message());
        return false;
    }
    
    wcph_write_log('wcph - BunnyCDN full purge successful');
    return true;
}

/**
 * Get the current page URL (for BB saves)
 * 
 * @return string|false Current page URL or false
 */
function wcph_get_current_page_url() {
    // For BB AJAX saves, try to get the URL from referrer or post data
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // Check if we have a post_id in the request
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if ($post_id) {
            return get_permalink($post_id);
        }
        
        // Try to get from referrer
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = wp_parse_url($_SERVER['HTTP_REFERER']);
            $path = isset($referer['path']) ? $referer['path'] : '/';
            $query = isset($referer['query']) ? '?' . $referer['query'] : '';
            
            // Remove fl_builder from query string
            $query = preg_replace('/[?&]fl_builder[^&]*/', '', $query);
            $query = preg_replace('/^&/', '?', $query);
            
            return home_url($path . $query);
        }
    }
    
    // For regular saves, get the current post
    global $post;
    if ($post && isset($post->ID)) {
        return get_permalink($post->ID);
    }
    
    return false;
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
                
                // ACF Options requires full BunnyCDN purge
                if (wcph_bunny_is_enabled()) {
                    wcph_bunny_purge_all();
                }
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
    
    // For REST API updates, purge the specific post URL from BunnyCDN
    if (wcph_bunny_is_enabled()) {
        $post_url = get_permalink($post->ID);
        if ($post_url) {
            wcph_bunny_purge_url($post_url);
        }
    }
    
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] wcph - REST API cache clear completed');
}, 10, 3);

/**
 * Manual test function for cache purge
 */
add_action('init', function() {
    if (isset($_GET['test_wcph_purge']) && current_user_can('manage_options')) {
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

// Initialize the updater on init hook to avoid translation loading issues
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'github-updater.php';
    
    // Initialize the updater on init hook
    add_action('init', function() {
        Weave_Cache_Purge_Updater::init(__FILE__);
    });
}
