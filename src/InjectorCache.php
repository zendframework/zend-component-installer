<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller;

class InjectorCache
{
    const CACHE_FILE = '.component-installer-cache';

    /**
     * Remove the installer cache if it exists.
     *
     * @return void
     */
    public static function removeInstallerCache()
    {
        if (is_writable(self::CACHE_FILE)) {
            unlink(self::CACHE_FILE);
        }
    }

    /**
     * Attempt to retrieve a cached injector, based on the current package types.
     *
     * @param int[] $packageTypes
     * @return null|Injector\InjectorInterface
     */
    public static function getCachedInjector(array $packageTypes)
    {
        $map = self::getCachedMap();

        foreach ($packageTypes as $type) {
            if (! isset($map[$type])) {
                continue;
            }

            $injectorClass = $map[$type];
            return new $injectorClass;
        }

    }

    /**
     * Cache an injector for later use.
     *
     * @param Injector\InjectorInterface $injector
     * @parram int[] $packageTypes
     * @return void
     */
    public static function cacheInjector(Injector\InjectorInterface $injector)
    {
        $injectorClass = get_class($injector);
        $map = self::getCachedMap();
        foreach ($injector->getTypesAllowed() as $type) {
            if (isset($map[$type])) {
                continue;
            }
            $map[$type] = $injectorClass;
        }

        self::cacheInjectorMap($map);
    }

    /**
     * Retrieve the cached injector map.
     */
    private static function getCachedMap()
    {
        if (! is_readable(self::CACHE_FILE)) {
            return [];
        }

        return include self::CACHE_FILE;
    }

    /**
     * Cache the injector map.
     *
     * @param string[] $map
     * @return void
     */
    private static function cacheInjectorMap(array $map)
    {
        $contents = '<' . "?php\nreturn " . var_export($map, true) . ';';
        file_put_contents(self::CACHE_FILE, $contents);
    }
}
