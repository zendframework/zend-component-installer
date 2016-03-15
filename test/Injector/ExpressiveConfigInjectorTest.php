<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies Ltd (http://www.zend.com)
 */

namespace ZendTest\ComponentInstaller\Injector;

use Zend\ComponentInstaller\Injector\ExpressiveConfigInjector;

class ExpressiveConfigInjectorTest extends AbstractInjectorTestCase
{
    protected $configFile = 'config/config.php';

    protected $injectorClass = ExpressiveConfigInjector::class;

    protected $injectorTypesAllowed = [
        ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER,
    ];

    public function convertToShortArraySyntax($contents)
    {
        return preg_replace('/array\(([^)]+)\)/s', '[$1]', $contents);
    }

    public function allowedTypes()
    {
        return [
            'config-provider' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, true],
            'component'       => [ExpressiveConfigInjector::TYPE_COMPONENT, false],
            'module'          => [ExpressiveConfigInjector::TYPE_MODULE, false],
        ];
    }

    public function injectComponentProvider()
    {
        // @codingStandardsIgnoreStart
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-application-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-application-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-application-import.config.php');

        $baseContentsFqcnShortArray              = $this->convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = $this->convertToShortArraySyntax($baseContentsImportLongArray);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-import.config.php');

        $expectedContentsFqcnShortArray              = $this->convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = $this->convertToShortArraySyntax($expectedContentsImportLongArray);

        return [
            'fqcn-long-array'    => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'fqcn-short-array'   => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportShortArray,            $expectedContentsImportShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packageAlreadyRegisteredProvider()
    {
        // @codingStandardsIgnoreStart
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-import.config.php');

        $fqcnShortArray              = $this->convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = $this->convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = $this->convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [$fqcnLongArray,               ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
            'global-long-array'  => [$globallyQualifiedLongArray,  ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
            'import-long-array'  => [$importLongArray,             ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
            'fqcn-short-array'   => [$fqcnShortArray,              ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
            'global-short-array' => [$globallyQualifiedShortArray, ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
            'import-short-array' => [$importShortArray,            ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function emptyConfiguration()
    {
        // @codingStandardsIgnoreStart
        $fqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-empty-fqcn.config.php');
        $globallyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-empty-globally-qualified.config.php');
        $importLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-empty-import.config.php');

        $fqcnShortArray              = $this->convertToShortArraySyntax($fqcnLongArray);
        $globallyQualifiedShortArray = $this->convertToShortArraySyntax($globallyQualifiedLongArray);
        $importShortArray            = $this->convertToShortArraySyntax($importLongArray);

        return [
            'fqcn-long-array'    => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $fqcnLongArray],
            'global-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $globallyQualifiedLongArray],
            'import-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $importLongArray],
            'fqcn-short-array'   => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $fqcnShortArray],
            'global-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $globallyQualifiedShortArray],
            'import-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $importShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function packagePopulatedInConfiguration()
    {
        // @codingStandardsIgnoreStart
        $baseContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-fqcn.config.php');
        $baseContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-globally-qualified.config.php');
        $baseContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-populated-import.config.php');

        $baseContentsFqcnShortArray              = $this->convertToShortArraySyntax($baseContentsFqcnLongArray);
        $baseContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($baseContentsGloballyQualifiedLongArray);
        $baseContentsImportShortArray            = $this->convertToShortArraySyntax($baseContentsImportLongArray);

        $expectedContentsFqcnLongArray              = file_get_contents(__DIR__ . '/TestAsset/expressive-application-fqcn.config.php');
        $expectedContentsGloballyQualifiedLongArray = file_get_contents(__DIR__ . '/TestAsset/expressive-application-globally-qualified.config.php');
        $expectedContentsImportLongArray            = file_get_contents(__DIR__ . '/TestAsset/expressive-application-import.config.php');

        $expectedContentsFqcnShortArray              = $this->convertToShortArraySyntax($expectedContentsFqcnLongArray);
        $expectedContentsGloballyQualifiedShortArray = $this->convertToShortArraySyntax($expectedContentsGloballyQualifiedLongArray);
        $expectedContentsImportShortArray            = $this->convertToShortArraySyntax($expectedContentsImportLongArray);

        return [
            'fqcn-long-array'    => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnLongArray,               $expectedContentsFqcnLongArray],
            'global-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedLongArray,  $expectedContentsGloballyQualifiedLongArray],
            'import-long-array'  => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportLongArray,             $expectedContentsImportLongArray],
            'fqcn-short-array'   => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsFqcnShortArray,              $expectedContentsFqcnShortArray],
            'global-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsGloballyQualifiedShortArray, $expectedContentsGloballyQualifiedShortArray],
            'import-short-array' => [ExpressiveConfigInjector::TYPE_CONFIG_PROVIDER, $baseContentsImportShortArray,            $expectedContentsImportShortArray],
        ];
        // @codingStandardsIgnoreEnd
    }
}
