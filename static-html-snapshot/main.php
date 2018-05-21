<?php
/*

Plugin Name: Static HTML Snapshot
Plugin URI: http://localhost
Description: Create a static version of your WordPress site.
Version: 0.0.1
Author: Jason Lusk
Author URI: http://jasonlusk.com
License: GPLv2

 */
define('WEBSITE_SNAPSHOT__PLUGIN_URL', plugin_dir_url(__FILE__));

add_action('admin_init', 'website_snapshot_admin_scripts');
add_action('admin_menu', 'website_snapshot_export_static_tab');

/**
 * website_snapshot_admin_scripts add the requiered scripts to the project
 */
function website_snapshot_admin_scripts()
{
    wp_enqueue_script('website_snapshot_main_js', WEBSITE_SNAPSHOT__PLUGIN_URL . 'main.js', ['jquery']);
    wp_localize_script('website_snapshot_main_js', 'website_snapshot', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'website_snapshot_nonce' => wp_create_nonce('website_snapshot-nonce')
    ]);
}

/**
 * Create admin tab for the plugin
 */
function website_snapshot_export_static_tab()
{
    add_menu_page('Static HTML', 'Static HTML', 'manage_options', __FILE__, 'website_snapshot_create_snapshot_UI');
}

/**
 * website_snapshot_create_snapshot_UI
 */
function website_snapshot_create_snapshot_UI()
{
    ?>

  <div id="snapshot-plugin">
    <div class="wrap">
      <div class="title">
        <h1>Static HTML Snapshot Manager</h1>
        <div style="padding:0% 12px;" class="error output wp-ui-text-notification"></div>
        <h2>Create new snapshot</h2>
        <div class="input-group">
          <input id="snapshot-name" type="text" placeholder="unique name">
        </div>
        <br>
        <div class="input-group">
          <input type="button" id="create-snapshot" class="button button-primary" value="Create">
          <div class="loader-inner" style="display: none;">
            <img src="images/wpspin_light-2x.gif" alt="" height="28" width="28">
          </div>
        </div>
      </div>
        <?php

        global $wpdb;
    $table_name = $wpdb->prefix . 'snapshot';

    $snapshots = $wpdb->get_results(
            'SELECT id, name, creationDate FROM ' . $table_name
        ); ?>

      <div id="available-snapshots" style="<?php echo count($snapshots) == 0 ? 'display: none' : '' ?>">
        <h2>Available snapshots</h2>
        <table class="widefat" cellspacing="0">
          <thead>
            <tr>
              <th scope="col" max-width="50">ID</th>
              <th scope="col" max-width="200">Name</th>
              <th scope="col">Created the</th>
              <th scope="col">Download</th>
              <th scope="col" max-width="50">Delete</th>
            </tr>
            </thead>
            <tbody>

            <?php
            foreach ($snapshots as $snapshot) {
                $snapshot_url = get_site_url() . '/' . $snapshot->name . '.tar';
                $exploded_date = explode(' ', $snapshot->creationDate); ?>

              <tr id="<?php echo 'snapshot-' . $snapshot->name ?>" class="alternate row-actions">
                <td><?php echo $snapshot->id ?></td>
                <td><?php echo $snapshot->name ?></td>
                <td><?php echo $exploded_date[0] ?></td>
                <td>
                    <a class="font-download" href="<?php echo $snapshot_url; ?>">
                        <span class="dashicons dashicons-download"></span>
                    </a>
                </td>
                <td class="delete plugins">
                    <a class="font-delete delete" id="delete-snapshot-<?php echo $snapshot->name; ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </a>
                </td>
              </tr><?php
            } ?>

            </tbody>
          </table>
        </div><!-- end #available-snapshots -->
      </div><!-- end .container -->

      <!-- template -->
      <table style="display: none">
        <tr id="template-row" class="alternate row-actions">
          <td></td>
          <td></td>
          <td></td>
          <td>
            <a class="font-download" href="<?php echo $snapshot_url; ?>">
                <span class="dashicons dashicons-download"></span>
            </a>
          </td>
          <td class="delete plugins">
            <a class="font-delete delete wp-ui-text-notification">
                <span class="dashicons dashicons-trash"></span>
            </a>
          </td>
        </tr>
      </table>
    </div><!-- end #snapshot-plugin -->

    <?php
}

// ajax hooks for delete
// add_action('wp_ajax_delete_snapshot', 'wp_ajax_delete_snapshot');
add_action('wp_ajax_delete_snapshot', 'delete_snapshot');


/**
 * delete_snapshot delete the snapshot with the specified name
 */
function delete_snapshot()
{
    $name = get_sanitize_input_name($_POST['name']);

    // delete the tar file
    exec('rm -rf ' . get_home_path() . '/' . $name . '.tar');

    // delete the database entry
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'snapshot', ['name' => $name]);

    // success response
    header('Content-Type: application/json');
    wp_die(wp_json_encode(['message' => 'Snapshot "' . $name . '" has been deleted']));
}

// ajax hooks for create
// add_action('wp_ajax_add_snapshot', 'wp_ajax_add_snapshot');
add_action('wp_ajax_add_snapshot', 'add_snapshot');

