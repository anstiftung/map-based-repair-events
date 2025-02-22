<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\Funding;
use App\Model\Entity\Fundingbudgetplan;
use App\Test\TestCase\AppTestCase;
use App\Test\TestCase\Traits\LogFileAssertionsTrait;
use App\Test\TestCase\Traits\LoginTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\Core\Configure;
use App\Model\Entity\Fundingupload;
use App\Test\TestCase\Traits\QueueTrait;
use Cake\TestSuite\EmailTrait;
use App\Services\PdfWriter\VerwendungsnachweisPdfWriterService;
use App\Services\FolderService;
use Cake\TestSuite\TestEmailTransport;
use Laminas\Diactoros\UploadedFile;

class FundingsControllerVerwendungsnachweisTest extends AppTestCase
{

    use EmailTrait;
    use IntegrationTestTrait;
    use LogFileAssertionsTrait;
    use LoginTrait;
    use QueueTrait;


    public function testVerwendungsnachweisLoggedOut(): void
    {
        $testFundingUid = 10;
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($testFundingUid));
        $this->assertResponseCode(302);
    }

    public function testVerwendungsnachweisAsRepairhelper(): void
    {
        $testFundingUid = 10;
        $this->loginAsRepairhelper();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($testFundingUid));
        $this->assertResponseCode(302);
    }

    public function testVerwendungsnachweisAsWrongOrga(): void
    {
        $testFundingUid = 10;
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->get($testFundingUid);
        $funding->owner = 3;
        $fundingsTable->save($funding);
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($testFundingUid));
        $this->assertResponseCode(302);
    }

    public function testVerwendungsnachweisFundingNotYetCompleted(): void
    {
        $testFundingUid = 10;
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->get($testFundingUid);
        $funding->money_transfer_date = null;
        $fundingsTable->save($funding);
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($testFundingUid));
        $this->assertFlashMessage('Der Förderantrag wurde noch nicht eingereicht oder das Geld wurde noch nicht überwiesen.');
    }

    public function testVerwendungsnachweisProcessOk(): void
    {
        $fundingUid = 10;
        $route = Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($fundingUid);
        $this->loginAsOrga();

        $this->get($route);
        $this->assertResponseOk();

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->findWithUsageproofAssociations($fundingUid);
        $this->assertNotEmpty($funding->fundingusageproof);
        $this->assertEquals(Funding::STATUS_DATA_MISSING, $funding->usageproof_status);
        $this->assertCount(1, $funding->fundingreceiptlists);
        $this->assertEquals(Funding::STATUS_DESCRIPTIONS_MISSING, $funding->usageproof_descriptions_status);
        $this->assertEquals(Funding::STATUS_RECEIPTLIST_DATA_MISSING, $funding->receiptlist_status);

        $testFundingusageproofIncomplete = [
            'main_description' => 'Test Main Description',
            'difference_declaration' => '',
            'checkbox_a' => 1,
        ];

        // 1) POST incomplete data
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofIncomplete,
            ],
        ]);

        $funding = $fundingsTable->findWithUsageproofAssociations($fundingUid);
        $this->assertEquals(Funding::STATUS_PENDING, $funding->usageproof_status);
        $this->assertEquals($testFundingusageproofIncomplete['main_description'], $funding->fundingusageproof->main_description);
        $this->assertEquals($testFundingusageproofIncomplete['difference_declaration'], $funding->fundingusageproof->difference_declaration);
        $this->assertEquals(Funding::STATUS_DESCRIPTIONS_PENDING, $funding->usageproof_descriptions_status);
        $this->assertEquals(Funding::STATUS_RECEIPTLIST_DATA_MISSING, $funding->receiptlist_status);
        $this->assertEquals(false, $funding->usageproof_is_submittable);

        $testFundingusageproofComplete = [
            'main_description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum.',
            'difference_declaration' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum.',
            'checkbox_a' => 1,
            'checkbox_b' => 1,
            'checkbox_c' => 1,
            'checkbox_d' => 1,
            'question_radio_a' => 1,
            'question_radio_b' => 1,
            'question_radio_c' => 1,
            'question_radio_d' => 1,
            'question_radio_e' => 1,
            'question_radio_f' => 1,
            'question_text_a' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum.',
            'question_text_b' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum.',
        ];

        $newFundingreceiptlistDescriptionOk = 'Fundingreceiptlist Description Ok';
        $newFundingreceiptlistAmountOk = 99;

        $validFundingreceiptlist = [
            'id' => 1,
            'type' => Fundingbudgetplan::TYPE_A,
            'description' => $newFundingreceiptlistDescriptionOk,
            'recipient' => 'Test Empfänger',
            'receipt_type' => 'Beleg',
            'payment_date' => '2025-01-10',
            'receipt_number' => '232343',
            'amount' => $newFundingreceiptlistAmountOk,
        ];

        $uploadTemplateJpgFile = TESTS . 'files/test.jpg';
        $uploadFilePrMaterial1 = TESTS . 'files/uploadPrMaterial1.jpg';
        copy($uploadTemplateJpgFile, $uploadFilePrMaterial1);

        // 2) POST complete data
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofComplete,
                'fundingreceiptlists' => [
                    $validFundingreceiptlist,
                    [
                        'id' => 2,
                        'type' => '', // invalid
                        'description' => $newFundingreceiptlistDescriptionOk,
                        'amount' => $newFundingreceiptlistAmountOk,
                        'recipient' => '',
                        'receipt_type' => '',
                        'payment_date' => '',
                        'receipt_number' => '',
                    ],  
                    [
                        'id' => 3,
                        'type' => Fundingbudgetplan::TYPE_B,
                        'description' => 'abc', // invalid
                        'amount' => $newFundingreceiptlistAmountOk,
                        'recipient' => '',
                        'receipt_type' => '',
                        'payment_date' => '2030-01-01',
                        'receipt_number' => '',
                    ],
                    [
                        'id' => 4,
                        'type' => Fundingbudgetplan::TYPE_C,
                        'description' => $newFundingreceiptlistDescriptionOk,
                        'amount' => -1, // invalid
                    ],
                ],
                'files_fundinguploads_pr_materials' => [
                    new UploadedFile(
                        $uploadFilePrMaterial1,
                        filesize($uploadFilePrMaterial1),
                        UPLOAD_ERR_OK,
                        'test.jpg',
                        'image/jpeg',
                    ),
                ],

            ],
        ]);
        //echo $this->_response->getBody()->__toString();

        $funding = $fundingsTable->findWithUsageproofAssociations($fundingUid);
        $this->assertEquals(Funding::STATUS_PENDING, $funding->usageproof_status);
        $this->assertEquals($testFundingusageproofComplete['main_description'], $funding->fundingusageproof->main_description);
        $this->assertEquals($testFundingusageproofComplete['difference_declaration'], $funding->fundingusageproof->difference_declaration);
        $this->assertEquals(Funding::STATUS_DATA_OK, $funding->usageproof_descriptions_status);
        $this->assertEquals(Funding::STATUS_DATA_OK, $funding->receiptlist_status);
        $this->assertEquals(Funding::STATUS_CHECKBOXES_OK, $funding->usageproof_checkboxes_status);
        $this->assertEquals(Funding::STATUS_QUESTIONS_OK, $funding->usageproof_questions_status);
        $this->assertEquals(true, $funding->usageproof_is_submittable);
        $this->assertEquals(1, count($funding->fundingreceiptlists));

        $this->assertEquals($validFundingreceiptlist['description'], $funding->fundingreceiptlists[0]->description);
        $this->assertEquals($validFundingreceiptlist['amount'], $funding->fundingreceiptlists[0]->amount);
        $this->assertEquals($validFundingreceiptlist['recipient'], $funding->fundingreceiptlists[0]->recipient);
        $this->assertEquals($validFundingreceiptlist['receipt_type'], $funding->fundingreceiptlists[0]->receipt_type);
        $this->assertEquals($validFundingreceiptlist['payment_date'], $funding->fundingreceiptlists[0]->payment_date->format('Y-m-d'));
        $this->assertEquals($validFundingreceiptlist['receipt_number'], $funding->fundingreceiptlists[0]->receipt_number);

        $this->assertResponseContains('Bitte gib ein gültiges Datum (TT.MM.JJJJ) ein');
        $this->assertResponseContains('Ausgabenbereich auswählen');
        $this->assertResponseContains('Betrag muss größer als 0 sein');
        $this->assertResponseContains('Das Datum muss zwischen 09.01.2025 und 28.02.2026 liegen.');
        $this->assertResponseContains('id="fundings-fundingreceiptlists-2-recipient-error"');
        $this->assertResponseContains('id="fundings-fundingreceiptlists-2-payment-date-error"');
        $this->assertResponseContains('id="fundings-fundingreceiptlists-2-receipt-number-error"');

        $this->assertCount(1, $funding->fundinguploads_pr_materials);
        foreach($funding->fundinguploads_pr_materials as $fundingupload) {
            $this->assertEquals(Fundingupload::TYPE_PR_MATERIAL, $fundingupload->type);
            $this->assertFileExists($fundingupload->full_path);
            $this->get(Configure::read('AppConfig.htmlHelper')->urlFundinguploadDetail($fundingupload->id));
            $this->assertResponseOk();
        }

        // 3) DELETE fundingreceiptlist
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofComplete,
                'fundingreceiptlists' => [
                    [...$validFundingreceiptlist, 'delete' => 1],
                ],
            ],
        ]);
        $funding = $fundingsTable->findWithUsageproofAssociations($fundingUid);
        $this->assertEquals(Funding::STATUS_PENDING, $funding->usageproof_status);
        $this->assertEmpty($funding->fundingreceiptlists);

        // 4) ADD fundingreceiptlist
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofComplete,
                'fundingreceiptlists' => [
                    $validFundingreceiptlist,
                ],
            ],
            'add_receiptlist' => 1,
        ]);
        $funding = $fundingsTable->findWithUsageproofAssociations($fundingUid);
        $this->assertEquals(Funding::STATUS_PENDING, $funding->usageproof_status);
        $this->assertEquals(2, count($funding->fundingreceiptlists));

        // 5) SUBMIT
        $this->post($route, [
            'referer' => '/',
            'submit_usageproof' => 1,
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofComplete,
                'fundingreceiptlists' => [
                    $validFundingreceiptlist,
                ],
            ],
        ]);
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);
        $this->assertNotNull($funding->usageproof_submit_date);
        $this->get($route);
        $this->assertFlashMessage('Der Verwendungsnachweis wurde bereits eingereicht und kann nicht mehr bearbeitet werden.');
        $this->assertRedirect(Configure::read('AppConfig.htmlHelper')->urlFundings());

        // 6) REJECT by admin
        $this->loginAsAdmin();
        $this->post('/admin/fundings/usageproofEdit/' . $fundingUid, [
            'referer' => '/',
            'Fundings' => [
                'usageproof_status' => Funding::STATUS_REJECTED_BY_ADMIN,
            ],
        ]);

        $this->runAndAssertQueue();
        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, $funding->owner_user->email);
        $this->assertMailSubjectContainsAt(0, 'Der Status deines Verwendungsnachweises wurde geändert');

        // 7) submit again
        $this->loginAsOrga();
        $this->post($route, [
            'referer' => '/',
            'submit_usageproof' => 1,
            'Fundings' => [
                'fundingusageproof' => $testFundingusageproofComplete,
                'fundingreceiptlists' => [
                    $validFundingreceiptlist,
                ],
            ],
        ]);
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        // 6) VERIFY by admin and trigger email with pdf
        $this->loginAsAdmin();
        $this->post('/admin/fundings/usageproofEdit/' . $fundingUid, [
            'referer' => '/',
            'Fundings' => [
                'usageproof_status' => Funding::STATUS_VERIFIED_BY_ADMIN,
            ],
        ]);

        $verwendungsnachweisPdfWriterService = new VerwendungsnachweisPdfWriterService();
        $verwendungsnachweisPdfFilename = $verwendungsnachweisPdfWriterService->getFilenameCustom($funding, $funding->usageproof_submit_date);
        $this->assertFileExists($verwendungsnachweisPdfWriterService->getUploadPath($fundingUid) . $verwendungsnachweisPdfFilename);

        TestEmailTransport::clearMessages();
        $this->runAndAssertQueue();

        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, $funding->owner_user->email);
        $this->assertMailSentToAt(0, $funding->fundingsupporter->contact_email);
        $this->assertMailContainsAttachment($verwendungsnachweisPdfFilename);

        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingVerwendungsnachweisDownload($fundingUid));
        $this->assertResponseOk();
        $this->assertContentType('application/pdf');


        // 9) CLEANUP files
        $filePath = Fundingupload::UPLOAD_PATH . $funding->uid;
        FolderService::deleteFolder($filePath);

    }

}