<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

use Zend\ComponentInstaller\Command;

return [
    [
        'name' => 'install',
        'route' => '[<path>]',
        'description' => 'Install the zend-mvc component installer scripts and related code into your project tree.',
        'short_description' => 'Install the component installer scripts',
        'options_descriptions' => [
            '[<path>]' => 'Path to the project, if not the current working directory'
        ],
        'handler' => Command\Installer::class,
    ],
    [
        'name' => 'self-update',
        'description' => 'Update to the latest version of the component installer.',
        'short_description' => 'Update this PHAR',
        'handler' => Command\SelfUpdate::class,
    ]
];
