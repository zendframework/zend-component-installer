<?php

return [
    [
        'name' => 'install',
        'route' => '[<path>]',
        'description' => 'Install the zend-mvc component installer scripts and related code into your project tree.',
        'short_description' => 'Install the component installer scripts',
        'options_descriptions' => [
            '[<path>]' => 'Path to the project, if not the current working directory'
        ],
        'handler' => Zend\ComponentInstaller\Command\Installer::class,
    ],
    [
        'name' => 'self-update',
        'description' => 'Update to the latest version of the component installer.',
        'short_description' => 'Update this PHAR',
        'handler' => Zend\ComponentInstaller\Command\SelfUpdate::class,
    ]
];
