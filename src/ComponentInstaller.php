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
    /**
     * @param \Composer\Script\PackageEvent $event
     */
    public static function postPackageInstall($event)
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
        $extra = $package->getExtra();

        if (! isset($extra['module'])) {
            // Do nothing if the package is not a module
            return;
        }
        $module = $extra['module'];

        $io->write(sprintf('<info>Installing module %s from package %s</info>', $module, $name));
        self::updateApplicationConfig($module, $io);
    }

    /**
     * @todo Needs implementation!
     * @param \Composer\Script\PackageEvent $event
     */
    public static function postPackageUninstall($event)
    {
    }

    /**
     * @param string $module
     * @param \Composer\IO\IOInterface $io
     */
    private static function updateApplicationConfig($module, $io)
    {
        $config = file_get_contents('config/application.config.php');

        if (self::moduleIsRegistered($module, $config)) {
            $io->write(sprintf('<info>    Module is already registered; skipping</info>'));
            return;
        }

        $pattern = '/^(\s+)(\'modules\'\s*\=\>\s*(array\(|\[))\s*$/m';
        $replacement = '$1$2' . "\n" . '$1    \'' . $module . '\',';
        $config = preg_replace($pattern, $replacement, $config);
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
}
