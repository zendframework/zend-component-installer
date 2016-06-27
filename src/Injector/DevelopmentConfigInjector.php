<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

class DevelopmentConfigInjector extends ApplicationConfigInjector
{
    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/development.config.php.dist';
}
