<?php
/**
 * Plugin Name:       Weave Cache Purge Helper
 * Plugin URI:        https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * Description:       Fork of Cache Purge Helper for Weave Digital Use. Adds additional WordPress, BB, ACF, and WP-Umbrella hooks to trigger cache purges in the correct order.
 * Version:           1.2.0
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
 */
function wcph_write_log($log) {
    if ( defined('WP_DEBUG') && WP_DEBUG === true && defined('WC_PHP_DEBUG') && WC_PHP_DEBUG === true ) {
        if ( is_array($log) || is_object($log) ) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

/**
 * Purge Cache Function
 */
function wcph_purge() {
    $called_action_hook = current_filter();
    wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - initiated on ', 'weave-cache-purge-helper') . $called_action_hook);
    
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
                wcph_write_log('[' . date('Y-m-d H:i:s') . '] ' . __('wcph - WP Umbrella triggered cache clear', 'weave-cache-purge-helper'));
                wcph_purge();
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
    
    add_action('upgrader_process_complete', 'wcph_purge', 10, 0);
    add_action('activated_plugin', 'wcph_purge', 10, 0);
    add_action('deactivated_plugin', 'wcph_purge', 10, 0);
    add_action('switch_theme', 'wcph_purge', 10, 0);
    
    // Beaver Builder
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
