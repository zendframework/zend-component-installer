<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

interface DiscoveryInterface
{
    /**
     * Attempt to discover if a given configuration file exists.
     *
     * Implementations should check if the file exists, and can potentially
     * look for known expected artifacts within the file to determine if
     * the configuration is one to which the installer can or should write to.
     *
     * @return bool
     */
    public function locate();
}
