<?php

namespace Maba\Bundle\WebpackBundle\Tests\Service;

use Codeception\TestCase\Test;
use Exception;
use Maba\Bundle\WebpackBundle\Service\AssetLocator;
use Maba\Bundle\WebpackBundle\Service\AssetResolver;
use Maba\Bundle\WebpackBundle\Service\EntryFileManager;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AssetResolverTest extends Test
{
    /**
     * @param string|Exception $expected
     * @param string $asset
     * @param $expectedAssetPath
     * @param $locatedPath
     * @param $entryFile
     * @dataProvider resolveAssetProvider
     */
    public function testResolveAsset($expected, $asset, $expectedAssetPath, $locatedPath, $entryFile)
    {
        /** @var MockObject|AssetLocator $assetLocator */
        $assetLocator = $this->getMockBuilder('Maba\Bundle\WebpackBundle\Service\AssetLocator')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $assetLocator
            ->expects($this->once())
            ->method('locateAsset')
            ->with($expectedAssetPath)
            ->willReturn($locatedPath)
        ;
        /** @var MockObject|EntryFileManager $entryFileManager */
        $entryFileManager = $this->getMockBuilder('Maba\Bundle\WebpackBundle\Service\EntryFileManager')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $entryFileManager
            ->expects($this->once())
            ->method('isEntryFile')
            ->with($locatedPath)
            ->willReturn($entryFile)
        ;

        $assetResolver = new AssetResolver($assetLocator, $entryFileManager);

        $this->assertSame($expected, $assetResolver->resolveAsset($asset));
    }

    public function resolveAssetProvider()
    {
        return [
            ['/full/path.js', '/full/path.js', '/full/path.js', '/full/path.js', false],
            ['/full/path with space.js', '/full/path with space.js', '/full/path with space.js', '/full/path with space.js', false],
            ['extract-file-loader?q=%2Ffull%2Fpath.js!', '/full/path.js', '/full/path.js', '/full/path.js', true],
            ['extract-file-loader?q=%2Ffull%2Fpath%20with%20space.js!', '/full/path with space.js', '/full/path with space.js', '/full/path with space.js', true],
            ['loader!/full/path.js', 'loader!/full/path.js', '/full/path.js', '/full/path.js', false],
            [
                'extract-file-loader?q=loader%21%2Ffull%2Fpath.js!',
                'loader!/full/path.js',
                '/full/path.js',
                '/full/path.js',
                true,
            ],
            [
                'extract-file-loader?q=-%21loader%21%2Ffull%2Fpath.js!',
                '-!loader!/full/path.js',
                '/full/path.js',
                '/full/path.js',
                true,
            ],
            ['/full/path.js', '@alias/path.js', '@alias/path.js', '/full/path.js', false],
            ['extract-file-loader?q=%2Ffull%2Fpath.js!', '@alias/path.js', '@alias/path.js', '/full/path.js', true],
            ['extract-file-loader?q=%2Ffull%2Fpath%20with%20space.js!', '@alias/path with space.js', '@alias/path with space.js', '/full/path with space.js', true],
            ['extract-file-loader?q=%2Falias%20path%20with%2Fspace.js!', '@alias/space.js', '@alias/space.js', '/alias path with/space.js', true],
            [
                'extract-file-loader?q=loader%21%2Ffull%2Fpath.js!',
                'loader!@alias/path.js',
                '@alias/path.js',
                '/full/path.js',
                true,
            ],
            ['loader!/full/path.js', 'loader!@alias/path.js', '@alias/path.js', '/full/path.js', false],
        ];
    }
}