/**
 * add_snapshot insert the new snapshot to the snapshot table
 */
function add_snapshot()
{
    if (!is_writable(plugin_dir_path(__FILE__))) {
        set_error_headers();
        wp_die(wp_json_encode(['message' => 'Check Write permissions']));
    }

    $name = get_sanitize_input_name($_POST['name']);

    // replace any space by underscore
    $name = str_replace(' ', '_', $name);

    global $wpdb;

    // check if the snapshot name already exists
    $table_name = $wpdb->prefix . 'snapshot';
    $selectQuery = "SELECT * FROM " . $table_name . " WHERE name = '" . $name . "'";
    $snapshot = $wpdb->get_row($selectQuery);

    if ($snapshot != null) {
        set_error_headers();
        wp_die(wp_json_encode(['message' => 'Snapshot name "' . $name . '" already exists']));
    }

    $args = ['post_type' => 'any', 'posts_per_page' => -1 ];
    $loop = new WP_Query($args);
    if ($loop->have_posts()) {
        while ($loop->have_posts()) {
            $loop->the_post();
            global $post;
            $permalinks[] = get_permalink($post->ID);
        }
    }
    wp_reset_query();

    // Generate the snapshot with wget
    $snapshot_url = website_snapshot_generate_static_site($name, $permalinks);


    // TODO: Give the user the choice of the timezone
    $now = new DateTime('NOW');
    $now->setTimezone(new DateTimeZone('US/Mountain'));
    $creation_date = $now->format('Y-m-d H:i:s');

    // Insert the new Static Snapshot into the DB
    $wpdb->insert($table_name, ['name' => $name, 'creationDate' => $creation_date], ['%s', '%s']);
    $snapshot = $wpdb->get_row($selectQuery);

    // Success response
    header('Content-Type: application/json; charset=UTF-8');
    $response = [
      'message' => 'Snapshot "' . $name . '" has been added to the database',
      'snapshot' => $snapshot,
      'snapshot_url' => $snapshot_url
    ];
    wp_die(wp_json_encode($response));
}

/**
 * get_sanitize_input_name if input name is valid, sanitize it
 */
function get_sanitize_input_name($name)
{
    check_input_name($name);
    return filter_var($name, FILTER_SANITIZE_STRING);
}

/**
 * check_input_name check the input name
 */
function check_input_name($name)
{
    $isNameEmpty = !@isset($name);
    $isNameTooLong = strlen($name) > 200;

    if ($isNameEmpty || $isNameTooLong) {
        set_error_headers();

        if ($isNameEmpty) {
            wp_die(wp_json_encode(['message' => 'Snapshot name is required']));
        } elseif ($isNameTooLong) {
            wp_die(wp_json_encode(['message' => 'Snapshot name is too long']));
        }
    }
}

/**
 * set_error_headers
 */
function set_error_headers()
{
    http_response_code(500);
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    header('Content-Type: application/json; charset=UTF-8');
}

/**
 * recursively delete files by extension
 * delete_recursively_('/home/username/directory/', '.txt');
 */
function delete_recursively_($path, $match)
{
    static $deleted = 0,
    $dsize = 0;
    $dirs = glob($path."*");
    $files = glob($path.$match);
    foreach ($files as $file) {
        if (is_file($file)) {
            $deleted_size += filesize($file);
            unlink($file);
            $deleted++;
        }
    }
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            $dir = basename($dir) . "/";
            delete_recursively_($path.$dir, $match);
        }
    }
    return "$deleted files deleted with a total size of $deleted_size bytes";
}


/**
 * Use wget to download a static version of the website
 * @param  $name        string  the name of the static-snapshot
 * @param  $permalinks  array   getting only some pages of the websites [NOT TESTED]
 * @return              string  snapshot url
 */
function website_snapshot_generate_static_site($name, $permalinks = null)
{
    $name = esc_html($name); // $name is used in some exec
    $static_site_dir = str_replace('http://', '', get_site_url());
    $output_path = plugin_dir_path(__FILE__) . 'output/';
    $snapshot_path = $output_path . $name;
    $site_domain = parse_url(get_site_url(), PHP_URL_HOST);

    $wget_command = 'wget -E -D'. $site_domain .' -k -N -p -P ' . plugin_dir_path(__FILE__) . 'output ';

    if ($permalinks === null) {
        $wget_command .= get_site_url();
    } else {
        $wget_command .= get_site_url();
        for ($i=0, $length = count($permalinks); $i < $length; $i++) {
            $wget_command .= $i !== $length - 1 ? $permalinks[$i] . ' ' : $permalinks[$i];
        }
    }
    exec($wget_command);
    exec('cd ' . $output_path . ' && mv ' . $static_site_dir . ' ' . $name); // rename dir
    find_files_and_replace_absolute($snapshot_path, $snapshot_path, '/\.(html|css|js).*$/'); // fix absolute urls
    find_files_and_remove_query_params($snapshot_path, $snapshot_path, '/\?.*/'); // strip query parameters from filenames
    exec('cd ' . $output_path . ' && tar -cvf ' . get_home_path() . '/' . $name . '.tar ' . $name); // create tar file
    exec('rm -rf ' . $output_path); // output directory

    return get_site_url() . '/' . $name . '.tar';
}


