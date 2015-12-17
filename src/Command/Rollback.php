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
 * Command for rolling back to a previous install of the phar.
 */
class Rollback
{
    use ProvideDetailsTrait;

    /**
     * @param Route $route
     * @param Console $console
     * @return int
     */
    public function __invoke(Route $route, Console $console)
    {
        $verbose = $route->getMatchedParam('verbose', false);

        $console->writeLine('Rolling back to a previous installed version...', Color::GREEN);
        $updater = new Updater();

        try {
            $result = $updater->rollback();
            if (! $result) {
                $console->writeLine('Rollback failed!', Color::RED);
                return 1;
            }
        } catch (Exception $e) {
            $console->writeLine('[ERROR] Could not rollback', Color::RED);

            if ($verbose) {
                $this->provideDetails($e, $console);
            }

            return 1;
        }

        $console->writeLine('Rollback complete!', Color::GREEN);
        return 0;
    }
}
