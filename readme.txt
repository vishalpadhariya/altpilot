=== AltPilot ===
Contributors: vishalpadhariya
Tags: alt text, seo, media library, bulk actions, automated
Requires at least: 5.0
Requires PHP: 8.2
Tested up to: 6.8
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto-generate missing ALT text for WordPress image attachments with multiple generation modes, bulk operations, and developer hooks.

== Description ==

AltPilot is a powerful WordPress plugin that automatically generates descriptive ALT text for images in your media library. Improve accessibility and SEO while saving time on manual ALT text entry.

= Features =

* **Automatic ALT Text Generation** - Generate ALT text from image titles, filenames, or combined with site name
* **Three Generation Modes** - Title Only, Title + Site Name, or Clean Filename
* **Bulk Operations** - Generate ALT text for your entire media library with one click
* **AJAX Batching** - Process images in batches to prevent timeouts
* **Media Library Integration** - View ALT status directly in media library
* **Logging** - Optional detailed logging for troubleshooting
* **Bulk Actions** - Process selected images from media library
* **Developer Hooks** - Extensive filters and actions for custom integration
* **Multi-language Ready** - Fully translatable

= Generation Modes =

**Title Only** - Uses the clean image title as ALT text

**Title + Site Name** - Combines the image title with your blog name

**Clean Filename** - Extracts readable text from filenames, removing common noise words (v1, v2, final, copy, img, image, etc.)

= Three Ways to Use =

1. **Enable Auto-generate** - Automatically generate ALT text when images are uploaded
2. **Bulk Run Tool** - Generate ALT text for entire media library from settings page
3. **Media Library Bulk Action** - Select specific images and generate ALT text

= Security & Performance =

* Secure nonce verification on all AJAX actions
* Proper capability checks for all admin functions
* Efficient batching prevents server timeouts
* Configurable batch sizes (5-200 images per batch)
* Works with large media libraries (1000+ images)

= Developer-Friendly =

* Modular class-based architecture
* Multiple action and filter hooks for customization
* Clean namespace implementation (AltPilot\)
* Well-documented code with PHPDoc comments
* Easy to extend and integrate with other plugins

= Translations =

AltPilot is fully translatable. Text domain: `altpilot`

== Installation ==

1. Download the plugin files
2. Upload the `altpilot` folder to `/wp-content/plugins/` directory
3. Activate the plugin through 'Plugins' menu in WordPress
4. Go to Settings → AltPilot to configure options

== Configuration ==

Navigate to **Settings → AltPilot** to configure:

* **Auto-generate on Upload** - Enable automatic generation when images are uploaded
* **Generation Mode** - Choose how ALT text is built (Title Only, Title + Site Name, Clean Filename)
* **Allowed Image Types** - Select which formats to process (JPG, PNG, GIF, WebP, AVIF, SVG)
* **Enable Logging** - Turn on logging to wp-content/uploads/altpilot-logs/
* **Batch Size** - Set images per batch (5-200, default 30)

== Usage ==

= Auto-generate on Upload =
Enable the "Auto-generate on upload" setting to automatically generate ALT text for newly uploaded images.

= Bulk Run All Media =
1. Go to Settings → AltPilot
2. Click "Run ALT Generator on all media (missing only)"
3. Watch the progress bar
4. Only processes images without existing ALT text

= Media Library Bulk Action =
1. Go to Media Library
2. Select one or more images
3. Choose "Generate ALT with AltPilot" from Bulk Actions
4. Click Apply

= Media Library Column =
View ALT status directly in the media library with the new "ALT Status" column showing:
* "Present" (green) - Image has ALT text
* "Missing" (red) - Image needs ALT text

== API Reference ==

= Filters =

**altpilot_generated_alt**
Modify the generated ALT text before it's saved.

    add_filter( 'altpilot_generated_alt', function( $alt, $attachment_id ) {
        return 'Image: ' . $alt;
    }, 10, 2 );

**altpilot_clean_title**
Modify the cleaned title before final ALT text.

**altpilot_clean_filename**
Modify the cleaned filename before final ALT text.

= Classes =

* `AltPilot\Core\Generator` - ALT text generation logic
* `AltPilot\Core\Logger` - Logging functionality
* `AltPilot\Core\Options` - Settings management
* `AltPilot\Admin\Admin` - Admin interface
* `AltPilot\Public_Hooks\Public_Hooks` - Frontend hooks

See DEVELOPER-GUIDE.md for detailed API documentation.

== FAQ ==

= Will this overwrite existing ALT text? =

No. AltPilot only generates ALT text for images that don't already have it.

= Can I customize the ALT text? =

Yes! Use the provided filters (altpilot_generated_alt, altpilot_clean_title, altpilot_clean_filename) to customize generation.

= What about performance with large media libraries? =

AJAX batching handles large libraries efficiently. The system processes configurable batches with delays to prevent timeouts.

= Where are logs stored? =

Logs are stored at wp-content/uploads/altpilot-logs/altpilot.log when logging is enabled.

= Which image types are supported? =

JPG/JPEG, PNG, GIF, WebP, AVIF, and SVG. All can be enabled/disabled in settings.

= Will this work with custom post types? =

Currently supports standard WordPress attachments. Use filters to extend for custom implementations.

= What happens on deactivation? =

Settings and logs are preserved. Simply reactivate to continue using.

== Screenshots ==

1. Settings page with configuration options
2. Media library with ALT Status column
3. Bulk action in media library
4. Progress bar for bulk run operations
5. Admin settings for advanced configuration

== Requirements ==

* WordPress 5.0 or higher
* PHP 7.2 or higher
* Multibyte string support (mb_* functions)

== Compatibility ==

Works with:
* WordPress 5.0 - 6.4+
* PHP 7.2 - 8.3+
* All standard WordPress themes
* WooCommerce (for product images)
* Most image optimization plugins

== Performance ==

* Configurable batch sizes (5-200 images)
* AJAX processing prevents timeouts
* Efficient database queries with meta queries
* Optional logging for better performance control
* Suitable for media libraries with 1000+ images

== Support ==

For questions, issues, or feature requests, please contact the plugin author or refer to documentation.

== Changelog ==

= 1.1.0 =
* Refactored into modular architecture
* Separated core, admin, and public functionality into classes
* Added comprehensive documentation
* Improved code organization and maintainability
* Enhanced logging capabilities
* Added developer hooks and filters
* Full namespace implementation

= 1.0.0 =
* Initial release
* Auto-generation on upload
* Bulk operations with AJAX batching
* Media library integration
* Settings page and configuration

== Upgrade Notice ==

= 1.1.0 =
Major refactor with improved architecture and documentation. No breaking changes. All existing settings are preserved.

== License ==

This plugin is licensed under GPLv2 or later.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2 or later,
    as published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA

== Credits ==

Built with WordPress best practices and security in mind.

Made with ❤️ for WordPress developers and site owners.
