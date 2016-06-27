<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_ExpectationFailedException as ExpectationFailedException;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ComponentInstaller\Collection;
use Zend\ComponentInstaller\ConfigDiscovery;
use Zend\ComponentInstaller\ConfigOption;
use Zend\ComponentInstaller\Injector;
use Zend\ComponentInstaller\Injector\InjectorInterface;
use Zend\ComponentInstaller\Injector\NoopInjector;

class ConfigDiscoveryTest extends TestCase
{
    private $projectRoot;

    private $discovery;

    public function setUp()
    {
        $this->projectRoot = vfsStream::setup('project');
        $this->discovery = new ConfigDiscovery();

        $this->allTypes = new Collection([
            InjectorInterface::TYPE_CONFIG_PROVIDER,
            InjectorInterface::TYPE_COMPONENT,
            InjectorInterface::TYPE_MODULE,
        ]);

        $this->injectorTypes = [
            'Zend\ComponentInstaller\Injector\ApplicationConfigInjector',
            'Zend\ComponentInstaller\Injector\DevelopmentConfigInjector',
            'Zend\ComponentInstaller\Injector\ExpressiveConfigInjector',
            'Zend\ComponentInstaller\Injector\ModulesConfigInjector',
        ];
    }

    public function createApplicationConfig()
    {
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createDevelopmentConfig()
    {
        vfsStream::newFile('config/development.config.php.dist')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createExpressiveConfig()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\n\$configManager = new ConfigManager([\n]);");
    }

    public function createModulesConfig()
    {
        vfsStream::newFile('config/modules.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n]);");
    }

    public function assertOptionsContainsNoopInjector(Collection $options)
    {
        if ($options->isEmpty()) {
            throw new ExpectationFailedException('Options array is empty; no NoopInjector found!');
        }

        $options = $options->toArray();
        $injector = array_shift($options)->getInjector();

        if (! $injector instanceof NoopInjector) {
            throw new ExpectationFailedException('Options array does not contain a NoopInjector!');
        }
    }

    public function assertOptionsContainsInjector($injectorType, Collection $options)
    {
        foreach ($options as $option) {
            if (! $option instanceof ConfigOption) {
                throw new ExpectationFailedException(sprintf(
                    'Invalid option returned: %s',
                    (is_object($option) ? get_class($option) : gettype($option))
                ));
            }

            if ($injectorType === get_class($option->getInjector())) {
                return;
            }
        }

        throw new ExpectationFailedException(sprintf(
            'Injector of type %s was not found in the options',
            $injectorType
        ));
    }

    public function testGetAvailableConfigOptionsReturnsEmptyArrayWhenNoConfigFilesPresent()
    {
        $result = $this->discovery->getAvailableConfigOptions($this->allTypes);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testGetAvailableConfigOptionsReturnsOptionsForEachSupportedPackageType()
    {
        $this->createApplicationConfig();
        $this->createDevelopmentConfig();
        $this->createExpressiveConfig();
        $this->createModulesConfig();

        $options = $this->discovery->getAvailableConfigOptions($this->allTypes, vfsStream::url('project'));
        $this->assertCount(5, $options);

        $this->assertOptionsContainsNoopInjector($options);
        foreach ($this->injectorTypes as $injector) {
            $this->assertOptionsContainsInjector($injector, $options);
        }
    }

    public function configFileSubset()
    {
        return [
            [
                'seedMethod' => 'createApplicationConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\ApplicationConfigInjector::class,
            ],
            [
                'seedMethod' => 'createApplicationConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ApplicationConfigInjector::class,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\DevelopmentConfigInjector::class,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\DevelopmentConfigInjector::class,
            ],
            [
                'seedMethod' => 'createExpressiveConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ExpressiveConfigInjector::class,
            ],
            [
                'seedMethod' => 'createExpressiveConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ExpressiveConfigInjector::class,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\ModulesConfigInjector::class,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ModulesConfigInjector::class,
            ],
        ];
    }

    /**
     * @dataProvider configFileSubset
     */
    public function testGetAvailableConfigOptionsCanReturnsSubsetOfOptionsBaseOnPackageType(
        $seedMethod,
        $type,
        $expected
    ) {
        $this->{$seedMethod}();
        $options = $this->discovery->getAvailableConfigOptions(new Collection([$type]), vfsStream::url('project'));
        $this->assertCount(2, $options);

        $this->assertOptionsContainsNoopInjector($options);
        $this->assertOptionsContainsInjector($expected, $options);
    }

    public function testNoOptionReturnedIfInjectorCannotRegisterType()
    {
        $this->createApplicationConfig();
        $options = $this->discovery->getAvailableConfigOptions(
            new Collection([InjectorInterface::TYPE_CONFIG_PROVIDER]),
            vfsStream::url('project')
        );

        $this->assertInstanceOf(Collection::class, $options);
        $this->assertTrue($options->isEmpty());
    }
}
