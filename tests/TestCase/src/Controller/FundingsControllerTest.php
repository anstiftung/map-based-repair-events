<?php

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\LoginTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use App\Test\Mock\GeoServiceMock;
use Cake\Controller\Controller;

class FundingsControllerTest extends AppTestCase
{

    use IntegrationTestTrait;
    use LogFileAssertionsTrait;
    use LoginTrait;

	public function controllerSpy(EventInterface $event, ?Controller $controller = null): void
    {
		parent::controllerSpy($event, $controller);
		$this->_controller->geoService = new GeoServiceMock();
	}

    public function setUp(): void {
        parent::setUp();
        $this->resetLogs();
        Configure::write('AppConfig.fundingsEnabled', true);
    }

    public function testRoutesLoggedOut() {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertResponseCode(302);
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
    }

    public function testRoutesAsRepairhelper() {
        $this->loginAsRepairhelper();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertResponseCode(302);
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
    }

    public function testEditWorkshopFundingNotAllowed() {

        $workshopsTable = $this->getTableLocator()->get('Workshops');
        $workshop = $workshopsTable->get(2);
        $workshop->country_code = 'AT';
        $workshopsTable->save($workshop);
        $eventsTable = $this->getTableLocator()->get('Events');
        $event = $eventsTable->get(6);
        $event->datumstart = '2020-01-01';
        $eventsTable->save($event);

        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
        $this->assertRedirectContains(Configure::read('AppConfig.htmlHelper')->urlFundings());
    }

    public function testEditNotInOrgaTeam() {
        $userWorkshopsTable = $this->getTableLocator()->get('UsersWorkshops');
        $userWorkshop = $userWorkshopsTable->find()->where(['workshop_uid' => 2])->first();
        $userWorkshopsTable->delete($userWorkshop);
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');
    }

    public function testEditAlreadyCreatedByOtherOwner() {
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $workshopUid = 2;
        $fundingsTable->save($fundingsTable->newEntity([
            'workshop_uid' => $workshopUid,
            'owner' => 3,
        ]));

        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit($workshopUid));
        $this->assertResponseCode(302);
        $this->assertFlashMessage('Der Förderantrag wurde bereits von einem anderen Nutzer (Max Muster) erstellt.');
        $this->assertRedirectContains(Configure::read('AppConfig.htmlHelper')->urlFundings());
    }

    public function testEditAsOrgaOk() {

        $testWorkshopUid = 2;

        $this->loginAsOrga();

        // add 4 events for 2025 (required for funding)
        $eventsTable = $this->getTableLocator()->get('Events');
        $i = 0;
        while($i<4) {
            $event = $eventsTable->newEntity([
                'workshop_uid' => $testWorkshopUid,
                'datumstart' => '2025-01-01',
                'status' => APP_ON,
                'created' => '2020-01-01 00:00:00',
            ]);
            $eventsTable->save($event);
            $i++;
        }

        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit($testWorkshopUid));
        $this->assertResponseOk();

        $newName = 'Testname';
        $newStreet = 'Teststraße 1';
        $newZip = '12345';
        $newCity = 'Teststadt';
        $newAdresszusatz = 'Adresszusatz';

        $newFundingsupporterName = 'Fundingsupporter Name';

        $newOwnerFirstname = 'Owner Firstname';
        $newOwnerLastname = 'Owner Lastname';
        $newOwnerEmail = 'test@test.at';

        $verifiedFields = [
            'fundings-workshop-name',
        ];

        $this->post(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit($testWorkshopUid), [
            'referer' => '/',
            'Fundings' => [
                'workshop' => [
                    'name' => $newName,
                    'street' => $newStreet . '<script>alert("XSS");</script>',
                    'zip' => $newZip,
                    'city' => $newCity,
                    'adresszusatz' => $newAdresszusatz,
                    'website' => 'non valid string',
                    'use_custom_coordinates' => 0,
                ],
                'fundingsupporter' => [
                    'name' => $newFundingsupporterName,
                    'website' => 'orf.at',
                ],
                'owner_user' => [
                    'firstname' => $newOwnerFirstname,
                    'lastname' => $newOwnerLastname,
                    'email' => $newOwnerEmail,
                    'use_custom_coordinates' => 0,
                ],
                'verified_fields' => array_merge($verifiedFields, ['fundings-workshop-website']),
            ]
        ]);
        $this->assertResponseContains('Der Förderantrag wurde erfolgreich zwischengespeichert.');

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->find(contain: ['Workshops', 'OwnerUsers', 'Fundingsupporters'])->first();
        $funding->owner_user->revertPrivatizeData();

        $this->assertEquals($verifiedFields, $funding->verified_fields); // must not contain invalid workshops-website

        $this->assertEquals($newName, $funding->workshop->name);
        $this->assertEquals($newStreet, $funding->workshop->street);
        $this->assertEquals('', $funding->workshop->website);
        $this->assertEquals($newZip, $funding->workshop->zip);
        $this->assertEquals($newCity, $funding->workshop->city);
        $this->assertEquals($newAdresszusatz, $funding->workshop->adresszusatz);

        $this->assertEquals($newFundingsupporterName, $funding->fundingsupporter->name);
        $this->assertEquals('https://orf.at', $funding->fundingsupporter->website);

        $this->assertEquals($newOwnerFirstname, $funding->owner_user->firstname);
        $this->assertEquals($newOwnerLastname, $funding->owner_user->lastname);
        $this->assertEquals($newOwnerEmail, $funding->owner_user->email);

    }

}
?>