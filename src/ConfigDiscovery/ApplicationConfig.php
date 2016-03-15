<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

class ApplicationConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/application.config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * @var string
     */
    protected $expected = '/^(\s+)(\'modules\'\s*\=\>\s*(array\(|\[))\s*$/m';
}
