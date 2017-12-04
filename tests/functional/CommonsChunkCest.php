<?php

class CommonsChunkCest
{
    public function _before(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function _after(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function getFewDifferentChunksFromGroups(FunctionalTester $I)
    {
        $configContents = <<<'CONTENTS'
var defaultConfigProvider = require('./default.js');
var webpack = require('webpack');
module.exports = function (options) {
    var config = defaultConfigProvider(options);
    config.plugins.push(new webpack.optimize.CommonsChunkPlugin({
        name: 'front_commons_chunk',
        chunks: options.groups['default'],
        minChunks: 2
    }));
    config.plugins.push(new webpack.optimize.CommonsChunkPlugin({
        name: 'admin_commons_chunk',
        chunks: options.groups['admin']
    }));
    return config;
};
CONTENTS;

        $I->bootKernelWith('commons_chunk');
        $I->runCommand('maba:webpack:setup');
        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');
        $I->moveFile(__DIR__ . '/Fixtures/app/config/webpack.config.js', __DIR__ . '/Fixtures/app/config/default.js');
        $I->writeToFile(__DIR__ . '/Fixtures/app/config/webpack.config.js', $configContents);

        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->dontSeeInCommandDisplay('error');

        $this->assertFront($I);
        $this->assertAdmin($I);
    }

    private function assertFront(FunctionalTester $I)
    {
        $I->amOnPage('/commons-chunk/front-1');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $url = $I->grabAttributeFrom('link[rel=stylesheet]#commons-chunk-css', 'href');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('vendor-1');
        $I->canSeeInThisFile('vendor-2');

        $url = $I->grabAttributeFrom('link[rel=stylesheet]#main-css', 'href');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->dontSeeInThisFile('vendor-1');
        $I->dontSeeInThisFile('vendor-2');
        $I->canSeeInThisFile('main-1');

        $url = $I->grabAttributeFrom('script#commons-chunk-js', 'src');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('vendor-1');
        $I->canSeeInThisFile('vendor-2');

        $url = $I->grabAttributeFrom('script#main-js', 'src');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->dontSeeInThisFile('vendor-1');
        $I->dontSeeInThisFile('vendor-2');
        $I->canSeeInThisFile('main-1');
    }

    private function assertAdmin(FunctionalTester $I)
    {
        $I->amOnPage('/commons-chunk/admin-1');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $url = $I->grabAttributeFrom('link[rel=stylesheet]#commons-chunk-css', 'href');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('vendor-1');

        $url = $I->grabAttributeFrom('link[rel=stylesheet]#main-css', 'href');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->dontSeeInThisFile('vendor-1');
        $I->canSeeInThisFile('main-1');

        $url = $I->grabAttributeFrom('script#commons-chunk-js', 'src');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('vendor-1');

        $url = $I->grabAttributeFrom('script#main-js', 'src');
        preg_match('#/compiled/(.*)#', $url, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->dontSeeInThisFile('vendor-1');
        $I->canSeeInThisFile('main-1');
    }
}
