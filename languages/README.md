# Skein Configurator Translations

This directory contains translation files for the Skein Configurator plugin.

## Available Translations

- **Polish (pl_PL)**: Complete translation available in `skein-configurator-pl_PL.po`

## How to Use Polish Translation

1. The Polish translation file is already included: `skein-configurator-pl_PL.po`
2. Compile it to .mo file using one of these methods:

### Method 1: Using Loco Translate Plugin (Recommended)
1. Install and activate the "Loco Translate" plugin
2. Go to Loco Translate → Plugins
3. Find "Skein Configurator" in the list
4. The Polish translation should be automatically detected
5. Click "Sync" if needed to update the translation

### Method 2: Using Poedit
1. Download and install Poedit (https://poedit.net/)
2. Open `skein-configurator-pl_PL.po` in Poedit
3. Click "Save" - this will automatically generate the .mo file

### Method 3: Using msgfmt command line
```bash
msgfmt -o skein-configurator-pl_PL.mo skein-configurator-pl_PL.po
```

## How to Add New Translations

1. Copy the template file `skein-configurator.pot`
2. Rename it to `skein-configurator-{locale}.po` (e.g., `skein-configurator-de_DE.po` for German)
3. Translate all msgstr entries
4. Compile to .mo file using one of the methods above

## Translation Context

All translatable strings in the plugin use the text domain: `skein-configurator`

## Files Structure

```
languages/
├── skein-configurator.pot          # Template file for new translations
├── skein-configurator-pl_PL.po     # Polish translation source
└── skein-configurator-pl_PL.mo     # Polish compiled translation (generated)
```

## Updating Translations

If you add new translatable strings to the plugin:

1. Update the .pot template file
2. Update existing .po files with new strings
3. Recompile .mo files

## WordPress Language Settings

To use Polish translation:
1. Go to Settings → General in WordPress admin
2. Set "Site Language" to "Polski"
3. The plugin will automatically load Polish translations

## String Locations

- **PHP Files**: All user-facing strings wrapped in `__()`, `_e()`, `esc_html__()`, etc.
- **JavaScript**: Strings passed via `wp_localize_script()` in `skeinConfig.strings` object
- **ACF Fields**: All field labels, instructions, and messages

## Support

For translation issues or to contribute new translations, please contact the plugin author.
