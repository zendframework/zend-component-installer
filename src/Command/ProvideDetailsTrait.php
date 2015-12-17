<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Command;

use Exception;
use Zend\Console\ColorInterface as Color;

trait ProvideDetailsTrait
{
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
