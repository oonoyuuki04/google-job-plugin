<?php
/**
 * Plugin Name: Google Job Posting
 * Plugin URI: https://hibiya-ca.co.jp
 * Description: WordPress求人情報をGoogleしごと検索に連携するプラグイン
 * Version: 1.0.1
 * Author: Hibiya CA
 * Author URI: https://hibiya-ca.co.jp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: google-job-posting
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Plugin constants
define('GJP_VERSION', '1.0.1');
define('GJP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GJP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GJP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once GJP_PLUGIN_DIR . 'includes/class-gjp-post-type.php';
require_once GJP_PLUGIN_DIR . 'includes/class-gjp-meta-boxes.php';
require_once GJP_PLUGIN_DIR . 'includes/class-gjp-structured-data.php';
require_once GJP_PLUGIN_DIR . 'includes/class-gjp-admin.php';

/**
 * Main plugin class
 */
class Google_Job_Posting {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));

        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize custom post type
        GJP_Post_Type::get_instance();

        // Initialize meta boxes
        GJP_Meta_Boxes::get_instance();

        // Initialize structured data
        GJP_Structured_Data::get_instance();

        // Initialize admin
        if (is_admin()) {
            GJP_Admin::get_instance();
        }
    }

    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('google-job-posting', false, dirname(GJP_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Register custom post type
        GJP_Post_Type::register_post_type();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $default_options = array(
            'company_name' => get_bloginfo('name'),
            'company_url' => home_url(),
            'company_logo' => '',
        );

        add_option('gjp_settings', $default_options);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize plugin
function gjp_init() {
    return Google_Job_Posting::get_instance();
}

// Start the plugin
gjp_init();
