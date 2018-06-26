<?php
/**
 * Plugin configuration file
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Anthony Allen, Jason Lusk
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 */

namespace Snapshot\StaticSnapshot;

$plugin_slug_plugin = [
    'textdomain'    => 'static-snapshot',
    'languages_dir' => 'languages',
];

$plugin_slug_settings = [
    'submenu_pages' => [
        [
            'parent_slug'  => 'options-general.php',
            'page_title'   => __('Static HTML Snapshot Settings', 'static-snapshot'),
            'menu_title'   => __('Static HTML Snapshot', 'static-snapshot'),
            'capability'   => 'manage_options',
            'menu_slug'    => 'static-snapshot',
            'view'         => PLUGIN_SLUG_DIR.'views/admin.view.php',
            'dependencies' => [
                'styles'   => [],
                'scripts'  => [
                    [
                        'handle'    => 'static-snapshot-js',
                        'src'       => PLUGIN_SLUG_URL.'js/main.js',
                        'deps'      => [ 'jquery' ],
                        'ver'       => '1.2.3',
                        'in_footer' => true,
                        'is_needed' => function ($context) {
                            if ($context) {
                                return false;
                            }

                            return true;
                        },
                        'localize'  => [],
                    ],
                ],
                'handlers' => [
                    'scripts' => 'BrightNucleus\Dependency\ScriptHandler',
                    'styles'  => 'BrightNucleus\Dependency\StyleHandler',
                ],
            ],
        ],
    ],
    'settings'      => [
        'setting1' => [
            'option_group'      => 'StaticSnapshot',
            'sanitize_callback' => null,
            'sections'          => [
                'section1' => [
                    'title'  => __('Create New Snapshot', 'static-snapshot'),
                    'view'   => PLUGIN_SLUG_DIR.'views/section1.view.php',
                    'fields' => [
                        'field1' => [
                            'title' => __('Snapshot Name', 'static-snapshot'),
                            'view'  => PLUGIN_SLUG_DIR.'views/snapshot_creation.view.php',
                        ],
                        'field2' => [
                            'title' => __('Local Deploy Path', 'static-snapshot'),
                            'view'  => PLUGIN_SLUG_DIR.'views/deployment_name.view.php',
                        ],
                    ],
                ],
                'section2' => [
                    'title'  => __('Available Snapshots', 'static-snapshot'),
                    'view'   => PLUGIN_SLUG_DIR.'views/snapshot_tables.view.php',
                    'fields' => [],
                ],
            ],
        ],
    ],
];

return [
    'Snapshot' => [
        'StaticSnapshot' => [
            'Plugin'   => $plugin_slug_plugin,
            'Settings' => $plugin_slug_settings,
        ],
    ],
];
