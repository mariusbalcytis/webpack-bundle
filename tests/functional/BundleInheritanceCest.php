<?php

class BundleInheritanceCest
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
        $I->bootKernelWith('inheritance');
        $I->runCommand('maba_webpack.command.setup');
        $I->seeFileFound(__DIR__ . '/Fixtures/package.json');
        $I->seeFileFound(__DIR__ . '/Fixtures/app/config/webpack.config.js');

        $I->runCommand('maba_webpack.command.compile');
        $I->seeCommandStatusCode(0);
        $I->seeInCommandDisplay('webpack');
        $I->seeInCommandDisplay('parent.js');
        $I->seeInCommandDisplay('child.js');
        $I->dontSeeInCommandDisplay('error');
    }
}
