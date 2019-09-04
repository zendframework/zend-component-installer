<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\Injector;

use PHPUnit\Framework\TestCase;
use Zend\ComponentInstaller\Injector\NoopInjector;

class NoopInjectorTest extends TestCase
{
    /** @var NoopInjector */
    private $injector;

    protected function setUp() : void
    {
        $this->injector = new NoopInjector();
    }

    /**
     * @dataProvider packageTypes
     *
     * @param string $type
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
     *
     * @param string $type
     */
    public function testInjectIsANoop($type)
    {
        $injected = $this->injector->inject('Foo\Bar', $type);

        $this->assertFalse($injected);
    }

    public function testRemoveIsANoop()
    {
        $removed = $this->injector->remove('Foo\Bar');

        $this->assertFalse($removed);
    }
}
