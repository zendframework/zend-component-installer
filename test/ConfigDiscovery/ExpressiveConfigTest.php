<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\ConfigDiscovery;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ComponentInstaller\ConfigDiscovery\ExpressiveConfig;

class ExpressiveConfigTest extends TestCase
{
    private $configDir;

    private $locator;

    public function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new ExpressiveConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate()
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents()
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    public function validExpressiveConfigContents()
    {
        // @codingStandardsIgnoreStart
        return [
            'fqcn-short-array'               => ['<' . "?php\n\$configManager = new Zend\Expressive\ConfigManager\ConfigManager([\n]);"],
            'globally-qualified-short-array' => ['<' . "?php\n\$configManager = new \Zend\Expressive\ConfigManager\ConfigManager([\n]);"],
            'imported-short-array'           => ['<' . "?php\n\$configManager = new ConfigManager([\n]);"],
            'fqcn-long-array'                => ['<' . "?php\n\$configManager = new Zend\Expressive\ConfigManager\ConfigManager(array(\n));"],
            'globally-qualified-long-array'  => ['<' . "?php\n\$configManager = new \Zend\Expressive\ConfigManager\ConfigManager(array(\n));"],
            'imported-long-array'            => ['<' . "?php\n\$configManager = new ConfigManager(array(\n));"],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider validExpressiveConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
