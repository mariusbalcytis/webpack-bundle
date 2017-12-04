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
        try {
            $I->amOnPage('/');
            $I->canSeeResponseCodeIs(500);
            $I->see('Manifest file not found');
        } catch (\LogicException $logicException) {
            // handle case for earlier symfony where there is no error listener
        }
    }

    public function getNoErrorIfAssetsAreDumped(FunctionalTester $I)
    {
        $I->bootKernelWith();
        $I->runCommand('maba:webpack:setup');
        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');

        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->dontSeeInCommandDisplay('error');

        $I->amOnPage('/');
        $I->canSeeResponseCodeIs(200);
        $I->dontSee('Manifest file not found');

        $I->dontSeeInSource('<link rel="stylesheet"');

        $I->seeInSource('<script src="/compiled/');
        $src = $I->grabAttributeFrom('script', 'src');

        preg_match('#/compiled/(.*)#', $src, $matches);
        $I->seeFileFound(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->openFile(__DIR__ . '/Fixtures/web/compiled/' . $matches[1]);
        $I->canSeeInThisFile('.green');
        $I->canSeeInThisFile('.red');
    }

    public function getErrorFromBundleWithoutErrorSuppressing(FunctionalTester $I)
    {
        $I->bootKernelWith('bundle_error');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(1);
    }

    public function getErrorWithTwigParseError(FunctionalTester $I)
    {
        $I->bootKernelWith('parse_error');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(1);
    }

    public function getErrorWithTwigParseErrorIfIgnoringUnknowns(FunctionalTester $I)
    {
        $I->bootKernelWith('parse_error2');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(1);
    }

    public function getNoErrorWithTwigParseErrorIfSuppressing(FunctionalTester $I)
    {
        $I->bootKernelWith('parse_error_suppress');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(0);
    }

    public function getErrorWithTwigUnknowns(FunctionalTester $I)
    {
        $I->bootKernelWith('unknowns');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(1);
    }

    public function getNoErrorWithTwigUnknownsIfIgnoring(FunctionalTester $I)
    {
        $I->bootKernelWith('unknowns_suppress');

        $I->runCommand('maba:webpack:setup');
        $I->runCommand('maba:webpack:compile');
        $I->seeCommandStatusCode(0);
    }
}
