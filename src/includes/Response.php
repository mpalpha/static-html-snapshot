<?php
/**
 * Error file
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

/**
 * Header response class.
 *
 * @since 1.0.0
 *
 * @package Snapshot\StaticSnapshot
 * @author  Anthony Allen
 */
class Response
{
    use ConfigTrait;

    /** @var array */
    private $errors       = [];

    /** @var array */
    private $success      = [];

    /** @var array */
    private $response     = [];

    /** @var string */
    private $responseType = '';

    /** @var integer */
    private $responseCode = 0;

    /** @var array */
    private $status = [
        200 => '200 OK',
        400 => '400 Bad Request',
        422 => 'Unprocessable Entity',
        500 => '500 Internal Server Error'
    ];


    /**
     * Response class constructor
     *
     * @param string $responseType
     * @param array  $responseData
     */
    public function __construct()
    {
    }

    /**
     * Sets our response array to be used later.
     *
     * @param  array $responseData
     * @return void
     */
    public function _setResponseData(string $responseType, array $responseData, int $responseCode)
    {
        $this->responseType = $responseType;
        $this->responseCode = $responseCode;

        if (count($this->response) > 0) {
            $this->response[] = $responseData;
        } else {
            $this->response = $responseData;
        }
    }


    /**
     * Return header with success or error data.
     *
     * @return void
     */
    public function getHeaderResponse()
    {
        // clear the old headers
        header_remove();
        http_response_code($this->responseCode);
        // set the header to make sure cache is forced
        header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
        // treat this as json
        header('Content-Type: application/json; charset=UTF-8');
        header('Status: '.$this->status[$this->responseCode]);
        $response = [
            'status' => $this->responseCode < 300, // success or not?
            'type' => $this->responseType,
            $this->response,
        ];

        wp_die(wp_json_encode($response));
    }
}
