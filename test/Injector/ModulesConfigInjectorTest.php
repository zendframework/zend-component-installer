<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Injector\ModulesConfigInjector;

class ModulesConfigInjectorTest extends AbstractInjectorTestCase
{
    /** @var string */
    protected $configFile = 'config/modules.config.php';

    /** @var string */
    protected $injectorClass = ModulesConfigInjector::class;

    /** @var int[] */
    protected $injectorTypesAllowed = [
        ModulesConfigInjector::TYPE_COMPONENT,
        ModulesConfigInjector::TYPE_MODULE,
        ModulesConfigInjector::TYPE_DEPENDENCY,
        ModulesConfigInjector::TYPE_BEFORE_APPLICATION,
    ];

    public function allowedTypes()
    {
        return [
            'config-provider'            => [ModulesConfigInjector::TYPE_CONFIG_PROVIDER, false],
            'component'                  => [ModulesConfigInjector::TYPE_COMPONENT, true],
            'module'                     => [ModulesConfigInjector::TYPE_MODULE, true],
            'dependency'                 => [ModulesConfigInjector::TYPE_DEPENDENCY, true],
            'before-application-modules' => [ModulesConfigInjector::TYPE_BEFORE_APPLICATION, true],
        ];
    }

    public function injectComponentProvider()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'Application',\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'Application',\n];";
        return [
            'component-long-array'  => [ModulesConfigInjector::TYPE_COMPONENT, $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'Foo\Bar',\n    'Application',\n);"],
            'component-short-array' => [ModulesConfigInjector::TYPE_COMPONENT, $baseContentsShortArray, '<' . "?php\nreturn [\n    'Foo\Bar',\n    'Application',\n];"],
            'module-long-array'     => [ModulesConfigInjector::TYPE_MODULE,    $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'Application',\n    'Foo\Bar',\n);"],
            'module-short-array'    => [ModulesConfigInjector::TYPE_MODULE,    $baseContentsShortArray, '<' . "?php\nreturn [\n    'Application',\n    'Foo\Bar',\n];"],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packageAlreadyRegisteredProvider()
    {
        // @codingStandardsIgnoreStart
        return [
            'component-long-array'  => ['<' . "?php\nreturn array(\n    'Foo\Bar',\n    'Application',\n);", ModulesConfigInjector::TYPE_COMPONENT],
            'component-short-array' => ['<' . "?php\nreturn [\n    'Foo\Bar',\n    'Application',\n];",      ModulesConfigInjector::TYPE_COMPONENT],
            'module-long-array'     => ['<' . "?php\nreturn array(\n    'Application',\n    'Foo\Bar',\n);", ModulesConfigInjector::TYPE_MODULE],
            'module-short-array'    => ['<' . "?php\nreturn [\n    'Application',\n    'Foo\Bar',\n];",      ModulesConfigInjector::TYPE_MODULE],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function emptyConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'Application',\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'Application',\n];";
        // @codingStandardsIgnoreEnd

        return [
            'long-array'  => [$baseContentsLongArray],
            'short-array' => [$baseContentsShortArray],
        ];
    }

    public function packagePopulatedInConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'Application',\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'Application',\n];";
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'Foo\Bar',\n    'Application',\n);", $baseContentsLongArray],
            'short-array' => ['<' . "?php\nreturn [\n    'Foo\Bar',\n    'Application',\n];",      $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
