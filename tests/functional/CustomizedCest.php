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
        $I->runCommand('maba_webpack.command.setup');
        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');

        $I->runCommand('maba_webpack.command.compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->dontSeeInCommandDisplay('error');

        $I->amOnPage('/customized');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $I->seeInSource('<link rel="stylesheet" href="/assets/');
        $href = $I->grabAttributeFrom('link', 'href');
        preg_match('#/assets/(.*)#', $href, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->canSeeInThisFile('.green');
        $I->canSeeInThisFile('.red');

        $I->seeInSource('<script src="/assets/');
        $src = $I->grabAttributeFrom('script', 'src');

        preg_match('#/assets/(.*)#', $src, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/assets/' . $matches[1]);
        $I->canSeeInThisFile('additional-asset-content');
        $I->canSeeInThisFile('app-asset-content');
        $I->dontSeeInThisFile('featureA-content');
        $I->dontSeeInThisFile('featureB-content');
    }
}
