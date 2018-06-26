<?php
/**
 * Initialize the plugin
 *
 * This file can use syntax from the required level of PHP or later.
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Anthony Allen, Jason Lusk
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 */

declare( strict_types = 1 );

namespace Snapshot\StaticSnapshot;

use Snapshot\StaticSnapshot\Plugin;
use BrightNucleus\Config\ConfigFactory;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

if (! defined('PLUGIN_SLUG_DIR')) {
    define('PLUGIN_SLUG_DIR', plugin_dir_path(__FILE__));
}

if (! defined('PLUGIN_SLUG_URL')) {
    define('PLUGIN_SLUG_URL', plugin_dir_url(__FILE__));
}

// Load Composer autoloader.
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    include_once __DIR__.'/vendor/autoload.php';
}

// Initialize the plugin.
$plugin = new Plugin(ConfigFactory::create(__DIR__.'/config/defaults.php')->getSubConfig('Snapshot\StaticSnapshot'), PLUGIN_SLUG_URL, PLUGIN_SLUG_DIR);

$GLOBALS['plugin_slug'] = $plugin;
$GLOBALS['plugin_slug']->run();
