<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
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
