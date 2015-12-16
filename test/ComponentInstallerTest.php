<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

namespace ZendTest\ComponentInstaller;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\PackageEvent;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\ComponentInstaller\ComponentInstaller;
use Zend\ComponentInstaller\FileInfoStub;

require_once __DIR__ . '/TestAsset/functions.php';

class ComponentInstallerTest extends TestCase
{
    public function setUp()
    {
        FileInfoStub::clear();
    }

    public function testPostPackageInstallReturnsEarlyIfEventIsNotInDevMode()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(false);
        $event->getOperation()->shouldNotBeCalled();
        $event->getIO()->shouldNotBeCalled();

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertSame(0, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageInstallReturnsEarlyIfApplicationConfigIsMissing()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->shouldNotBeCalled();
        $event->getIO()->shouldNotBeCalled();

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageInstallDoesNothingIfComposerExtraIsEmpty()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageInstallDoesNothingIfComposerExtraDoesNotContainZFSection()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['branch-alias' => ['master' => '1.0-dev']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageInstallDoesNothingIfZFExtraSectionDoesNotContainComponentOrModule()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => ['unknown' => 'operation']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageInstallAddsListedModuleToEndOfApplicationConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => ['module' => 'Some\\Module']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(1);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        $escaped = preg_quote('Some\\Module');
        $this->assertRegExp(
            '/\'modules\' \=\> array\([^)]+\'' . $escaped . '\',[^\']+\),/s',
            FileInfoStub::get('config/application.config.php')
        );
    }

    public function testPostPackageInstallAddsListedComponentToTopOfApplicationConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => ['component' => 'Some\\Component']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(1);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        $escaped = preg_quote('Some\\Component');
        $this->assertRegExp(
            '/\'modules\' \=\> array\(\s+\'' . $escaped . '\',/s',
            FileInfoStub::get('config/application.config.php')
        );
    }

    public function testPostPackageInstallCanAddBothComponentAndModule()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
            'module' => 'Some\\Module',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        $config = FileInfoStub::get('config/application.config.php');
        $escaped = preg_quote('Some\\Component');
        $this->assertRegExp(
            '/\'modules\' \=\> array\(\s+\'' . $escaped . '\',/s',
            $config
        );

        $escaped = preg_quote('Some\\Module');
        $this->assertRegExp(
            '/\'modules\' \=\> array\([^)]+\'' . $escaped . '\',[^\']+\),/s',
            $config
        );
    }

    public function testPostPackageInstallDoesNothingIfComponentAlreadyInConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application-with-component.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => ['component' => 'Some\\Component']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertSame(0, FileInfoStub::getInvocationCount('put'));
    }

    public function testPostPackageInstallDoesNothingIfModuleAlreadyInConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application-with-module.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => ['module' => 'Some\\Module']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageInstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertSame(0, FileInfoStub::getInvocationCount('put'));
    }

    public function testPostPackageUninstallReturnsEarlyIfEventIsNotInDevMode()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(false);
        $event->getOperation()->shouldNotBeCalled();
        $event->getIO()->shouldNotBeCalled();

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertSame(0, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageUninstallReturnsEarlyIfApplicationConfigIsMissing()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->shouldNotBeCalled();
        $event->getIO()->shouldNotBeCalled();

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageUninstallDoesNothingIfComposerExtraIsEmpty()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageUninstallDoesNothingIfComposerExtraDoesNotContainZFSection()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['branch-alias' => ['master' => '1.0-dev']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageUninstallDoesNothingIfZFExtraSectionDoesNotContainComponentOrModule()
    {
        FileInfoStub::defineFile('config/application.config.php');

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => ['unknown' => 'operation']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertSame(1, FileInfoStub::getInvocationCount('exists'));
    }

    public function testPostPackageUninstallRemovesListedModuleFromApplicationConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application-with-module.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => ['module' => 'Some\\Module']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(1);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        $escaped = preg_quote('Some\\Module');
        $this->assertNotRegExp(
            '/\'modules\' \=\> array\([^)]+\'' . $escaped . '\'/s',
            FileInfoStub::get('config/application.config.php')
        );
    }

    public function testPostPackageUninstallRemovesListedComponentFromApplicationConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application-with-component.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => ['component' => 'Some\\Component']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(1);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        $escaped = preg_quote('Some\\Component');
        $this->assertNotRegExp(
            '/\'modules\' \=\> array\([^)]+\'' . $escaped . '\'/s',
            FileInfoStub::get('config/application.config.php')
        );
    }

    public function testPostPackageUninstallCanRemoveBothComponentAndModule()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application-with-component-and-module.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
            'module' => 'Some\\Module',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('put'));

        foreach (['Some\\Component', 'Some\\Module'] as $test) {
            $this->assertNotRegExp(
                '/\'modules\' \=\> array\([^)]+\'' . preg_quote($test) . '\'/s',
                FileInfoStub::get('config/application.config.php')
            );
        }
    }

    public function testPostPackageUninstallDoesNothingIfComponentNotInConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => ['component' => 'Some\\Component']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertSame(0, FileInfoStub::getInvocationCount('put'));
    }

    public function testPostPackageUninstallDoesNothingIfModuleNotInConfig()
    {
        FileInfoStub::defineFile(
            'config/application.config.php',
            \file_get_contents(__DIR__ . '/TestAsset/application.config.php')
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => ['module' => 'Some\\Module']]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $io = $this->prophesize(IOInterface::class);
        $io->write(Argument::type('string'))->shouldBeCalledTimes(2);

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());
        $event->getIO()->willReturn($io->reveal());

        $this->assertNull(ComponentInstaller::postPackageUninstall($event->reveal()));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('get'));
        $this->assertSame(0, FileInfoStub::getInvocationCount('put'));
    }
}
