<?php // @codingStandardsIgnoreFile
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

namespace Zend\ComponentInstaller;

/**
 * Test spy for is_file
 *
 * @param string $name
 * @return bool
 */
function is_file($name)
{
    return FileInfoStub::exists($name);
}

/**
 * Test spy for file_get_contents
 *
 * @param string $name
 * @return false|string
 */
function file_get_contents($name)
{
    return FileInfoStub::get($name);
}

/**
 * Test spy for file_put_contents
 *
 * @param string $name
 * @param string $contents
 * @return int
 */
function file_put_contents($name, $contents)
{
    return FileInfoStub::put($name, $contents);
}

/**
 * Test spy for file operations.
 */
class FileInfoStub
{
    /**
     * @var string[]
     */
    private static $map = [];

    /**
     * @var int[]
     */
    private static $methodInvocationCount = [
        'exists' => 0,
        'get' => 0,
        'put' => 0,
    ];

    /**
     * Clear the file map and invocation counts.
     */
    public static function clear()
    {
        self::$map = [];
        self::$methodInvocationCount = [
            'exists' => 0,
            'get' => 0,
            'put' => 0,
        ];
    }

    /**
     * Define a file, without necessarily specifying its contents.
     *
     * @param string $name
     * @param string $contents
     */
    public static function defineFile($name, $contents = '')
    {
        self::$map[$name] = $contents;
    }

    /**
     * Does the file exist in the map?
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        return isset(self::$map[$name]);
    }

    /**
     * Retrieve the file contents
     *
     * @param string $name
     * @return false|string
     */
    public static function get($name)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        if (! self::exists($name)) {
            return false;
        }
        return self::$map[$name];
    }

    /**
     * Put a file's contents into the map
     *
     * @param string $name
     * @param string $contents
     * @return int
     */
    public static function put($name, $contents)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        self::defineFile($name, $contents);
        return strlen($contents);
    }

    /**
     * Retrieve a count of the number of times a specific method has been called
     *
     * @param string $method
     * @return int
     */
    public static function getInvocationCount($method)
    {
        if (! array_key_exists($method, self::$methodInvocationCount)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid method "%s" called for %s',
                $method,
                __CLASS__
            ));
        }
        return self::$methodInvocationCount[$method];
    }
}
