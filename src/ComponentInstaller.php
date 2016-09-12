<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as CommandEvent;
use Composer\Script\PackageEvent;

/**
 * If a package represents a component module, update the application configuration.
 *
 * Packages opt-in to this workflow by defining one or more of the keys:
 *
 * - extra.zf.component
 * - extra.zf.module
 * - extra.zf.config-provider
 *
 * with the value being the string namespace the component and/or module
 * defines, or, in the case of config-provider, the fully qualified class name
 * of the provider:
 *
 * <code class="lang-javascript">
 * {
 *   "extra": {
 *     "zf": {
 *       "component": "Zend\\Form",
 *       "module": "ZF\\Apigility\\ContentNegotiation",
 *       "config-provider": "Zend\\Expressive\\PlatesRenderer\\ConfigProvider"
 *     }
 *   }
 * }
 * </code>
 *
 * With regards to components and modules, for this to work correctly, the
 * package MUST define a `Module` in the namespace listed in either the
 * extra.zf.component or extra.zf.module definition.
 *
 * Components are added to the TOP of the modules list, to ensure that userland
 * code and/or modules can override the settings. Modules are added to the
 * BOTTOM of the modules list. Config providers are added to the TOP of
 * configuration providers.
 *
 * In either case, you can edit the appropriate configuration file when
 * complete to create a specific order.
 */
