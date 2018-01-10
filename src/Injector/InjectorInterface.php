<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Exception;

/**
 * @todo add getConfigFile() method in 2.0
 */
interface InjectorInterface
{
    const TYPE_CONFIG_PROVIDER = 0;
    const TYPE_COMPONENT = 1;
    const TYPE_MODULE = 2;
    const TYPE_DEPENDENCY = 3;
    const TYPE_BEFORE_APPLICATION = 4;

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
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function inject($package, $type);

    /**
     * Remove a package from the configuration.
     *
     * @param string $package Package to remove.
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function remove($package);

    /**
     * Set modules of the application.
     *
     * @param array $modules
     * @return self
     */
    public function setApplicationModules(array $modules);

    /**
     * Set dependencies for the module.
     *
     * @param array $modules
     * @return self
     */
    public function setModuleDependencies(array $modules);
}
