<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\Injector;

use Composer\IO\IOInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\ComponentInstaller\Injector\NoopInjector;

class NoopInjectorTest extends TestCase
{
    public function setUp()
    {
        $this->injector = new NoopInjector();
    }

    /**
     * @dataProvider packageTypes
     */
    public function testWillRegisterAnyType($type)
    {
        $this->assertTrue($this->injector->registersType($type), 'NoopInjector does not register type ' . $type);
    }

    public function testGetTypesAllowedReturnsNoTypes()
    {
        $this->assertEquals([], $this->injector->getTypesAllowed());
    }

    public function packageTypes()
    {
        return [
            'config-provider' => [NoopInjector::TYPE_CONFIG_PROVIDER],
            'component'       => [NoopInjector::TYPE_COMPONENT],
            'module'          => [NoopInjector::TYPE_MODULE],
        ];
    }

    /**
     * @dataProvider packageTypes
     */
    public function testInjectIsANoop($type)
    {
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::any())->shouldNotBeCalled();
        $this->assertNull($this->injector->inject('Foo\Bar', $type, $io->reveal()));
    }

    /**
     * @dataProvider packageTypes
     */
    public function testRemoveIsANoop($type)
    {
        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::any())->shouldNotBeCalled();
        $this->assertNull($this->injector->remove('Foo\Bar', $io->reveal()));
    }
}
