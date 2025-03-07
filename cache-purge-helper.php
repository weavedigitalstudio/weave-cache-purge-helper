<?php
/**
 * Plugin Name:       Weave Cache Purge Helper
 * Plugin URI:        https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * Description:       Fork of Cache Purge Helper for Weave Digital Use. Adds additional WordPress, BB, ACF, and WP-Umbrella hooks to trigger cache purges in the correct order.
 * Version:           1.3.3
 * Author:            Gareth Bissland, Paul Stoute, Jordan Trask, Jeff Cleverley
 * Author URI:        https://weave.co.nz
 * Text Domain:       weave-cache-purge-helper
 * Primary Branch:    main
 * GitHub Plugin URI: weavedigitalstudio/weave-cache-purge-helper/
 * Requires PHP:      7.2
 * Requires at least: 5.0
 * Tested up to:      6.3
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
 * Direct Purge Cache Function
 * This is used when we need an immediate cache purge without debouncing
 */
function wcph_direct_purge() {
    $called_action_hook = current_filter();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - direct purge initiated on ', 'weave-cache-purge-helper') . $called_action_hook);
    
    // Default WordPress Cache Purge
    wp_cache_flush();
    
    // FIRST: Clear Beaver Builder cache if it's active
    if (defined('FL_BUILDER_VERSION') && class_exists('FLBuilderModel')) {
        wcph_write_log(__('wcph - Clearing Beaver Builder cache.', 'weave-cache-purge-helper'));
        FLBuilderModel::delete_asset_cache_for_all_posts();
        wcph_write_log(__('wcph - Beaver Builder cache cleared.', 'weave-cache-purge-helper'));
    }
    
    // SECOND: Clear Nginx/Litespeed cache
    $purge_executed = false;
    
    // Check and Purge Nginx Helper Cache
    if ( function_exists( 'is_plugin_active' ) && is_plugin_active('nginx-helper/nginx-helper.php') ) {
        global $nginx_purger;
        if ( isset( $nginx_purger ) && is_object( $nginx_purger ) ) {
            wcph_write_log(__('wcph - nginx-helper plugin detected, purging cache.', 'weave-cache-purge-helper'));
            $nginx_purger->purge_all();
            $purge_executed = true; // Mark purge as executed
        }
    }
    
    // Check and Purge LiteSpeed Cache (if no other purge executed)
    if ( !$purge_executed && function_exists('litespeed_purge_all') ) {
        wcph_write_log(__('wcph - litespeed-cache plugin detected, purging cache.', 'weave-cache-purge-helper'));
        do_action('litespeed_purge_all');
        $purge_executed = true; // Mark purge as executed
    }
    
    // If no supported cache plugin was detected, log a message
    if ( !$purge_executed ) {
        wcph_write_log(__('wcph - No supported cache plugin detected. No cache purge executed.', 'weave-cache-purge-helper'));
    } else {
        wcph_write_log(__('wcph - cache purge completed.', 'weave-cache-purge-helper'));
    }
}

/**
 * Debounced Cache Purge Function
 * Uses a transient to prevent multiple cache purges within a short timeframe
 */
function wcph_purge() {
    $called_action_hook = current_filter();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - initiated on ', 'weave-cache-purge-helper') . $called_action_hook);
    
    // Use the standard debounce transient name
    $transient_name = 'wcph_purge_scheduled';
    
    // Check if we already have a purge scheduled
    if (get_transient($transient_name)) {
        wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - purge already scheduled, resetting timer', 'weave-cache-purge-helper'));
        // If we do, just update the transient to extend the timer
        set_transient($transient_name, true, 3); // 3 seconds
        return;
    }
    
    // Otherwise, schedule a new purge
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - scheduling purge in 3 seconds', 'weave-cache-purge-helper'));
    set_transient($transient_name, true, 3); // 3 seconds
    
    // Schedule the actual purge to happen after the delay
    wp_schedule_single_event(time() + 3, 'wcph_do_purge');
}

