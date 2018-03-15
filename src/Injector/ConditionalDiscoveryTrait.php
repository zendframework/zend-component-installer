<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\Injector;

use function str_replace;

trait ConditionalDiscoveryTrait
{
    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function inject($package, $type)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return false;
        }

        return parent::inject('\\' . $package, $type);
    }

    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function remove($package)
    {
        if (! $this->validConfigAggregatorConfig()) {
            return false;
        }

        return parent::remove('\\' . $package);
    }

    /**
     * Does the config file hold valid ConfigAggregator configuration?
     *
     * @return bool
     */
    private function validConfigAggregatorConfig()
    {
        $discoveryClass = $this->discoveryClass;
        $discovery = new $discoveryClass($this->getProjectRoot());
        return $discovery->locate();
    }

    /**
     * Calculate the project root from the config file
     *
     * @return string
     */
    private function getProjectRoot()
    {
        if (static::DEFAULT_CONFIG_FILE === $this->configFile) {
            return '';
        }
        return str_replace('/' . static::DEFAULT_CONFIG_FILE, '', $this->configFile);
    }
}
