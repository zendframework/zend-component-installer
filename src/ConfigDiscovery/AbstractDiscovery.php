<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\ConfigDiscovery;

abstract class AbstractDiscovery implements DiscoveryInterface
{
    /**
     * Configuration file to look for.
     *
     * Implementations MUST overwite this.
     *
     * @var string
     */
    protected $configFile;

    /**
     * Expected pattern to match if the configuration file exists.
     *
     * Implementations MUST overwrite this.
     *
     * @var string
     */
    protected $expected;

    /**
     * Constructor
     *
     * Optionally specify project directory; $configFile will be relative to
     * this value.
     *
     * @param string $projectDirectory
     */
    public function __construct($projectDirectory = '')
    {
        if ('' !== $projectDirectory && is_dir($projectDirectory)) {
            $this->configFile = sprintf(
                '%s/%s',
                $projectDirectory,
                $this->configFile
            );
        }
    }

    /**
     * Determine if the configuration file exists and contains modules.
     *
     * @return bool
     */
    public function locate()
    {
        if (! is_file($this->configFile)) {
            return false;
        }

        $config = file_get_contents($this->configFile);
        return (1 === preg_match($this->expected, $config));
    }
}
