<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

class ApplicationConfigInjector extends AbstractInjector
{
    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/application.config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var string[]
     */
    protected $injectionPatterns = [
        self::TYPE_COMPONENT => [
            'pattern' => '/^(\s+)(\'modules\'\s*\=\>\s*(?:array\s*\(|\[))\s*$/m',
            'replacement' => "\$1\$2\n\$1    '%s',",
        ],
        self::TYPE_MODULE => [
            'pattern' => "/('modules'\s*\=\>\s*(?:array\s*\(|\[).*?)\n(\s+)(\)|\])/s",
            'replacement' => "\$1\n\$2    '%s',\n\$2\$3",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * @var string
     */
    protected $isRegisteredPattern = '/\'modules\'\s*\=\>\s*(?:array\(|\[)[^)\]]*\'%s\'/s';

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
