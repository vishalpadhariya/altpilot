<?php

/**
 * AltPilot Admin Class
 *
 * Handles plugin admin UI, settings page, and bulk actions.
 *
 * @package AltPilot
 * @subpackage Admin
 */

namespace AltPilot\Admin;

use AltPilot\Core\Generator;
use AltPilot\Core\Logger;
use AltPilot\Core\Options;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin handler for plugin settings and UI.
 */
class Admin
{

    const NONCE_ACTION = 'altpilot_bulk_run';
    const NONCE_NAME   = 'altpilot_bulk_nonce';

    /**
     * Options manager.
     *
     * @var Options
     */
    private $options;

    /**
     * Generator instance.
     *
     * @var Generator
     */
    private $generator;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Options   $options   Options manager.
     * @param Generator $generator Generator instance.
     * @param Logger    $logger    Logger instance.
     */
    public function __construct(Options $options, Generator $generator, Logger $logger)
    {
        $this->options = $options;
        $this->generator = $generator;
        $this->logger = $logger;

        // Settings page.
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));

        // Bulk action (media library).
        add_filter('bulk_actions-upload', array($this, 'register_bulk_action'));
        add_filter('handle_bulk_actions-upload', array($this, 'handle_bulk_action'), 10, 3);

        // Media column.
        add_filter('manage_upload_columns', array($this, 'add_media_column'));
        add_action('manage_media_custom_column', array($this, 'render_media_column'), 10, 2);

        // AJAX endpoint.
        add_action('wp_ajax_altpilot_bulk_run', array($this, 'ajax_bulk_run'));

        // Admin assets.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Admin notices.
        add_action('admin_notices', array($this, 'show_admin_notices'));

        // Inline JS for bulk run.
        add_action('admin_footer', array($this, 'print_bulk_run_script'));
    }

    /**
     * Add settings page to admin menu.
     */
    public function add_settings_page()
    {
        add_submenu_page(
            'upload.php',
            __('AltPilot', 'altpilot'),
            __('AltPilot', 'altpilot'),
            'manage_options',
            'altpilot',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings and fields.
     */
    public function register_settings()
    {
        register_setting('altpilot_settings_group', Options::OPTION_NAME, array(
            'sanitize_callback' => array($this->options, 'sanitize'),
        ));

        add_settings_section(
            'altpilot_main_section',
            __('AltPilot Settings', 'altpilot'),
            array($this, 'settings_section_cb'),
            'altpilot'
        );

        add_settings_field(
            'auto_generate_on_upload',
            __('Auto-generate on upload', 'altpilot'),
            array($this, 'field_auto_generate_cb'),
            'altpilot',
            'altpilot_main_section'
        );

        add_settings_field(
            'mode',
            __('Generation Mode', 'altpilot'),
            array($this, 'field_mode_cb'),
            'altpilot',
            'altpilot_main_section'
        );

        add_settings_field(
            'allowed_mimes',
            __('Allowed image types', 'altpilot'),
            array($this, 'field_allowed_mimes_cb'),
            'altpilot',
            'altpilot_main_section'
        );

        add_settings_field(
            'enable_logging',
            __('Enable logging', 'altpilot'),
            array($this, 'field_logging_cb'),
            'altpilot',
            'altpilot_main_section'
        );

        add_settings_field(
            'batch_size',
            __('Batch size for bulk run', 'altpilot'),
            array($this, 'field_batch_size_cb'),
            'altpilot',
            'altpilot_main_section'
        );
    }

    /**
     * Settings section callback.
     */
    public function settings_section_cb()
    {
        echo '<p>' . esc_html__('Configure AltPilot behaviour and tools.', 'altpilot') . '</p>';
    }

    /**
     * Auto-generate on upload field callback.
     */
    public function field_auto_generate_cb()
    {
        $checked = ! empty($this->options->get('auto_generate_on_upload')) ? 'checked' : '';
?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Options::OPTION_NAME); ?>[auto_generate_on_upload]" value="1" <?php echo esc_attr($checked); ?>>
            <?php esc_html_e('Automatically generate ALT text for images without ALT when uploaded.', 'altpilot'); ?>
        </label>
    <?php
    }

    /**
     * Generation mode field callback.
     */
    public function field_mode_cb()
    {
        $mode = $this->options->get('mode');
    ?>
        <select name="<?php echo esc_attr(Options::OPTION_NAME); ?>[mode]">
            <option value="title_only" <?php selected($mode, 'title_only'); ?>><?php esc_html_e('Title Only', 'altpilot'); ?></option>
            <option value="title_site" <?php selected($mode, 'title_site'); ?>><?php esc_html_e('Title + Site Name', 'altpilot'); ?></option>
            <option value="filename_clean" <?php selected($mode, 'filename_clean'); ?>><?php esc_html_e('Clean Filename', 'altpilot'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Choose how AltPilot builds generated ALT values.', 'altpilot'); ?></p>
    <?php
    }

    /**
     * Allowed MIME types field callback.
     */
    public function field_allowed_mimes_cb()
    {
        $allowed = $this->options->get('allowed_mimes');
        $defaults = $this->options->get_defaults();
        $choices = $defaults['allowed_mimes'];
        $labels = array(
            'image/jpeg' => 'JPG / JPEG',
            'image/png' => 'PNG',
            'image/gif' => 'GIF',
            'image/webp' => 'WebP',
            'image/avif' => 'AVIF',
            'image/svg+xml' => 'SVG',
        );
        foreach ($choices as $mime) {
            $chk = in_array($mime, $allowed, true) ? 'checked' : '';
            printf(
                '<label style="display:inline-block;margin-right:10px;"><input type="checkbox" name="%1$s[allowed_mimes][]" value="%2$s" %3$s> %4$s</label>',
                esc_attr(Options::OPTION_NAME),
                esc_attr($mime),
                esc_attr($chk),
                esc_html(isset($labels[$mime]) ? $labels[$mime] : $mime)
            );
        }
    }

    /**
     * Enable logging field callback.
     */
    public function field_logging_cb()
    {
        $checked = ! empty($this->options->get('enable_logging')) ? 'checked' : '';
    ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(Options::OPTION_NAME); ?>[enable_logging]" value="1" <?php echo esc_attr($checked); ?>>
            <?php esc_html_e('Write operation logs to uploads/altpilot-logs/', 'altpilot'); ?>
        </label>
    <?php
    }

    /**
     * Batch size field callback.
     */
    public function field_batch_size_cb()
    {
        $size = $this->options->get('batch_size');
    ?>
        <input type="number" min="5" max="200" name="<?php echo esc_attr(Options::OPTION_NAME); ?>[batch_size]" value="<?php echo esc_attr($size); ?>">
        <p class="description"><?php esc_html_e('Number of items to process per AJAX batch during bulk runs.', 'altpilot'); ?></p>
    <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'altpilot'));
        }
    ?>
        <div class="wrap">
            <h1><?php esc_html_e('AltPilot Settings', 'altpilot'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('altpilot_settings_group');
                do_settings_sections('altpilot');
                submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Tools', 'altpilot'); ?></h2>
            <p><?php esc_html_e('Use the button below to generate missing ALT text across your media library. This runs in the background using batches to avoid timeouts.', 'altpilot'); ?></p>

            <p>
                <button id="altpilot-run-all" class="button button-primary"><?php esc_html_e('Run ALT Generator on all media (missing only)', 'altpilot'); ?></button>
            </p>

            <div id="altpilot-progress" style="display:none; margin-top:15px;">
                <p><strong><?php esc_html_e('Progress', 'altpilot'); ?></strong></p>
                <p><span id="altpilot-status">0</span></p>
                <div style="background:#f1f1f1;border:1px solid #ddd;height:20px;width:100%;border-radius:4px;overflow:hidden;">
                    <div id="altpilot-bar" style="height:20px;width:0%;background:#0073aa;"></div>
                </div>
                <p id="altpilot-summary" style="margin-top:10px;"></p>
            </div>

            <?php if (! empty($this->options->get('enable_logging'))) : ?>
                <p><?php esc_html_e('Logs are stored at wp-content/uploads/altpilot-logs/altpilot.log', 'altpilot'); ?></p>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_admin_assets($hook)
    {
        if ('settings_page_altpilot' !== $hook) {
            return;
        }

        $batch_size = $this->options->get('batch_size');
        $data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'batch_size' => intval($batch_size),
        );
        wp_localize_script('altpilot-admin', 'AltPilotData', $data);
    }

    /**
     * Register bulk action in media library.
     *
     * @param array $bulk_actions Existing bulk actions.
     *
     * @return array Updated bulk actions.
     */
    public function register_bulk_action($bulk_actions)
    {
        $bulk_actions['altpilot_generate_alt'] = __('Generate ALT with AltPilot', 'altpilot');
        return $bulk_actions;
    }

    /**
     * Handle bulk action from media library.
     *
     * @param string $redirect_to The redirect URL.
     * @param string $doaction    The action being performed.
     * @param array  $post_ids    The post IDs being acted upon.
     *
     * @return string The redirect URL.
     */
    public function handle_bulk_action($redirect_to, $doaction, $post_ids)
    {
        if ($doaction !== 'altpilot_generate_alt') {
            return $redirect_to;
        }

        if (! current_user_can('upload_files')) {
            $redirect_to = add_query_arg('altpilot_error', 'perm', $redirect_to);
            return $redirect_to;
        }

        $processed = 0;
        $skipped = 0;
        $allowed = $this->options->get('allowed_mimes');
        $mode = $this->options->get('mode');

        foreach ((array) $post_ids as $post_id) {
            $mime = get_post_mime_type($post_id);
            if (! in_array($mime, $allowed, true)) {
                $skipped++;
                continue;
            }

            $existing_alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
            if (! empty($existing_alt)) {
                $skipped++;
                continue;
            }

            $title = get_the_title($post_id);
            $alt = $this->generator->build_alt($post_id, $title, $mode);

            if (! empty($alt)) {
                update_post_meta($post_id, '_wp_attachment_image_alt', wp_strip_all_tags($alt));
                $processed++;
                $this->logger->log(sprintf('Bulk: Attachment %d ALT set to: %s', $post_id, $alt), $this->options->get('enable_logging'));
            } else {
                $skipped++;
            }
        }

        $redirect_to = add_query_arg(array(
            'altpilot_processed' => intval($processed),
            'altpilot_skipped' => intval($skipped),
        ), $redirect_to);

        return $redirect_to;
    }

    /**
     * AJAX handler for bulk run across all media.
     */
    public function ajax_bulk_run()
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'permission'), 403);
        }

        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $batch_size = $this->options->get('batch_size');
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $allowed = $this->options->get('allowed_mimes');
        $mode = $this->options->get('mode');

        // Query attachments with missing or empty alt meta.
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => $allowed,
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'ID',
            'order' => 'ASC',
            'fields' => 'ids',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '=',
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $query = new \WP_Query($args);
        $ids = $query->posts;

        $processed = 0;
        $skipped = 0;

        foreach ((array) $ids as $id) {
            $existing_alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            if (! empty($existing_alt)) {
                $skipped++;
                continue;
            }

            $title = get_the_title($id);
            $alt = $this->generator->build_alt($id, $title, $mode);

            if (! empty($alt)) {
                update_post_meta($id, '_wp_attachment_image_alt', wp_strip_all_tags($alt));
                $processed++;
                $this->logger->log(sprintf('AJAX Bulk: Attachment %d ALT set to: %s', $id, $alt), $this->options->get('enable_logging'));
            } else {
                $skipped++;
            }
        }

        $more = (count($ids) === $batch_size);

        wp_send_json_success(array(
            'processed' => $processed,
            'skipped' => $skipped,
            'count' => count($ids),
            'offset' => $offset,
            'next_offset' => $offset + $batch_size,
            'more' => $more,
        ));
    }

    /**
     * Add ALT status column to media library.
     *
     * @param array $cols Existing columns.
     *
     * @return array Updated columns.
     */
    public function add_media_column($cols)
    {
        $cols['altpilot_alt_status'] = __('ALT Status', 'altpilot');
        return $cols;
    }

    /**
     * Render ALT status column.
     *
     * @param string $column_name The column name.
     * @param int    $post_id     The post ID.
     */
    public function render_media_column($column_name, $post_id)
    {
        if ('altpilot_alt_status' !== $column_name) {
            return;
        }

        $alt = get_post_meta($post_id, '_wp_attachment_image_alt', true);
        if (! empty($alt)) {
            echo '<span style="color:green;">' . esc_html__('Present', 'altpilot') . '</span>';
        } else {
            echo '<span style="color:#a00;">' . esc_html__('Missing', 'altpilot') . '</span>';
        }
    }

    /**
     * Show admin notices for bulk action results.
     */
    public function show_admin_notices()
    {
        if (isset($_REQUEST['altpilot_processed']) || isset($_REQUEST['altpilot_error'])) {
            $processed = isset($_REQUEST['altpilot_processed']) ? intval($_REQUEST['altpilot_processed']) : 0;
            $skipped = isset($_REQUEST['altpilot_skipped']) ? intval($_REQUEST['altpilot_skipped']) : 0;
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php
                    /* translators: 1: number of images updated, 2: number of images skipped */
                    echo esc_html(sprintf(__('AltPilot: %1$d images updated, %2$d skipped.', 'altpilot'), $processed, $skipped));
                    ?></p>
            </div>
        <?php
        }

        if (isset($_REQUEST['altpilot_error']) && 'perm' === $_REQUEST['altpilot_error']) {
        ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e('AltPilot: You do not have permission to run this action.', 'altpilot'); ?></p>
            </div>
        <?php
        }
    }

    /**
     * Print inline JavaScript for bulk run functionality.
     */
    public function print_bulk_run_script()
    {
        $screen = get_current_screen();
        if (! $screen || 'settings_page_altpilot' !== $screen->id) {
            return;
        }

        $batch_size = $this->options->get('batch_size');
        $nonce = wp_create_nonce(self::NONCE_ACTION);
        $ajax = admin_url('admin-ajax.php');
        ?>
        <script type="text/javascript">
            (function($) {
                $('#altpilot-run-all').on('click', function(e) {
                    e.preventDefault();
                    if (!confirm('<?php echo esc_js(__("Run AltPilot across entire media library? This will only set missing ALT attributes. Continue?", 'altpilot')); ?>')) {
                        return;
                    }
                    $('#altpilot-progress').show();
                    $('#altpilot-bar').css('width', '0%');
                    $('#altpilot-status').text('0');
                    $('#altpilot-summary').text('');
                    var offset = 0;
                    var totalProcessed = 0;
                    var totalSkipped = 0;
                    var batch = <?php echo (int) $batch_size; ?>;

                    function runBatch() {
                        $('#altpilot-status').text(offset);
                        $.post('<?php echo esc_js($ajax); ?>', {
                            action: 'altpilot_bulk_run',
                            offset: offset,
                            nonce: '<?php echo esc_js($nonce); ?>'
                        }, function(response) {
                            if (response && response.success) {
                                totalProcessed += response.data.processed;
                                totalSkipped += response.data.skipped;
                                offset = response.data.next_offset;
                                var processedCount = totalProcessed + totalSkipped;
                                var pct = Math.min(100, Math.round((processedCount / (processedCount + 1)) * 100));
                                $('#altpilot-bar').css('width', pct + '%');
                                $('#altpilot-summary').text('<?php echo esc_js(__("Processed:", 'altpilot')); ?>' + totalProcessed + ' | <?php echo esc_js(__("Skipped:", 'altpilot')); ?>' + totalSkipped);
                                if (response.data.more) {
                                    setTimeout(runBatch, 250);
                                } else {
                                    $('#altpilot-bar').css('width', '100%');
                                    $('#altpilot-summary').text('<?php echo esc_js(__("Done. Processed:", 'altpilot')); ?>' + totalProcessed + ' | <?php echo esc_js(__("Skipped:", 'altpilot')); ?>' + totalSkipped);
                                    $('<div class="notice notice-success is-dismissible"><p><?php echo esc_js(__("AltPilot bulk run finished.", 'altpilot')); ?></p></div>').insertBefore('.wrap');
                                }
                            } else {
                                var msg = (response && response.data && response.data.message) ? response.data.message : 'error';
                                $('<div class="notice notice-error is-dismissible"><p>AltPilot AJAX error: ' + msg + '</p></div>').insertBefore('.wrap');
                            }
                        }).fail(function(xhr) {
                            $('<div class="notice notice-error is-dismissible"><p>AltPilot: AJAX request failed.</p></div>').insertBefore('.wrap');
                        });
                    }
                    runBatch();
                });
            })(jQuery);
        </script>
<?php
    }
}