class ComponentInstaller implements
    EventSubscriberInterface,
    PluginInterface
{
    /**
     * Cached injectors to re-use for packages installed later in the current process.
     *
     * @var Injector\InjectorInterface[]
     */
    private $cachedInjectors = [];

    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * Map of known package types to composer config keys.
     *
     * @param array
     */
    private $packageTypes = [
        Injector\InjectorInterface::TYPE_CONFIG_PROVIDER => 'config-provider',
        Injector\InjectorInterface::TYPE_COMPONENT => 'component',
        Injector\InjectorInterface::TYPE_MODULE => 'module',
    ];

    /**
     * Project root in which to install.
     *
     * @var string
     */
    private $projectRoot;

    /**
     * Constructor
     *
     * Optionally accept the project root into which to install.
     *
     * @param string $projectRoot
     */
    public function __construct($projectRoot = '')
    {
        if (is_string($projectRoot) && ! empty($projectRoot) && is_dir($projectRoot)) {
            $this->projectRoot = $projectRoot;
        }
    }

    /**
     * Activate plugin.
     *
     * Sets internal pointers to Composer and IOInterface instances, and resets
     * cached injector map.
     *
     * @param Composer $composer
     * @param IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer        = $composer;
        $this->io              = $io;
        $this->cachedInjectors = [];
    }

    /**
     * Return list of event handlers in this class.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'post-package-install'   => 'onPostPackageInstall',
            'post-package-uninstall' => 'onPostPackageUninstall',
        ];
    }

    /**
     * post-package-install event hook.
     *
     * This routine exits early if any of the following conditions apply:
     *
     * - Executed in non-development mode
     * - No config/application.config.php is available
     * - The composer.json does not define one of either extra.zf.component
     *   or extra.zf.module
     * - The value used for either extra.zf.component or extra.zf.module are
     *   empty or not strings.
     *
     * Otherwise, it will attempt to update the application configuration
     * using the value(s) discovered in extra.zf.component and/or extra.zf.module,
     * writing their values into the `modules` list.
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        $package = $event->getOperation()->getPackage();
        $name    = $package->getName();
        $extra   = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $packageTypes = $this->discoverPackageTypes($extra);
        $options = (new ConfigDiscovery())
            ->getAvailableConfigOptions($packageTypes, $this->projectRoot);

        if ($options->isEmpty()) {
            // No configuration options found; do nothing.
            return;
        }

        $this->marshalInstallableModules($extra, $options)
            ->each(function ($module) use ($name) {
            })
            // Create injectors
            ->reduce(function ($injectors, $module) use ($options, $packageTypes) {
                $injectors[$module] = $this->promptForConfigOption($module, $options, $packageTypes);
                return $injectors;
            }, new Collection([]))
            // Inject modules into configuration
            ->each(function ($injector, $module) use ($name, $packageTypes) {
                $this->injectModuleIntoConfig($name, $module, $injector, $packageTypes);
            });
    }

    /**
     * post-package-uninstall event hook
     *
     * This routine exits early if any of the following conditions apply:
     *
     * - Executed in non-development mode
     * - No config/application.config.php is available
     * - The composer.json does not define one of either extra.zf.component
     *   or extra.zf.module
     * - The value used for either extra.zf.component or extra.zf.module are
     *   empty or not strings.
     *
     * Otherwise, it will attempt to update the application configuration
     * using the value(s) discovered in extra.zf.component and/or extra.zf.module,
     * removing their values from the `modules` list.
     *
     * @param PackageEvent $event
     * @return void
     */
    public function onPostPackageUninstall(PackageEvent $event)
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        $options = (new ConfigDiscovery())
            ->getAvailableConfigOptions(
                new Collection(array_keys($this->packageTypes)),
                $this->projectRoot
            );

        if ($options->isEmpty()) {
            // No configuration options found; do nothing.
            return;
        }

        $package = $event->getOperation()->getPackage();
        $name    = $package->getName();
        $extra   = $this->getExtraMetadata($package->getExtra());
        $this->removePackageFromConfig($name, $extra, $options);
    }

    /**
     * Retrieve the zf-specific metadata from the "extra" section
     *
     * @param array $extra
     * @return array
     */
    private function getExtraMetadata(array $extra)
    {
        return isset($extra['zf']) && is_array($extra['zf'])
            ? $extra['zf']
            : []
        ;
    }

    /**
     * Discover what package types are relevant based on what the package
     * exposes in the extra configuration.
     *
     * @param string[] $extra
     * @return int[] Array of Injector\InjectorInterface::TYPE_* constants.
     */
    private function discoverPackageTypes(array $extra)
    {
        $packageTypes = array_flip($this->packageTypes);
        $knownTypes   = array_keys($packageTypes);
        return Collection::create(array_keys($extra))
            ->filter(function ($type) use ($knownTypes) {
                return in_array($type, $knownTypes, true);
            })
            ->reduce(function ($discoveredTypes, $type) use ($packageTypes) {
                $discoveredTypes[] = $packageTypes[$type];
                return $discoveredTypes;
            }, new Collection([]));
    }

    /**
     * Marshal a collection of defined package types.
     *
     * @param array $extra extra.zf value
     * @return Collection
     */
    private function marshalPackageTypes(array $extra)
    {
        // Create a collection of types registered in the package.
        return Collection::create($this->packageTypes)
            ->filter(function ($configKey, $type) use ($extra) {
                return $this->metadataForKeyIsValid($configKey, $extra);
            });
    }

    /**
     * Marshal a collection of package modules.
     *
     * @param array $extra extra.zf value
     * @param Collection $packageTypes
     * @param Collection $options ConfigOption instances
     * @return Collection
     */
    private function marshalPackageModules(array $extra, Collection $packageTypes, Collection $options)
    {
        // We only want to list modules that the application can configure.
        $supportedTypes = $options
            ->reduce(function ($allowed, $option) {
                return $allowed->merge($option->getInjector()->getTypesAllowed());
            }, new Collection([]))
            ->unique()
            ->toArray();

        return $packageTypes
            ->reduce(function ($modules, $configKey, $type) use ($extra, $supportedTypes) {
                if (! in_array($type, $supportedTypes, true)) {
                    return $modules;
                }
                return $modules->merge((array) $extra[$configKey]);
            }, new Collection([]))
            // Make sure the list is unique
            ->unique();
    }

    /**
     * Prepare a list of modules to install/register with configuration.
     *
     * @param string[] $extra
     * @param ConfigOption[] $options
     * @return string[] List of packages to install
     */
    private function marshalInstallableModules(array $extra, Collection $options)
    {
        return $this->marshalPackageModules($extra, $this->marshalPackageTypes($extra), $options)
            // Filter out modules that do not have a registered injector
            ->reject(function ($module) use ($options) {
                return $options->reduce(function ($registered, $option) use ($module) {
                    return $registered || $option->getInjector()->isRegistered($module);
                }, false);
            });
    }

    /**
     * Prompt for the user to select a configuration location to update.
     *
     * @param string $name
     * @param Collection $options
     * @param Collection $packageTypes
     * @return Injector\InjectorInterface
     */
    private function promptForConfigOption($name, Collection $options, Collection $packageTypes)
    {
        if ($cachedInjector = $this->getCachedInjector($packageTypes)) {
            return $cachedInjector;
        }

        $ask = $options->reduce(function ($ask, $option, $index) {
            $ask[] = sprintf(
                "  [<comment>%d</comment>] %s\n",
                $index,
                $option->getPromptText()
            );
            return $ask;
        }, []);

        array_unshift($ask, sprintf(
            "\n  <question>Please select which config file you wish to inject '%s' into:</question>\n",
            $name
        ));
        array_push($ask, '  Make your selection (default is <comment>0</comment>):');

        while (true) {
            $answer = $this->io->ask($ask, 0);

            if (is_numeric($answer) && isset($options[(int) $answer])) {
                $injector = $options[(int) $answer]->getInjector();
                $this->promptToRememberOption($injector);
                return $injector;
            }

            $this->io->write('<error>Invalid selection</error>');
        }
    }

    /**
     * Prompt the user to determine if the selection should be remembered for later packages.
     *
     * @todo Will need to store selection in filesystem and remove when all packages are complete
     * @param Injector\InjectorInterface $injector
     * return void
     */
    private function promptToRememberOption(Injector\InjectorInterface $injector)
    {
        $ask = ["\n  <question>Remember this option for other packages of the same type? (y/N)</question>"];

        while (true) {
            $answer = strtolower($this->io->ask($ask, 'n'));

            switch ($answer) {
                case 'y':
                    $this->cacheInjector($injector);
                    return;
                case 'n':
                    // intentionally fall-through
                default:
                    return;
            }
        }
    }

    /**
     * Inject a module into available configuration.
     *
     * @param string $package Package name
     * @param string $module Module to install in configuration
     * @param Injector\InjectorInterface $injector Injector to use.
     * @param Collection $packageTypes
     * @return void
     */
    private function injectModuleIntoConfig(
        $package,
        $module,
        Injector\InjectorInterface $injector,
        Collection $packageTypes
    ) {
        // Find the first package type the injector can handle.
        $type = $packageTypes
            ->reduce(function ($discovered, $type) use ($injector) {
                if ($discovered) {
                    return $discovered;
                }

                $discovered = $injector->registersType($type) ? $type : $discovered;
                return $discovered;
            }, false);

        $this->io->write(sprintf('<info>Installing %s from package %s</info>', $module, $package));
        $injector->inject($module, $type, $this->io);
    }

    /**
     * Remove a package from configuration.
     *
     * @param string $package Package name
     * @param array $metadata Metadata pulled from extra.zf
     * @param Collection $configOptions Discovered configuration options from
     *     which to remove package.
     * @return void
     */
    private function removePackageFromConfig($package, array $metadata, Collection $configOptions)
    {
        // Create a collection of types registered in the package.
        $packageTypes = $this->marshalPackageTypes($metadata);

        // Create a collection of configured injectors for the package types
        // registered.
        $injectors = $configOptions
            ->map(function ($configOption) {
                return $configOption->getInjector();
            })
            ->filter(function ($injector) use ($packageTypes) {
                return $packageTypes->reduce(function ($registered, $key, $type) use ($injector) {
                    return $registered || $injector->registersType($type);
                }, false);
            });

        // Create a collection of unique modules based on the package types present,
        // and remove each from configuration.
        $this->marshalPackageModules($metadata, $packageTypes, $configOptions)
            ->each(function ($module) use ($package, $injectors) {
                $this->removeModuleFromConfig($module, $package, $injectors);
            });
    }

    /**
     * Remove an individual module defined in a package from configuration.
     *
     * @param string $module Module to remove
     * @param string $package Package in which module is defined
     * @param Collection $injectors Injectors to use for removal
     * @return void
     */
    private function removeModuleFromConfig($module, $package, Collection $injectors)
    {
        $injectors->each(function ($injector) use ($module, $package) {
            $this->io->write(sprintf('<info>Removing %s from package %s</info>', $module, $package));
            $injector->remove($module, $this->io);
        });
    }

    /**
     * Is a given module name valid?
     *
     * @param string $module
     * @return bool
     */
    private function moduleIsValid($module)
    {
        return (is_string($module) && ! empty($module));
    }

    /**
     * Is a given metadata value (extra.zf.*) valid?
     *
     * @param string $key Key to examine in metadata
     * @param array $metadata
     * @return bool
     */
    private function metadataForKeyIsValid($key, array $metadata)
    {
        if (! isset($metadata[$key])) {
            return false;
        }

        if (is_string($metadata[$key])) {
            return $this->moduleIsValid($metadata[$key]);
        }

        if (! is_array($metadata[$key])) {
            return false;
        }

        return Collection::create($metadata[$key])
            ->reduce(function ($valid, $value) {
                if (false === $valid) {
                    return $valid;
                }
                return $this->moduleIsValid($value);
            }, null);
    }

    /**
     * Attempt to retrieve a cached injector, based on the current package types.
     *
     * @param Collection $packageTypes
     * @return null|Injector\InjectorInterface
     */
    private function getCachedInjector(Collection $packageTypes)
    {
        return $packageTypes->reduce(function ($injector, $type) {
            if (null !== $injector || ! isset($this->cachedInjectors[$type])) {
                return $injector;
            }

            return $this->cachedInjectors[$type];
        }, null);
    }

    /**
     * Cache an injector for later use.
     *
     * @param Injector\InjectorInterface $injector
     * @return void
     */
    private function cacheInjector(Injector\InjectorInterface $injector)
    {
        Collection::create($injector->getTypesAllowed())
            ->reject(function ($type) {
                return isset($this->cachedInjectors[$type]);
            })
            ->each(function ($type) use ($injector) {
                $this->cachedInjectors[$type] = $injector;
            });
    }
}
