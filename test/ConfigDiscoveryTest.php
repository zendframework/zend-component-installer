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
            Injector\ApplicationConfigInjector::class,
            Injector\ConfigInjectorChain::class,
            Injector\ExpressiveConfigInjector::class,
            Injector\ModulesConfigInjector::class,
        ];
    }

    public function createApplicationConfig()
    {
        vfsStream::newFile('config/application.config.php')
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createDevelopmentConfig($dist = true)
    {
        $configFileName = 'config/development.config.php' . ($dist ? '.dist' : '');
        vfsStream::newFile($configFileName)
            ->at($this->projectRoot)
            ->setContent('<' . "?php\nreturn [\n    'modules' => [\n    ]\n];");
    }

    public function createDevelopmentWorkConfig()
    {
        $this->createDevelopmentConfig(false);
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
                return $option->getInjector();
            }
        }

        throw new ExpectationFailedException(sprintf(
            'Injector of type %s was not found in the options',
            $injectorType
        ));
    }

    public function assertOptionsContainsInjectorInChain($injectorType, Collection $options)
    {
        $chain = $this->assertOptionsContainsInjector(Injector\ConfigInjectorChain::class, $options);

        foreach ($chain->getCollection() as $injector) {
            if (! $injector instanceof InjectorInterface) {
                throw new ExpectationFailedException(sprintf(
                    'Invalid Injector returned: %s',
                    (is_object($injector) ? get_class($injector) : gettype($injector))
                ));
            }

            if ($injectorType === get_class($injector)) {
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
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createApplicationConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ApplicationConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\DevelopmentConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\DevelopmentConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentWorkConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\DevelopmentWorkConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createDevelopmentWorkConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\DevelopmentWorkConfigInjector::class,
                'chain'      => true,
            ],
            [
                'seedMethod' => 'createExpressiveConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ExpressiveConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createExpressiveConfig',
                'type'       => InjectorInterface::TYPE_CONFIG_PROVIDER,
                'expected'   => Injector\ExpressiveConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_COMPONENT,
                'expected'   => Injector\ModulesConfigInjector::class,
                'chain'      => false,
            ],
            [
                'seedMethod' => 'createModulesConfig',
                'type'       => InjectorInterface::TYPE_MODULE,
                'expected'   => Injector\ModulesConfigInjector::class,
                'chain'      => false,
            ],
        ];
    }

    /**
     * @dataProvider configFileSubset
     */
    public function testGetAvailableConfigOptionsCanReturnsSubsetOfOptionsBaseOnPackageType(
        $seedMethod,
        $type,
        $expected,
        $chain
    ) {
        $this->{$seedMethod}();
        $options = $this->discovery->getAvailableConfigOptions(new Collection([$type]), vfsStream::url('project'));
        $this->assertCount(2, $options);

        $this->assertOptionsContainsNoopInjector($options);
        if ($chain) {
            $this->assertOptionsContainsInjectorInChain($expected, $options);
        } else {
            $this->assertOptionsContainsInjector($expected, $options);
        }
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
