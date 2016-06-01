<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

use Composer\IO\IOInterface;

interface InjectorInterface
{
    const TYPE_CONFIG_PROVIDER = 0;
    const TYPE_COMPONENT = 1;
    const TYPE_MODULE = 2;

    /**
     * Whether or not the injector can handle the given type.
     *
     * @param int $type One of the TYPE_* constants.
     * @return bool
     */
    public function registersType($type);

    /**
     * Return a list of types the injector handles.
     *
     * @return int[]
     */
    public function getTypesAllowed();

    /**
     * Is a given package already registered?
     *
     * @param string $package
     * @return bool
     */
    public function isRegistered($package);

    /**
     * Register a package with the configuration.
     *
     * @param string $package Package to inject into configuration.
     * @param int $type One of the TYPE_* constants.
     * @param IOInterface $io
     * @return void
     */
    public function inject($package, $type, IOInterface $io);

    /**
     * Remove a package from the configuration.
     *
     * @param string $package Package to remove.
     * @param IOInterface $io
     * @return void
     */
    public function remove($package, IOInterface $io);
}