/**
 * find_files_and_replace_absolute find files and search and replace absolute paths with relative paths
 * @param   $root_path  string  the root path
 * @param   $dir        string  the directory where to start
 * @param   $pattern    string  the type of files on which to apply the search and replace
 */
function find_files_and_replace_absolute($root_path, $dir = '.', $pattern = '/./')
{
    $prefix = $dir . '/';

    $dir = dir($dir);

    while (false !== ($file = $dir->read())) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $file = $prefix . $file;
        if (is_dir($file)) {
            find_files_and_replace_absolute($root_path, $file, $pattern);
        }
        if (preg_match($pattern, $file)) {
            $content = read_content($file);
            $backtrack = get_backtrack($root_path, $file, $pattern);
            $content = format_content_for_local_use(get_site_url(), $backtrack, $content);
            $content = str_replace(['%3F'], ['?'], $content); // decode url param
            $content = str_replace('../fonts.g', 'fonts.g', $content);
            $content = str_replace('../maxcdn.b', 'maxcdn.b', $content);
            $content = str_replace('../cdnjs.c', 'cdnjs.c', $content);
            $content = str_replace('../vjs.z', 'vjs.z', $content);
            unlink($file); // delete the file
            write_content($file, $content);
        }
    }
}
/**
 * find_files_and_remove_query_params find files and remove query parameters from the filenames.
 * @param   $root_path  string  the root path
 * @param   $dir        string  the directory where to start
 * @param   $pattern    string  the type of files on which to apply the search and replace
 */
function find_files_and_remove_query_params($root_path, $dir = '.', $pattern = '/./')
{
    $prefix = $dir . '/';

    $dir = dir($dir);

    while (false !== ($file = $dir->read())) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $file = $prefix . $file;
        if (is_dir($file)) {
            find_files_and_remove_query_params($root_path, $file, $pattern);
        }
        if (preg_match($pattern, $file)) {
            $content = read_content($file);
            $content = str_replace(['%3F'], ['?'], $content);
            unlink($file); // delete the file
            write_content(preg_replace('/\?.*/', '', $file), $content);
        }
    }
}

/**
 * get_backtrack get the right number of ../ to replace the root URL of the site
 * @param   $root_path  string  the root path of the site
 * @param   $file       string  the path of the file
 * @param   $pattern    string  the file types we are looking for
 * @return              string  repeat number of ../ one after the other
 */
function get_backtrack($root_path, $file, $pattern)
{
    $path_after_root_array = explode($root_path, $file);
    $path_after_root = $path_after_root_array[1];
    $count = count(explode('/', $path_after_root)) - 1; // don't beginning
    $count = $count - 1; // don't count end
    return str_repeat('../', $count);
}


/**
 * format_content_for_local_use make sure the URLS are now locals
 * @param  string $root_url  the root path of the site
 * @param  string $backtrack the backtrack string (../ n times)
 * @param  string $content   the current file contents
 * @return string            the current file contents for local use
 */
function format_content_for_local_use($root_url, $backtrack, $content)
{
    $root_url_regex = preg_quote($root_url, '/');

    // Match: window.location.href = 'http://myurl/one.1/two.2/three'
    // !Match: window.location.href = 'http://myurl/one.1/two.2/three.3'
    $pattern = '/(window\.location\.href\s=\s)(\'|")' . $root_url_regex . '\/(.*\/)*(\w+)(\'|")/';

    // Quote ($1) + backtrack (../) + slugs ($2) + /index.html ($3) + quote ($4)
    $replacement = '$1$2' . $backtrack . '$3$4/index.html$5';

    $content = preg_replace($pattern, $replacement, $content);
    return str_replace($root_url.'/', $backtrack, $content); // backtrack for remaning items
}


/**
 * Read the content of a file
 * @param   $file  string  the path of the file
 * @return         string  the file contents
 */
function read_content($file)
{
    $file_handler = fopen($file, 'r') or die("can't open file");
    $contents = fread($file_handler, filesize($file));
    fclose($file_handler);
    return $contents;
}


/**
 * Write new content to file
 * @param   $file     string  the path of the file
 * @param   $content  string  the content of the file
 */
function write_content($file, $content)
{
    $file_handler = fopen($file, 'w+') or die("can't open file");
    fwrite($file_handler, $content);
    fclose($file_handler);
}


/**
 * website_snapshot_static_exporter_options_install create the table
 */
function website_snapshot_static_exporter_options_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "snapshot";

    // create table if none already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = 'CREATE TABLE wp_snapshot (
      id INT UNSIGNED AUTO_INCREMENT,
      name VARCHAR(200) UNIQUE NOT NULL,
      creationDate DATETIME UNIQUE NOT NULL,
      PRIMARY KEY(id)
    );';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
// run the install scripts upon plugin activation
register_activation_hook(__FILE__, 'website_snapshot_static_exporter_options_install');
