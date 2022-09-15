<?php
namespace massuserimport\acceptance;


use massuserimport\AcceptanceTester;

class ImportUsersCest
{
    
    public function testImportUserWithSpace(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->amOnRoute(['/admin/user']);
        $I->expectTo('see the massimport tab');
        $I->see('Import users');
        $I->click('Import users');
        $I->waitForElement('#csv-csvfile');
        $I->attachFile('#csv-csvfile', 'users.csv');
        $I->click('Submit');
        
        $I->waitForText('test.user');
        
        $I->expectTo('See my new imported user');
        $I->see('test.user');
        $I->see('test@me.com');
        $I->see('ImportFirst');
        $I->see('ImportLast');
        
        $I->expectTo('See my new imported user in the user overview');
        $I->amOnRoute(['/admin/user']);
        $I->see('test.user');
        $I->see('test@me.com');
        $I->see('ImportFirst');
        $I->see('ImportLast');
    }
    
}