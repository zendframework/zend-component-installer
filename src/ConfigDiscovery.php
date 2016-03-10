<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller;

class ConfigDiscovery
{
    /**
     * Map of known configuration files and their locators.
     *
     * @var string[]
     */
    private $discovery = [
        'config/application.config.php' => ConfigDiscovery\ApplicationConfig::class,
        'config/modules.config.php' => ConfigDiscovery\ModulesConfig::class,
        'config/development.config.php' => ConfigDiscovery\DevelopmentConfig::class,
        'config/config.php' => ConfigDiscovery\ExpressiveConfig::class,
    ];

    /**
     * Map of config files to injectors
     *
     * @var string[]
     */
    private $injectors = [
        'config/application.config.php' => Injector\ApplicationConfigInjector::class,
        'config/modules.config.php' => Injector\ModulesConfigInjector::class,
        'config/development.config.php' => Injector\DevelopmentConfigInjector::class,
        'config/config.php' => Injector\ExpressiveConfigInjector::class,
    ];

    /**
     * Return a list of available configuration options.
     *
     * @param array $availableTypes List of TYPE_* constants indicating valid
     *     package types that could be injected.
     * @return ConfigOption[]
     */
    public function getAvailableConfigOptions(array $availableTypes)
    {
        $discovered = [
            new ConfigOption('Do not inject', new Injector\NoopInjector()),
        ];

        foreach ($this->discovery as $file => $discoveryClass) {
            $discovery = new $discoveryClass();
            if (! $discovery->locate()) {
                continue;
            }

            $injectorClass = $this->injectors[$file];
            $injector = new $injectorClass();

            if (! $this->injectorCanRegisterAvailableType($injector, $availableTypes)) {
                continue;
            }

            $discovered[] = new ConfigOption($file, $injector);
        }

        return (count($discovered) === 1)
            ? []
            : $discovered;
    }

    /**
     * Determine if the given injector can handle any of the types exposed by the package.
     *
     * @param Injector\InjectorInterface $injector
     * @param int[] $availableTypes
     * @return bool
     */
    private function injectorCanRegisterAvailableType(Injector\InjectorInterface $injector, array $availableTypes)
    {
        foreach ($availableTypes as $type) {
            if ($injector->registersType($type)) {
                return true;
            }
        }
        return false;
    }
}
