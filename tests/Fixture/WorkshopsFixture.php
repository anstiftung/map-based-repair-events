<?php
declare(strict_types=1);

namespace App\Test\Fixture;

class WorkshopsFixture extends AppFixture
{

    public array $records = [
        [
            'uid' => 2,
            'name' => 'Test Workshop',
            'url' => 'test-workshop',
            'email' => 'test-workshop@mailinator.com',
            'owner' => 1,
            'text' => 'Test Workshop Text',
            'city' => 'Berlin',
            'zip' => '10115',
            'country_code' => 'DE',
            'street' => 'Torstraße 123',
            'adresszusatz' => 'Stiege 2 &amp; Stiege 3',
            'created' => '2021-01-01 00:00:00',
            'lat' => 52.532,
            'lng' => 13.384,
            'status' => APP_ON,
        ],
        [
            'uid' => 11,
            'name' => 'Test Workshop 2',
            'url' => 'test-workshop-2',
            'email' => 'test-workshop-2@mailinator.com',
            'owner' => 1,
        ]
    ];

}
?>