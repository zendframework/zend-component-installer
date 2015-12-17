<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

use Zend\ComponentInstaller\Command;

$routes = [
    [
        'name' => 'install',
        'route' => '[<path>]',
        'description' => 'Install the zend-mvc component installer scripts and related code into your project tree.',
        'short_description' => 'Install the component installer scripts',
        'options_descriptions' => [
            '[<path>]' => 'Path to the project, if not the current working directory',
        ],
        'handler' => Command\Installer::class,
    ],
];

// self-update and rollback only make sense in the context of a PHAR file.
if (substr(__FILE__, 0, 7) === 'phar://') {
    $routes[] = [
        'name' => 'self-update',
        'route' => '[--verbose]',
        'description' => 'Update to the latest version of the component installer.',
        'short_description' => 'Update this PHAR',
        'options_descriptions' => [
            '[--verbose]' => 'Enable verbosity when updating (useful for troubleshooting)',
        ],
        'handler' => Command\SelfUpdate::class,
    ];
    $routes[] = [
        'name' => 'rollback',
        'route' => '[--verbose]',
        'description' => 'Rollback to a previously installed version of the PHAR, if available.',
        'short_description' => 'Rollback to the previously installed version',
        'options_descriptions' => [
            '[--verbose]' => 'Enable verbosity when updating (useful for troubleshooting)',
        ],
        'handler' => Command\Rollback::class,
    ];
}

return $routes;
