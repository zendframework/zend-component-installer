<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

namespace Zend\ComponentInstaller;

/* These are commented out for a reason.
 *
 * If we type-hint on these classes, then the project needs to depend on
 * the composer/composer package. Considering that the package itself likely
 * has no direct requirement on that package, and it's only purpose is for
 * this installer, it's an artificial requirement.
 *
 * Throughout the code, where typehints could be of use, they are annotated
 * in docblocks.
 *
 * use Composer\IO\IOInterface;
 * use Composer\Script\PackageEvent;
 */

/**
 * If a package represents a component module, update the application configuration.
 */
class ComponentInstaller
{
    const PLACEMENT_COMPONENT = 1;
    const PLACEMENT_MODULE = 2;

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
     * @param \Composer\Script\PackageEvent $event
     */
    public static function postPackageInstall($event)
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

        if (isset($extra['module'])) {
            $io->write(sprintf('<info>Installing module %s from package %s</info>', $extra['module'], $name));
            self::addModuleToApplicationConfig($extra['module'], $io, self::PLACEMENT_MODULE);
        }

        if (isset($extra['component'])) {
            $io->write(sprintf('<info>Installing component module %s from package %s</info>', $extra['component'], $name));
            self::addModuleToApplicationConfig($extra['component'], $io, self::PLACEMENT_COMPONENT);
        }
    }

    /**
     * @todo Needs implementation!
     * @param \Composer\Script\PackageEvent $event
     */
    public static function postPackageUninstall($event)
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
     * @param string $module
     * @param \Composer\IO\IOInterface $io
     * @param int $placement One of the PLACEMENT_* constants
     */
    private static function addModuleToApplicationConfig($module, $io, $placement)
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
     * @param string $module
     * @param \Composer\IO\IOInterface $io
     */
    private static function removeModuleFromApplicationConfig($module, $io)
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
