<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\Injector;

class ModulesConfigInjector extends AbstractInjector
{
    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/modules.config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var array[]
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\n    '%s',",
        ],
        self::TYPE_MODULE => [
            'pattern' => "/(return\s+(?:array\s*\(|\[).*?)\n(\s*)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
        self::TYPE_DEPENDENCY => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[)[^)\]]*\'%s\')/m',
            'replacement' => "\$1,\n    '%s'",
        ],
        self::TYPE_BEFORE_APPLICATION => [
            'pattern' => '/^(return\s+(?:array\s*\(|\[)[^)\]]*)(\'%s\')/m',
            'replacement' => "\$1'%s',\n    \$2",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * @var string
     */
    protected $isRegisteredPattern = '/return\s+(?:array\(|\[)[^)\]]*\'%s\'/s';

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * @var string[]
     */
    protected $removalPatterns = [
        'pattern' => '/^\s+\'%s\',\s*$/m',
        'replacement' => '',
    ];
}
