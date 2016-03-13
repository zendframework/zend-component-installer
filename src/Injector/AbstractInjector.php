<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

use Composer\IO\IOInterface;

abstract class AbstractInjector implements InjectorInterface
{
    /**
     * Types this injector is allowed to register.
     *
     * Implementations MAY overwrite this value.
     *
     * @param int[]
     */
    protected $allowedTypes = [
        self::TYPE_COMPONENT,
        self::TYPE_MODULE,
    ];

    /**
     * Replacements to make after removing code from the configuration for clean-up purposes.
     *
     * Implementations MAY overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     'pattern' => 'regular expression',
     *     'replacement' => 'preg_replace replacement',
     * ],
     * ```
     *
     * @var string[]
     */
    protected $cleanUpPatterns = [
        'pattern' => "/(array\(|\[|,)(\r?\n){2}/s",
        'replacement' => "\$1\n",
    ];

    /**
     * Configuration file to update.
     *
     * Implementations MUST overwrite this value.
     *
     * @var string
     */
    protected $configFile;

    /**
     * Patterns and replacements to use when registering a code item.
     *
     * Implementations MUST overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     TYPE_CONSTANT => [
     *         'pattern' => 'regular expression',
     *         'replacement' => 'preg_replace replacement, with %s placeholder for package',
     *     ],
     * ]
     * ```
     *
     * @var string[]
     */
    protected $injectionPatterns = [];

    /**
     * Pattern to use to determine if the code item is registered.
     *
     * Implementations MUST overwrite this value.
     *
     * @var string
     */
    protected $isRegisteredPattern;

    /**
     * Patterns and replacements to use when removing a code item.
     *
     * Implementations MUST overwrite this value.
     *
     * Structure MUST be:
     *
     * ```
     * [
     *     'pattern' => 'regular expression, with %s placeholder for component namespace/configuration class',
     *     'replacement' => 'preg_replace replacement, usually an empty string',
     * ],
     * ```
     *
     * @var string[]
     */
    protected $removalPatterns = [];

    /**
     * {@inheritDoc}
     */
    public function registersType($type)
    {
        return in_array($type, $this->allowedTypes, true);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesAllowed()
    {
        return $this->allowedTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type, IOInterface $io)
    {
        $config = file_get_contents($this->configFile);

        if ($this->isRegistered($package, $config)) {
            $io->write(sprintf('<info>    Package is already registered; skipping</info>'));
            return;
        }

        $pattern = $this->injectionPatterns[$type]['pattern'];
        $replacement = sprintf(
            $this->injectionPatterns[$type]['replacement'],
            $package
        );

        $config = preg_replace($pattern, $replacement, $config);
        file_put_contents($this->configFile, $config);
    }

    /**
     * Remove a package from the configuration.
     *
     * @var string $package Package name.
     * @var string $config
     * @return void
     */
    public function remove($package, $type, IOInterface $io)
    {
        $config = file_get_contents($this->configFile);

        if (! $this->isRegistered($package, $config)) {
            return;
        }

        $config = preg_replace(
            sprintf($this->removalPatterns['pattern'], preg_quote($package)),
            $this->removalPatterns['replacement'],
            $config
        );

        $config = preg_replace(
            $this->cleanUpPatterns['pattern'],
            $this->cleanUpPatterns['replacement'],
            $config
        );

        file_put_contents($this->configFile, $config);
    }

    /**
     * Is the code item registered in the configuration already?
     *
     * @var string $package Package name
     * @var string $config
     * @return bool
     */
    protected function isRegistered($package, $config)
    {
        return (1 === preg_match(sprintf($this->isRegisteredPattern, preg_quote($package)), $config));
    }
}
