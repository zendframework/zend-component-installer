<?php
namespace Zend\ComponentInstaller\Command;

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;

class Installer
{
    const SCRIPT_POST_PACKAGE_INSTALL = 'Zend\ComponentInstaller\ComponentInstaller::postPackageInstall';
    const SCRIPT_POST_PACKAGE_UNINSTALL = 'Zend\ComponentInstaller\ComponentInstaller::postPackageUninstall';

    public function __invoke($route, $console)
    {
        $path = $route->getMatchedParam('path', realpath(getcwd()));

        $installPath = $this->createComponentInstallerDirectory($path);
        if (false === $installPath) {
            $console->writeLine(sprintf(
                'Unable to create component-installer directory in selected path (%s); aborting',
                $path
            ), Color::RED);
            return 1;
        }

        if (false === copy(__DIR__ . '/../ComponentInstaller.php', sprintf('%s/ComponentInstaller.php', $installPath))) {
            $console->writeLine(sprintf(
                'Unable to copy ComponentInstaller.php to %s/component-installer/; aborting',
                $path
            ), Color::RED);
            return 1;
        }
        $composer = $this->getComposer($path);
        if (false === $composer
            || empty($composer)
        ) {
            $console->writeLine(sprintf(
                'Unable to read/parse %s/composer.json; aborting',
                $path
            ), Color::RED);
            return 1;
        }

        $composer = $this->injectAutoloadEntry($composer);
        $composer = $this->injectScripts($composer);
        if (false === $this->writeComposer($composer, $path)) {
            $console->writeLine(sprintf(
                'Unable to write updated %s/composer.json; aborting',
                $path
            ), Color::RED);
            return 1;
        }

        $console->writeLine('ComponentInstaller installed!', Color::GREEN);
        return 0;
    }

    private function createComponentInstallerDirectory($path)
    {
        $newPath = sprintf('%s/component-installer', $path);
        if (is_dir($newPath)) {
            return $newPath;
        }
        if (false === mkdir($newPath, 0755, true)) {
            return false;
        }
        return $newPath;
    }

    private function getComposer($path)
    {
        $composerFile = sprintf('%s/composer.json', $path);
        if (! is_file($composerFile)) {
            return false;
        }
        $composerJson = file_get_contents($composerFile);
        return json_decode($composerJson, true);
    }

    private function injectAutoloadEntry(array $composer)
    {
        if (isset($composer['autoload']['psr-4']['Zend\\ComponentInstaller\\'])) {
            return $composer;
        }
        $composer['autoload']['psr-4']['Zend\\ComponentInstaller\\'] = 'component-installer/';
        return $composer;
    }

    private function injectScripts(array $composer)
    {
        if (! isset($composer['scripts']['post-package-install'])) {
            $composer['scripts']['post-package-install'] = [];
        }
        if (! isset($composer['scripts']['post-package-uninstall'])) {
            $composer['scripts']['post-package-uninstall'] = [];
        }
        if (! in_array(self::SCRIPT_POST_PACKAGE_INSTALL, $composer['scripts']['post-package-install'])) {
            $composer['scripts']['post-package-install'][] = self::SCRIPT_POST_PACKAGE_INSTALL;
        }
        if (! in_array(self::SCRIPT_POST_PACKAGE_UNINSTALL, $composer['scripts']['post-package-uninstall'])) {
            $composer['scripts']['post-package-uninstall'][] = self::SCRIPT_POST_PACKAGE_UNINSTALL;
        }
        return $composer;
    }

    private function writeComposer(array $composer, $path)
    {
        return file_put_contents(
            sprintf('%s/composer.json', $path),
            json_encode(
                $composer,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
