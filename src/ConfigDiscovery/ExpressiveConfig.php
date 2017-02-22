<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
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
     * Pattern is set in constructor to ensure PCRE quoting is correct.
     *
     * @var string
     */
    protected $expected = '';

    public function __construct($projectDirectory = '')
    {
        $this->expected = sprintf(
            '/new (?:%s?%s)?ConfigManager\(\s*(?:array\(|\[)/s',
            preg_quote('\\'),
            preg_quote('Zend\Expressive\ConfigManager\\')
        );

        parent::__construct($projectDirectory);
    }
}
