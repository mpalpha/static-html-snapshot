<?php
/**
 * Plugin Name: Static HTML Snapshot
 *
 * This file should only use syntax available in PHP 5.2.4 or later.
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Jason Lusk, Anthony Allen
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Static HTML Snapshot
 * Plugin URI:        http://localhost
 * Description:       Create a static version of your WordPress site.
 * Version:           1.0.0
 * Author:            Jason Lusk, Anthony Allen
 * Author URI:        http://jasonlusk.com
 * Text Domain:       static-snapshot
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires PHP:      7.0
 * Requires WP:       4.7
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (version_compare(PHP_VERSION, '7.0', '<')) {
    add_action('plugins_loaded', 'plugin_slug_init_deactivation');


    /**
     * Initialize deactivation functions.
     */
    function plugin_slug_init_deactivation()
    {
        if (current_user_can('activate_plugins')) {
            add_action('admin_init', 'plugin_slug_deactivate');
            add_action('admin_notices', 'plugin_slug_deactivation_notice');
        }
    }//end plugin_slug_init_deactivation()


    /**
     * Deactivate the plugin.
     */
    function plugin_slug_deactivate()
    {
        deactivate_plugins(plugin_basename(__FILE__));
    }//end plugin_slug_deactivate()


    /**
     * Show deactivation admin notice.
     */
    function plugin_slug_deactivation_notice()
    {
        $notice = sprintf(
            // Translators: 1: Required PHP version, 2: Current PHP version.
            '<strong>Genesis JS / No JS</strong> requires PHP %1$s to run. This site uses %2$s, so the plugin has been <strong>deactivated</strong>.',
            '7.0',
            PHP_VERSION
        );
        ?>
     <div class="updated"><p><?php echo wp_kses_post($notice); ?></p></div>
        <?php
        if (isset($_GET['activate'])) {
            // WPCS: input var okay, CSRF okay.
            unset($_GET['activate']);
            // WPCS: input var okay.
        }
    }//end plugin_slug_deactivation_notice()


    return false;
}//end if

/*
    * Load plugin initialisation file.
 */
require plugin_dir_path(__FILE__).'/init.php';
