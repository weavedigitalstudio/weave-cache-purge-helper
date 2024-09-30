<?php
/**
 * Plugin Name:       Weave Cache Purge Helper
 * Plugin URI:        https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * Description:       Fork of Cache Purge Helper for Weave Digital Use. Adds additional WordPress and BB, ACF hooks to trigger NGINX Helper / lscache plugin purges.
 * Version:           1.1.0
 * Author:            Gareth Bissland, Paul Stoute, Jordan Trask, Jeff Cleverley
 * Author URI:        https://weave.co.nz
 * Text Domain:       weave-cache-purge-helper
 * Domain Path:       /languages
 * Requires at least: 3.0
 * Tested up to:      5.4
 *
 * @link              https://github.com/weavedigitalstudio/weave-cache-purge-helper/
 * @since             1.0.1
 * @package           weave-cache-purge-helper
 * Original Author:   Paul Stoute, Jordan Trask, Jeff Cleverley
 * Original Plugin URI: https://github.com/managingwp/cache-purge-helper
 */
 
/** Purge Cache Function
  *
  * If both nginx-helper and litespeed-cache plugin exist, purges will happen for both.
  * This is cover instances where nginx-helper is used for server cache but litespeed-cache
  * is used for other functions, or there is a mis-configuration.
  *
  * A better idea would be to check what server is being used and warn that the wrong plugin
  * is activated for purging server cache.
  */
 
function wcph_purge() {
    // Purge WordPress Cache
    $called_action_hook = current_filter();
    wcph_write_log('wcph - initiated on ' . $called_action_hook);
    wp_cache_flush();
  
    // If nginx-helper plugin is enabled, purge cache.
    if ( is_plugin_active('nginx-helper/nginx-helper.php') ) {
    global $nginx_purger;
    if ( isset( $nginx_purger ) ) {
        wcph_write_log('wcph - nginx-helper plugin detected, purging cache.');
        $nginx_purger->purge_all();
        }
    }
    // If litespeed-cache plugin is enabled, purge cache.
    if ( function_exists('litespeed_purge_all') ) {
        wcph_write_log('wcph - litespeed-cache plugin detected, purging cache.');
        do_action('litespeed_purge_all');
    }

    wcph_write_log('wcph - cache purge completed.');
}

/** Log to WordPress Debug Log Function
 *
 * Log to PHP error_log if WP_DEBUG and WC_PHP_DEBUG are set!
 *
 */

function wcph_write_log($log) {
    if (WP_DEBUG === true && defined('WC_PHP_DEBUG') && WC_PHP_DEBUG === true) {
        if (is_array($log) || is_object($log)) {
            error_log(print_r($log, true));
        } else {
            error_log($log);
        }
    }
}

/** Initialize Plugin Hooks */
function wcph_init_hooks() {
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $initialized = true;

    // Plugin Update Hooks
    add_action('upgrader_process_complete', 'wcph_purge', 10, 0); // After plugins have been updated
    add_action('activated_plugin', 'wcph_purge', 10, 0); // After a plugin has been activated
    add_action('deactivated_plugin', 'wcph_purge', 10, 0); // After a plugin has been deactivated
    add_action('switch_theme', 'wcph_purge', 10, 0); // After a theme has been changed

    // Beaver Builder
    if (defined('FL_BUILDER_VERSION')) {
        add_action('fl_builder_cache_cleared', 'wcph_purge', 10, 3);
        add_action('fl_builder_after_save_layout', 'wcph_purge', 10, 3);
        add_action('fl_builder_after_save_user_template', 'wcph_purge', 10, 3);
        add_action('upgrader_process_complete', 'wcph_purge', 10, 3);
    }

    // ACF Options Page
    if (function_exists('acf')) {
        add_action('acf/save_post', function($post_id) {
            if ($post_id === 'options') {
                wcph_write_log('wcph - ACF options page saved, clearing Beaver Builder cache.');
                // Clear Beaver Builder cache
                FLBuilderModel::delete_asset_cache_for_all_posts();
                wcph_write_log('wcph - Beaver Builder cache cleared.');

                // Purge caches
                wcph_purge();
            }
        });
    }
}

// Initialize plugin hooks only once
add_action('init', 'wcph_init_hooks');
