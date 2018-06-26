<?php
/**
 * Admin page view
 *
 * @package   Gamajo\PluginSlug
 * @author    Gary Jones
 * @copyright 2017 Gamajo
 * @license   GPL-2.0+
 */
?>
<div class="wrap">
    <style>
        td.snapshot_success, .success {
            color: #79ba49;
        }
        .success.wp-ui-text-notification {
            color: #79ba49;
            border-left-color: #79ba49;
            background: #fff;
            border-left: 4px solid #79ba49;
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
        }
    </style>
    <div id="snapshot-plugin">
        <div style="padding:0% 12px;" class="error output wp-ui-text-notification"></div>
        <div style="padding:0% 12px;" class="success output wp-ui-text-notification"></div>
        <?php echo settings_fields('StaticSnapshot'); ?>

        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php echo do_settings_sections('StaticSnapshot'); ?>
    </div>
</div>
