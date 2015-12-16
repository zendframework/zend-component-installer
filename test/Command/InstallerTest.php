<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 */

namespace ZendTest\ComponentInstaller;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use ReflectionClass;
use Zend\ComponentInstaller\Command\Installer;
use Zend\ComponentInstaller\Command\DirStub;
use Zend\ComponentInstaller\Command\FileInfoStub;
use Zend\ComponentInstaller\ComponentInstaller;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;

class InstallerTest extends TestCase
{
    public $classFilePath;

    public function setUp()
    {
        FileInfoStub::clear();
        DirStub::clear();

        $this->route = $this->prophesize(Route::class);
        $this->route->getMatchedParam('path', Argument::type('string'))->willReturn(__DIR__);

        $this->console = $this->prophesize(Console::class);
    }

    public function getComponentInstallerClassFilePath()
    {
        if ($this->classFilePath) {
            return $this->classFilePath;
        }
        $r = new ReflectionClass(ComponentInstaller::class);
        $this->classFilePath = $r->getFileName();
        return $this->classFilePath;
    }

    public function assertComposerContains($key, $value, array $composer, $message = null)
    {
        $message = $message ?: sprintf(
            'Composer did not contain key "%s" or did not match value "%s"',
            $key,
            var_export($value, true)
        );
        $keys = explode('.', $key);
        do {
            $this->assertInternalType('array', $composer);
            $key = array_shift($keys);
            $this->assertArrayHasKey($key, $composer);
            $composer = $composer[$key];
        } while (count($keys));
        $this->assertEquals($value, $composer);
    }

