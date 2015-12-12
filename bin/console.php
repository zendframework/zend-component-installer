<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

require __DIR__ . '/../vendor/autoload.php';

use Zend\Console\Console;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

$version = '0.0.1';

$application = new Application(
    'Component Installer',
    $version,
    include __DIR__ . '/../config/routes.php',
    Console::getInstance(),
    new Dispatcher()
);

$exit = $application->run();
exit($exit);
