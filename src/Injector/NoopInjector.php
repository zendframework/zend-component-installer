<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\Injector;

class NoopInjector implements InjectorInterface
{
    /**
     * {@inheritDoc}
     *
     * @return true
     */
    public function registersType($type)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesAllowed()
    {
        return [];
    }

    /**
     * @param string $package
     * @return false
     */
    public function isRegistered($package)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($package)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setApplicationModules(array $modules)
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setModuleDependencies(array $modules)
    {
        return $this;
    }
}