    public function testInstallerErrorsIfUnableToCreateInstallDirectory()
    {
        DirStub::defineDir(__DIR__ . '/component-installer', false);

        $this->console->writeLine(
            Argument::containingString('Unable to create component-installer directory'),
            Color::RED
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(1, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertGreaterThan(0, DirStub::getInvocationCount('create'));
        $this->assertEquals(0, FileInfoStub::getInvocationCount('copy'));
    }

    public function testInstallerErrorsIfUnableToCopyClassIntoTree()
    {
        DirStub::defineDir(__DIR__ . '/component-installer');
        FileInfoStub::defineCopyFailure(
            $this->getComponentInstallerClassFilePath(),
            __DIR__ . '/component-installer/ComponentInstaller.php'
        );

        $this->console->writeLine(
            Argument::containingString('Unable to copy'),
            Color::RED
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(1, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertEquals(0, DirStub::getInvocationCount('create'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('copy'));
    }

    public function testInstallerErrorsIfUnableToOpenComposerFile()
    {
        $this->console->writeLine(
            Argument::containingString('Unable to read/parse'),
            Color::RED
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(1, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertEquals(1, DirStub::getInvocationCount('create'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('copy'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertEquals(0, FileInfoStub::getInvocationCount('get'));
    }

    public function testInstallerErrorsIfComposerContentsAreEmpty()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{}');

        $this->console->writeLine(
            Argument::containingString('Unable to read/parse'),
            Color::RED
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(1, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertEquals(1, DirStub::getInvocationCount('create'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('copy'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertEquals(1, FileInfoStub::getInvocationCount('get'));
    }

    public function testInstallerErrorsIfUnableToWriteComposerFile()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');
        FileInfoStub::defineWriteError(__DIR__ . '/composer.json');

        $this->console->writeLine(
            Argument::containingString('Unable to write updated'),
            Color::RED
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(1, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertEquals(1, DirStub::getInvocationCount('create'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('copy'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertEquals(1, FileInfoStub::getInvocationCount('get'));
        $this->assertEquals(1, FileInfoStub::getInvocationCount('put'));
    }

    public function testInstallerReturnsZeroForSucessfulOperation()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertGreaterThan(0, DirStub::getInvocationCount('exists'));
        $this->assertEquals(1, DirStub::getInvocationCount('create'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('copy'));
        $this->assertGreaterThan(0, FileInfoStub::getInvocationCount('exists'));
        $this->assertEquals(1, FileInfoStub::getInvocationCount('get'));
        $this->assertEquals(1, FileInfoStub::getInvocationCount('put'));
    }

    public function testInstallerCreatesDirectoryWhenNotPresent()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $this->assertEquals(1, DirStub::getInvocationCount('create'));
    }

    public function testInstallerCreatesPsr4EntryForHookClassInComposer()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'autoload.psr-4.Zend\\ComponentInstaller\\',
            'component-installer/',
            $composer
        );
    }

    public function testInstallerInjectsPostPackageInstallScriptInComposer()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-install',
            ['Zend\ComponentInstaller\ComponentInstaller::postPackageInstall'],
            $composer
        );
    }

    public function testInstallerInjectsPostPackageUninstallScriptInComposer()
    {
        FileInfoStub::defineFile(__DIR__ . '/composer.json', '{"name": "some/project"}');

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-uninstall',
            ['Zend\ComponentInstaller\ComponentInstaller::postPackageUninstall'],
            $composer
        );
    }

    public function unknownScriptValueProvider()
    {
        return [
            'string' => ['"Foo"'],
            'array' => ['["Foo"]'],
        ];
    }

    /**
     * @dataProvider unknownScriptValueProvider
     */
    public function testInstallerAddsPostPackageInstallScriptToExistingSection($initialValue)
    {
        FileInfoStub::defineFile(
            __DIR__ . '/composer.json',
            '{"name": "some/project","scripts":{"post-package-install":' . $initialValue . '}}'
        );

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-install',
            ['Foo', 'Zend\ComponentInstaller\ComponentInstaller::postPackageInstall'],
            $composer
        );
    }

    /**
     * @dataProvider unknownScriptValueProvider
     */
    public function testInstallerAddsPostPackageUninstallScriptToExistingSection($initialValue)
    {
        FileInfoStub::defineFile(
            __DIR__ . '/composer.json',
            '{"name": "some/project","scripts":{"post-package-uninstall":' . $initialValue . '}}'
        );

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-uninstall',
            ['Foo', 'Zend\ComponentInstaller\ComponentInstaller::postPackageUninstall'],
            $composer
        );
    }

    public function installScriptValueProvider()
    {
        return [
            'string' => ['Zend\\ComponentInstaller\\ComponentInstaller::postPackageInstall'],
            'array' => [['Zend\\ComponentInstaller\\ComponentInstaller::postPackageInstall']],
        ];
    }

    /**
     * @dataProvider installScriptValueProvider
     */
    public function testInstallerDoesNotAddPostPackageInstallScriptIfAlreadyPresent($initialValue)
    {
        $json = ['name' => 'some/project', 'scripts' => ['post-package-install' => $initialValue]];
        FileInfoStub::defineFile(
            __DIR__ . '/composer.json',
            json_encode($json)
        );

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-install',
            ['Zend\ComponentInstaller\ComponentInstaller::postPackageInstall'],
            $composer
        );
    }

    public function uninstallScriptValueProvider()
    {
        return [
            'string' => ['Zend\\ComponentInstaller\\ComponentInstaller::postPackageUninstall'],
            'array' => [['Zend\\ComponentInstaller\\ComponentInstaller::postPackageUninstall']],
        ];
    }

    /**
     * @dataProvider uninstallScriptValueProvider
     */
    public function testInstallerDoesNotAddPostPackageUninstallScriptIfAlreadyPresent($initialValue)
    {
        $json = ['name' => 'some/project', 'scripts' => ['post-package-uninstall' => $initialValue]];
        FileInfoStub::defineFile(
            __DIR__ . '/composer.json',
            json_encode($json)
        );

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'scripts.post-package-uninstall',
            ['Zend\ComponentInstaller\ComponentInstaller::postPackageUninstall'],
            $composer
        );
    }

    public function testInstallerDoesNotAddAutoloaderEntryIfAlreadyPresent()
    {
        $json = json_encode([
            'autoload' => ['psr-4' => [
                'Zend\\ComponentInstaller\\' => 'foo-bar/',
            ]],
        ]);
        FileInfoStub::defineFile(
            __DIR__ . '/composer.json',
            $json
        );

        $this->console->writeLine(
            Argument::containingString('ComponentInstaller installed'),
            Color::GREEN
        )->shouldBeCalled();

        $installer = new Installer();
        $this->assertEquals(0, $installer($this->route->reveal(), $this->console->reveal()));

        $composerJson = FileInfoStub::get(__DIR__ . '/composer.json');
        $composer = json_decode($composerJson, true);
        $this->assertComposerContains(
            'autoload.psr-4.Zend\\ComponentInstaller\\',
            'foo-bar/',
            $composer
        );
    }
}
