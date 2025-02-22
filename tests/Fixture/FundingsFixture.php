<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\I18n\DateTime;
use Cake\I18n\Date;

class FundingsFixture extends AppFixture
{

    public function init(): void
    {
        $this->records = [
            [
                'uid' => 10,
                'workshop_uid' => 11,
                'owner' => 1,
                'submit_date' => new DateTime('2024-01-23 09:10:00'),
                'money_transfer_date' => new Date('2024-01-24'),
                'fundingsupporter_id' => 1,
            ],
        ];
        parent::init();
    }

}
?>