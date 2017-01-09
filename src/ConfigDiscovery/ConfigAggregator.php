<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

class ConfigAggregator extends AbstractDiscovery
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
            '/new (?:%s?%s)?ConfigAggregator\(\s*(?:array\(|\[)/s',
            preg_quote('\\'),
            preg_quote('Zend\ConfigAggregator\\')
        );

        parent::__construct($projectDirectory);
    }
}
