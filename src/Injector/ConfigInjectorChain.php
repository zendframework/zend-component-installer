<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Collection;
use Zend\ComponentInstaller\ConfigDiscovery\DiscoveryChainInterface;

use function in_array;

class ConfigInjectorChain implements InjectorInterface
{
    /**
     * ConfigInjectors Collection
     *
     * @var Collection
     */
    protected $chain;

    /**
     * Types this injector is allowed to register.
     *
     * Implementations MAY overwrite this value.
     *
     * @param int[]
     */
    protected $allowedTypes;

    /**
     * Constructor
     *
     * Optionally accept the project root directory; if non-empty, it is used
     * to prefix the $configFile.
     *
     * @param mixed $injectors
     * @param DiscoveryChainInterface $discoveryChain
     * @param Collection $availableTypes
     * @param string $projectRoot
     */
    public function __construct(
        $injectors,
        DiscoveryChainInterface $discoveryChain,
        Collection $availableTypes,
        $projectRoot = ''
    ) {
        $this->chain = Collection::create($injectors)
            // Keep only those injectors that discovery exists in discoveryChain
            ->filter(function ($injector, $file) use ($discoveryChain) {
                return $discoveryChain->discoveryExists($file);
            })
            // Create an injector for the config file
            ->map(function ($injector) use ($projectRoot) {
                return new $injector($projectRoot);
            })
            // Keep only those injectors that match types available for the package
            ->filter(function ($injector) use ($availableTypes) {
                return $availableTypes->reduce(function ($flag, $type) use ($injector) {
                    return $flag || $injector->registersType($type);
                }, false);
            });
    }

    /**
     * {@inheritDoc}
     */
    public function registersType($type)
    {
        return in_array($type, $this->getTypesAllowed(), true);
    }

    /**
     * {@inheritDoc}
     */
    public function getTypesAllowed()
    {
        if ($this->allowedTypes) {
            return $this->allowedTypes;
        }
        $allowedTypes = [];
        foreach ($this->chain->getIterator() as $injector) {
            $allowedTypes = $allowedTypes + $injector->getTypesAllowed();
        }
        $this->allowedTypes = $allowedTypes;
        return $allowedTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function isRegistered($package)
    {
        $isRegisteredCount = $this->chain
            ->filter(function ($injector) use ($package) {
                return $injector->isRegistered($package);
            })
            ->count();
        return $this->chain->count() === $isRegisteredCount;
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type)
    {
        $injected = false;

        $this->chain
            ->each(function ($injector) use ($package, $type, &$injected) {
                $injected = $injector->inject($package, $type) || $injected;
            });

        return $injected;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($package)
    {
        $removed = false;

        $this->chain
            ->each(function ($injector) use ($package, &$removed) {
                $removed = $injector->remove($package) || $removed;
            });

        return $removed;
    }

    /**
     *
     * @return Collection
     */
    public function getCollection()
    {
        return $this->chain;
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
