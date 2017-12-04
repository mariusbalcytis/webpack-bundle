<?php

class CustomizedCest
{
    public function _before(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function _after(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function getNoErrorIfAssetsAreDumped(FunctionalTester $I)
    {
        $I->bootKernelWith('customized');
        $I->runCommand('maba:webpack:setup');

        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');

        $this->assertCompilationSuccessful($I);
    }

    public function getNoErrorIfAssetsAreDumpedWithWebpack1(FunctionalTester $I)
    {
        $I->bootKernelWith('customized_v1');
        $I->runCommand('maba:webpack:setup', ['--useWebpackV1' => null]);

        $I->seeFileFound(__DIR__ . '/Fixtures/root_v1/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/root_v1/config.js');

        $this->assertCompilationSuccessful($I);
    }

    protected function assertCompilationSuccessful(FunctionalTester $I)
    {
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->dontSeeInCommandDisplay('error');

        $I->amOnPage('/customized');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $I->seeInSource('<link rel="stylesheet" id="css1" href="/assets/');
        $href = $I->grabAttributeFrom('link#css1', 'href');
        preg_match('#/assets/(.*)#', $href, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->canSeeInThisFile('.green');
        $I->canSeeInThisFile('.red');
        $I->canSeeInThisFile('-ms-fullscreen a.css');
        $I->amGoingTo('Check if less file was compiled');
        $I->canSeeInThisFile('color: #123456');
        $I->canSeeInThisFile('-ms-fullscreen a.less');
        $I->amGoingTo('Check if sass file was compiled');
        $I->canSeeInThisFile('color: #654321');
        $I->canSeeInThisFile('-ms-fullscreen a.scss');
        $I->amGoingTo('Check if cat.png was included');
        $I->canSeeInThisFile('background: url(/assets/');

        $I->seeInSource('<link rel="stylesheet" id="css2" href="/assets/');
        $href = $I->grabAttributeFrom('link#css2', 'href');
        preg_match('#/assets/(.*)#', $href, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->canSeeInThisFile('color: #123456');

        $I->seeInSource('<script src="/assets/');
        $src = $I->grabAttributeFrom('script', 'src');
        preg_match('#/assets/(.*)#', $src, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->canSeeInThisFile('additional-asset-content');
        $I->canSeeInThisFile('additional asset B');
        $I->canSeeInThisFile('app-asset-content');
        $I->dontSeeInThisFile('featureA-content');
        $I->dontSeeInThisFile('featureB-content');

        $I->seeInSource('<img src="/assets/');
        $src = $I->grabAttributeFrom('img', 'src');
        preg_match('#/assets/(.*)#', $src, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->seeFileIsSmallerThan(
            __DIR__ . '/Fixtures/web/assets/' . $matches[1],
            __DIR__ . '/Fixtures/src/WebpackTestBundle/Resources/assets/cat.png'
        );
    }
}
