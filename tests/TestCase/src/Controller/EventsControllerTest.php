<?php

namespace App\Test\TestCase\Controller;

use App\Services\GeoService;
use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\LoginTrait;
use App\Test\TestCase\Traits\QueueTrait;
use App\Test\TestCase\Traits\UserAssertionsTrait;
use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\StringCompareTrait;
use Cake\I18n\Date;
use Cake\I18n\Time;

class EventsControllerTest extends AppTestCase
{
    use LoginTrait;
    use IntegrationTestTrait;
    use UserAssertionsTrait;
    use EmailTrait;
    use StringCompareTrait;
    use LogFileAssertionsTrait;
    use QueueTrait;

    private $newEventData;
    private $Event;
    private $User;

    public function loadNewEventData()
    {
        $this->newEventData = [
            'eventbeschreibung' => 'description',
            'datumstart' => '',
            'uhrzeitstart' => '',
            'uhrzeitend' => '',
            'veranstaltungsort' => 'Room 1',
            'strasse' => '',
            'zip' => '',
            'ort' => '',
            'author' => 'John Doe',
            'categories' => [
                '_ids' => [
                    '0' => 87
                ]
            ],
            'use_custom_coordinates' => true,
            'lat' => '',
            'lng' => ''
        ];
    }

    public function testAddEventValidations()
    {
        $this->loadNewEventData();
        $this->loginAsOrga();
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlEventNew(2),
            [
                'referer' => '/',
                $this->newEventData
            ]
        );
        $this->assertResponseContains('Bitte trage die Stadt ein.');
        $this->assertResponseContains('Bitte trage die Straße ein.');
        $this->assertResponseContains('Bitte trage die PLZ ein.');
        $this->assertResponseContains('Bitte trage ein Datum ein.');
        $this->assertResponseContains('Bitte trage eine von-Uhrzeit ein.');
        $this->assertResponseContains('Bitte trage eine bis-Uhrzeit ein.');
        $this->assertResponseContains(GeoService::ERROR_OUT_OF_BOUNDING_BOX);

    }

    public function testAddEventsOk()
    {
        $this->loadNewEventData();
        $this->loginAsOrga();
        $this->newEventData['eventbeschreibung'] = 'description</title></script><img src=n onerror=alert("x")>';
        $this->newEventData['workshop_uid'] = 2;
        $this->newEventData['ort'] = 'Berlin';
        $this->newEventData['strasse'] = 'Demo Street 1';
        $this->newEventData['zip'] = '10999';
        $this->newEventData['lat'] = '48,1291558';
        $this->newEventData['lng'] = '11,3626812';
        $this->newEventData['datumstart'] = '01.01.2020';
        $this->newEventData['uhrzeitstart'] = '10:00';
        $this->newEventData['uhrzeitend'] = '20:00';

        $newEventData2 = [
            'datumstart' => '01.02.2020',
            'uhrzeitstart' => '12:00',
            'uhrzeitend' => '22:00',
        ];

        $data = [
            'referer' => '/',
            $this->newEventData,
            $newEventData2,
        ];
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlEventNew(2),
            $data,
        );
        $this->assertResponseNotContains('error');

        $this->Event = $this->getTableLocator()->get('Events');
        $events = $this->Event->find('all', contain: [
            'Categories',
        ])->toArray();

        $eventIndexA = 2;
        $this->assertEquals(4, count($events));
        $this->assertEquals($events[$eventIndexA]->eventbeschreibung, 'description<img src="n" alt="n" />');
        $this->assertEquals($events[$eventIndexA]->strasse, $this->newEventData['strasse']);
        $this->assertEquals($events[$eventIndexA]->datumstart, new Date($this->newEventData['datumstart']));
        $this->assertEquals($events[$eventIndexA]->uhrzeitstart, new Time($this->newEventData['uhrzeitstart']));
        $this->assertEquals($events[$eventIndexA]->uhrzeitend, new Time($this->newEventData['uhrzeitend']));
        $this->assertEquals($events[$eventIndexA]->categories[0]->id, $this->newEventData['categories']['_ids'][0]);
        $this->assertEquals($events[$eventIndexA]->owner, 1);
        $this->assertEquals($events[$eventIndexA]->workshop_uid, 2);

        $eventIndexB = 3;
        $this->assertEquals($events[$eventIndexB]->datumstart, new Date($newEventData2['datumstart']));
        $this->assertEquals($events[$eventIndexB]->uhrzeitstart, new Time($newEventData2['uhrzeitstart']));
        $this->assertEquals($events[$eventIndexB]->uhrzeitend, new Time($newEventData2['uhrzeitend']));

        $this->assertMailCount(0);

    }

    public function testEditEventWithoutNotifications()
    {
        $data = [
            'renotify' => false,
            'eventbeschreibung' => 'new description',
            'strasse' => 'new street',
            'zip' => '46464',
            'ort' => 'testort',
            'land' => 'de',
            'datumstart' => '02.01.2030',
            'uhrzeitstart' => '10:00',
            'uhrzeitend' => '11:00',
            'use_custom_coordinates' => true,
            'lat' => '48.1291558',
            'lng' => '11.3626812',
            'status' => APP_ON,
        ];
        $this->doTestEditForm($data);
        $this->assertMailCount(0);
    }

    public function testEditEventWithNotifications()
    {
        $data = [
            'renotify' => true,
            'status' => APP_OFF,
            'datumstart' => new Date('02.01.2040'),
            'eventbeschreibung' => 'new description',
            'strasse' => 'new street',
            'zip' => '46464',
            'ort' => 'testort',
            'datumstart' => '02.01.2030',
            'uhrzeitstart' => '10:00',
            'uhrzeitend' => '11:00',
            'use_custom_coordinates' => true,
            'lat' => '48.1291558',
            'lng' => '11.3626812',
            'is_online_event' => true,
        ];

        $this->doTestEditForm($data);
        $this->runAndAssertQueue();

        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, 'worknews-test@mailinator.com');
        $this->assertMailContainsAt(0, '- Der Termin wurde deaktiviert.');
        $this->assertMailContainsAt(0, '- Das Datum des Termins wurde von Sonntag, 01.01.2040 auf <b>Mittwoch, 02.01.2030</b> geändert.');
        $this->assertMailContainsAt(0, '- Neue Uhrzeit: <b>10:00 - 11:00 Uhr</b>');
        $this->assertMailContainsAt(0, '- Neuer Veranstaltungsort: <b>testort, new street</b>');
        $this->assertMailContainsAt(0, '- Der Termin findet jetzt als <b>Online-Termin</b> statt.');
    }

    public function testAjaxGetAllEventsForMap()
    {
        $this->configRequest([
            'headers' => [
                'X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);
        $expectedResult = file_get_contents(TESTS . 'comparisons' . DS . 'events-for-map.json');
        $expectedNextEventDate = Date::now()->addDays(7)->format('Y-m-d');
        $expectedResult = $this->correctExpectedDate($expectedResult, $expectedNextEventDate);
        $this->get('/events/ajaxGetAllEventsForMap');
        $this->assertResponseContains($expectedResult);
        $this->assertResponseOk();
    }

    public function testDeleteEvent()
    {
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlEventDelete(6));
        $this->Event = $this->getTableLocator()->get('Events');
        $event = $this->Event->find('all', conditions: [
            'Events.uid' => 6
        ])->first();
        $this->assertEquals($event->status, APP_DELETED);
        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, 'worknews-test@mailinator.com');
        $this->assertMailContainsAt(0, 'Die von dir abonnierte Initiative <b>Test Workshop</b> hat folgenden Termin gelöscht: <b>Sonntag, 01.01.2040</b>.');
    }

    private function doTestEditForm($data)
    {
        $this->Event = $this->getTableLocator()->get('Events');
        $event = $this->Event->find('all', conditions: [
            'Events.uid' => 6,
        ])->first();

        $this->loginAsOrga();
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlEventEdit($event->uid),
            [
                'referer' => '/',
                $data,
            ]
        );

        $event = $this->Event->find('all',
            conditions: [
                'Events.uid' => 6,
            ]
        )->first();

        $this->assertEquals($event->eventbeschreibung, $data['eventbeschreibung']);
        $this->assertEquals($event->strasse, $data['strasse']);
        $this->assertEquals($event->datumstart, new Date($data['datumstart']));
        $this->assertEquals($event->uhrzeitstart, new Time($data['uhrzeitstart']));
        $this->assertEquals($event->uhrzeitend, new Time($data['uhrzeitend']));

    }

}
?>