<?php
namespace LongArray\Application;

use ClassMap\SomeInterface;

class Module implements SomeInterface
{
    public function getModuleDependencies()
    {
        return array(
            'D1',
            'D2',
        );
    }
}
