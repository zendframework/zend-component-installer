<?php
namespace LongArray\Application;

use ClassMap\SomeInterface;

class Module implements SomeInterface
{
    public function getModuleDependencies()
    {
        return array(
            'Foo\\D1',
            'Bar\\D2',
        );
    }
}
