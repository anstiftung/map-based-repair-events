<?php

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Traits\LoadAllFixturesTrait;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\LoginTrait;
use App\Test\TestCase\Traits\UserAssertionsTrait;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\StringCompareTrait;
use Cake\TestSuite\TestCase;
use Cake\I18n\FrozenDate;

class EventsControllerTest extends TestCase
{
    use LoginTrait;
    use IntegrationTestTrait;
    use UserAssertionsTrait;
    use StringCompareTrait;
    use LogFileAssertionsTrait;
    use LoadAllFixturesTrait;
    
    private $newEventData;
    
    public function loadNewEventData()
    {
        $this->newEventData = [
            'eventbeschreibung' => 'description',
            'datumstart' => new FrozenDate('2020-01-01'),
            'uhrzeitstart' => '09:00',
            'uhrzeitend' => '20:00',
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
                'Events' => $this->newEventData
            ]
        );
        $this->assertResponseContains('Bitte trage die Stadt ein.');
        $this->assertResponseContains('Bitte trage die Straße ein.');
        $this->assertResponseContains('Bitte trage die PLZ ein.');
        $this->assertResponseContains('Die Eingabe muss eine Zahl zwischen -90 und 90 sein.');
        $this->assertResponseContains('Die Eingabe muss eine Zahl zwischen -180 und 180 sein.');
    }
    
    public function testAddEventOk()
    {
        $this->loadNewEventData();
        $this->loginAsOrga();
        $this->newEventData['eventbeschreibung'] = 'description</title></script><img src=n onerror=alert("x")>';
        $this->newEventData['ort'] = 'Berlin';
        $this->newEventData['strasse'] = 'Demo Street 1';
        $this->newEventData['zip'] = '10999';
        $this->newEventData['lat'] = '48,1291558';
        $this->newEventData['lng'] = '11,3626812';
        $this->post(
            Configure::read('AppConfig.htmlHelper')->urlEventNew(2),
            [
                'referer' => '/',
                'Events' => $this->newEventData
            ]
        );
        $this->assertResponseNotContains('error');
        
        $this->Event = TableRegistry::getTableLocator()->get('Events');
        $events = $this->Event->find('all', [
            'contain' => [
                'Categories',
            ]
        ])->toArray();
        $this->assertEquals(2, count($events));
        $this->assertEquals($events[1]->eventbeschreibung, 'description');
        $this->assertEquals($events[1]->strasse, $this->newEventData['strasse']);
        $this->assertEquals($events[1]->categories[0]->id, $this->newEventData['categories']['_ids'][0]);
        $this->assertEquals($events[1]->owner, 1);
    }
    
}
?>