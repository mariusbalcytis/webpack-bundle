<?php

namespace Maba\Bundle\WebpackBundle\Tests\Service;

use Codeception\TestCase\Test;
use Maba\Bundle\WebpackBundle\Service\EntryFileManager;

class EntryFileManagerTest extends Test
{
    /**
     * @param string $expected
     * @param string $asset
     * @param array $enabledExtensions
     * @param array $disabledExtensions
     * @param array $typeMap
     * @dataProvider dataProvider
     */
    public function testGetEntryFileType($expected, $asset, $enabledExtensions, $disabledExtensions, $typeMap)
    {
        $entryFileManager = new EntryFileManager($enabledExtensions, $disabledExtensions, $typeMap);

        $this->assertSame($expected, $entryFileManager->getEntryFileType($asset));
    }

    public function dataProvider()
    {
        return [ // @formatter:off
            'parses simple extension' => [
                /* expected            */  'css',
                /* asset               */  'style.css',
                /* enabled extensions  */  [],
                /* disabled extensions */  ['js'],
                /* type map            */  [],
            ],
            'parses extension with loaders, aliases and paths' => [
                /* expected            */  'css',
                /* asset               */  '-!loader?q=a.js&w=r.less!anotherloader!@alias/path/file-name with spaces.css',
                /* enabled extensions  */  [],
                /* disabled extensions */  ['js'],
                /* type map            */  [],
            ],
            'returns null if type disabled' => [
                /* expected            */  null,
                /* asset               */  'file.js',
                /* enabled extensions  */  [],
                /* disabled extensions */  ['js'],
                /* type map            */  [],
            ],
            'enabled overrides disabled' => [
                /* expected            */  'js',
                /* asset               */  'file.js',
                /* enabled extensions  */  ['js'],
                /* disabled extensions */  ['js'],
                /* type map            */  [],
            ],
            'looks at enabled even if no disabled provided' => [
                /* expected            */  'js',
                /* asset               */  'file.js',
                /* enabled extensions  */  ['js'],
                /* disabled extensions */  [],
                /* type map            */  [],
            ],
            'returns null if type not enabled' => [
                /* expected            */  null,
                /* asset               */  'file.js',
                /* enabled extensions  */  ['css'],
                /* disabled extensions */  [],
                /* type map            */  [],
            ],
            'no enabled nor disabled means disabled for all types' => [
                /* expected            */  null,
                /* asset               */  'file.js',
                /* enabled extensions  */  [],
                /* disabled extensions */  [],
                /* type map            */  [],
            ],
            'looks at type map' => [
                /* expected            */  'css',
                /* asset               */  'file.less',
                /* enabled extensions  */  [],
                /* disabled extensions */  ['js'],
                /* type map            */  ['css' => ['less']],
            ],
            'looks at enabled before typemap' => [
                /* expected            */  'css',
                /* asset               */  'file.less',
                /* enabled extensions  */  ['less'],
                /* disabled extensions */  [],
                /* type map            */  ['css' => ['less']],
            ],
            'looks at enabled only before typemap' => [
                /* expected            */  null,
                /* asset               */  'file.less',
                /* enabled extensions  */  ['css'],
                /* disabled extensions */  [],
                /* type map            */  ['css' => ['less']],
            ],
            'looks at first found in typemap' => [
                /* expected            */  'css',
                /* asset               */  'file.less',
                /* enabled extensions  */  [],
                /* disabled extensions */  ['js'],
                /* type map            */
                [
                    'js' => ['coffee'],
                    'css' => [
                        'scss',
                        'sass',
                        'less',
                    ],
                    'other' => ['less'],
                ],
            ],
        ];
    }
}
