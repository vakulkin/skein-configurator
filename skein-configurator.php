<?php
/**
 * Plugin Name: Skein Configurator
 * Author: Anton Vakulov
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('SKEIN_CONFIGURATOR_VERSION', '1.0.0');
define('SKEIN_CONFIGURATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKEIN_CONFIGURATOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SKEIN_CONFIGURATOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Skein Configurator Class
 */
final class Skein_Configurator
{
    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Main Skein_Configurator Instance
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->includes();
    }

    /**
     * Hook into actions and filters
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
        add_action('plugins_loaded', array($this, 'load_textdomain'), 1);
        add_action('plugins_loaded', array($this, 'init'), 10);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('skein-configurator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * Declare compatibility with WooCommerce features
     */
    public function declare_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    /**
     * Include required core files
     */
    private function includes()
    {
        // Admin classes
        require_once SKEIN_CONFIGURATOR_PLUGIN_DIR . 'includes/class-skein-acf-config.php';
        require_once SKEIN_CONFIGURATOR_PLUGIN_DIR . 'includes/class-skein-admin.php';

        // Frontend classes
        require_once SKEIN_CONFIGURATOR_PLUGIN_DIR . 'includes/class-skein-frontend.php';

        // Cart integration
        require_once SKEIN_CONFIGURATOR_PLUGIN_DIR . 'includes/class-skein-cart.php';
    }

    /**
     * Init Skein Configurator when WordPress initializes
     */
    public function init()
    {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Check if ACF is active
        if (!class_exists('ACF')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
            return;
        }

        // Initialize classes
        Skein_ACF_Config::instance();
        Skein_Admin::instance();
        Skein_Frontend::instance();
        Skein_Cart::instance();
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
        ?>
<div class="error">
	<p><?php esc_html_e('Skein Configurator requires WooCommerce to be installed and active.', 'skein-configurator'); ?>
	</p>
</div>
<?php
    }

    /**
     * ACF missing notice
     */
    public function acf_missing_notice()
    {
        ?>
<div class="error">
	<p><?php esc_html_e('Skein Configurator requires Advanced Custom Fields (ACF) to be installed and active.', 'skein-configurator'); ?>
	</p>
</div>
<?php
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Activation code
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Deactivation code
        flush_rewrite_rules();
    }
}

/**
 * Returns the main instance of Skein_Configurator
 */
function skein_configurator()
{
    return Skein_Configurator::instance();
}

// Initialize the plugin
skein_configurator();

?>