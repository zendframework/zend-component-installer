<?php
namespace Zend\ComponentInstaller\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;

class SelfUpdate
{
    const URL_PHAR = 'https://weierophinney.github.io/component-installer/component-installer.phar';
    const URL_VERSION = 'https://weierophinney.github.io/component-installer/component-installer.phar.version';

    public function __invoke(Route $route, Console $console)
    {
        $updater = new Updater();
        $updater->getStrategy()->setPharUrl(self::URL_PHAR);
        $updater->getStrategy()->setVersionUrl(self::URL_VERSION);

        try {
            $result = $updater->update();
            if (! $result) {
                $console->writeLine('No updated needed!', Color::GREEN);
                return 0;
            }
            $new = $updater->getNewVersion();
            $old = $updater->getOldVersion();
            $console->writeLine(sprintf(
                'Updated from %s to %s',
                $old,
                $new
            ), Color::GREEN);

            return 0;
        } catch (Exception $e) {
            $console->writeLine(
                '[ERROR] Could not update',
                Color::RED
            );
            return 1;
        }
    }
}
