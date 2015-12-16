<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

namespace Zend\ComponentInstaller;

use Composer\IO\IOInterface;
use Composer\Script\PackageEvent;

/**
 * If a package represents a component module, update the application configuration.
 *
 * Packages opt-in to this workflow by defining one or both of the keys:
 *
 * - extra.zf.component
 * - extra.zf.module
 *
 * with the value being the string namespace the component and/or module
 * defines:
 *
 * <code class="lang-javascript">
 * {
 *   "extra": {
 *     "zf": {
 *       "component": "Zend\\Form",
 *       "module": "ZF\\Apigility\\ContentNegotiation"
 *     }
 *   }
 * }
 * </code>
 *
 * Additionally, for this to work correctly, the package MUST define a `Module`
 * in the namespace listed in either the extra.zf.component or extra.zf.module
 * definition.
 *
 * Components are added to the TOP of the modules list, to ensure that userland
 * code and/or modules can override the settings. Modules are added to the
 * bottom of the modules list.
 *
 * In either case, you can edit the modules list when complete to create a
 * specific order.
 */
class ComponentInstaller
{
    const PLACEMENT_COMPONENT = 1;
    const PLACEMENT_MODULE = 2;

    /**
     * @var string[] patterns and replacements used based on placement in the
     *     application configuration.
     */
    private static $placementPatterns = [
        self::PLACEMENT_COMPONENT => [
            'pattern' => '/^(\s+)(\'modules\'\s*\=\>\s*(array\(|\[))\s*$/m',
            'replacement' => "\$1\$2\n\$1    '%s',",
        ],
        self::PLACEMENT_MODULE => [
            'pattern' => "/('modules'\s*\=\>\s*(?:array\s*\(|\[).*?)\n(\s+)\)/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2)",
        ],
    ];

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
    public static function postPackageInstall(PackageEvent $event)
    {
        $io = $event->getIo();

        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        if (! is_file('config/application.config.php')) {
            // Do nothing if config/application.config.php does not exist
            return;
        }

        $package = $event->getOperation()->getPackage();
        $name  = $package->getName();
        $extra = self::getExtraMetadata($package->getExtra());

        if (isset($extra['module']) && is_string($extra['module'] && ! empty($extra['module']))) {
            $io->write(sprintf('<info>Installing module %s from package %s</info>', $extra['module'], $name));
            self::addModuleToApplicationConfig($extra['module'], $io, self::PLACEMENT_MODULE);
        }

        if (isset($extra['component']) && is_string($extra['component'] && ! empty($extra['component']))) {
            $io->write(sprintf('<info>Installing component module %s from package %s</info>', $extra['component'], $name));
            self::addModuleToApplicationConfig($extra['component'], $io, self::PLACEMENT_COMPONENT);
        }
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
    public static function postPackageUninstall(PackageEvent $event)
    {
        if (! $event->isDevMode()) {
            // Do nothing in production mode.
            return;
        }

        if (! is_file('config/application.config.php')) {
            // Do nothing if config/application.config.php does not exist
            return;
        }

        $io = $event->getIo();

        $package = $event->getOperation()->getPackage();
        $name  = $package->getName();
        $extra = self::getExtraMetadata($package->getExtra());

        if (isset($extra['module'])) {
            $io->write(sprintf('<info>Uninstalling module %s (from package %s)</info>', $extra['module'], $name));
            self::removeModuleFromApplicationConfig($extra['module'], $io);
        }

        if (isset($extra['component'])) {
            $io->write(sprintf('<info>Uninstalling component module %s (from package %s)</info>', $extra['component'], $name));
            self::removeModuleFromApplicationConfig($extra['component'], $io);
        }
    }

    /**
     * Add a module to the application config, at the specified location.
     *
     * If the module is detected in the application config, this method returns
     * early.
     *
     * Otherwise, it reads the application config and injects the module name
     * based on the $placement provided, writing the changes back to the disk.
     *
     * @param string $module
     * @param IOInterface $io
     * @param int $placement One of the PLACEMENT_* constants
     * @return void
     */
    private static function addModuleToApplicationConfig($module, IOInterface $io, $placement)
    {
        $config = file_get_contents('config/application.config.php');

        if (self::moduleIsRegistered($module, $config)) {
            $io->write(sprintf('<info>    Module is already registered; skipping</info>'));
            return;
        }

        $pattern = self::$placementPatterns[$placement]['pattern'];
        $replacement = sprintf(
            self::$placementPatterns[$placement]['replacement'],
            $module
        );

        $config = preg_replace($pattern, $replacement, $config);
        file_put_contents('config/application.config.php', $config);
    }

    /**
     * Add a module to the application config, at the specified location.
     *
     * If the module is NOT detected in the application config, this method
     * returns early.
     *
     * Otherwise, it reads the application config and removes the module name,
     * writing the changes back to the disk.
     *
     * @param string $module
     * @param IOInterface $io
     */
    private static function removeModuleFromApplicationConfig($module, IOInterface $io)
    {
        $config = file_get_contents('config/application.config.php');

        if (! self::moduleIsRegistered($module, $config)) {
            $io->write(sprintf('<info>    Module was not registered with application; skipping</info>'));
            return;
        }

        $pattern = '/^\s+\'' . preg_quote($module) . '\',\s*$/m';
        $config = preg_replace($pattern, '', $config);
        $config = preg_replace("/(\r?\n){2}/s", "\n", $config);
        file_put_contents('config/application.config.php', $config);
    }

    /**
     * Is the module already present in the configuration?
     *
     * @param string $module
     * @param string $config
     * @return bool
     */
    private static function moduleIsRegistered($module, $config)
    {
        return preg_match(
            '/\'modules\'\s*\=\>\s*(array\(|\[)[^)\]]*\'' . $module . '\'/s',
            $config
        );
    }

    /**
     * Retrieve the zf-specific metadata from the "extra" section
     *
     * @param array $extra
     * @return array
     */
    private static function getExtraMetadata(array $extra)
    {
        return isset($extra['zf']) && is_array($extra['zf'])
            ? $extra['zf']
            : []
        ;
    }
}
