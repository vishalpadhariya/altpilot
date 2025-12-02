# AltPilot

**Auto-generate missing ALT text for WordPress image attachments**

AltPilot is a powerful WordPress plugin that automatically generates descriptive ALT text for images in your media library. It supports multiple generation modes, bulk operations, logging, and provides developer-friendly hooks for customization.

## Features

- ✨ **Automatic ALT Text Generation** - Generate ALT text from image titles, filenames, or combined with site name
- 🎯 **Three Generation Modes**
  - **Title Only** - Use clean image title as ALT text
  - **Title + Site Name** - Combine title with your blog name
  - **Clean Filename** - Extract readable text from filenames, removing noise
- 🚀 **Bulk Operations** - Generate ALT text for your entire media library with one click
- ⚙️ **AJAX Batching** - Process images in batches to prevent timeouts
- 📋 **Media Library Integration** - View ALT status directly in media library columns
- 📊 **Logging** - Optional detailed logging for troubleshooting
- 🎮 **Bulk Actions** - Process selected images from media library
- 🔌 **Developer Hooks** - Extensive filters and actions for custom integration
- 🌍 **Multi-language Ready** - Fully translatable (text domain: `altpilot`)

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/altpilot/` directory
3. Activate the plugin in WordPress admin
4. Go to **Settings → AltPilot** to configure

## Configuration

Navigate to **Settings → AltPilot** in the WordPress admin panel.

### Settings

#### Auto-generate on Upload
Enable automatic ALT text generation when new images are uploaded to your media library.

#### Generation Mode
Choose how AltPilot builds ALT text:
- **Title Only** - Uses just the image title
- **Title + Site Name** - Appends your blog name to the title
- **Clean Filename** - Extracts readable text from filenames, removing common noise words (v1, v2, final, copy, etc.)

#### Allowed Image Types
Select which image formats AltPilot will process:
- JPG/JPEG
- PNG
- GIF
- WebP
- AVIF
- SVG

#### Enable Logging
Turn on logging to help troubleshoot generation issues. Logs are stored at `wp-content/uploads/altpilot-logs/altpilot.log`.

#### Batch Size
Set the number of images to process per AJAX batch when running bulk operations (minimum: 5, maximum: 200).

## Usage

### Manual Single Bulk Action
1. Go to **Media Library**
2. Select one or more images
3. From the "Bulk Actions" dropdown, choose "Generate ALT with AltPilot"
4. Click "Apply"

### Automatic Bulk Run
1. Go to **Settings → AltPilot**
2. Under "Tools", click "Run ALT Generator on all media (missing only)"
3. Watch the progress bar as the plugin processes your images
4. The tool only generates ALT text for images that don't already have it

### Media Library Column
A new "ALT Status" column appears in the media library showing:
- **Present** (green) - Image has ALT text
- **Missing** (red) - Image needs ALT text

## How It Works

### Title Only Mode
```
Image Title: "Product Photo"
Generated ALT: "Product Photo"
```

### Title + Site Name Mode
```
Image Title: "Sunset Landscape"
Site Name: "Travel Blog"
Generated ALT: "Sunset Landscape – Travel Blog"
```

### Clean Filename Mode
```
Filename: "IMG_2024_01_15_sunset-final-v2.jpg"
Generated ALT: "Sunset" (removes IMG, dates, version numbers, "final")
```

## API Reference

### Filters

#### `altpilot_generated_alt`
Allows modification of the generated ALT text before it's saved.

**Parameters:**
- `$alt` (string) - The generated ALT text
- `$attachment_id` (int) - The attachment post ID

**Example:**
```php
add_filter( 'altpilot_generated_alt', function( $alt, $attachment_id ) {
    // Add prefix to all generated ALT text
    return 'Image: ' . $alt;
}, 10, 2 );
```

#### `altpilot_clean_title`
Modify the cleaned title before final ALT text generation (Title Only mode).

**Parameters:**
- `$title` (string) - The cleaned title

**Example:**
```php
add_filter( 'altpilot_clean_title', function( $title ) {
    // Convert to lowercase
    return strtolower( $title );
} );
```

#### `altpilot_clean_filename`
Modify the cleaned filename before final ALT text generation (Clean Filename mode).

**Parameters:**
- `$name` (string) - The cleaned filename

**Example:**
```php
add_filter( 'altpilot_clean_filename', function( $name ) {
    // Add custom suffix
    return $name . ' - Image';
} );
```

### Classes & Methods

The plugin uses a modular architecture with the following main classes:

#### `AltPilot\Core\Generator`
Handles ALT text generation logic.

**Methods:**
- `build_alt( $attachment_id, $title, $mode )` - Generate ALT text for an attachment
  - `$attachment_id` (int) - The attachment ID
  - `$title` (string) - Image title
  - `$mode` (string) - Generation mode: 'title_only', 'title_site', or 'filename_clean'
  - Returns: (string) Generated ALT text

#### `AltPilot\Core\Logger`
Handles operation logging.

**Methods:**
- `log( $message, $enabled )` - Log a message if logging is enabled
  - `$message` (string) - Message to log
  - `$enabled` (bool) - Whether logging is enabled
  - Returns: (bool) Success
- `get_log_dir()` - Get the log directory path
- `get_log_file()` - Get the log file path
- `clear_logs()` - Clear all logs

#### `AltPilot\Core\Options`
Manages plugin settings.

**Methods:**
- `get_all()` - Get all options
- `get( $key, $default )` - Get a specific option
  - `$key` (string) - Option key
  - `$default` (mixed) - Default value
  - Returns: (mixed) Option value
- `update( $new_options )` - Update options
  - `$new_options` (array) - Options to update
  - Returns: (array) Sanitized options

#### `AltPilot\Admin\Admin`
Handles admin interface and functionality.

#### `AltPilot\Public_Hooks\Public_Hooks`
Handles public (frontend) hooks like auto-generation on upload.

## Compatibility

- **WordPress:** 5.0+
- **PHP:** 7.2+
- **MB Functions:** Uses `mb_convert_case()` for multibyte character support

## Frequently Asked Questions

### Will this overwrite existing ALT text?
No. AltPilot only generates ALT text for images that don't already have it. Existing ALT text is never modified.

### Can I customize the ALT text generation?
Yes! Use the provided filters (`altpilot_generated_alt`, `altpilot_clean_title`, `altpilot_clean_filename`) to customize generation behavior.

### What happens to logs?
Logs are stored in `wp-content/uploads/altpilot-logs/altpilot.log`. You can enable/disable logging in settings, or clear logs manually by deleting the file.

### Why are some images not being processed?
Check these conditions:
1. Image must match an allowed MIME type in settings
2. Image must not already have ALT text
3. Ensure sufficient server permissions for logging directory (if enabled)

### Can I use this with custom post types?
Currently, AltPilot only processes standard WordPress attachments. Use filters to extend functionality for custom implementations.

### How does "Clean Filename" mode work?
It removes common noise words (v1, v2, final, copy, img, image, etc.), numeric sequences, and file separators, then capitalizes the result.

### Will running bulk operations timeout?
No. The AJAX batching system processes a configurable batch of images (default: 30) with 250ms delays, preventing timeouts even on large media libraries.

## Performance

- Batch processing prevents PHP timeout errors
- Configurable batch sizes (5-200 items)
- Efficient WP_Query with meta query for finding missing ALT text
- Optional logging can be disabled for better performance
- Suitable for media libraries with 1000+ images

## Troubleshooting

### ALT text not generating
1. Check if "Auto-generate on upload" is enabled
2. Verify image MIME type is in allowed list
3. Ensure image doesn't already have ALT text
4. Check logs if logging is enabled

### Bulk run not completing
1. Increase batch size gradually to find optimal setting
2. Check server PHP timeout settings
3. Verify sufficient server memory (>128MB recommended)
4. Check browser console for JavaScript errors

### Logs not being created
1. Ensure `wp-content/uploads/` directory is writable
2. Check server file permissions
3. Verify logging is enabled in settings
4. Look for existing `altpilot-logs/` directory permissions

## Development

The plugin uses a modular architecture with clear separation of concerns:

- `includes/Core/` - Core functionality (Generator, Logger, Options)
- `includes/Admin/` - Admin interface and settings
- `includes/Public/` - Public-facing functionality
- `assets/` - JavaScript and CSS assets
- `languages/` - Localization files

See `DEVELOPER-GUIDE.md` for detailed information on extending the plugin.

## Support & Contributing

For issues, questions, or contributions, please refer to the plugin documentation or contact the plugin author.

## License

This plugin is licensed under GPLv2 or later. See the LICENSE file for details.

## Changelog

### Version 1.1.0
- Refactored into modular architecture
- Separated core, admin, and public functionality into classes
- Added comprehensive documentation
- Improved code organization and maintainability
- Enhanced logging capabilities
- Added developer hooks and filters

### Version 1.0.0
- Initial release
- Auto-generation on upload
- Bulk operations
- Media library integration
- Settings page

## Credits

Built with WordPress best practices and security in mind.

---

**Made with ❤️ for WordPress developers and site owners.**
