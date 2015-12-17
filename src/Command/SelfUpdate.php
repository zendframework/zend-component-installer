<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Command;

use Exception;
use Humbug\SelfUpdate\Updater;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;

/**
 * Command for performing a phar self-update.
 */
class SelfUpdate
{
    // @codingStandardsIgnoreStart
    const URL_PHAR = 'https://zendframework.github.io/zend-component-installer/zend-component-installer.phar';
    const URL_VERSION = 'https://zendframework.github.io/zend-component-installer/zend-component-installer.phar.version';
    // @codingStandardsIgnoreEnd

    /**
     * @param Route $route
     * @param Console $console
     * @return int
     */
    public function __invoke(Route $route, Console $console)
    {
        $verbose = $route->getMatchedParam('verbose', false);

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

            if ($verbose) {
                $this->provideDetails($e, $console);
            }

            return 1;
        }
    }

    /**
     * Provide execution error details
     *
     * @param Exception $exception
     * @param Console $console
     */
    private function provideDetails(Exception $exception, Console $console)
    {
        $console->writeLine('Details:');

        do {
            $console->writeLine(sprintf(
                '(%d) %s in %s:%d:',
                $exception->getCode(),
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ), Color::RED);
            $trace = $exception->getTraceAsString();
            $trace = preg_replace('/^/m', '    ', $trace);
            $trace = preg_replace("/(\r?\n)/s", PHP_EOL, $trace);
            $console->writeLine($trace);
        } while ($exception = $exception->getPrevious());
    }
}
