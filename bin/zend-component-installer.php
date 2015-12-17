<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

require __DIR__ . '/../vendor/autoload.php';

use Zend\Console\Console;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

$version = '@package_version@';

// Reset version if not rewritten (which happens when using
// a phar)
$version = ($version === '@' . 'package_version' . '@')
    ? 'dev-master'
    : $version;

$application = new Application(
    'Component Installer',
    $version,
    include __DIR__ . '/../config/routes.php',
    Console::getInstance(),
    new Dispatcher()
);

$exit = $application->run();
exit($exit);
