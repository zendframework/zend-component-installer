<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Injector\ModulesConfigInjector;

class ModulesConfigInjectorTest extends AbstractInjectorTestCase
{
    protected $configFile = 'config/modules.config.php';

    protected $injectorClass = ModulesConfigInjector::class;

    protected $injectorTypesAllowed = [
        ModulesConfigInjector::TYPE_COMPONENT,
        ModulesConfigInjector::TYPE_MODULE,
    ];

    public function allowedTypes()
    {
        return [
            'config-provider' => [ModulesConfigInjector::TYPE_CONFIG_PROVIDER, false],
            'component'       => [ModulesConfigInjector::TYPE_COMPONENT, true],
            'module'          => [ModulesConfigInjector::TYPE_MODULE, true],
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
        return [
            'component-long-array'  => [ModulesConfigInjector::TYPE_COMPONENT, $baseContentsLongArray],
            'component-short-array' => [ModulesConfigInjector::TYPE_COMPONENT, $baseContentsShortArray],
            'module-long-array'     => [ModulesConfigInjector::TYPE_MODULE,    $baseContentsLongArray],
            'module-short-array'    => [ModulesConfigInjector::TYPE_MODULE,    $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packagePopulatedInConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'Application',\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'Application',\n];";
        return [
            'component-long-array'  => [ModulesConfigInjector::TYPE_COMPONENT, '<' . "?php\nreturn array(\n    'Foo\Bar',\n    'Application',\n);", $baseContentsLongArray],
            'component-short-array' => [ModulesConfigInjector::TYPE_COMPONENT, '<' . "?php\nreturn [\n    'Foo\Bar',\n    'Application',\n];",      $baseContentsShortArray],
            'module-long-array'     => [ModulesConfigInjector::TYPE_MODULE,    '<' . "?php\nreturn array(\n    'Application',\n    'Foo\Bar',\n);", $baseContentsLongArray],
            'module-short-array'    => [ModulesConfigInjector::TYPE_MODULE,    '<' . "?php\nreturn [\n    'Application',\n    'Foo\Bar',\n];",      $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
