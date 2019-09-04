<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer\InstallationManager;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionObject;
use Zend\ComponentInstaller\ComponentInstaller;

use function dirname;
use function file_get_contents;
use function implode;
use function is_array;
use function mkdir;
use function preg_match;
use function sprintf;
use function strpos;
use function strstr;

class ComponentInstallerTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $projectRoot;

    /**
     * @var ComponentInstaller|ObjectProphecy
     */
    private $installer;

    /**
     * @var Composer|ObjectProphecy
     */
    private $composer;

    /**
     * @var RootPackageInterface|ObjectProphecy
     */
    private $rootPackage;

    /**
     * @var IOInterface|ObjectProphecy
     */
    private $io;

    /**
     * @var InstallationManager|ObjectProphecy
     */
    private $installationManager;

    protected function setUp() : void
    {
        $this->projectRoot = vfsStream::setup('project');
        $this->installer = new ComponentInstaller(vfsStream::url('project'));

        $this->composer = $this->prophesize(Composer::class);
        $this->rootPackage = $this->prophesize(RootPackageInterface::class);
        $this->io = $this->prophesize(IOInterface::class);

        $this->composer->getPackage()->willReturn($this->rootPackage->reveal());
        $this->rootPackage->getExtra()->willReturn([]);

        $this->installer->activate(
            $this->composer->reveal(),
            $this->io->reveal()
        );

        $this->installationManager = $this->prophesize(InstallationManager::class);
        $this->composer->getInstallationManager()->willReturn($this->installationManager->reveal());
    }

    public static function assertPrompt($argument, $packageName = null)
    {
        if (! is_string($argument)) {
            return false;
        }

        if (false !== strpos($argument, 'Remember this option for other packages of the same type?')) {
            return true;
        }

        if (! $packageName) {
            return false;
        }

        if (false === strpos(
            $argument,
            sprintf("Please select which config file you wish to inject '%s' into", $packageName)
        )) {
            return false;
        }

        if (false === strpos($argument, 'Do not inject')) {
            return false;
        }

        if (false === strpos($argument, 'application.config.php')) {
            return false;
        }

        return true;
    }

    public function createApplicationConfig($contents = null)
    {
        $contents = $contents ?: '<' . "?php\nreturn [\n    'modules' => [\n    ]\n];";
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    protected function createModuleClass($path, $contents)
    {
        vfsStream::newDirectory(dirname($path))
            ->at($this->projectRoot);

        vfsStream::newFile($path)
            ->at($this->projectRoot)
            ->setContent($contents);
    }

    public function testMissingDependency()
    {
        $installPath = 'install/path';
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'SomeApplication',\n    ]\n];"
        );

        $this->createModuleClass(
            $installPath . '/src/SomeComponent/Module.php',
            <<<CONTENT
<?php
namespace SomeComponent;

class Module {
    public function getModuleDependencies()
    {
        return ['SomeDependency'];
    }
}
CONTENT
        );

        /** @var PackageInterface|ObjectProphecy $package */
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([
            'zf' => [
                'component' => 'SomeComponent',
            ],
        ]);
        $package->getAutoload()->willReturn([
            'psr-0' => [
                'SomeComponent\\' => 'src/',
            ],
        ]);

        $this->installationManager->getInstallPath(Argument::exact($package->reveal()))
            ->willReturn(vfsStream::url('project/' . $installPath));

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            return ComponentInstallerTest::assertPrompt($argument, 'SomeComponent');
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            return ComponentInstallerTest::assertPrompt($argument);
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing SomeComponent from package some/component');
        }))->shouldBeCalled();

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Dependency SomeDependency is not registered in the configuration');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
    }

    public function dependency()
    {
        return [
            // 'description' => [
            //   'package name to install',
            //   [enabled modules],
            //   [dependencies],
            //   [result: enabled modules in order],
            //   autoloading: psr-0, psr-4, classmap or files
            //   autoloadPath: only for classmap
            // ],
            'one-dependency-on-top-psr-0' => [
                'MyPackage1',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage1', 'App'],
                'psr-0',
            ],
            'one-dependency-on-bottom-psr-0' => [
                'MyPackage2',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage2'],
                'psr-0',
            ],
            'no-dependencies-psr-0' => [
                'MyPackage3',
                ['App'],
                [],
                ['MyPackage3', 'App'],
                'psr-0',
            ],
            'two-dependencies-psr-0' => [
                'MyPackage4',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage4', 'App'],
                'psr-0',
            ],
            'two-dependencies-in-reverse-order-psr-0' => [
                'MyPackage5',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage5', 'App'],
                'psr-0',
            ],
            'two-dependencies-with-more-packages-psr-0' => [
                'MyPackage6',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage6', 'App2'],
                'psr-0',
            ],
            // PSR-4 autoloading
            'one-dependency-on-top-psr-4' => [
                'MyPackage11',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage11', 'App'],
                'psr-4',
            ],
            'one-dependency-on-bottom-psr-4' => [
                'MyPackage12',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage12'],
                'psr-4',
            ],
            'no-dependencies-psr-4' => [
                'MyPackage13',
                ['App'],
                [],
                ['MyPackage13', 'App'],
                'psr-4',
            ],
            'two-dependencies-psr-4' => [
                'MyPackage14',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage14', 'App'],
                'psr-4',
            ],
            'two-dependencies-in-reverse-order-psr-4' => [
                'MyPackage15',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage15', 'App'],
                'psr-4',
            ],
            'two-dependencies-with-more-packages-psr-4' => [
                'MyPackage16',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage16', 'App2'],
                'psr-4',
            ],
            // classmap autoloading - dir
            'one-dependency-on-top-classmap' => [
                'MyPackage21',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage21', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'one-dependency-on-bottom-classmap' => [
                'MyPackage22',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage22'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'no-dependencies-classmap' => [
                'MyPackage23',
                ['App'],
                [],
                ['MyPackage23', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-classmap' => [
                'MyPackage24',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage24', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-in-reverse-order-classmap' => [
                'MyPackage25',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage25', 'App'],
                'classmap',
                'path-classmap/to/module/',
            ],
            'two-dependencies-with-more-packages-classmap' => [
                'MyPackage26',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage26', 'App2'],
                'classmap',
                'path-classmap/to/module/',
            ],
            // classmap autoloading - file
            'one-dependency-on-top-classmap-file' => [
                'MyPackage31',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage31', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'one-dependency-on-bottom-classmap-file' => [
                'MyPackage32',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage32'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'no-dependencies-classmap-file' => [
                'MyPackage33',
                ['App'],
                [],
                ['MyPackage33', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-classmap-file' => [
                'MyPackage34',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage34', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-in-reverse-order-classmap-file' => [
                'MyPackage35',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage35', 'App'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            'two-dependencies-with-more-packages-classmap-file' => [
                'MyPackage36',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage36', 'App2'],
                'classmap',
                'path-classmap/to/module/Module.php',
            ],
            // files autoloading
            'one-dependency-on-top-files' => [
                'MyPackage41',
                ['D1', 'App'],
                ['D1'],
                ['D1', 'MyPackage41', 'App'],
                'files',
            ],
            'one-dependency-on-bottom-files' => [
                'MyPackage42',
                ['App', 'D1'],
                ['D1'],
                ['App', 'D1', 'MyPackage42'],
                'files',
            ],
            'no-dependencies-files' => [
                'MyPackage43',
                ['App'],
                [],
                ['MyPackage43', 'App'],
                'files',
            ],
            'two-dependencies-files' => [
                'MyPackage44',
                ['D1', 'D2', 'App'],
                ['D1', 'D2'],
                ['D1', 'D2', 'MyPackage44', 'App'],
                'files',
            ],
            'two-dependencies-in-reverse-order-files' => [
                'MyPackage45',
                ['D2', 'D1', 'App'],
                ['D1', 'D2'],
                ['D2', 'D1', 'MyPackage45', 'App'],
                'files',
            ],
            'two-dependencies-with-more-packages-files' => [
                'MyPackage46',
                ['D1', 'App1', 'D2', 'App2'],
                ['D1', 'D2'],
                ['D1', 'App1', 'D2', 'MyPackage46', 'App2'],
                'files',
            ],
        ];
    }

    /**
     * @dataProvider dependency
     *
     * @param string $packageName
     * @param array $enabledModules
     * @param array $dependencies
     * @param array $result
     * @param string $autoloading classmap|files|psr-0|psr-4
     * @param null|string $autoloadPath
     */
    public function testInjectModuleWithDependencies(
        $packageName,
        array $enabledModules,
        array $dependencies,
        array $result,
        $autoloading,
        $autoloadPath = null
    ) {
        $installPath = 'install/path';
        $modules = "\n        '" . implode("',\n        '", $enabledModules) . "',";
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [" . $modules . "\n    ],\n];"
        );

        switch ($autoloading) {
            case 'classmap':
                $pathToModule = 'path-classmap/to/module';
                $autoload = [
                    $autoloadPath,
                ];
                break;
            case 'files':
                $pathToModule = 'path/to/module';
                $autoload = [
                    'path/to/module/Module.php',
                ];
                break;
            case 'psr-0':
                $pathToModule = sprintf('src/%s', $packageName);
                $autoload = [
                    $packageName . '\\' => 'src/',
                ];
                break;
            case 'psr-4':
                $pathToModule = 'src';
                $autoload = [
                    $packageName . '\\' => 'src/',
                ];
                break;
        }

        $dependenciesStr = $dependencies ? "'" . implode("', '", $dependencies) . "'" : '';
        $this->createModuleClass(
            sprintf('%s/%s/Module.php', $installPath, $pathToModule),
            <<<CONTENT
<?php
namespace $packageName;

class Module {
    public function getModuleDependencies()
    {
        return [$dependenciesStr];
    }
}
CONTENT
        );

        /** @var PackageInterface|ObjectProphecy $package */
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([
            'zf' => [
                'component' => $packageName,
            ],
        ]);
        $package->getAutoload()->willReturn([
            $autoloading => $autoload,
        ]);

        $this->installationManager->getInstallPath(Argument::exact($package->reveal()))
            ->willReturn(vfsStream::url('project/' . $installPath));

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) use ($packageName) {
            return ComponentInstallerTest::assertPrompt($argument, $packageName);
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            return ComponentInstallerTest::assertPrompt($argument);
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) use ($packageName) {
            return strstr($argument, sprintf('Installing %s from package some/component', $packageName));
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));

        $config = include vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        $this->assertEquals($result, $modules);
    }

    public function modules()
    {
        return [
            // 'description' => [
            //   [available application modules],
            //   [enabled modules in order],
            //   [result: expected enabled modules in order],
            // ],
            'two-application-modules' => [
                ['App1', 'App2'],
                ['App1', 'App2'],
                ['SomeModule', 'App1', 'App2'],
            ],
            'with-some-component' => [
                ['App1'],
                ['SomeComponent', 'App1'],
                ['SomeComponent', 'SomeModule', 'App1'],
            ],
            'two-application-modules-with-some-component' => [
                ['App1', 'App2'],
                ['SomeComponent', 'App1', 'App2'],
                ['SomeComponent', 'SomeModule', 'App1', 'App2'],
            ],
            'two-application-modules-with-some-component-another-order' => [
                ['App1', 'App2'],
                ['SomeComponent', 'App2', 'App1'],
                ['SomeComponent', 'SomeModule', 'App2', 'App1'],
            ],
            'component-between-application-modules' => [
                ['App1', 'App2'],
                ['App1', 'SomeComponent', 'App2'],
                ['SomeModule', 'App1', 'SomeComponent', 'App2'],
            ],
            'no-application-modules' => [
                [],
                ['SomeComponent'],
                ['SomeComponent', 'SomeModule'],
            ],
        ];
    }

    /**
     * @dataProvider modules
     *
     * @param array $availableModules
     * @param array $enabledModules
     * @param array $result
     */
    public function testModuleBeforeApplicationModules(array $availableModules, array $enabledModules, array $result)
    {
        $modulePath = vfsStream::newDirectory('module')->at($this->projectRoot);
        foreach ($availableModules as $module) {
            vfsStream::newDirectory($module)->at($modulePath);
        }

        $modules = "\n        '" . implode("',\n        '", $enabledModules) . "',";
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [" . $modules . "\n    ],\n];"
        );

        /** @var PackageInterface|ObjectProphecy $package */
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/module');
        $package->getExtra()->willReturn([
            'zf' => [
                'module' => 'SomeModule',
            ],
        ]);
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            return ComponentInstallerTest::assertPrompt($argument, 'SomeModule');
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            return ComponentInstallerTest::assertPrompt($argument);
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing SomeModule from package some/module');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));

        $config = include vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        $this->assertEquals($result, $modules);
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::any())->shouldNotBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallDoesNotPromptForWhitelistedPackages()
    {
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->rootPackage->getExtra()->willReturn(['zf' => [
            'component-whitelist' => ['some/component'],
        ]]);

        $this->io->write(Argument::that(function ($argument) {
            return strpos($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);
    }

    public function testOnPostPackageInstallPromptsForConfigOptions()
    {
        $this->createApplicationConfig();

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
        ]]);
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Other\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $io = $this->io;
        $askValidator = function ($argument) {
            if (! is_string($argument)) {
                    return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        };
        $io
            ->ask(Argument::that($askValidator), 'y')
            ->will(function () use ($io, $askValidator) {
                $io
                    ->ask(Argument::that($askValidator), 'y')
                    ->willReturn('y');
                return 'n';
            });

        $io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Other\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);
        $this->assertStringContainsString("'Other\Component'", $config);
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('other/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Other\\Component',
        ]]);
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Other\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Other\Component from package other/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Other\Component'", $config);
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('y')->shouldBeCalledTimes(1);

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/component');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = file_get_contents(vfsStream::url('project/config/application.config.php'));
        $this->assertStringContainsString("'Some\Component'", $config);

        // Now do a second pass, with another package
        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('other/component');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Other\\Component',
        ]]);
        $package->getAutoload()->willReturn([]);

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
        $this->assertStringContainsString("'Other\Component'", $config);
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io
            ->write('<info>    Removing Some\Component from package some/component</info>')
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io
            ->write('<info>    Removing Some\Component from package some/component</info>')
            ->shouldBeCalled();
        $this->io
            ->write('<info>    Removing Other\Component from package some/component</info>')
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
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Module' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('y');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Module from package some/module');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = include vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        $this->assertEquals([
            'Some\Component',
            'Some\Module',
        ], $modules);
    }

    public function testAppendModuleAndPrependComponent()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'SomeApplication',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getAutoload()->willReturn([]);
        $package->getName()->willReturn('some/package');
        $package->getExtra()->willReturn(['zf' => [
            'module' => 'Some\\Module',
            'component' => 'Some\\Component',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Module' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Module from package some/package');
        }))->shouldBeCalled();

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/package');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = include vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        $this->assertEquals([
            'Some\Component',
            'SomeApplication',
            'Some\Module',
        ], $modules);
    }

    public function testPrependComponentAndAppendModule()
    {
        $this->createApplicationConfig(
            '<' . "?php\nreturn [\n    'modules' => [\n        'SomeApplication',\n    ]\n];"
        );

        $package = $this->prophesize(PackageInterface::class);
        $package->getAutoload()->willReturn([]);
        $package->getName()->willReturn('some/package');
        $package->getExtra()->willReturn(['zf' => [
            'component' => 'Some\\Component',
            'module' => 'Some\\Module',
        ]]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Module' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }

            if (false === strpos(
                $argument,
                "Please select which config file you wish to inject 'Some\Component' into"
            )) {
                return false;
            }

            if (false === strpos($argument, 'Do not inject')) {
                return false;
            }

            if (false === strpos($argument, 'application.config.php')) {
                return false;
            }

            return true;
        }), 1)->willReturn(1);

        $this->io->ask(Argument::that(function ($argument) {
            if (! is_string($argument)) {
                return false;
            }
            if (false === strpos($argument, 'Remember')) {
                return false;
            }

            return true;
        }), 'y')->willReturn('n');

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Module from package some/package');
        }))->shouldBeCalled();

        $this->io->write(Argument::that(function ($argument) {
            return strstr($argument, 'Installing Some\Component from package some/package');
        }))->shouldBeCalled();

        $this->assertNull($this->installer->onPostPackageInstall($event->reveal()));
        $config = include vfsStream::url('project/config/application.config.php');
        $modules = $config['modules'];
        $this->assertEquals([
            'Some\Component',
            'SomeApplication',
            'Some\Module',
        ], $modules);
    }

    public function moduleClass()
    {
        return [
            [__DIR__ . '/TestAsset/ModuleBadlyFormatted.php', ['BadlyFormatted\Application' => ['Dependency1']]],
            [__DIR__ . '/TestAsset/ModuleWithDependencies.php', ['MyNamespace' => ['Dependency']]],
            [__DIR__ . '/TestAsset/ModuleWithInterface.php', ['LongArray\Application' => ['Foo\D1', 'Bar\D2']]],
            [__DIR__ . '/TestAsset/ModuleWithoutDependencies.php', []],
            [__DIR__ . '/TestAsset/ModuleWithEmptyArrayDependencies.php', []],
        ];
    }

    /**
     * @dataProvider moduleClass
     *
     * @param string $file
     * @param array $result
     */
    public function testGetModuleDependenciesFromModuleClass($file, $result)
    {
        $r = new ReflectionObject($this->installer);
        $rm = $r->getMethod('getModuleDependencies');
        $rm->setAccessible(true);

        $dependencies = $rm->invoke($this->installer, $file);

        $this->assertEquals($result, $dependencies);
    }

    public function testGetModuleClassesDependenciesHandlesAutoloadersWithMultiplePathsMappedToSameNamespace()
    {
        $installPath = 'install/path';
        $this->setUpModuleDependencies($installPath);

        $autoloaders = [
            'psr-0' => [
                'DoesNotExist\\' => [
                    'src/Psr0/',
                    'src/Psr0Too/',
                ],
            ],
            'psr-4' => [
                'DoesNotExistEither\\' => [
                    'src/Psr4/',
                    'src/Psr4Too/',
                ],
            ],
            'classmap' => [
                'src/Classmapped/',
                'src/ClassmappedToo/',
            ],
            'files' => [
                'src/File/Module.php',
                'src/FileToo/Module.php',
            ],
        ];

        $package = $this->prophesize(PackageInterface::class);
        $package->getAutoload()->willReturn($autoloaders);

        $this->installationManager
            ->getInstallPath(Argument::that([$package, 'reveal']))
            ->willReturn(vfsStream::url('project/' . $installPath));

        $r = new ReflectionObject($this->installer);
        $rm = $r->getMethod('loadModuleClassesDependencies');
        $rm->setAccessible(true);

        $dependencies = $rm->invoke($this->installer, $package->reveal());
        $this->assertEquals([
            'DoesNotExist'       => ['DoesNotExistDependency'],
            'DoesNotExistEither' => ['DoesNotExistEitherDependency'],
            'ClassmappedToo'     => ['ClassmappedTooDependency'],
            'File'               => ['FileDependency'],
        ], $dependencies);
    }

    public function setUpModuleDependencies($path)
    {
        $this->createModuleClass(
            $path . '/src/Psr0Too/DoesNotExist/Module.php',
            <<<CONTENT
<?php
namespace DoesNotExist;

class Module
{
    public function getModuleDependencies()
    {
        return ['DoesNotExistDependency'];
    }
}
CONTENT
        );

        $this->createModuleClass(
            $path . '/src/Psr4/Module.php',
            <<<CONTENT
<?php
namespace DoesNotExistEither;

class Module
{
    public function getModuleDependencies()
    {
        return ['DoesNotExistEitherDependency'];
    }
}
CONTENT
        );

        mkdir(sprintf('%s/%s/src/ClassmappedToo', vfsStream::url('project'), $path));
        $this->createModuleClass(
            $path . '/src/ClassmappedToo/Module.php',
            <<<CONTENT
<?php
namespace ClassmappedToo;

class Module
{
    public function getModuleDependencies()
    {
        return ['ClassmappedTooDependency'];
    }
}
CONTENT
        );

        $this->createModuleClass(
            $path . '/src/File/Module.php',
            <<<CONTENT
<?php
namespace File;

class Module
{
    public function getModuleDependencies()
    {
        return ['FileDependency'];
    }
}
CONTENT
        );
    }

    /**
     * @dataProvider injectorConfigProvider
     *
     * @param string $configContents
     * @param array $configNames
     * @param string $expectedName
     */
    public function testUninstallMessageWithDifferentInjectors($configContents, array $configNames, $expectedName)
    {
        foreach ($configNames as $configName) {
            $this->createConfigFile($configName, $configContents);
        }

        $package = $this->prophesize(PackageInterface::class);
        $package->getName()->willReturn('some/component');
        $package->getExtra()->willReturn([
            'zf' => [
                'component' => [
                    'Some\\Component',
                ],
            ]
        ]);
        $package->getAutoload()->willReturn([]);

        $operation = $this->prophesize(InstallOperation::class);
        $operation->getPackage()->willReturn($package->reveal());

        $event = $this->prophesize(PackageEvent::class);
        $event->isDevMode()->willReturn(true);
        $event->getOperation()->willReturn($operation->reveal());

        $this->io
            ->write('<info>    Removing Some\Component from package some/component</info>')
            ->shouldBeCalled();

        // assertion
        $this->io
            ->write(Argument::that(function ($argument) use ($expectedName) {
                return (bool)preg_match(
                    sprintf('#Removed package from %s#', $expectedName),
                    $argument
                );
            }))
            ->shouldBeCalled();

        $this->installer->onPostPackageUninstall($event->reveal());
    }

    /**
     * @return array
     */
    public function injectorConfigProvider()
    {
        $config = <<<'CONFIG'
<?php
return [
    'modules' => [
        'Some\Component'
    ]
];
CONFIG;

        return [
            'application.config.php' => [
                $config,
                ['application.config.php'],
                '.*?config/application.config.php'
            ],
            'development.config.php' => [
                $config,
                ['development.config.php.dist', 'development.config.php'],
                '.*?config/development.config.php.dist.*?config/development.config.php'
            ],
        ];
    }

    /**
     * Create config on demand
     *
     * @param string $name
     * @param string $contents
     */
    private function createConfigFile($name, $contents)
    {
        vfsStream::newFile('config/' . $name)
            ->at($this->projectRoot)
            ->setContent($contents);
    }
}
