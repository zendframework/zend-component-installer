<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Script\PackageEvent;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\ComponentInstaller\ComponentInstaller;
use Zend\ComponentInstaller\FileInfoStub;

class ComponentInstallerTest extends TestCase
{
    private $projectRoot;

    public function setUp()
    {
        $this->projectRoot = vfsStream::setup('project');
        $this->installer = new ComponentInstaller(vfsStream::url('project'));

        $this->io = $this->prophesize(IOInterface::class);
        $this->installer->activate(
            $this->prophesize(Composer::class)->reveal(),
            $this->io->reveal()
        );
    }

    public function createApplicationConfig($contents = null)
    {
        $contents = $contents ?: '<' . "?php\nreturn [\n    'modules' => [\n    ]\n];";
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    public function testSubscribesToExpectedEvents()
    {
        $this->assertEquals([
            'post-package-install'   => 'onPostPackageInstall',
            'post-package-uninstall' => 'onPostPackageUninstall',
        ], $this->installer->getSubscribedEvents());
    }

    public function testOnPostPackageInstallReturnsEarlyIfEventIsNotInDevMode()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(false);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
    }

    public function testPostPackageInstallDoesNothingIfComposerExtraIsEmpty()
    {
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
    }

    public function testOnPostPackageInstallReturnsEarlyIfApplicationConfigIsMissing()
    {
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
            'config-provider' => 'Some\\Component\\ConfigProvider',
            'module' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
    }

    public function testPostPackageInstallDoesNothingIfZFExtraSectionDoesNotContainComponentOrModule()
    {
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => []]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
    }

    public function testOnPostPackageInstallDoesNotPromptIfPackageIsAlreadyInConfiguration()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'Some\Component',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallPromptsForConfigOptions()
    {
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Some\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallPromptsForConfigOptionsWhenDefinedAsArrays()
    {
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => [
                'Some\\Component',
                'Other\\Component',
            ],
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Some\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Other\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('n')->shouldBeCalledTimes(2);

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Other\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Some\Component'", $config);
        $this->assertContains("'Other\Component'", $config);
    }

    public function testMultipleInvocationsOfOnPostPackageInstallCanPromptMultipleTimes()
    {
        // Do a first pass, with an initial package
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Some\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('other/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Other\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Other\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Other\Component from package other/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Other\Component'", $config);
    }

    public function testMultipleInvocationsOfOnPostPackageInstallCanReuseOptions()
    {
        // Do a first pass, with an initial package
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Some\Component' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('other/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Other\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Other\Component from package other/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertContains("'Other\Component'", $config);
    }

    public function testOnPostPackageUninstallReturnsEarlyIfEventIsNotInDevMode()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(false);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($this->installer->onPostPackageUninstall($event->reveal()));
    }

    public function testOnPostPackageUninstallReturnsEarlyIfNoRelevantConfigFilesAreFound()
    {
        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->shouldNotBeCalled();

        $this->assertNull($this->installer->onPostPackageUninstall($event->reveal()));
    }

    public function testOnPostPackageUninstallRemovesPackageFromConfiguration()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'Some\Component',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io
            ->write('<info>Removing Some\Component from package some/component</info>')
            ->shouldBeCalled();

        $this->io
            ->write(Argument::that(function ($argument) {
                return (bool) preg_match(
                    '#Removed package from .*?config/application.config.php#',
                    $argument
                );
            }))
            ->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageUninstall($event->reveal()));

        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertNotContains('Some\Component', $config);
    }

    public function testOnPostPackageUninstallCanRemovePackageArraysFromConfiguration()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'Some\Component',\n    'Other\Component',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => [
                'Some\\Component',
                'Other\\Component',
            ],
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io
            ->write('<info>Removing Some\Component from package some/component</info>')
            ->shouldBeCalled();
        $this->io
            ->write('<info>Removing Other\Component from package some/component</info>')
            ->shouldBeCalled();

        $this->io
            ->write(Argument::that(function ($argument) {
                return (bool) preg_match(
                    '#Removed package from .*?config/application.config.php#',
                    $argument
                );
            }))
            ->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageUninstall($event->reveal()));

        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertNotContains('Some\Component', $config);
        $this->assertNotContains('Other\Component', $config);
    }

    public function testModuleIsAppended()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'Some\Component',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn(['zf' => [
            'module' => 'Some\\Module',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }

            if (! strstr($argument[0], "Please select which config file you wish to inject 'Some\Module' into")) {
                return false;
            }

            if (! strstr($argument[1], 'Do not inject')) {
                return false;
            }

            if (! strstr($argument[2], 'application.config.php')) {
                return false;
            }

            return true;
        }), 0)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_array($argument)) {
                return false;
            }
            if (! strstr($argument[0], 'Remember')) {
                return false;
            }

            return true;
        }), 'n')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Module from package some/module');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = include(vfsStream::url('project/config/application.config.php'));
        $modules = $config['modules'];
        $this->assertEquals([
            'Some\Component',
            'Some\Module'
        ], $modules);
    }
}
