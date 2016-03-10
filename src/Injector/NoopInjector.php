<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace Zend\ComponentInstaller\Injector;

use Composer\IO\IOInterface;

class NoopInjector implements InjectorInterface
{
    /**
     * {@inheritDoc}
     *
     * @return false
     */
    public function registersType($type)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function inject($package, $type, IOInterface $io)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function remove($package, $type, IOInterface $io)
    {
    }
}
