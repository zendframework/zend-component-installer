<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Injector\ApplicationConfigInjector;

class ApplicationConfigInjectorTest extends AbstractInjectorTestCase
{
    /** @var string */
    protected $configFile = 'config/application.config.php';

    /** @var string */
    protected $injectorClass = ApplicationConfigInjector::class;

    /** @var int[] */
    protected $injectorTypesAllowed = [
        ApplicationConfigInjector::TYPE_COMPONENT,
        ApplicationConfigInjector::TYPE_MODULE,
        ApplicationConfigInjector::TYPE_DEPENDENCY,
        ApplicationConfigInjector::TYPE_BEFORE_APPLICATION,
    ];

    public function allowedTypes()
    {
        return [
            'config-provider'            => [ApplicationConfigInjector::TYPE_CONFIG_PROVIDER, false],
            'component'                  => [ApplicationConfigInjector::TYPE_COMPONENT, true],
            'module'                     => [ApplicationConfigInjector::TYPE_MODULE, true],
            'dependency'                 => [ApplicationConfigInjector::TYPE_DEPENDENCY, true],
            'before-application-modules' => [ApplicationConfigInjector::TYPE_BEFORE_APPLICATION, true],
        ];
    }

    public function injectComponentProvider()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'component-long-array'  => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);"],
            'component-short-array' => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];"],
            'module-long-array'     => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsLongArray,  '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);"],
            'module-short-array'    => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsShortArray, '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];"],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packageAlreadyRegisteredProvider()
    {
        // @codingStandardsIgnoreStart
        return [
            'component-long-array'      => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", ApplicationConfigInjector::TYPE_COMPONENT],
            'component-short-array'     => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           ApplicationConfigInjector::TYPE_COMPONENT],
            'component-escaped-slashes' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\\\\Bar',\n        'Application',\n    ]\n];",           ApplicationConfigInjector::TYPE_COMPONENT],
            'module-long-array'         => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);", ApplicationConfigInjector::TYPE_MODULE],
            'module-short-array'        => ['<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];",           ApplicationConfigInjector::TYPE_MODULE],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function emptyConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        // @codingStandardsIgnoreEnd

        return [
            'long-array'  => [$baseContentsLongArray],
            'short-array' => [$baseContentsShortArray],
        ];
    }

    public function packagePopulatedInConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", $baseContentsLongArray],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
