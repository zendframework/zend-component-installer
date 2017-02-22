<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
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
