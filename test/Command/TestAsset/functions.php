<?php // @codingStandardsIgnoreFile
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Command;

/**
 * Test spy for copy
 *
 * @param string $source
 * @param string $target
 * @return bool
 */
function copy($source, $target)
{
    return FileInfoStub::copy($source, $target);
}

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
 * Test spy for is_dir
 *
 * @param string $path
 * @return bool
 */
function is_dir($path)
{
    return DirStub::exists($path);
}

/**
 * Test spy for mkdir
 *
 * @param string $path
 * @param int $mode
 * @param bool $recursive
 * @return bool
 */
function mkdir($path, $mode = 0777, $recursive = false)
{
    return DirStub::create($path, $mode, $recursive);
}

/**
 * Test spy for file operations.
 */
class FileInfoStub
{
    /**
     * @var string[]
     */
    private static $copyMap = [];

    /**
     * @var string[]
     */
    private static $map = [];

    /**
     * @var int[]
     */
    private static $methodInvocationCount = [
        'copy' => 0,
        'exists' => 0,
        'get' => 0,
        'put' => 0,
    ];

    /**
     * @var string[]
     */
    private static $writeErrorMap = [];

    /**
     * Clear the file map and invocation counts.
     */
    public static function clear()
    {
        self::$copyMap = [];
        self::$writeErrorMap = [];
        self::$map = [];
        self::$methodInvocationCount = [
            'copy' => 0,
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
     * Define a copy operation that should fail
     *
     * @param string $source
     * @param string $target
     */
    public static function defineCopyFailure($source, $target)
    {
        self::$copyMap[$source] = $target;
    }

    /**
     * Define a file that, when a write is attempted, results in an error
     *
     * @param string $file
     */
    public static function defineWriteError($file)
    {
        self::$writeErrorMap[$file] = true;
    }

    /**
     * "Copy" a file
     *
     * If an identical entry already exists, returns false;
     * otherwise, adds the entry and returns true.
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public static function copy($source, $target)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        if (array_key_exists($source, self::$copyMap)
            && $target === self::$copyMap[$source]
        ) {
            return false;
        }
        return true;
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
     * @return false|int
     */
    public static function put($name, $contents)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        if (array_key_exists($name, self::$writeErrorMap)) {
            return false;
        }
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

class DirStub
{
    private static $map = [];

    private static $methodInvocationCount = [
        'create' => 0,
        'exists' => 0,
    ];

    public static function clear()
    {
        self::$map = [];
        self::$methodInvocationCount = [
            'create' => 0,
            'exists' => 0,
        ];
    }

    /**
     * Define a directory
     *
     * If $exists is true, the is_dir will return true for this path.
     *
     * If not, is_dir will return false.
     *
     * @param string $path
     * @param bool $exists
     */
    public static function defineDir($path, $exists = true)
    {
        self::$map[$path] = $exists ? [0777, false] : false;
    }

    /**
     * "Create" a directory
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool If the path already exists in the map, but is set to a
     *     boolean false value, this will return false in order to emulate a
     *     mkdir failure.
     */
    public static function create($path, $mode, $recursive)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        if (array_key_exists($path, self::$map)) {
            return false;
        }
        self::$map[$path] = [$mode, $recursive];
        return true;
    }

    /**
     * Does the directory exist?
     *
     * Returns true if the path is in the map, and the value is not boolean
     * false.
     *
     * @param string $path
     * @return bool
     */
    public static function exists($path)
    {
        ++self::$methodInvocationCount[__FUNCTION__];
        return array_key_exists($path, self::$map) && false !== self::$map[$path];
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
