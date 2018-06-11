<?php
/**
 * Enqueueable Interface.
 *
 * @package   BrightNucleus\Contract
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.brightnucleus.com/
 * @copyright 2015-2016 Alain Schlesser, Bright Nucleus
 */

namespace BrightNucleus\Contract;

/**
 * Object is enqueueable.
 *
 * @since   0.1.0
 *
 * @package BrightNucleus\Contract
 * @author  Alain Schlesser <alain.schlesser@gmail.com>
 */
interface Enqueueable
{

    /**
     * Enqueue the Enqueueable asset.
     *
     * @since 0.1.0
     *
     * @param mixed $args Optional. Arguments to pass to enqueue function.
     */
    public function enqueue($args = null);
}
