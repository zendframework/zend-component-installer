<?php
/**
 * @see       https://github.com/zendframework/zend-component-installer for the canonical source repository
 * @copyright Copyright (c) 2016-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-component-installer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\ComponentInstaller\ConfigDiscovery;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Zend\ComponentInstaller\ConfigDiscovery\ConfigAggregator;

class ConfigAggregatorTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $configDir;

    /** @var ConfigAggregator */
    private $locator;

    protected function setUp() : void
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new ConfigAggregator(
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
            'fqcn-short-array'               => ['<' . "?php\n\$aggregator = new Zend\ConfigAggregator\ConfigAggregator([\n]);"],
            'globally-qualified-short-array' => ['<' . "?php\n\$aggregator = new \Zend\ConfigAggregator\ConfigAggregator([\n]);"],
            'imported-short-array'           => ['<' . "?php\n\$aggregator = new ConfigAggregator([\n]);"],
            'fqcn-long-array'                => ['<' . "?php\n\$aggregator = new Zend\ConfigAggregator\ConfigAggregator(array(\n));"],
            'globally-qualified-long-array'  => ['<' . "?php\n\$aggregator = new \Zend\ConfigAggregator\ConfigAggregator(array(\n));"],
            'imported-long-array'            => ['<' . "?php\n\$aggregator = new ConfigAggregator(array(\n));"],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider validExpressiveConfigContents
     *
     * @param string $contents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/config.php')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
