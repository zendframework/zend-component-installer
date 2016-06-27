<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\ConfigDiscovery;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\ComponentInstaller\ConfigDiscovery\DevelopmentConfig;

class DevelopmentConfigTest extends TestCase
{
    private $configDir;

    private $locator;

    public function setUp()
    {
        $this->configDir = vfsStream::setup('project');
        $this->locator = new DevelopmentConfig(
            vfsStream::url('project')
        );
    }

    public function testAbsenceOfFileReturnsFalseOnLocate()
    {
        $this->assertFalse($this->locator->locate());
    }

    public function testLocateReturnsFalseWhenFileDoesNotHaveExpectedContents()
    {
        vfsStream::newFile('config/development.config.php.dist')
            ->at($this->configDir)
            ->setContent('<' . "?php\nreturn [];");
        $this->assertFalse($this->locator->locate());
    }

    public function validDevelopmentConfigContents()
    {
        return [
            'long-array'  => ['<' . "?php\nreturn array(\n    'modules' => array(\n    )\n);"],
            'short-array' => ['<' . "?php\nreturn [\n    'modules' => [\n    ]\n];"],
        ];
    }

    /**
     * @dataProvider validDevelopmentConfigContents
     */
    public function testLocateReturnsTrueWhenFileExistsAndHasExpectedContent($contents)
    {
        vfsStream::newFile('config/development.config.php.dist')
            ->at($this->configDir)
            ->setContent($contents);

        $this->assertTrue($this->locator->locate());
    }
}
