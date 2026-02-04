<?php

/**
 * Cart Integration Class
 * Handles adding custom data to cart and displaying in cart/checkout/orders
 */

if (!defined('ABSPATH')) {
    exit;
}

class Skein_Cart
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
        // Validate before adding to cart
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);

        // Add custom data to cart
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);

        // Display custom data in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);

        // Save custom data to order items
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_data'), 10, 4);

        // Display in order details (customer view)
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'format_order_item_meta'), 10, 2);

        // Display in admin order details
        add_action('woocommerce_before_order_itemmeta', array($this, 'display_admin_order_item_meta'), 10, 3);

        // Adjust cart item price based on length
        add_filter('woocommerce_add_cart_item', array($this, 'adjust_cart_item_price'), 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'adjust_cart_item_price'), 10, 2);

        // Modify add to cart button on product listings
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'change_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_add_to_cart_url', array($this, 'change_add_to_cart_url'), 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', array($this, 'change_add_to_cart_link'), 10, 2);

        // Add gradient overlay to cart item thumbnails
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'add_gradient_to_cart_thumbnail'), 10, 3);
    }

    /**
     * Validate required fields before adding to cart
     */
    public function validate_add_to_cart($passed, $product_id, $quantity)
    {
        // Check if product has configurator enabled
        $enabled = get_field('enable_skein_configurator', $product_id);

        if (!$enabled) {
            return $passed;
        }

        $errors = array();

        // Validate length selection
        $length_selected = false;
        if (isset($_POST['skein_selected_length']) && !empty($_POST['skein_selected_length'])) {
            $length = json_decode(stripslashes($_POST['skein_selected_length']), true);
            if (!empty($length) && isset($length['length_value']) && $length['length_value'] > 0) {
                $length_selected = true;
            }
        }

        if (!$length_selected) {
            $errors[] = __('Please select a Length before adding to cart.', 'skein-configurator');
        }

        // Validate color selection
        $colors_selected = false;
        if (isset($_POST['skein_selected_colors']) && !empty($_POST['skein_selected_colors'])) {
            $colors = json_decode(stripslashes($_POST['skein_selected_colors']), true);
            if (!empty($colors) && is_array($colors) && count($colors) > 0) {
                $colors_selected = true;
            }
        }

        if (!$colors_selected) {
            $errors[] = __('Please select at least one color before adding to cart.', 'skein-configurator');
        }

        // If there are errors, display them and prevent adding to cart
        if (!empty($errors)) {
            foreach ($errors as $error) {
                wc_add_notice($error, 'error');
            }
            return false;
        }

        return $passed;
    }

    /**
     * Add custom skein data to cart item
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        // Check if product has configurator enabled
        $enabled = get_field('enable_skein_configurator', $product_id);

        if (!$enabled) {
            return $cart_item_data;
        }

        // Get Colors
        if (isset($_POST['skein_selected_colors']) && !empty($_POST['skein_selected_colors'])) {
            $colors = json_decode(stripslashes($_POST['skein_selected_colors']), true);
            if (!empty($colors)) {
                $cart_item_data['skein_colors'] = $colors;
            }
        }

        // Get selected length
        if (isset($_POST['skein_selected_length']) && !empty($_POST['skein_selected_length'])) {
            $length = json_decode(stripslashes($_POST['skein_selected_length']), true);
            if (!empty($length)) {
                // Calculate price based on length
                $price_per_unit = get_field('skein_price_per_unit', $product_id);
                if ($price_per_unit && isset($length['length_value'])) {
                    $length['length_price'] = floatval($length['length_value']) * floatval($price_per_unit);
                }
                $cart_item_data['skein_length'] = $length;
            }
        }

        // Make each configuration unique
        if (isset($cart_item_data['skein_colors']) || isset($cart_item_data['skein_length'])) {
            $cart_item_data['skein_unique_key'] = md5(microtime() . rand());
        }

        return $cart_item_data;
    }

    /**
     * Display custom data in cart and checkout
     */
    public function display_cart_item_data($item_data, $cart_item)
    {
        // Display selected length
        if (isset($cart_item['skein_length']) && is_array($cart_item['skein_length'])) {
            $length = $cart_item['skein_length'];
            if (isset($length['length_value'])) {
                $unit = isset($length['length_unit']) ? $length['length_unit'] : __('meters', 'skein-configurator');
                $item_data[] = array(
                    'key' => __('Length', 'skein-configurator'),
                    'value' => sprintf(
                        '%s %s',
                        $length['length_value'],
                        $unit
                    ),
                    'display' => '',
                );
            }
        }

        // Display Colors
        if (isset($cart_item['skein_colors']) && !empty($cart_item['skein_colors'])) {
            $color_names = array();
            
            foreach ($cart_item['skein_colors'] as $color) {
                if (!is_array($color) || !isset($color['name'])) {
                    continue;
                }
                $color_names[] = esc_html($color['name']);
            }

            $item_data[] = array(
                'key' => __('Colors', 'skein-configurator'),
                'value' => implode(' → ', $color_names),
                'display' => '',
            );
        }

        return $item_data;
    }

    /**
     * Save custom data to order items
     */
    public function save_order_item_data($item, $cart_item_key, $values, $order)
    {
        // Save length
        if (isset($values['skein_length']) && is_array($values['skein_length'])) {
            $length = $values['skein_length'];
            if (isset($length['length_value'])) {
                $unit = isset($length['length_unit']) ? $length['length_unit'] : 'meters';
                $item->add_meta_data(
                    __('Length', 'skein-configurator'),
                    sprintf('%s %s', $length['length_value'], $unit),
                    true
                );
                // Store raw data for admin
                $item->add_meta_data('_skein_length_data', $length, false);
            }
        }

        // Save colors
        if (isset($values['skein_colors']) && is_array($values['skein_colors'])) {
            $colors = $values['skein_colors'];

            // Create a readable string
            $color_names = array_map(function ($color) {
                return isset($color['name']) ? $color['name'] : '';
            }, $colors);
            $color_names = array_filter($color_names); // Remove empty values

            $item->add_meta_data(
                __('Colors', 'skein-configurator'),
                implode(' → ', $color_names),
                true
            );

            // Store raw data for admin
            $item->add_meta_data('_skein_colors_data', $colors, false);
        }
    }

    /**
     * Format order item meta for display
     */
    public function format_order_item_meta($formatted_meta, $item)
    {
        // Color names are already formatted correctly by save_order_item_data
        // No need for additional formatting
        return $formatted_meta;
    }

    /**
     * Display custom meta in admin order details
     */
    public function display_admin_order_item_meta($item_id, $item, $product)
    {
        if (!is_admin()) {
            return;
        }

        $colors_data = $item->get_meta('_skein_colors_data', true);
        $length_data = $item->get_meta('_skein_length_data', true);

        if ($colors_data || $length_data) {
            echo '<div class="skein-admin-order-meta" style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #0071a1;">';

            if ($length_data && is_array($length_data)) {
                if (isset($length_data['length_value'])) {
                    $unit = isset($length_data['length_unit']) ? $length_data['length_unit'] : 'meters';
                    echo '<p style="margin: 0 0 8px 0;"><strong>' . esc_html__('Length:', 'skein-configurator') . '</strong> ';
                    echo esc_html($length_data['length_value'] . ' ' . $unit);
                    if (isset($length_data['length_price'])) {
                        echo ' <span style="color: #666;">(' . wc_price($length_data['length_price']) . ')</span>';
                    }
                    echo '</p>';
                }
            }

            if ($colors_data && is_array($colors_data)) {
                $color_names = array();
                foreach ($colors_data as $color) {
                    if (!is_array($color) || !isset($color['name'])) {
                        continue;
                    }
                    $color_names[] = esc_html($color['name']);
                }
                
                echo '<p style="margin: 0;"><strong>' . esc_html__('Colors:', 'skein-configurator') . '</strong> ';
                echo implode(' → ', $color_names);
                echo '</p>';
            }

            echo '</div>';
        }
    }

    /**
     * Adjust cart item price based on selected length
     */
    public function adjust_cart_item_price($cart_item, $session_values = null)
    {
        if (isset($cart_item['skein_length']) && is_array($cart_item['skein_length'])) {
            $length = $cart_item['skein_length'];

            if (isset($length['length_price'])) {
                $new_price = floatval($length['length_price']);

                if ($new_price > 0) {
                    $cart_item['data']->set_price($new_price);
                }
            }
        }

        return $cart_item;
    }

    /**
     * Change add to cart button text on product listings
     */
    public function change_add_to_cart_text($text, $product)
    {
        if (!$product) {
            return $text;
        }

        $enabled = get_field('enable_skein_configurator', $product->get_id());

        if ($enabled) {
            return __('Select Options', 'skein-configurator');
        }

        return $text;
    }

    /**
     * Change add to cart button URL on product listings
     */
    public function change_add_to_cart_url($url, $product)
    {
        if (!$product) {
            return $url;
        }

        $enabled = get_field('enable_skein_configurator', $product->get_id());

        if ($enabled) {
            return get_permalink($product->get_id());
        }

        return $url;
    }

    /**
     * Modify the entire add to cart link HTML on product listings
     */
    public function change_add_to_cart_link($html, $product)
    {
        if (!$product) {
            return $html;
        }

        $enabled = get_field('enable_skein_configurator', $product->get_id());

        if ($enabled) {
            // Build the select options button
            $link = sprintf(
                '<a href="%s" class="button product_type_simple add_to_cart_button" rel="nofollow">%s</a>',
                esc_url(get_permalink($product->get_id())),
                esc_html__('Select Options', 'skein-configurator')
            );

            return $link;
        }

        return $html;
    }

    /**
     * Add gradient overlay to cart item thumbnails
     */
    public function add_gradient_to_cart_thumbnail($product_img, $cart_item, $cart_item_key)
    {
        // Check if this cart item has skein colors
        if (!isset($cart_item['skein_colors']) || empty($cart_item['skein_colors'])) {
            return $product_img;
        }

        // Get product ID
        $product_id = $cart_item['product_id'];
        
        // Check if configurator is enabled
        $enabled = get_field('enable_skein_configurator', $product_id);
        if (!$enabled) {
            return $product_img;
        }

        // Get overlay opacity
        $opacity = Skein_ACF_Config::get_overlay_opacity($product_id);
        
        // Build gradient stops from selected colors
        $colors = $cart_item['skein_colors'];
        $gradient_stops = '';
        $num_colors = count($colors);
        
        if ($num_colors > 0) {
            foreach ($colors as $index => $color) {
                if (!isset($color['code'])) {
                    continue;
                }
                $offset = ($index / max(1, $num_colors - 1)) * 100;
                if ($num_colors == 1) {
                    $offset = 50;
                }
                $gradient_stops .= sprintf(
                    '<stop offset="%s%%" stop-color="%s" stop-opacity="%s" />',
                    $offset,
                    esc_attr($color['code']),
                    $opacity
                );
            }
        }

        // Generate unique ID for this cart item
        $unique_id = 'skein-cart-' . md5($cart_item_key);
        
        // Build the HTML with image wrapper and SVG overlay
        $html = '<div class="skein-cart-image-wrapper" style="position: relative; display: inline-block;">';
        $html .= $product_img;
        $html .= sprintf(
            '<svg class="skein-cart-overlay" style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%; pointer-events: none; mix-blend-mode: multiply;" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="%s">
                        %s
                    </radialGradient>
                </defs>
                <circle cx="400" cy="400" r="400" fill="url(#%s)" />
            </svg>',
            $unique_id,
            $gradient_stops,
            $unique_id
        );
        $html .= '</div>';

        return $html;
    }
}
