<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Injector\ApplicationConfigInjector;

class ApplicationConfigInjectorTest extends AbstractInjectorTestCase
{
    protected $configFile = 'config/application.config.php';

    protected $injectorClass = ApplicationConfigInjector::class;

    protected $injectorTypesAllowed = [
        ApplicationConfigInjector::TYPE_COMPONENT,
        ApplicationConfigInjector::TYPE_MODULE,
    ];

    public function allowedTypes()
    {
        return [
            'config-provider' => [ApplicationConfigInjector::TYPE_CONFIG_PROVIDER, false],
            'component'       => [ApplicationConfigInjector::TYPE_COMPONENT, true],
            'module'          => [ApplicationConfigInjector::TYPE_MODULE, true],
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
            'component-long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", ApplicationConfigInjector::TYPE_COMPONENT],
            'component-short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           ApplicationConfigInjector::TYPE_COMPONENT],
            'module-long-array'     => ['<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);", ApplicationConfigInjector::TYPE_MODULE],
            'module-short-array'    => ['<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];",           ApplicationConfigInjector::TYPE_MODULE],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function emptyConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'component-long-array'  => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsLongArray],
            'component-short-array' => [ApplicationConfigInjector::TYPE_COMPONENT, $baseContentsShortArray],
            'module-long-array'     => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsLongArray],
            'module-short-array'    => [ApplicationConfigInjector::TYPE_MODULE,    $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packagePopulatedInConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsLongArray  = '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n    )\n);";
        $baseContentsShortArray = '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n    ]\n];";
        return [
            'component-long-array'  => [ApplicationConfigInjector::TYPE_COMPONENT, '<' . "?php\nreturn array(\n    'modules' => array(\n        'Foo\Bar',\n        'Application',\n    )\n);", $baseContentsLongArray],
            'component-short-array' => [ApplicationConfigInjector::TYPE_COMPONENT, '<' . "?php\nreturn [\n    'modules' => [\n        'Foo\Bar',\n        'Application',\n    ]\n];",           $baseContentsShortArray],
            'module-long-array'     => [ApplicationConfigInjector::TYPE_MODULE,    '<' . "?php\nreturn array(\n    'modules' => array(\n        'Application',\n        'Foo\Bar',\n    )\n);", $baseContentsLongArray],
            'module-short-array'    => [ApplicationConfigInjector::TYPE_MODULE,    '<' . "?php\nreturn [\n    'modules' => [\n        'Application',\n        'Foo\Bar',\n    ]\n];",           $baseContentsShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
