<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

use Composer\IO\IOInterface;

class ExpressiveConfigInjector extends AbstractInjector
{
    /**
     * {@inheritDoc}
     */
    protected $allowedTypes = [
        self::TYPE_CONFIG_PROVIDER,
    ];

    /**
     * Configuration file to update.
     *
     * @var string
     */
    protected $configFile = 'config/config.php';

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * @var string[]
     */
    protected $injectionPatterns = [
        self::TYPE_CONFIG_PROVIDER => [
            'pattern' => '/(new (?:\\?Zend\\Expressive\\ConfigManager\\\\)?ConfigManager\(\s*(?:array\(|\[)\s*)$/m',
            'replacement' => "\$1\n    %s::class,",
        ],
    ];

    // @codingStandardsIgnoreStart
    /**
     * Pattern to use to determine if the code item is registered.
     *
     * @var string
     */
    protected $isRegisteredPattern = '/new (?:\\?Zend\\Expressive\\ConfigManager\\\\)?ConfigManager\(\s*(?:array\(|\[).*\s+%s::class/s';
    // @codingStandardsIgnoreEnd

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * @var string[]
     */
    protected $removalPatterns = [
        'pattern' => '/^\s+%s::class,\s*$/m',
        'replacement' => '',
    ];

    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function inject($package, $type, IOInterface $io)
    {
        parent::inject('\\' . $package, $type, $io);
    }

    /**
     * {@inheritDoc}
     *
     * Prepends the package with a `\\` in order to ensure it is fully
     * qualified, preventing issues in config files that are namespaced.
     */
    public function remove($package, $type, IOInterface $io)
    {
        parent::remove('\\' . $package, $type, $io);
    }
}
