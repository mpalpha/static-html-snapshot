<?php
/**
 * Main plugin file
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Anthony Allen
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 */

declare( strict_types = 1 );

namespace Snapshot\StaticSnapshot;

use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;
use BrightNucleus\Config\Exception\FailedToProcessConfigException;
use BrightNucleus\Settings\Settings;

use Snapshot\StaticSnapshot\File;
use Snapshot\StaticSnapshot\Deploy;

/**
 * Main plugin class.
 *
 * @since 0.1.0
 *
 * @package Snapshot\StaticSnapshot
 * @author  Anthony Allen
 */
class Plugin
{


    use ConfigTrait;

    /**
     * Static instance of the plugin.
     *
     * @since 0.1.0
     *
     * @var self
     */
    protected static $instance;
    protected $plugin_url = '';

    /** @var File */
    private $FileObj;

    /** @var Deploy */
    private $DeployObj;


    /**
     * Instantiate a Plugin object.
     *
     * Don't call the constructor directly, use the `Plugin::get_instance()`
     * static method instead.
     *
     * @since 0.1.0
     *
     * @throws FailedToProcessConfigException If the Config could not be parsed correctly.
     *
     * @param ConfigInterface $config Config to parametrize the object.
     */
    public function __construct(ConfigInterface $config, string $plugin_url)
    {
        $this->processConfig($config);
        $this->plugin_url     = $plugin_url;
        $this->FileObj = new File();
        $this->DeployObj = new Deploy();
    }


    /**
     * Launch the initialization process.
     *
     * @since 0.1.0
     */
    public function run()
    {
        add_action('admin_init', [$this, 'website_snapshot_admin_scripts']);
        add_action('plugins_loaded', [ $this, 'load_textdomain' ]);
        add_action('wp_ajax_create_snapshot', [ $this->FileObj, 'create_snapshot' ]);
        add_action('wp_ajax_delete_snapshot', [ $this->FileObj, 'delete_snapshot' ]);
        add_action('wp_ajax_save_deploy_path', [ $this->DeployObj, 'save_deploy_path' ]);
        add_action('wp_ajax_deploy_snapshot', [ $this->DeployObj, 'deploy_snapshot' ]);

        // Initialize admin page.
        $admin_page = new Settings($this->config->getSubConfig('Settings'));
        $admin_page->register();
    }


    /**
     * website_snapshot_admin_scripts add the requiered scripts to the project
     */
    public function website_snapshot_admin_scripts()
    {
        wp_enqueue_script('website_snapshot_main_js', $this->plugin_url.'js/main.js', [ 'jquery' ]);
        wp_localize_script(
            'website_snapshot_main_js',
            'website_snapshot',
            [
                'ajax_url'               => admin_url('admin-ajax.php'),
                'website_snapshot_nonce' => wp_create_nonce('website_snapshot-nonce'),
            ]
        );
    }


    /**
     * Load the plugin text domain.
     *
     * @since 0.1.0
     */
    public function load_textdomain()
    {
        $text_domain   = $this->config->getKey('Plugin.textdomain');
        $languages_dir = 'languages';
        if ($this->config->hasKey('Plugin/languages_dir')) {
            $languages_dir = $this->config->getKey('Plugin.languages_dir');
        }

        load_plugin_textdomain($text_domain, false, $text_domain.'/'.$languages_dir);
    }
}
