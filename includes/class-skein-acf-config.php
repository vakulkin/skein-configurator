<?php

/**
 * ACF Configuration Class
 * Defines all ACF field groups for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Skein_ACF_Config
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
        add_action('acf/init', array($this, 'register_product_fields'));
    }

    /**
     * Register product-specific fields
     */
    public function register_product_fields()
    {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_skein_product_settings',
                'title' => __('Skein Configurator', 'skein-configurator'),
                'fields' => array(
                    array(
                        'key' => 'field_enable_skein_configurator',
                        'label' => __('Enable Skein Configurator', 'skein-configurator'),
                        'name' => 'enable_skein_configurator',
                        'type' => 'true_false',
                        'instructions' => __('Enable the color configurator for this product.', 'skein-configurator'),
                        'required' => 0,
                        'default_value' => 0,
                        'ui' => 1,
                        'ui_on_text' => __('Yes', 'skein-configurator'),
                        'ui_off_text' => __('No', 'skein-configurator'),
                    ),
                    array(
                        'key' => 'field_skein_product_image_note',
                        'label' => __('Product Image Note', 'skein-configurator'),
                        'name' => 'skein_product_image_note',
                        'type' => 'message',
                        'message' => __('The main product image will be used as the base for the color overlay. Use a high-quality photo of a neutral-colored skein for best results.', 'skein-configurator'),
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_enable_skein_configurator',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_skein_product_colors',
                        'label' => __('Available Colors', 'skein-configurator'),
                        'name' => 'skein_product_colors',
                        'type' => 'repeater',
                        'instructions' => __('Define all available yarn colors that customers can choose from for this product.', 'skein-configurator'),
                        'required' => 0,
                        'layout' => 'table',
                        'button_label' => __('Add Color', 'skein-configurator'),
                        'min' => 1,
                        'max' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_enable_skein_configurator',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_product_color_enabled',
                                'label' => __('Enabled', 'skein-configurator'),
                                'name' => 'color_enabled',
                                'type' => 'true_false',
                                'required' => 0,
                                'default_value' => 1,
                                'ui' => 1,
                                'ui_on_text' => __('Yes', 'skein-configurator'),
                                'ui_off_text' => __('No', 'skein-configurator'),
                            ),
                            array(
                                'key' => 'field_product_color_name',
                                'label' => __('Color Name', 'skein-configurator'),
                                'name' => 'color_name',
                                'type' => 'text',
                                'required' => 1,
                                'placeholder' => __('e.g., Ocean Blue', 'skein-configurator'),
                            ),
                            array(
                                'key' => 'field_product_color_code',
                                'label' => __('Color Code', 'skein-configurator'),
                                'name' => 'color_code',
                                'type' => 'color_picker',
                                'required' => 1,
                                'default_value' => '#3498db',
                                'enable_opacity' => 0,
                                'return_format' => 'string',
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_skein_product_lengths',
                        'label' => __('Length Options', 'skein-configurator'),
                        'name' => 'skein_product_lengths',
                        'type' => 'repeater',
                        'instructions' => __('Define available skein lengths with their corresponding prices for this product.', 'skein-configurator'),
                        'required' => 0,
                        'layout' => 'table',
                        'button_label' => __('Add Length', 'skein-configurator'),
                        'min' => 1,
                        'max' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_enable_skein_configurator',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                        'sub_fields' => array(
                            array(
                                'key' => 'field_product_length_enabled',
                                'label' => __('Enabled', 'skein-configurator'),
                                'name' => 'length_enabled',
                                'type' => 'true_false',
                                'required' => 0,
                                'default_value' => 1,
                                'ui' => 1,
                                'ui_on_text' => __('Yes', 'skein-configurator'),
                                'ui_off_text' => __('No', 'skein-configurator'),
                            ),
                            array(
                                'key' => 'field_product_length_value',
                                'label' => __('Length (meters)', 'skein-configurator'),
                                'name' => 'length_value',
                                'type' => 'number',
                                'required' => 1,
                                'min' => 1,
                                'step' => 1,
                            ),
                            array(
                                'key' => 'field_product_length_price',
                                'label' => __('Price', 'skein-configurator'),
                                'name' => 'length_price',
                                'type' => 'number',
                                'required' => 1,
                                'min' => 0,
                                'step' => 0.01,
                                'prepend' => get_woocommerce_currency_symbol(),
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'product',
                        ),
                    ),
                ),
                'menu_order' => 10,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
            ));
        }
    }

    /**
     * Helper function to get available colors for a product
     */
    public static function get_available_colors($product_id = null)
    {
        if (!$product_id) {
            global $product;
            $product_id = $product ? $product->get_id() : 0;
        }
        if (!$product_id) {
            return array();
        }
        $colors = get_field('skein_product_colors', $product_id);
        if (!$colors) {
            return array();
        }
        // Filter only enabled colors
        return array_filter($colors, function($color) {
            return isset($color['color_enabled']) ? $color['color_enabled'] : true;
        });
    }

    /**
     * Helper function to get available lengths for a product
     */
    public static function get_available_lengths($product_id = null)
    {
        if (!$product_id) {
            global $product;
            $product_id = $product ? $product->get_id() : 0;
        }
        if (!$product_id) {
            return array();
        }
        $lengths = get_field('skein_product_lengths', $product_id);
        if (!$lengths) {
            return array();
        }
        // Filter only enabled lengths
        return array_filter($lengths, function($length) {
            return isset($length['length_enabled']) ? $length['length_enabled'] : true;
        });
    }

    /**
     * Helper function to get max colors setting for a product
     */
    public static function get_max_colors($product_id = null)
    {
        return 5;
    }

    /**
     * Helper function to get overlay opacity for a product
     */
    public static function get_overlay_opacity($product_id = null)
    {
        return 0.7;
    }
}
