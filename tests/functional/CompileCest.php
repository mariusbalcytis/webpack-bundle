<?php

class CompileCest
{
    public function _before(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function _after(FunctionalTester $I)
    {
        $I->cleanUp();
    }

    public function getInternalErrorIfAssetsNotDumped(FunctionalTester $I)
    {
        $I->bootKernelWith();
        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(500);
        $I->see('Manifest file not found');
    }

    public function getNoErrorIfAssetsAreDumped(FunctionalTester $I)
    {
        $I->bootKernelWith();
        $I->runCommand('maba_webpack.command.setup');
        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');

        $I->runCommand('maba_webpack.command.compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->dontSeeInCommandDisplay('error');

        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $I->dontSeeInSource('<link rel="stylesheet"');

        $I->seeInSource('<script src="http://localhost:8080/compiled/');
        $src = $I->grabAttributeFrom('script', 'src');

        preg_match('#http://localhost:8080/compiled/(.*)#', $src, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('.green');
        $I->canSeeInThisFile('.red');
    }
}
