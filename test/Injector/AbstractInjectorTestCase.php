<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\Injector;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\ComponentInstaller\Injector\AbstractInjector;

use function file_get_contents;

abstract class AbstractInjectorTestCase extends TestCase
{
    /** @var vfsStreamDirectory */
    protected $configDir;

    /** @var string */
    protected $configFile;

    /** @var AbstractInjector */
    protected $injector;

    /** @var string */
    protected $injectorClass;

    /** @var int[] */
    protected $injectorTypesAllowed = [];

    protected function setUp() : void
    {
        $this->configDir = vfsStream::setup('project');

        $injectorClass = $this->injectorClass;
        $this->injector = new $injectorClass(
            vfsStream::url('project')
        );
    }

    abstract public function allowedTypes();

    /**
     * @dataProvider allowedTypes
     *
     * @param string $type
     * @param bool $expected
     */
    public function testRegistersTypesReturnsExpectedBooleanBasedOnType($type, $expected)
    {
        $this->assertSame($expected, $this->injector->registersType($type));
    }

    public function testGetTypesAllowedReturnsListOfAllExpectedTypes()
    {
        $this->assertEquals($this->injectorTypesAllowed, $this->injector->getTypesAllowed());
    }

    abstract public function injectComponentProvider();

    /**
     * @dataProvider injectComponentProvider
     *
     * @param string $type
     * @param string $initialContents
     * @param string $expectedContents
     */
    public function testInjectAddsPackageToModulesListInAppropriateLocation($type, $initialContents, $expectedContents)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $injected = $this->injector->inject('Foo\Bar', $type);

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertEquals($expectedContents, $result);
        $this->assertTrue($injected);
    }

    abstract public function packageAlreadyRegisteredProvider();

    /**
     * @dataProvider packageAlreadyRegisteredProvider
     *
     * @param string $contents
     * @param string $type
     */
    public function testInjectDoesNotModifyContentsIfPackageIsAlreadyRegistered($contents, $type)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $injected = $this->injector->inject('Foo\Bar', $type);

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($contents, $result);
        $this->assertFalse($injected);
    }

    abstract public function emptyConfiguration();

    /**
     * @dataProvider emptyConfiguration
     *
     * @param string $contents
     */
    public function testRemoveDoesNothingIfPackageIsNotInConfigFile($contents)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $removed = $this->injector->remove('Foo\Bar');
        $this->assertFalse($removed);
    }

    abstract public function packagePopulatedInConfiguration();

    /**
     * @dataProvider packagePopulatedInConfiguration
     *
     * @param string $initialContents
     * @param string $expectedContents
     */
    public function testRemoveRemovesPackageFromConfigurationWhenFound($initialContents, $expectedContents)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $removed = $this->injector->remove('Foo\Bar');

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($expectedContents, $result);
        $this->assertTrue($removed);
    }
}
