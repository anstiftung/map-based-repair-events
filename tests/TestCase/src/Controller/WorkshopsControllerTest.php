<?php

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\LoginTrait;
use App\Test\TestCase\Traits\UserAssertionsTrait;
use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\StringCompareTrait;
use Cake\TestSuite\TestEmailTransport;
use App\Services\GeoService;

class WorkshopsControllerTest extends AppTestCase
{
    use LoginTrait;
    use IntegrationTestTrait;
    use UserAssertionsTrait;
    use StringCompareTrait;
    use EmailTrait;
    use LogFileAssertionsTrait;

    private $Workshop;
    private $User;

    public function testAjaxGetAllWorkshopsForMap()
    {
        $this->configRequest([
            'headers' => [
                'X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);
        $this->_compareBasePath = ROOT . DS . 'tests' . DS . 'comparisons' . DS;
        $this->get('/workshops/ajaxGetAllWorkshopsForMap');
        $this->assertSameAsFile('workshops-for-map.json', $this->_response->getBody()->__toString());
    }

    public function testWorkshopDetail()
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshopDetail('test-workshop'));
        $this->assertResponseOk();
        $this->assertResponseNotEmpty();
        $this->doUserPrivacyAssertions();
    }

    public function testWorkshopSearchWithExceptionKeyword()
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshops('aachen'));
        $this->assertResponseOk();
        $this->assertResponseNotEmpty();
        $this->assertResponseContains('<div class="numbers">0 Initiativen gefunden</div>');
    }

    public function testWorkshopSearchWithNonExceptionKeyword()
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshops('Test'));
        $this->assertResponseOk();
        $this->assertResponseNotEmpty();
        $this->assertResponseContains('<div class="numbers">1 Initiative gefunden</div>');
    }

    public function testApplyToWorkshopAsRepairhelper()
    {

        $this->executeLogFileAssertions = false;

        $workshopUid = 2;
        $userUid = 3;
        $this->loginAsRepairhelper();

        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlUserWorkshopApplicationUser(),
            [
                'referer' => '/',
                'users_workshops' => [
                    'workshop_uid' => $workshopUid,
                    'user_uid' => $userUid,
                ]
            ]
        );

        $this->Workshop = $this->getTableLocator()->get('Workshops');
        $workshop = $this->Workshop->find('all',
        conditions: [
            'Workshops.uid' => $workshopUid,
        ],
        contain: [
            'Users',
        ])->first();

        $this->assertEquals(2, count($workshop->users));
        $this->assertEquals($userUid, $workshop->users[1]->uid);
        $this->assertEquals(null, $workshop->users[1]->_joinData->approved);

        $this->assertMailCount(1);
        $this->assertMailContainsAt(0, 'Max Muster (maxmuster@mailinator.com) möchte bei ');
        $this->assertMailSentToAt(0, 'johndoe@mailinator.com');

        $this->get(Configure::read('AppConfig.htmlHelper')->urlWorkshopEdit($workshopUid));
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login?redirect=%2Finitiativen%2Fbearbeiten%2F2');

    }

    public function testAddWorkshop()
    {

        $workshopForPost = [
            'name' => 'test initiative',
            'url' => 'test-initiative',
            'use_custom_coordinates' => true,
            'lat' => 52.520008,
            'lng' => 13.404954,
];

        $this->loginAsOrga();
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlWorkshopNew(),
            [
                'referer' => '/',
                'Workshops' => $workshopForPost
            ]
        );

        $this->Workshop = $this->getTableLocator()->get('Workshops');
        $workshop = $this->Workshop->find('all', conditions: [
            'Workshops.url' => $workshopForPost['url']
        ])->first();

        $this->assertEquals($workshop->name, $workshopForPost['name']);
        $this->assertEquals($workshop->url, $workshopForPost['url']);

        $this->assertMailCount(1);
        $this->assertMailSentTo(Configure::read('AppConfig.debugMailAddress'));
        $this->assertMailContainsTextAt(0, '"test initiative" erstellt');

    }

    public function testAddWorkshopWithWrongGeoData()
    {

        $workshopForPost = [
            'name' => 'test initiative',
            'url' => 'test-initiative',
            'use_custom_coordinates' => true,
            'lat' => 13.404954, // wrong - data swapped: lat = lng
            'lng' => 52.520008, // wrong - data swapped: lng = lat
        ];

        $this->loginAsOrga();
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlWorkshopNew(),
            [
                'referer' => '/',
                'Workshops' => $workshopForPost
            ]
        );

        $this->assertResponseContains(GeoService::ERROR_OUT_OF_BOUNDING_BOX);
        $this->assertMailCount(0);

    }


    public function testEditWorkshopAsOrga()
    {
        $this->loginAsOrga();
        $workshopUid = 2;
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlWorkshopEdit($workshopUid),
            [
                'referer' => '/',
                'Workshops' => [
                    'name' => 'Test Workshop',
                    'url' => 'test-workshop',
                    'use_custom_coordinates' => true,
                    'text' => '<iframe></iframe>workshop info',
                    'lat' => 52.520008,
                    'lng' => 13.404954,
                ]
            ]
        );

        $this->assertMailCount(1);
        $this->assertMailSentTo(Configure::read('AppConfig.debugMailAddress'));
        $this->assertMailContainsTextAt(0, '"Test Workshop" geändert');

        $this->Workshop = $this->getTableLocator()->get('Workshops');
        $workshop = $this->Workshop->find('all', conditions: [
            'Workshops.uid' => $workshopUid,
        ])->first();
        $this->assertEquals('workshop info', $workshop->text);

    }

    public function testAjaxGetWorkshopsAndUsersForTags()
    {
        $this->configRequest([
            'headers' => [
                'X_REQUESTED_WITH' => 'XMLHttpRequest'
            ],
        ]);
        $expectedResult = file_get_contents(TESTS . 'comparisons' . DS . 'data-for-vow-tags-widget.json');
        $expectedResult = $this->correctServerName($expectedResult);
        $this->get('/workshops/ajaxGetWorkshopsAndUsersForTags?tags[]=3dreparieren');
        $this->assertResponseContains($expectedResult);
    }


    public function testRestWorkshopsBerlin()
    {
        $expectedResult = file_get_contents(TESTS . 'comparisons' . DS . 'rest-workshops-berlin.json');
        $expectedResult = $this->correctServerName($expectedResult);
        $this->get('/api/v1/workshops?city=berlin');
        $this->assertResponseContains($expectedResult);
        $this->assertResponseOk();
    }

    public function testRestWorkshopsHamburg()
    {
        $this->get('/api/v1/workshops?city=hamburg');
        $this->assertResponseContains('no workshops found');
        $this->assertResponseCode(404);
    }

    public function testRestWorkshopsWrongParam()
    {
        $this->get('/api/v1/workshops?city=ha');
        $this->assertResponseContains('city not passed or invalid (min 3 chars)');
        $this->assertResponseCode(400);
    }

}
?>