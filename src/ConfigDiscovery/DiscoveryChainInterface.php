<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

interface DiscoveryChainInterface
{
    /**
     * Determine if discovery exists
     *
     * @return bool
     */
    public function discoveryExists($name);
}
