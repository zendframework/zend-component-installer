<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

class ExpressiveConfig extends AbstractDiscovery
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/config.php';

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * @var string
     */
    protected $expected = '/new (?:\\?Zend\\Expressive\\ConfigManager\\\\)?ConfigManager\(\s*(?:array\(|\[)/s';
}
