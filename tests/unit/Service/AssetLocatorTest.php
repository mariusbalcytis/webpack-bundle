<?php

namespace Maba\Bundle\WebpackBundle\Tests\Service;

use Codeception\TestCase\Test;
use Exception;
use Maba\Bundle\WebpackBundle\Exception\AssetNotFoundException;
use Maba\Bundle\WebpackBundle\Service\AliasManager;
use Maba\Bundle\WebpackBundle\Service\AssetLocator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use RuntimeException;

class AssetLocatorTest extends Test
{
    /**
     * @param string|Exception $expected
     * @param string $asset
     * @param string|null $expectedAlias
     * @param string|null|Exception $aliasPath
     * @dataProvider locateAssetProvider
     */
    public function testLocateAsset($expected, $asset, $expectedAlias = null, $aliasPath = null)
    {
        /** @var MockObject|AliasManager $aliasManager */
        $aliasManager = $this->getMockBuilder('Maba\Bundle\WebpackBundle\Service\AliasManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        if ($expectedAlias !== null) {

            $expectation = $aliasManager
                ->expects($this->once())
                ->method('getAliasPath')
                ->with($expectedAlias)
            ;

            if ($aliasPath instanceof Exception) {
                $expectation->willThrowException($aliasPath);
            } else {
                $expectation->willReturn($aliasPath);
            }

        } else {
            $aliasManager
                ->expects($this->never())
                ->method('getAliasPath')
            ;
        }

        $assetLocator = new AssetLocator($aliasManager);

        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
            $assetLocator->locateAsset($asset);
        } else {
            $this->assertSame($expected, $assetLocator->locateAsset($asset));
        }
    }

    public function locateAssetProvider()
    {
        $dir = realpath(__DIR__ . '/../Fixtures');

        return [ // @formatter:off
            'works with full path' => [
                /* expected       */  $dir . '/assetA.txt',
                /* asset          */  $dir . '/assetA.txt',
            ],
            'works with alias' => [
                /* expected       */  $dir . '/assetA.txt',
                /* asset          */  '@aliasName/assetA.txt',
                /* expected alias */  '@aliasName',
                /* alias path     */  $dir,
            ],
            'works with alias and subdirectories' => [
                /* expected       */  $dir . '/subdirectory/assetB.txt',
                /* asset          */  '@aliasName/subdirectory/assetB.txt',
                /* expected alias */  '@aliasName',
                /* alias path     */  $dir,
            ],
            'throws exception if file not found' => [
                /* expected       */  new AssetNotFoundException(),
                /* asset          */  $dir . '/non-existent-file',
            ],
            'throws exception if file not found via alias' => [
                /* expected       */  new AssetNotFoundException(),
                /* asset          */  '@aliasName/subdirectory/does-not-exists',
                /* expected alias */  '@aliasName',
                /* alias path     */  $dir,
            ],
            'throws exception if alias not found' => [
                /* expected       */  new AssetNotFoundException(),
                /* asset          */  '@aliasName/assetA.txt',
                /* expected alias */  '@aliasName',
                /* alias path     */  new RuntimeException(),
            ],
        ];
    }
}
