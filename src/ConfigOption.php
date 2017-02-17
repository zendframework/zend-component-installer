<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\ComponentInstaller;

class ConfigOption
{
    /**
     * @var Injector\InjectorInterface
     */
    private $injector;

    /**
     * @var string
     */
    private $promptText;

    /**
     * @param string $promptText
     * @param Injector\InjectorInterface $injector
     */
    public function __construct($promptText, Injector\InjectorInterface $injector)
    {
        $this->promptText = $promptText;
        $this->injector = $injector;
    }

    /**
     * @return string
     */
    public function getPromptText()
    {
        return $this->promptText;
    }

    /**
     * @return Injector\InjectorInterface
     */
    public function getInjector()
    {
        return $this->injector;
    }
}
