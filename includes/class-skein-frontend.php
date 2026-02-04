<?php
/**
 * Frontend Display Class
 * Handles the configurator UI on product pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Skein_Frontend
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_configurator'), 10);
        add_action('wp_footer', array($this, 'add_overlay_svg_template'));
        add_filter('woocommerce_get_price_html', array($this, 'hide_price_for_configurator'), 10, 2);
        add_action('wp_footer', array($this, 'disable_woodmart_zoom'));
    }

    /**
     * Hide price for products with configurator enabled
     */
    public function hide_price_for_configurator($price, $product)
    {
        if (!is_product()) {
            return $price;
        }
        
        $product_id = $product->get_id();
        $enabled = get_field('enable_skein_configurator', $product_id);
        
        if ($enabled) {
            return '';
        }
        
        return $price;
    }

    /**
     * Add body class for configurator products
     */
    public function add_configurator_body_class($classes)
    {
        if ($this->is_configurator_enabled()) {
            $classes[] = 'skein-configurator-enabled';
        }
        return $classes;
    }

    /**
     * Disable Woodmart theme zoom/lightbox for configurator products
     */
    public function disable_woodmart_zoom()
    {
        if (!$this->is_configurator_enabled()) {
            return;
        }

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Disable Woodmart photoswipe/lightbox
                $('.woocommerce-product-gallery').off('click', '.woocommerce-product-gallery__trigger');
                $('.woocommerce-product-gallery__image a').off('click').on('click', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                // Remove photoswipe initialization
                if (typeof $.fn.wc_product_gallery !== 'undefined') {
                    $('.woocommerce-product-gallery').trigger('destroy.wc_product_gallery');
                }
                
                // Disable Woodmart specific zoom
                $('.product-images').addClass('no-zoom');
                $('.product-image-wrap').off('click');
                
                // Prevent any image click events
                $('.product-images a, .product-image-wrap a').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            });
        </script>
        <?php
    }

    /**
     * Check if current product has configurator enabled
     */
    private function is_configurator_enabled()
    {
        if (!is_product()) {
            return false;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product')) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product) {
            return false;
        }

        return get_field('enable_skein_configurator', $product->get_id());
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets()
    {
        if (!$this->is_configurator_enabled()) {
            return;
        }

        // Font Awesome is already loaded on the site

        // Enqueue SortableJS
        wp_enqueue_script(
            'sortablejs',
            'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js',
            array(),
            '1.15.0',
            true
        );

        // Enqueue SweetAlert2
        wp_enqueue_style(
            'sweetalert2-css',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11.0.0'
        );

        wp_enqueue_script(
            'sweetalert2-js',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
            array(),
            '11.0.0',
            true
        );

        // Enqueue custom CSS
        wp_enqueue_style(
            'skein-configurator-css',
            SKEIN_CONFIGURATOR_PLUGIN_URL . 'assets/css/frontend.css',
            array('sweetalert2-css'),
            SKEIN_CONFIGURATOR_VERSION
        );

        // Enqueue custom JS
        wp_enqueue_script(
            'skein-configurator-js',
            SKEIN_CONFIGURATOR_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'sortablejs', 'sweetalert2-js'),
            SKEIN_CONFIGURATOR_VERSION,
            true
        );

        // Get product ID for settings
        global $product;
        $product_id = $product ? $product->get_id() : 0;

        // Localize script with settings
        wp_localize_script('skein-configurator-js', 'skeinConfig', array(
            'maxColors' => Skein_ACF_Config::get_max_colors($product_id),
            'overlayOpacity' => Skein_ACF_Config::get_overlay_opacity($product_id),
            'strings' => array(
                'emptySlot' => __('Empty Slot', 'skein-configurator'),
                'empty' => __('Empty', 'skein-configurator'),
                'clickToRemove' => __('Click to remove', 'skein-configurator'),
                'dragToReorder' => __('Drag to reorder colors', 'skein-configurator'),
                'selectLength' => __('Please select a skein length', 'skein-configurator'),
                'selectColor' => __('Select Color', 'skein-configurator'),
                'close' => __('Close', 'skein-configurator'),
                'colorSelected' => __('Color Selected', 'skein-configurator'),
                'selectAnotherColor' => __('Do you want to select a color for another empty slot?', 'skein-configurator'),
                'yes' => __('Yes', 'skein-configurator'),
                'no' => __('No', 'skein-configurator'),
            ),
        ));
    }

    /**
     * Add overlay SVG via JavaScript
     */
    public function add_overlay_svg_template()
    {
        if (!$this->is_configurator_enabled()) {
            return;
        }
        ?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		// Wrap product image with overlay if not already wrapped
		var $productImage = $('.woocommerce-product-gallery__image img, .wp-post-image').first();

		if ($productImage.length && !$productImage.closest('.skein-image-wrapper').length) {
			var svgOverlay =
				'<svg class="skein-overlay" id="skeinOverlaySvg" viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">' +
				'<defs>' +
				'<radialGradient id="skeinGradient">' +
				'</radialGradient>' +
				'</defs>' +
				'<circle cx="400" cy="400" r="400" fill="url(#skeinGradient)" />' +
				'</svg>';

			$productImage.wrap('<div class="skein-image-wrapper"></div>');
			$productImage.after(svgOverlay);
		}
	});
