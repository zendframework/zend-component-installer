<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

class DevelopmentWorkConfig extends ApplicationConfig
{
    /**
     * Configuration file to look for.
     *
     * @var string
     */
    protected $configFile = 'config/development.config.php';
}
