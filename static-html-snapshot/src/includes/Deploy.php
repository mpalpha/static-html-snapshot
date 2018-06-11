<?php
/**
 * Static Site Deployment Class
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

use Snapshot\StaticSnapshot\Response;
use Snapshot\StaticSnapshot\Sanitize;

class Deploy
{

    /** @var Sanitize */
    private $SanitizeObj;

    /** @var Response */
    private $ResponseObj;

    public function __construct()
    {
        $this->SanitizeObj = new Sanitize();
        $this->ResponseObj = new Response();
    }

    /**
     * Save deployment path
     *
     * @return void
     */
    public function save_deploy_path()
    {
        $table_name = 'wp_shs_deploy_path';
        $name       =  $this->SanitizeObj->getSanitizeInputName($_POST['deploy_path']);

        global $wpdb;
        wp_reset_query();

        $now = new \DateTime('NOW');
        $now->setTimezone(new \DateTimeZone('US/Mountain'));
        $creation_date = $now->format('Y-m-d H:i:s');

        $selectQuery = 'SELECT * FROM '.$table_name;
        $deployPath  = $wpdb->get_row($selectQuery);

        if ($deployPath != null) {
            $wpdb->query(
                'UPDATE '.$table_name." SET
            deploy_path = '".$name."', date_created = '".$creation_date."'
            WHERE id = '1'"
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                'deploy_path'  => $name,
                'date_created' => $creation_date,
                ]
            );
        }

        $this->ResponseObj->_setResponseData('Success', [
            'message'      => 'Snapshot deploy path "'.$name.'" has been added to the database',
            'snapshot_url' => $name,
            ], 200);
        $this->ResponseObj->getHeaderResponse();
    }

    /**
     * Deploy static assets to saved deployment path.
     *
     * @return void
     */
    function deploy_snapshot()
    {
        global $wpdb;
        wp_reset_query();
        $name        =  $this->SanitizeObj->getSanitizeInputName($_POST['snapshot_name']);
        $tarFile     = get_home_path().$name.'.tar';
        $deploy_path = '';
        $table_name  = 'wp_shs_deploy_path';
        $resData = [];
        $resType = '';
        $resCode = 0;

        $deployPathResults = $wpdb->get_results(
            'SELECT deploy_path FROM '.$table_name." WHERE id='1' "
        );

        if (count($deployPathResults) > 0) {
            $deploy_path = $deployPathResults[0]->deploy_path;
            $cmd         = 'rm -rf '.$deploy_path.' && mkdir -p '.$deploy_path.' && tar xvf '.$tarFile.' -C '.$deploy_path.' --strip-components=1';

            exec($cmd, $output, $return);

            if ($return) {
                $resType = 'Error';
                $resData = [
                    'message' => 'Snapshot '.$name.' '.$tarFile.' failed to deploy to '.$deploy_path,
                    'cmd' => $cmd
                ];
                $resCode = 500;

                $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
                $this->ResponseObj->getHeaderResponse();
            } else {
                $table_name = 'wp_shs_snapshot';
                $now        = new \DateTime('NOW');
                $now->setTimezone(new \DateTimeZone('US/Mountain'));
                $deployed_date = $now->format('Y-m-d H:i:s');

                $wpdb->query(
                    'UPDATE '.$table_name." SET
                    deployedDate = '".$deployed_date."'
                    WHERE name = '".$name."'"
                );

                $resType = 'Success';
                $resData = [
                    'message'       => 'Snapshot "'.$name.'" has been deployed',
                    'deployed'      => $deployed_date,
                    'snapshot_name' => $name,
                ];
                $resCode = 200;

                $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
                $this->ResponseObj->getHeaderResponse();
            }
        }
    }
}
