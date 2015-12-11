<?php
namespace Zend\ComponentInstaller;

/*
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Script\PackageEvent;
 */

class ComponentInstaller
{
    public static function postPackageInstall(/* PackageEvent */ $event)
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

    private static function updateApplicationConfig($module, /* IOInterface */ $io)
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

    private static function moduleIsRegistered($module, $config)
    {
        return preg_match(
            '/\'modules\'\s*\=\>\s*(array\(|\[)[^)\]]*\'' . $module . '\'/s',
            $config
        );
    }
}
