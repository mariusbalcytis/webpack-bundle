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
        return array(
            'parses simple extension' => array(
                'css',
                'style.css',
                array(),
                array('js'),
                array(),
            ),
            'parses extension with loaders, aliases and paths' => array(
                'css',
                '-!loader?q=a.js&w=r.less!anotherloader!@alias/path/file-name with spaces.css',
                array(),
                array('js'),
                array(),
            ),
            'returns null if type disabled' => array(
                null,
                'file.js',
                array(),
                array('js'),
                array(),
            ),
            'enabled overrides disabled' => array(
                'js',
                'file.js',
                array('js'),
                array('js'),
                array(),
            ),
            'looks at enabled even if no disabled provided' => array(
                'js',
                'file.js',
                array('js'),
                array(),
                array(),
            ),
            'returns null if type not enabled' => array(
                null,
                'file.js',
                array('css'),
                array(),
                array(),
            ),
            'no enabled nor disabled means disabled for all types' => array(
                null,
                'file.js',
                array(),
                array(),
                array(),
            ),
            'looks at type map' => array(
                'css',
                'file.less',
                array(),
                array('js'),
                array('css' => array('less')),
            ),
            'looks at enabled before typemap' => array(
                'css',
                'file.less',
                array('less'),
                array(),
                array('css' => array('less')),
            ),
            'looks at enabled only before typemap' => array(
                null,
                'file.less',
                array('css'),
                array(),
                array('css' => array('less')),
            ),
            'looks at first found in typemap' => array(
                'css',
                'file.less',
                array(),
                array('js'),
                array('js' => array('coffee'), 'css' => array('scss', 'sass', 'less'), 'other' => array('less')),
            ),
        );
    }
}
