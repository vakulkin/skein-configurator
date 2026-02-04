<?php

/**
 * Admin Class
 * Handles admin-specific functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Skein_Admin
{
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('manage_product_posts_columns', array($this, 'add_product_column'));
        add_action('manage_product_posts_custom_column', array($this, 'render_product_column'), 10, 2);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load on product edit pages and settings page
        if ($hook === 'post.php' || $hook === 'post-new.php' || strpos($hook, 'skein-configurator-settings') !== false) {
            wp_enqueue_style(
                'skein-admin-css',
                SKEIN_CONFIGURATOR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                SKEIN_CONFIGURATOR_VERSION
            );
        }
    }

    /**
     * Add custom column to products list
     */
    public function add_product_column($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'name') {
                $new_columns['skein_configurator'] = __('Skein Configurator', 'skein-configurator');
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column content
     */
    public function render_product_column($column, $post_id)
    {
        if ($column === 'skein_configurator') {
            $enabled = get_field('enable_skein_configurator', $post_id);
            if ($enabled) {
                echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>';
            } else {
                echo '<span class="dashicons dashicons-minus" style="color: #ddd;"></span>';
            }
        }
    }
}
