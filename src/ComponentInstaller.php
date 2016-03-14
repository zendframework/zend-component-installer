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
     * Does nothing.
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
            'post-package-install'      => 'onPostPackageInstall',
            'post-package-uninstall'    => 'onPostPackageUninstall',
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
        $name  = $package->getName();
        $extra = $this->getExtraMetadata($package->getExtra());

        if (empty($extra)) {
            // Package does not define anything of interest; do nothing.
            return;
        }

        $packageTypes = $this->discoverPackageTypes($extra);
        $options = (new ConfigDiscovery())
            ->getAvailableConfigOptions($packageTypes, $this->projectRoot);

        if (empty($options)) {
            // No configuration options found; do nothing.
            return;
        }

        $injector = $this->promptForConfigOption($name, $options, $packageTypes);
        $this->injectPackageIntoConfig($name, $extra, $injector);
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
            ->getAvailableConfigOptions(array_keys($this->packageTypes), $this->projectRoot);

        if (empty($options)) {
            // No configuration options found; do nothing.
            return;
        }

        $package = $event->getOperation()->getPackage();
        $name  = $package->getName();
        $extra = $this->getExtraMetadata($package->getExtra());
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
        $discoveredTypes = [];
        foreach (array_keys($extra) as $type) {
            if (! in_array($type, array_keys($packageTypes), true)) {
                continue;
            }
            $discoveredTypes[] = $packageTypes[$type];
        }
        return $discoveredTypes;
    }

    /**
     * Prompt for the user to select a configuration location to update.
     *
     * @param string $name
     * @param ConfigOption[] $options
     * @param int[] $packageTypes
     * @return Injector\InjectorInterface
     */
    private function promptForConfigOption($name, array $options, array $packageTypes)
    {
        if ($cachedInjector = $this->getCachedInjector($packageTypes)) {
            return $cachedInjector;
        }

        $ask = [sprintf(
            "\n  <question>Please select which config file you wish to inject '%s' into:</question>\n",
            $name
        )];

        foreach ($options as $index => $option) {
            $ask[] = sprintf(
                "  [<comment>%d</comment>] %s\n",
                $index,
                $option->getPromptText()
            );
        }

        $ask[] = '  Make your selection (default is <comment>0</comment>):';

        while (true) {
            $answer = $this->io->ask($ask, 0);

            if (is_numeric($answer) && isset($options[(int) $answer])) {
                $this->promptToRememberOption($options[(int) $answer]->getInjector(), $packageTypes);
                return $options[(int) $answer]->getInjector();
            }

            $this->io->write('<error>Invalid selection</error>');
        }
    }

    /**
     * Prompt the user to determine if the selection should be remembered for later packages.
     *
     * @todo Will need to store selection in filesystem and remove when all packages are complete
     * @param Injector\InjectorInterface $injector
     * @param int[] $packageTypes
     * return void
     */
    private function promptToRememberOption(Injector\InjectorInterface $injector, array $packageTypes)
    {
        $ask = ["\n  <question>Remember this option for other packages of the same type? (y/N)</question>"];

        while (true) {
            $answer = strtolower($this->io->ask($ask, 'n'));

            switch ($answer) {
                case 'y':
                    $this->cacheInjector($injector);
                    return;
                case 'n':
                    // intentionaly fall-through
                default:
                    return;
            }
        }
    }

    /**
     * Inject a package into available configuration.
     *
     * @param string $package Package name
     * @param array $metadata Package metadata with potential injections.
     * @param Injector\InjectorInterface $injector Injector to use.
     * @return void
     */
    private function injectPackageIntoConfig($package, array $metadata, Injector\InjectorInterface $injector)
    {
        foreach ($this->packageTypes as $type => $key) {
            if (! $injector->registersType($type)) {
                continue;
            }

            if (! $this->metadataKeyIsValid($key, $metadata)) {
                continue;
            }

            $this->io->write(sprintf('<info>Installing %s from package %s</info>', $metadata[$key], $package));
            $injector->inject($metadata[$key], $type, $this->io);
        }
    }

    /**
     * Remove a package from configuration.
     *
     * @param string $package Package name
     * @param ConfigOption[] $configOptions Discovered configuration locations
     *     to remove package from.
     * @param Injector\InjectorInterface $injector Injector to use.
     * @return void
     */
    private function removePackageFromConfig($package, array $metadata, array $configOptions)
    {
        foreach ($this->packageTypes as $type => $key) {
            foreach ($configOptions as $configOption) {
                $injector = $configOption->getInjector();

                if (! $injector->registersType($type)) {
                    continue;
                }

                if (! $this->metadataKeyIsValid($key, $metadata)) {
                    continue;
                }

                $this->io->write(sprintf('<info>Removing %s from package %s</info>', $metadata[$key], $package));
                $injector->remove($metadata[$key], $type, $this->io);
            }
        }
    }

    /**
     * Is a given metadata key valid and accessible?
     *
     * @param string $key
     * @param array $metadata
     * @return bool
     */
    private function metadataKeyIsValid($key, array $metadata)
    {
        return (isset($metadata[$key]) && is_string($metadata[$key]) && ! empty($metadata[$key]));
    }

    /**
     * Attempt to retrieve a cached injector, based on the current package types.
     *
     * @param int[] $packageTypes
     * @return null|Injector\InjectorInterface
     */
    private function getCachedInjector(array $packageTypes)
    {
        foreach ($packageTypes as $type) {
            if (! isset($this->cachedInjectors[$type])) {
                continue;
            }

            return $this->cachedInjectors[$type];
        }
    }

    /**
     * Cache an injector for later use.
     *
     * @param Injector\InjectorInterface $injector
     * @parram int[] $packageTypes
     * @return void
     */
    private function cacheInjector(Injector\InjectorInterface $injector)
    {
        foreach ($injector->getTypesAllowed() as $type) {
            if (isset($this->cachedInjectors[$type])) {
                continue;
            }
            $this->cachedInjectors[$type] = $injector;
        }
    }
}
