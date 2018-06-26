<?php
/**
 * Sanitize Input Class.
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

/**
 * Sanitize Input Class.
 *
 * @since 1.0.0
 *
 * @package Snapshot\StaticSnapshot
 * @author  Anthony Allen
 */
class Sanitize
{

    /** @var Response */
    private $ResponseObj;

    public function __construct()
    {
        $this->ResponseObj = new Response();
    }

    /**
     * If input name is valid, sanitize it.
     *
     * @param string $name
     * @return void
     */
    public function getSanitizeInputName(string $name)
    {
        $this->checkInputName($name);
        return filter_var($name, FILTER_SANITIZE_STRING);
    }

    /**
     * Check the input name.
     *
     * @param string $name
     * @return void
     */
    private function checkInputName(string $name)
    {
        $isNameEmpty   = ! @isset($name);
        $isNameTooLong = strlen($name) > 200;
        $resType = '';
        $resData = [];
        $resCode = 0;
        $hasError = '';

        if ($isNameEmpty || $isNameTooLong) {
            $resType = 'Error';
            $resCode = 500;
            $hasError = true;

            if ($isNameEmpty) {
                $resData = [ 'message' => 'Snapshot name is required' ];
            } elseif ($isNameTooLong) {
                $resData = [ 'message' => 'Snapshot name is too long' ];
            }

            $this->ResponseObj->_setResponseData($resType, $resData, $resCode);
            $this->ResponseObj->getHeaderResponse();
        }
    }
}
