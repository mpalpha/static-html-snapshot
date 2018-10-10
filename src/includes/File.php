<?php
/**
 * File creation
 *
 * @package   Snapshot\StaticSnapshot
 * @author    Anthony Allen, Jason Lusk
 * @copyright 2018 Snapshot
 * @license   GPL-2.0+
 */

declare( strict_types = 1 );

namespace Snapshot\StaticSnapshot;

use BrightNucleus\Config\ConfigInterface;
use BrightNucleus\Config\ConfigTrait;
use BrightNucleus\Config\Exception\FailedToProcessConfigException;
use BrightNucleus\Settings\Settings;

use Snapshot\StaticSnapshot\Response;
use Snapshot\StaticSnapshot\Sanitize;

/**
 * File creation class.
 *
 * @since 1.0.0
 *
 * @package Snapshot\StaticSnapshot
 * @author  Anthony Allen
 */
class File
{

    /** @var Sanitize */
    private $SanitizeObj;

    /** @var Response */
    private $ResponseObj;

    /** @var string */
    public $pluginDir;

    private $wgetCmd;
    /**
     * File Handler class constructor.
     */
    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
        $this->SanitizeObj = new Sanitize();
        $this->ResponseObj = new Response();
    }

    /**
     * Create our static HTML snapshot and insert the new snapshot to the snapshot table.
     *
     * @return void
     */
    public function create_snapshot()
    {
        global $wpdb;
        $resType = '';
        $resData = [];
        $resCode = 0;
        $hasError = '';

        if (! is_writable($this->pluginDir)) {
            $resType = 'Error';
            $resData = [ 'message' => 'Check plugin directory write permissions' ];
            $resCode = 500;
            $hasError = true;
        }

        $name = $this->SanitizeObj->getSanitizeInputName($_POST['name']);
        // replace any space by underscore
        $name = str_replace(' ', '_', $name);
        // check if the snapshot name already exists
        $table_name  = 'wp_shs_snapshot';
        $selectQuery = 'SELECT * FROM '.$table_name." WHERE name = '".$name."'";
        $snapshot    = $wpdb->get_row($selectQuery);

        if ($snapshot != null) {
            $resType = 'Error';
            $resData = [ 'message' => 'Snapshot name "'.$name.'" already exists' ];
            $resCode = 500;
            $hasError = true;
        }

        if (!$hasError) {
            $args = [
                'post_type'      => 'any',
                'posts_per_page' => -1,
            ];

            $loop = new \WP_Query($args);
            if ($loop->have_posts()) {
                while ($loop->have_posts()) {
                    $loop->the_post();
                    global $post;
                    $permalinks[] = get_permalink($post->ID);
                }
            }

            wp_reset_query();
            // Generate the snapshot with wget
            $snapshot_url = $this->generateStaticSite($name, $permalinks);
            // TODO: Give the user the choice of the timezone
            $now = new \DateTime('NOW');
            $now->setTimezone(new \DateTimeZone('US/Mountain'));
            $creation_date = $now->format('Y-m-d H:i:s');

            // Insert the new Static Snapshot into the DB
            $wpdb->insert(
                $table_name,
                [
                    'name'         => $name,
                    'creationDate' => $creation_date,
                ],
                [
                    '%s',
                    '%s',
                ]
            );

            $snapshot = $wpdb->get_row($selectQuery);
        }

        if ($snapshot && $snapshot_url && !$hasError) {
            $resType = 'Success';
            $resData = [
            'message'      => 'Snapshot "'.$name.'" has been added to the database',
            'snapshot'     => $snapshot,
            'snapshot_url' => $snapshot_url,
            ];
            $resCode = 200;
        }

        $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
        $this->ResponseObj->getHeaderResponse();
    }

    /**
     * Delete snapshot from db and it's file.
     *
     * @return void
     */
    public function delete_snapshot()
    {
        $name = $this->SanitizeObj->getSanitizeInputName($_POST['name']);
        $tarName = get_home_path().$name.'.tar';
        $command = 'rm -rf '. $tarName;
        $resData = [];
        $resType = '';
        $resCode = 0;


        // delete the tar file
        exec($command, $output, $return);

        if ($return) {
            $resType = 'Error';
            $resData = [
                'message' => 'Snapshot "'. $tarName .'" failed to delete',
                'cmd' => $command
            ];
            $resCode = 500;
        } else {
            // delete the database entry
            global $wpdb;
            $wpdb->delete('wp_shs_snapshot', [ 'name' => $name ]);

            $resType = 'Success';
            $resData = [
                'message' => 'Snapshot "' . $tarName . '" has been deleted',
            ];
            $resCode = 200;
        }

        $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
        $this->ResponseObj->getHeaderResponse();
    }

    /**
     * Generate static assets.
     *
     * @param string $name
     * @param array $permalinks
     * @return void
     */
    private function generateStaticSite(string $name, array $permalinks = [])
    {

        $name = esc_html($name);
        // $name is used in some exec
        $static_site_dir = str_replace('http://', '', get_site_url());
        $theme_path      = get_template_directory();
        $output_path     = $this->pluginDir.'output/';
        $snapshot_path   = $output_path.$name;
        $site_domain     = parse_url(get_site_url(), PHP_URL_HOST);
        $this->wgetCmd = 'wget -e robots=off -E -D'.$site_domain.' -k -N -p -P '.$this->pluginDir.'output ';

        $move_command = 'cd '.$output_path.' && mv '.$static_site_dir.' '.$name;
        $create_tar_command = 'cd '.$output_path.' && tar -cvf '.get_home_path().'/'.$name.'.tar '.$name;
        $delete_output_command = 'rm -rf '.$output_path;
        $output_result = '';

        if ($permalinks === []) {
            $this->wgetCmd .= get_site_url();
        } else {
            $this->wgetCmd .= get_site_url();
            for ($i = 0, $length = count($permalinks); $i < $length; $i++) {
                $this->wgetCmd .= $i !== ($length - 1) ? $permalinks[$i].' ' : $permalinks[$i];
            }
        }

        $output_result .= '$this->wgetCmd:'.exec($this->wgetCmd, $output, $result);

        if (is_dir($theme_path.'/json')) {
            $copy_json_command = 'cp -R '.$theme_path.'/json '.$output_path.'/'.$static_site_dir.'/json ';
            $output_result .= '$copy_json_command:'.exec($copy_json_command, $output, $result);
        }

        $output_result .= '$move_command:'.exec($move_command, $output, $result);

        // rename dir
        $this->findFilesReplaceAbsolute($snapshot_path, $snapshot_path, '/\.(html|css|js).*$/');
        // fix absolute urls
        $this->findFilesRemoveQueryParams($snapshot_path, $snapshot_path, '/\?.*/');

        // strip query parameters from filenames
        $output_result .= '$create_tar_command:'.exec($create_tar_command, $output, $result);
        // create tar file
        $output_result .= '$delete_output_command:'.exec($delete_output_command, $output, $result);

        if ($result) {
            $resType = 'Error';
            $resData = [
                'message' => 'Snapshot Generate Static Site failed check the following commands.',
                'wget_cmd' => $this->wgetCmd,
                'copy_json_command' => $copy_json_command,
                'move_cmd' => $move_cmd,
                'create_tar_command' => $create_tar_command,
                'delete_output_command' => $delete_output_command
            ];
            $resCode = 500;

            $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
            $this->ResponseObj->getHeaderResponse();
        }

        // output directory
        return get_site_url().'/'.$name.'.tar';
    }

    /**
     * Replace absolute paths.
     *
     * @param string $root_path
     * @param string $dir
     * @param string $pattern
     * @return void
     */
    private function findFilesReplaceAbsolute(string $root_path, string $dir = '.', string $pattern = '/./')
    {
        $prefix = $dir.'/';
        $dir = dir($dir);
        while (false !== ( $file = $dir->read() )) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $file = $prefix.$file;

            if (is_dir($file)) {
                $this->findFilesReplaceAbsolute($root_path, $file, $pattern);
            }

            if (preg_match($pattern, $file)) {
                $content   = $this->readContent($file);
                $backtrack = $this->getBacktrack($root_path, $file, $pattern);
                $content   = $this->formatContentForLocalUse(get_site_url(), $backtrack, $content);

                $content   = str_replace([ '%3F' ], [ '?' ], $content);
                // decode url param
                $content = str_replace('../fonts.g', 'fonts.g', $content);
                $content = str_replace('../maxcdn.b', 'maxcdn.b', $content);
                $content = str_replace('../cdnjs.c', 'cdnjs.c', $content);
                $content = str_replace('../vjs.z', 'vjs.z', $content);
                unlink($file);
                // delete the file
                $this->writeContent($file, $content);
            }
        }
    }

    /**
     * Remove query params.
     *
     * @param string $root_path
     * @param string $dir
     * @param string $pattern
     * @return void
     */
    private function findFilesRemoveQueryParams(string $root_path, string $dir = '.', string $pattern = '/./')
    {
        $prefix = $dir.'/';
        $dir = dir($dir);

        while (false !== ( $file = $dir->read() )) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $file = $prefix.$file;
            if (is_dir($file)) {
                $this->findFilesRemoveQueryParams($root_path, $file, $pattern);
            }

            if (preg_match($pattern, $file)) {
                $content = $this->readContent($file);
                $content = str_replace([ '%3F' ], [ '?' ], $content);
                unlink($file);
                // delete the file
                $this->writeContent(preg_replace('/\?.*/', '', $file), $content);
            }
        }
    }

    /**
     * Format file contents.
     *
     * @param string $root_url
     * @param string $backtrack
     * @param string $content
     * @return void
     */
    private function formatContentForLocalUse($root_url, $backtrack, $content)
    {
        $root_url_regex = preg_quote($root_url, '/');
        // Match: window.location.href = 'http://myurl/one.1/two.2/three'
        // !Match: window.location.href = 'http://myurl/one.1/two.2/three.3'
        $pattern = '/(window\.location\.href\s=\s)(\'|")'.$root_url_regex.'\/(.*\/)*(\w+)(\'|")/';
        // Quote ($1) + backtrack (../) + slugs ($2) + /index.html ($3) + quote ($4)
        $replacement = '$1$2'.$backtrack.'$3$4/index.html$5';
        $content = preg_replace($pattern, $replacement, $content);

        return str_replace($root_url.'/', $backtrack, $content);
        // backtrack for remaning items
    }

    /**
     * Undocumented function
     *
     * @param string $root_path
     * @param string $file
     * @param string $pattern
     * @return void
     */
    private function getBacktrack(string $root_path, string $file, string $pattern)
    {
        $path_after_root_array = explode($root_path, $file);
        $path_after_root       = $path_after_root_array[1];
        $count = (count(explode('/', $path_after_root)) - 1);
        // don't beginning
        $count = ($count - 1);
        // don't count end
        return str_repeat('../', $count);
    }

    /**
     * Undocumented function
     *
     * @param string $file
     * @return void
     */
    private function readContent(string $file)
    {
        $file_handler = fopen($file, 'r') or die("can't open file");
        $contents     = fread($file_handler, filesize($file));
        fclose($file_handler);
        return $contents;
    }

    /**
     * Undocumented function
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    private function writeContent(string $file, string $content)
    {
        $file_handler = fopen($file, 'w+') or die("can't open file");
        fwrite($file_handler, $content);
        fclose($file_handler);
    }
}
