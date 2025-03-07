<?php
/**
 * GitHub Update Checker for Weave Cache Purge Helper
 *
 * @package    Weave_Cache_Purge_Helper
 * @subpackage Updates
 * @version    1.1.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Weave_Cache_Purge_Updater {
    private $file;
    private $plugin;
    private $basename;
    private $github_response;
    private $github_username = "weavedigitalstudio";
    private $github_repo = "weave-cache-purge-helper";

    // Plugin icons
    private const ICON_SMALL = "https://weave-hk-github.b-cdn.net/weave/icon-128x128.png";
    private const ICON_LARGE = "https://weave-hk-github.b-cdn.net/weave/icon-256x256.png";

    /**
     * Constructor
     * 
     * @param string $file Main plugin file path
     */
    public function __construct($file) {
        $this->file = $file;

        if (is_admin() && function_exists("get_plugin_data")) {
            $this->plugin = get_plugin_data($this->file);
        }

        $this->basename = plugin_basename($this->file);

        // Hook into the WordPress update system
        add_filter("pre_set_site_transient_update_plugins", [$this, "check_update"]);
        add_filter("plugins_api", [$this, "plugin_info"], 20, 3);
        add_filter("upgrader_post_install", [$this, "after_install"], 10, 3);
    }

    /**
     * Initialize the updater
     * 
     * @param string $file Main plugin file path
     */
    public static function init($file) {
        new self($file);
    }

    /**
     * Get repository information from GitHub with caching
     * 
     * @return object|false Repository info or false on failure
     */
    private function get_repository_info() {
        if (!is_null($this->github_response)) {
            return $this->github_response;
        }
        
        // Check for a cached response (cache for 4 hours)
        $cached = get_transient('weave_cache_purge_helper_github_response');
        if (false !== $cached) {
            $this->github_response = $cached;
            return $this->github_response;
        }

        $request_uri = sprintf(
            "https://api.github.com/repos/%s/%s/releases/latest",
            $this->github_username,
            $this->github_repo
        );

        $args = [
            'headers' => [
                'User-Agent' => 'WordPress',
            ]
        ];

        $response = wp_remote_get($request_uri, $args);

        if (is_wp_error($response)) {
            error_log("GitHub API request failed: " . $response->get_error_message());
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log("GitHub API request failed with response code: " . wp_remote_retrieve_response_code($response));
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));

        if (!isset($body->tag_name, $body->assets) || empty($body->assets)) {
            error_log("GitHub API response missing required fields or assets.");
            return false;
        }

        // Fetch the actual zip file URL
        $body->zipball_url = $body->assets[0]->browser_download_url ?? '';

        if (empty($body->zipball_url)) {
            error_log("No valid download URL found for the latest release.");
            return false;
        }

        // Cache the response for 4 hours (or use 6 * HOUR_IN_SECONDS for 6 hours)
        set_transient('weave_cache_purge_helper_github_response', $body, 4 * HOUR_IN_SECONDS);
        $this->github_response = $body;
        return $this->github_response;
    }

    /**
     * Check for plugin updates
     * 
     * @param object $transient Update transient
     * @return object Updated transient
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $repository_info = $this->get_repository_info();
        if (!$repository_info) {
            return $transient;
        }

        $current_version = $transient->checked[$this->basename] ?? "";
        $latest_version = ltrim($repository_info->tag_name, "v");

        if (version_compare($latest_version, $current_version, "gt")) {
            $plugin = [
                "slug" => dirname($this->basename),
                "package" => $repository_info->zipball_url,
                "new_version" => $latest_version,
                "tested" => get_bloginfo("version"),
                "icons" => [
                    "1x" => self::ICON_SMALL,
                    "2x" => self::ICON_LARGE,
                ],
            ];

            $transient->response[$this->basename] = (object) $plugin;
        }

        return $transient;
    }

    /**
     * Provide plugin information in the update UI
     * 
     * @param object|false $res Result object or false
     * @param string $action Action name
     * @param object $args Request arguments
     * @return object Plugin info object
     */
    public function plugin_info($res, $action, $args) {
        if ($action !== "plugin_information" || $args->slug !== dirname($this->basename)) {
            return $res;
        }

        $repository_info = $this->get_repository_info();
        if (!$repository_info) {
            return $res;
        }

        $info = new \stdClass();
        $info->name = "Weave Cache Purge Helper";
        $info->slug = dirname($this->basename);
        $info->version = ltrim($repository_info->tag_name, "v");
        $info->tested = get_bloginfo("version");
        $info->last_updated = $repository_info->published_at ?? "";
        $info->download_link = $repository_info->zipball_url;
        $info->icons = [
            "1x" => self::ICON_SMALL,
            "2x" => self::ICON_LARGE,
        ];

        return $info;
    }

    /**
     * Handle plugin installation process
     * 
     * @param bool|WP_Error $response Installation response
     * @param array $hook_extra Extra arguments
     * @param array $result Installation result data
     * @return array Modified result data
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->file);
        $wp_filesystem->move($result["destination"], $install_directory);
        $result["destination"] = $install_directory;

        return $result;
    }
}
