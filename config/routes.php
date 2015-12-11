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
    ]
];
