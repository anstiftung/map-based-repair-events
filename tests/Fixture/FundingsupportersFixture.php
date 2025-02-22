<?php
declare(strict_types=1);

namespace App\Test\Fixture;

class FundingsupportersFixture extends AppFixture
{

    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'contact_email' => 'fundingsupporter_test@mailinator.com',
            ],
        ];
        parent::init();
    }
}
?>