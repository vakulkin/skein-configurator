# Skein Configurator Plugin

A comprehensive WooCommerce product customizer that allows customers to select and arrange up to 5 yarn colors for custom skeins (balls of yarn), with real-time visual preview using a radial gradient overlay.

## Features

- **Visual Color Configurator**: Real-time SVG radial gradient overlay on product images
- **Flexible Color Selection**: Up to 5 colors with drag-and-drop reordering
- **Length Options**: Multiple skein lengths with different prices
- **Seamless Integration**: Works with existing WooCommerce products
- **ACF-Powered Settings**: Easy-to-use admin interface via Advanced Custom Fields
- **Cart & Order Integration**: Custom configurations stored and displayed throughout checkout
- **Responsive Design**: Works on all device sizes

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- Advanced Custom Fields (ACF) plugin
- PHP 7.4 or higher

## Installation

1. **Upload Plugin**
   - Upload the `skein-configurator` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Install Dependencies**
   - Install and activate **WooCommerce**
   - Install and activate **Advanced Custom Fields** (free or pro version)

3. **Activate Plugin**
   - Go to WordPress admin → Plugins
   - Find "Skein Configurator" and click "Activate"

## Configuration

### Step 1: Global Settings

1. Go to **Skein Settings** in WordPress admin menu
2. **Color Palette Tab**:
   - Click "Add Color" to add yarn colors
   - Enter color name (e.g., "Ocean Blue")
   - Select color code using color picker
   - Add as many colors as you want
3. **Length Options Tab**:
   - Click "Add Length" to add skein lengths
   - Enter length value (e.g., 100)
   - Select unit (meters, yards, or feet)
   - Enter price for this length
   - Add multiple length options
4. **Display Settings Tab**:
   - Adjust overlay opacity (0.1 to 1.0)
   - Set maximum colors (default: 5)

### Step 2: Configure Products

1. Edit or create a WooCommerce product
2. In the right sidebar, find **Skein Configurator** panel
3. Toggle "Enable Skein Configurator" to **Yes**
4. Set a product image (use a neutral-colored skein photo for best results)
5. Save/publish the product

## Usage

### For Customers (Frontend)

1. Visit a skein product page
2. **Select Length**: Choose desired skein length (affects price)
3. **Select Colors**: 
   - Click color swatches to add to slots (up to 5 colors)
   - Drag slots to reorder colors
   - Click filled slots to remove colors
4. **Preview**: Watch the real-time gradient overlay update on product image
5. **Add to Cart**: Configuration is saved with the cart item

### For Administrators

**View Orders**:
- Order details show selected colors with visual swatches
- Length and price information clearly displayed
- Admin order screen shows detailed configuration

**Product List**:
- Products with configurator enabled show ✓ icon in product list

## Technical Details

### How It Works

**Visual Preview**:
- SVG element overlays product image using `position: absolute`
- Radial gradient updates via JavaScript when colors change
- CSS `mix-blend-mode: multiply` blends colors with yarn texture
- Semi-transparent overlay preserves product photo detail

**Color Gradient Logic**:
- 1 color: Solid radial fill
- 2+ colors: Evenly distributed from center to edge
- Colors blend smoothly between positions
- Order of slots determines gradient pattern (center → outer)

**Data Storage**:
- Selected colors stored as JSON array with names and hex codes
- Length stored as JSON object with value, unit, and price
- Cart items store unique configurations
- Order meta preserves full customization data

### File Structure

```
skein-configurator/
├── skein-configurator.php          # Main plugin file
├── includes/
│   ├── class-skein-acf-config.php  # ACF field definitions
│   ├── class-skein-admin.php       # Admin functionality
│   ├── class-skein-frontend.php    # Frontend display
│   └── class-skein-cart.php        # Cart/order integration
├── assets/
│   ├── css/
│   │   ├── frontend.css            # Customer-facing styles
│   │   └── admin.css               # Admin styles
│   └── js/
│       └── frontend.js             # Interactive features
└── README.md
```

## Customization

### Modify Overlay Opacity
Go to **Skein Settings → Display Settings** and adjust the overlay opacity slider.

### Change Maximum Colors
Go to **Skein Settings → Display Settings** and change the maximum colors value (1-10).

### Custom Styling
Add custom CSS to override default styles:

```css
/* Change configurator background */
.skein-configurator {
    background: #ffffff;
    border-color: #your-color;
}

/* Customize color swatches */
.skein-color-swatch {
    border-radius: 50%; /* Make circular */
}
```

### Extend Functionality
Use WordPress hooks and filters:

```php
// Modify available colors programmatically
add_filter('skein_available_colors', function($colors) {
    // Your custom logic
    return $colors;
});

// Modify gradient generation
add_filter('skein_gradient_stops', function($stops, $colors) {
    // Your custom gradient logic
    return $stops;
}, 10, 2);
```

## Troubleshooting

**Configurator not appearing**:
- Verify WooCommerce and ACF are active
- Check product has "Enable Skein Configurator" toggled on
- Ensure colors and lengths are configured in settings

**Overlay not showing**:
- Make sure product has a featured image
- Check browser console for JavaScript errors
- Verify SVG element is present in page source

**Colors not saving to cart**:
- Check PHP error logs
- Verify WooCommerce cart is functioning
- Clear browser cache and cookies

**Price not updating**:
- Ensure length options have prices set
- Check WooCommerce price settings
- Verify product base price is set

## Support & Development

### Changelog

**Version 1.0.0**
- Initial release
- Color selection with up to 5 colors
- Length options with pricing
- Real-time SVG gradient preview
- Full cart and order integration
- ACF-powered admin interface

### Future Enhancements

- Pattern options (stripes, ombre, speckled)
- Weight/thickness selection
- Yarn texture options
- Preview image download
- Social sharing of designs
- Saved color combinations

## License

GPL v2 or later

## Credits

Developed for custom yarn product customization in WooCommerce stores.
