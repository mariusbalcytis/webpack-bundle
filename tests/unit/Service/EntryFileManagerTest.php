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
        return [
            'parses simple extension' => [
                'css',
                'style.css',
                [],
                ['js'],
                [],
            ],
            'parses extension with loaders, aliases and paths' => [
                'css',
                '-!loader?q=a.js&w=r.less!anotherloader!@alias/path/file-name with spaces.css',
                [],
                ['js'],
                [],
            ],
            'returns null if type disabled' => [
                null,
                'file.js',
                [],
                ['js'],
                [],
            ],
            'enabled overrides disabled' => [
                'js',
                'file.js',
                ['js'],
                ['js'],
                [],
            ],
            'looks at enabled even if no disabled provided' => [
                'js',
                'file.js',
                ['js'],
                [],
                [],
            ],
            'returns null if type not enabled' => [
                null,
                'file.js',
                ['css'],
                [],
                [],
            ],
            'no enabled nor disabled means disabled for all types' => [
                null,
                'file.js',
                [],
                [],
                [],
            ],
            'looks at type map' => [
                'css',
                'file.less',
                [],
                ['js'],
                ['css' => ['less']],
            ],
            'looks at enabled before typemap' => [
                'css',
                'file.less',
                ['less'],
                [],
                ['css' => ['less']],
            ],
            'looks at enabled only before typemap' => [
                null,
                'file.less',
                ['css'],
                [],
                ['css' => ['less']],
            ],
            'looks at first found in typemap' => [
                'css',
                'file.less',
                [],
                ['js'],
                ['js' => ['coffee'], 'css' => ['scss', 'sass', 'less'], 'other' => ['less']],
            ],
        ];
    }
}