</script>
<?php
    }

    /**
     * Render the configurator UI
     */
    public function render_configurator()
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;

        global $product;

        if (!$this->is_configurator_enabled()) {
            return;
        }

        $colors = Skein_ACF_Config::get_available_colors();
        $lengths = Skein_ACF_Config::get_available_lengths($product->get_id());
        $max_colors = Skein_ACF_Config::get_max_colors($product->get_id());

        if (empty($colors)) {
            echo '<div class="skein-configurator-error">';
            echo '<p>' . esc_html__('Please add colors for this product.', 'skein-configurator') . '</p>';
            echo '</div>';
            return;
        }

        if (empty($lengths)) {
            echo '<div class="skein-configurator-error">';
            echo '<p>' . esc_html__('Please add length options for this product.', 'skein-configurator') . '</p>';
            echo '</div>';
            return;
        }

        ?>
<div id="skeinConfigurator" class="skein-configurator">

	<!-- Length Selection -->
	<div class="skein-section skein-length-section">
		<h3 class="skein-section-title">
			<?php esc_html_e('Select Skein Length', 'skein-configurator'); ?>
		</h3>
		<div class="skein-length-dropdown-wrapper">
			<select name="skein_length" id="skeinLengthSelect" class="skein-length-select">
				<option value=""><?php esc_html_e('Choose length...', 'skein-configurator'); ?></option>
				<?php foreach ($lengths as $index => $length): ?>
				<option 
					value="<?php echo esc_attr(json_encode($length)); ?>"
					data-price="<?php echo esc_attr($length['length_price']); ?>"
					<?php selected($index, 0); ?>
				>
					<?php 
                        $unit = isset($length['length_unit']) ? $length['length_unit'] : __('meters', 'skein-configurator');
                        echo esc_html($length['length_value'] . ' ' . $unit . ' - ' . strip_tags(wc_price($length['length_price']))); 
                    ?>
				</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<!-- Color Selection -->
	<div class="skein-section skein-color-section">
		<h3 class="skein-section-title">
			<?php
                    printf(
                        esc_html__('Select Colors (Up to %d)', 'skein-configurator'),
                        $max_colors
                    );
        ?>
		</h3>

		<!-- Hidden Color Data for JS -->
		<div class="skein-color-data" style="display: none;">
			<?php foreach ($colors as $color): ?>
			<div class="skein-color-swatch"
				data-color-name="<?php echo esc_attr($color['color_name']); ?>"
				data-color-code="<?php echo esc_attr($color['color_code']); ?>">
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Color Slots -->
		<div class="skein-color-slots-wrapper">
			<p class="skein-slots-instruction">
				<?php esc_html_e('Click a slot to select a color. Drag to reorder.', 'skein-configurator'); ?>
			</p>
			<ul class="skein-color-slots" id="skeinColorSlots">
				<?php for ($i = 1; $i <= $max_colors; $i++): ?>
				<li class="skein-color-slot empty"
					data-slot="<?php echo $i; ?>">
					<span class="slot-edit-icon" title="<?php esc_attr_e('Edit color', 'skein-configurator'); ?>">
					<i class="fas fa-pencil-alt"></i>
					</span>
					<span class="slot-name"><?php esc_html_e('Empty', 'skein-configurator'); ?></span>
					<span class="slot-clear-icon" title="<?php esc_attr_e('Clear color', 'skein-configurator'); ?>">
					<i class="fas fa-times"></i>
					</span>
				</li>
				<?php endfor; ?>
			</ul>
		</div>
	</div>

	<!-- Hidden inputs to store configuration -->
	<input type="hidden" name="skein_selected_colors" id="skeinSelectedColors" value="">
	<input type="hidden" name="skein_selected_length" id="skeinSelectedLength" value="">

</div>
<?php
    }
}
?>