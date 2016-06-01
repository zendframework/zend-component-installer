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
     * Pattern is set in constructor due to PCRE quoting issues.
     *
     * @var string[]
     */
    protected $injectionPatterns = [
        self::TYPE_CONFIG_PROVIDER => [
            'pattern'     => '',
            'replacement' => "\$1\n    %s::class,",
        ],
    ];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Set in constructor due to PCRE quoting issues.
     *
     * @var string
     */
    protected $isRegisteredPattern = '';

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * @var string[]
     */
    protected $removalPatterns = [
        'pattern'     => '/^\s+%s::class,\s*$/m',
        'replacement' => '',
    ];

    /**
     * {@inheritDoc}
     *
     * Sets $isRegisteredPattern and pattern for $injectionPatterns to ensure
     * proper PCRE quoting.
     */
    public function __construct($projectRoot = '')
    {
        $this->isRegisteredPattern = '/new (?:'
            . preg_quote('\\')
            . '?'
            . preg_quote('Zend\Expressive\ConfigManager\\')
            . ')?ConfigManager\(\s*(?:array\(|\[).*\s+%s::class/s';

        $this->injectionPatterns[self::TYPE_CONFIG_PROVIDER]['pattern'] = sprintf(
            '/(new (?:%s?%s)?ConfigManager\(\s*(?:array\(|\[)\s*)$/m',
            preg_quote('\\'),
            preg_quote('Zend\Expressive\ConfigManager\\')
        );

        parent::__construct($projectRoot);
    }

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
    public function remove($package, IOInterface $io)
    {
        parent::remove('\\' . $package, $io);
    }
}
