<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

class DevelopmentConfig extends ApplicationConfig
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/development.config.php.dist';
}
