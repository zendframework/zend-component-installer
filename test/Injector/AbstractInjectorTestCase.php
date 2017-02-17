<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\Injector;

use Composer\IO\IOInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

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

    public function setUp()
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
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldNotBeCalled();

        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $this->injector->inject('Foo\Bar', $type, $io->reveal());

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertEquals($expectedContents, $result);
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

        $io = $this->prophesize(IOInterface::class);
        $io->write('<info>    Package is already registered; skipping</info>')->shouldBeCalled();

        $this->injector->inject('Foo\Bar', $type, $io->reveal());

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($contents, $result);
    }

    abstract public function emptyConfiguration();

    /**
     * @dataProvider emptyConfiguration
     *
     * @param string $contents
     */
    public function testRemoveDoesNothingIfPackageIsNotInConfigFile($type, $contents)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($contents);

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldNotBeCalled();

        $this->assertNull($this->injector->remove('Foo\Bar', $io->reveal()));
    }

    abstract public function packagePopulatedInConfiguration();

    /**
     * @dataProvider packagePopulatedInConfiguration
     *
     * @param string $initialContents
     * @param string $expectedContents
     */
    public function testRemoveRemovesPackageFromConfigurationWhenFound($type, $initialContents, $expectedContents)
    {
        vfsStream::newFile($this->configFile)
            ->at($this->configDir)
            ->setContent($initialContents);

        $configFile = $this->configFile;
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::that(function ($message) use ($configFile) {
            $pattern = sprintf('#^<info>    Removed package from .*?%s</info>$#', preg_quote($configFile));
            return preg_match($pattern, $message);
        }))->shouldBeCalled();

        $this->injector->remove('Foo\Bar', $io->reveal());

        $result = file_get_contents(vfsStream::url('project/' . $this->configFile));
        $this->assertSame($expectedContents, $result);
    }
}