/**
 * Execute the actual cache purge
 * This will only run after the debounce period has elapsed
 */
function wcph_do_purge() {
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - executing delayed purge', 'weave-cache-purge-helper'));
    
    // Clear the scheduling transient
    delete_transient('wcph_purge_scheduled');
    
    // Call the direct purge function to perform the actual cache clearing
    wcph_direct_purge();
}

// Register the action for our delayed purge
add_action('wcph_do_purge', 'wcph_do_purge');

/**
 * Manual test function for cache purge
 * Added in v1.3.1
 */
add_action('init', function() {
    if (isset($_GET['test_wcph_purge']) && current_user_can('manage_options')) {
        wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - Manual test of cache purge system initiated', 'weave-cache-purge-helper'));
        // For manual tests, use direct purge to get immediate feedback
        wcph_direct_purge();
        echo '<div style="background: #fff; border: 1px solid #008000; padding: 20px; margin: 20px; font-family: sans-serif;">
            <h2>Cache Purge Test Completed</h2>
            <p>The cache purge test has been initiated. Check your debug log for results.</p>
            <p><a href="javascript:history.back()">Go Back</a></p>
        </div>';
        exit;
    }
});

// REST API hooks (v1.3.0)
add_action('rest_after_insert_post', function($post, $request, $creating) {
    $post_type = $post->post_type;
    $action_type = $creating ? 'created' : 'updated';
    
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - REST API post ' . $action_type . ': ', 'weave-cache-purge-helper') . $post_type . ' (ID: ' . $post->ID . ')');
    wcph_purge();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - REST API cache clear requested', 'weave-cache-purge-helper'));
}, 10, 3);

// Add hook for meta field updates (v1.3.1)
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - Post meta updated: ', 'weave-cache-purge-helper') . $meta_key . ' for post ID: ' . $post_id);
    wcph_purge();
}, 10, 4);

// WP-Umbrella Integration (v1.2.0)
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
                wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - WP Umbrella triggered cache clear', 'weave-cache-purge-helper'));
                // For third-party integrations like WP Umbrella, use direct purge for immediate effect
                wcph_direct_purge();
                wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - WP Umbrella cache clear completed', 'weave-cache-purge-helper'));
            }
        }
        
        add_filter('wp_umbrella_cache_compatibilities', 'wpu_add_cache_compatibilities');
        function wpu_add_cache_compatibilities($classes)
        {
            $classes[] = '\WPUmbrellaNginxHelper';
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
    
    // Critical system events that should use direct purge for immediate effect
    add_action('upgrader_process_complete', 'wcph_direct_purge', 10, 0);
    add_action('activated_plugin', 'wcph_direct_purge', 10, 0);
    add_action('deactivated_plugin', 'wcph_direct_purge', 10, 0);
    add_action('switch_theme', 'wcph_direct_purge', 10, 0);
    
    // Beaver Builder events - these can use the debounced purge to improve performance
    if ( defined('FL_BUILDER_VERSION') ) {
        add_action('fl_builder_cache_cleared', 'wcph_purge', 10, 0);
        add_action('fl_builder_after_save_layout', 'wcph_purge', 10, 0);
        add_action('fl_builder_after_save_user_template', 'wcph_purge', 10, 0);
    }
    
    // ACF Options Page Purge
    if ( function_exists('acf') ) {
        add_action('acf/save_post', function($post_id) {
            if ($post_id === 'options') {
                wcph_write_log(__('wcph - ACF options page saved, clearing Beaver Builder cache.', 'weave-cache-purge-helper'));
                if (defined('FL_BUILDER_VERSION') && class_exists('FLBuilderModel')) {
                    FLBuilderModel::delete_asset_cache_for_all_posts();
                    wcph_write_log(__('wcph - Beaver Builder cache cleared.', 'weave-cache-purge-helper'));
                }
                wcph_purge();
            }
        });
    }
}

add_action('init', 'wcph_init_hooks');

if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'github-updater.php';
    Weave_Cache_Purge_Updater::init(__FILE__);
}
