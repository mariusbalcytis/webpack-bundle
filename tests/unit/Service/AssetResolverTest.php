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
        return [ // @formatter:off
            [
                /* expected            */  '/full/path.js',
                /* asset               */  '/full/path.js',
                /* expected asset path */  '/full/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  false,
            ],
            [
                /* expected            */  'extract-file-loader?q=%2Ffull%2Fpath.js!',
                /* asset               */  '/full/path.js',
                /* expected asset path */  '/full/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  true,
            ],
            [
                /* expected            */  'loader!/full/path.js',
                /* asset               */  'loader!/full/path.js',
                /* expected asset path */  '/full/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  false,
            ],
            [
                /* expected            */  'extract-file-loader?q=loader%21%2Ffull%2Fpath.js!',
                /* asset               */  'loader!/full/path.js',
                /* expected asset path */  '/full/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  true,
            ],
            [
                /* expected            */  'extract-file-loader?q=-%21loader%21%2Ffull%2Fpath.js!',
                /* asset               */  '-!loader!/full/path.js',
                /* expected asset path */  '/full/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  true,
            ],
            [
                /* expected            */  '/full/path.js',
                /* asset               */  '@alias/path.js',
                /* expected asset path */  '@alias/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  false,
            ],
            [
                /* expected            */  'extract-file-loader?q=%2Ffull%2Fpath.js!',
                /* asset               */  '@alias/path.js',
                /* expected asset path */  '@alias/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  true,
            ],
            [
                /* expected            */  'extract-file-loader?q=loader%21%2Ffull%2Fpath.js!',
                /* asset               */  'loader!@alias/path.js',
                /* expected asset path */  '@alias/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  true,
            ],
            [
                /* expected            */  'loader!/full/path.js',
                /* asset               */  'loader!@alias/path.js',
                /* expected asset path */  '@alias/path.js',
                /* located path        */  '/full/path.js',
                /* entry file          */  false,
            ],
        ];
    }
}
