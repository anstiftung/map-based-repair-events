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
use Cake\Event\EventInterface;
use App\Test\Mock\GeoServiceMock;
use Cake\Controller\Controller;
use App\Model\Table\FundingsTable;
use Laminas\Diactoros\UploadedFile;
use App\Model\Entity\Fundingupload;
use App\Services\PdfWriter\FoerderantragPdfWriterService;
use App\Services\PdfWriter\FoerderbewilligungPdfWriterService;
use App\Test\TestCase\Traits\QueueTrait;
use Cake\TestSuite\EmailTrait;
use Cake\Http\Exception\NotFoundException;
use App\Services\PdfWriter\VerwendungsnachweisPdfWriterService;
use App\Services\FolderService;

class FundingsControllerTest extends AppTestCase
{

    use EmailTrait;
    use IntegrationTestTrait;
    use LogFileAssertionsTrait;
    use LoginTrait;
    use QueueTrait;

	public function controllerSpy(EventInterface $event, ?Controller $controller = null): void
    {
		parent::controllerSpy($event, $controller);
		$this->_controller->geoService = new GeoServiceMock();
	}

    public function setUp(): void
    {
        parent::setUp();
        $this->resetLogs();
        Configure::write('AppConfig.fundingsEnabled', true);
    }

    public function testRoutesLoggedOut(): void
    {
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertResponseCode(302);
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
    }

    public function testRoutesAsRepairhelper(): void
    {
        $this->loginAsRepairhelper();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertResponseCode(302);
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
    }

    public function testEditWorkshopFundingNotAllowed(): void
    {

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

    public function testEditNotInOrgaTeam(): void
    {
        $userWorkshopsTable = $this->getTableLocator()->get('UsersWorkshops');
        $userWorkshop = $userWorkshopsTable->find()->where(['workshop_uid' => 2])->first();
        $userWorkshopsTable->delete($userWorkshop);
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/users/login');
    }

    public function testEditAlreadyCreatedByOtherOwner(): void
    {
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

    private function prepareWorkshopForFunding($workshopUid): void
    {
        // add 4 events for 2025 (required for funding)
        $eventsTable = $this->getTableLocator()->get('Events');
        $i = 0;
        while($i<4) {
            $event = $eventsTable->newEntity([
                'workshop_uid' => $workshopUid,
                'datumstart' => '2025-01-01',
                'status' => APP_ON,
                'created' => '2020-01-01 00:00:00',
            ]);
            $eventsTable->save($event);
            $i++;
        }
    }

    public function testFundingProcessOk(): void
    {

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $testWorkshopUid = 2;
        $route = Configure::read('AppConfig.htmlHelper')->urlFundingsEdit($testWorkshopUid);
        $this->loginAsOrga();
        $this->prepareWorkshopForFunding($testWorkshopUid);

        $this->get($route);
        $this->assertResponseOk();

        $fundingUid = $fundingsTable->find()->orderByDesc('uid')->first()->uid;
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);
        $this->assertEquals(FundingsTable::FUNDINGBUDGETPLANS_COUNT, count($funding->fundingbudgetplans));
        $this->assertNotEmpty($funding->fundingsupporter);
        $this->assertNotEmpty($funding->fundingdata);
        $this->assertNotEmpty($funding->owner_user);
        $this->assertEquals(1, $funding->owner_user->uid);

        $newName = 'Testname';
        $newStreet = 'Teststraße 1';
        $newZip = '12345';
        $newCity = 'Teststadt';
        $newAdresszusatz = 'Adresszusatz';

        $newFundingsupporterName = 'Fundingsupporter Name';

        $newOwnerFirstname = 'Owner Firstname';
        $newOwnerLastname = 'Owner Lastname';
        $newOwnerEmail = 'test@test.at';

        $newFundingdataDescription = 'Fundingdata Description';

        $newFundingbudgetplanDescriptionOk = 'Fundingdata Description Ok';
        $newFundingbudgetplanAmountOk = 99;

        $verifiedFields = [
            'fundings-workshop-name',
        ];

        $testWorkshop = [
            'name' => $newName . '🥳',
            'street' => $newStreet . '<script>alert("XSS");</script>',
            'zip' => $newZip,
            'city' => $newCity,
            'adresszusatz' => $newAdresszusatz,
            'website' => 'non valid string',
            'use_custom_coordinates' => 0,
        ];

        $testFundingsupporter = [
            'name' => $newFundingsupporterName . '🥳',
            'website' => 'orf.at',
        ];

        $testFundingdata = [
            'description' => $newFundingdataDescription . '🥳',
        ];

        $testOwnerUser = [
            'firstname' => $newOwnerFirstname . '🥳',
            'lastname' => $newOwnerLastname,
            'email' => $newOwnerEmail,
            'use_custom_coordinates' => 0,
        ];


        $uploadTemplateJpgFile = TESTS . 'files/test.jpg';
        $uploadTemplateTxtFile = TESTS . 'files/test.txt';
        $uploadFileActivityProof1 = TESTS . 'files/uploadActivityProof1.jpg';
        $uploadFileActivityProof2 = TESTS . 'files/uploadActivityProof2.txt';
        $uploadFileFreistellungsbescheid1 = TESTS . 'files/uploadTFreistellungsbescheid1.jpg';
        $uploadFileFreistellungsbescheid2 = TESTS . 'files/uploadFreistellungsbescheid2.jpg';
        $uploadFileZuwendungsbestaetigung1 = TESTS . 'files/uploadZuwendungsbestaetigung1.jpg';
        copy($uploadTemplateJpgFile, $uploadFileActivityProof1);
        copy($uploadTemplateTxtFile, $uploadFileActivityProof2);
        copy($uploadTemplateJpgFile, $uploadFileFreistellungsbescheid1);
        copy($uploadTemplateJpgFile, $uploadFileFreistellungsbescheid2);
        copy($uploadTemplateJpgFile, $uploadFileZuwendungsbestaetigung1);

        // 1) POST
        $this->post($route, [
            'referer' => '/',
            'submit_funding' => 1, // must fail
            'Fundings' => [
                'workshop' => $testWorkshop,
                'fundingsupporter' => $testFundingsupporter,
                'fundingdata' => $testFundingdata,
                'owner_user' => $testOwnerUser,
                'verified_fields' => array_merge($verifiedFields, ['fundings-workshop-website']),
                'files_fundinguploads_activity_proofs' => [
                    new UploadedFile(
                        $uploadFileActivityProof1,
                        filesize($uploadFileActivityProof1),
                        UPLOAD_ERR_OK,
                        'test.jpg',
                        'image/jpeg',
                    ),
                ],
                'files_fundinguploads_freistellungsbescheids' => [
                    new UploadedFile(
                        $uploadFileFreistellungsbescheid1,
                        filesize($uploadFileFreistellungsbescheid1),
                        UPLOAD_ERR_OK,
                        'test.jpg',
                        'image/jpeg',
                    ),
                ],
                'fundingbudgetplans' => [
                    [
                        'id' => 1,
                        'type' => Fundingbudgetplan::TYPE_A,
                        'description' => $newFundingbudgetplanDescriptionOk,
                        'amount' => $newFundingbudgetplanAmountOk,
                    ],
                    [
                        'id' => 2,
                        'type' => '', // invalid
                        'description' => $newFundingbudgetplanDescriptionOk,
                        'amount' => $newFundingbudgetplanAmountOk,
                    ],
                    [
                        'id' => 3,
                        'type' => Fundingbudgetplan::TYPE_B,
                        'description' => 'abc', // invalid
                        'amount' => $newFundingbudgetplanAmountOk,
                    ],
                    [
                        'id' => 4,
                        'type' => Fundingbudgetplan::TYPE_C,
                        'description' => $newFundingbudgetplanDescriptionOk,
                        'amount' => -1, // invalid
                    ],
                ],
            ]
        ]);
        $this->assertResponseContains('Der Förderantrag wurde erfolgreich zwischengespeichert.');

        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        $this->assertNull($funding->submit_date);
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
        $this->assertTextEndsWith('street,phone,city', $funding->owner_user->private);

        $this->assertEquals($newFundingdataDescription, $funding->fundingdata->description);

        $this->assertCount(1, $funding->fundinguploads_activity_proofs);
        foreach($funding->fundinguploads_activity_proofs as $fundingupload) {
            $this->assertEquals(Fundingupload::TYPE_ACTIVITY_PROOF, $fundingupload->type);
            $this->assertFileExists($fundingupload->full_path);
            $this->get(Configure::read('AppConfig.htmlHelper')->urlFundinguploadDetail($fundingupload->id));
            $this->assertResponseOk();
        }

        $this->assertCount(1, $funding->fundinguploads_freistellungsbescheids);
        foreach($funding->fundinguploads_freistellungsbescheids as $fundingupload) {
            $this->assertEquals(Fundingupload::TYPE_FREISTELLUNGSBESCHEID, $fundingupload->type);
            $this->assertFileExists($fundingupload->full_path);
            $this->get(Configure::read('AppConfig.htmlHelper')->urlFundinguploadDetail($fundingupload->id));
            $this->assertResponseOk();
        }

        $this->assertEquals(FundingsTable::FUNDINGBUDGETPLANS_COUNT, count($funding->fundingbudgetplans));
        $this->assertEquals(Fundingbudgetplan::TYPE_A, $funding->fundingbudgetplans[0]->type);
        $this->assertEquals($newFundingbudgetplanDescriptionOk, $funding->fundingbudgetplans[0]->description);
        $this->assertEquals($newFundingbudgetplanAmountOk, $funding->fundingbudgetplans[0]->amount);

        $emptyFundingbudgets = [2, 3, 4];
        foreach($funding->fundingbudgetplans as $fundingbudgetplan) {
            if (!in_array($fundingbudgetplan->id, $emptyFundingbudgets)) {
                continue;
            }
            $this->assertFalse($fundingbudgetplan->is_valid);
            $this->assertFalse($fundingbudgetplan->is_not_empty);
        }

        // 2) POST test upload validations
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'workshop' => $testWorkshop,
                'fundingsupporter' => $testFundingsupporter,
                'fundingdata' => $testFundingdata,
                'owner_user' => $testOwnerUser,
                'fundinguploads_activity_proofs' => [
                    $funding->fundinguploads_activity_proofs[0]->toArray(),
                ],
                'fundinguploads_freistellungsbescheids' => [
                    $funding->fundinguploads_freistellungsbescheids[0]->toArray(),
                ],
                'files_fundinguploads_activity_proofs' => [
                    new UploadedFile(
                        $uploadFileActivityProof2,
                        filesize($uploadFileActivityProof2),
                        UPLOAD_ERR_OK,
                        'test.txt',
                        'text/plain',
                    ),
                ],
                'files_fundinguploads_freistellungsbescheids' => [
                    new UploadedFile(
                        $uploadFileFreistellungsbescheid2,
                        filesize($uploadFileFreistellungsbescheid2),
                        UPLOAD_ERR_OK,
                        'test.jpg',
                        'image/jpeg',
                    ),
                ],
            ]
        ]);
        $this->assertResponseContains('Es ist nur eine Datei erlaubt.');
        $this->assertResponseContains('Nur PDF, JPG und PNG-Dateien sind erlaubt.');

        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        $this->assertCount(1, $funding->fundinguploads_activity_proofs);
        $this->assertCount(1, $funding->fundinguploads_freistellungsbescheids);

        // 2) POST test delete uploads
        $this->post($route, [
            'referer' => '/',
            'Fundings' => [
                'workshop' => $testWorkshop,
                'fundingsupporter' => $testFundingsupporter,
                'fundingdata' => $testFundingdata,
                'owner_user' => $testOwnerUser,
                'delete_fundinguploads_freistellungsbescheids' => [
                    $funding->fundinguploads_freistellungsbescheids[0]->id,
                ],
                'delete_fundinguploads_activity_proofs' => [
                    $funding->fundinguploads_activity_proofs[0]->id,
                ],
            ]
        ]);

        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);
        $this->assertCount(0, $funding->fundinguploads_activity_proofs);
        $this->assertCount(0, $funding->fundinguploads_freistellungsbescheids);

        // 4) POST create a valid funding and submit
        $funding->activity_proof_status = Funding::STATUS_VERIFIED_BY_ADMIN;
        $funding->freistellungsbescheid_status = Funding::STATUS_VERIFIED_BY_ADMIN;
        $fundingsTable->save($funding);

        $validTestWorkshop = $testWorkshop;

        $validTestOwnerUser = $testOwnerUser;
        $validTestOwnerUser['email'] = 'test@mailinator.com';
        $validTestOwnerUser['zip'] = 22222;
        $validTestOwnerUser['city'] = 'Berlin';
        $validTestOwnerUser['phone'] = '1234567890';

        $validTestFundingsupporter = $testFundingsupporter;
        $validTestFundingsupporter['legal_form'] = 'Rechtsform';
        $validTestFundingsupporter['street'] = 'asdfasdf';
        $validTestFundingsupporter['zip'] = 22222;
        $validTestFundingsupporter['city'] = 'Berlin';
        $validTestFundingsupporter['contact_firstname'] = 'Test';
        $validTestFundingsupporter['contact_lastname'] = 'Test';
        $validTestFundingsupporter['contact_phone'] = '1234590';
        $validTestFundingsupporter['contact_email'] = 'test1@mailinator.com';
        $validTestFundingsupporter['contact_function'] = 'Funktion';
        $validTestFundingsupporter['bank_account_owner'] = 'Kontoinhaber';
        $validTestFundingsupporter['bank_institute'] = 'Bank';
        $validTestFundingsupporter['iban'] = 'DE 89370400440532013000';
        $validTestFundingsupporter['bic'] = 'RZOODE2L510';

        $validTestFundingdata['description'] = 'Fundingdata Description Ok Fundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description OkFundingdata Description';
        $validTestFundingdata['checkbox_a'] = 1;
        $validTestFundingdata['checkbox_b'] = 1;
        $validTestFundingdata['checkbox_c'] = 1;

        $verifiedFields = [
            'fundings-workshop-name',
            'fundings-workshop-street',
            'fundings-workshop-zip',
            'fundings-workshop-city',
            'fundings-workshop-adresszusatz',
            'fundings-workshop-website',
            'fundings-workshop-email',
            'fundings-owner-user-firstname',
            'fundings-owner-user-lastname',
            'fundings-owner-user-street',
            'fundings-owner-user-email',
            'fundings-owner-user-zip',
            'fundings-owner-user-city',
            'fundings-owner-user-phone',
            'fundings-fundingsupporter-name',
            'fundings-fundingsupporter-legal-form',
            'fundings-fundingsupporter-street',
            'fundings-fundingsupporter-zip',
            'fundings-fundingsupporter-city',
            'fundings-fundingsupporter-website',
            'fundings-fundingsupporter-contact-firstname',
            'fundings-fundingsupporter-contact-lastname',
            'fundings-fundingsupporter-contact-phone',
            'fundings-fundingsupporter-contact-email',
            'fundings-fundingsupporter-contact-function',
            'fundings-fundingsupporter-bank-account-owner',
            'fundings-fundingsupporter-bank-institute',
            'fundings-fundingsupporter-iban',
            'fundings-fundingsupporter-bic',
        ];
        $validTestWorkshop['website'] = 'https://example.com';
        $this->post($route, [
            'referer' => '/',
            'submit_funding' => 1,
            'Fundings' => [
                'workshop' => $validTestWorkshop,
                'owner_user' => $validTestOwnerUser,
                'fundingsupporter' => $validTestFundingsupporter,
                'fundingdata' => $validTestFundingdata,
                'verified_fields' => $verifiedFields,
            ],
        ]);

        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);
        $this->assertNotNull($funding->submit_date);
        $this->assertEquals('DE89370400440532013000', $funding->fundingsupporter->iban); // must be cleaned

        $foerderantragPdfWriterService = new FoerderantragPdfWriterService();
        $foerderantragPdfFilename = $foerderantragPdfWriterService->getFilenameCustom($funding, $funding->submit_date);
        $foerderbewilligungPdfWriterService = new FoerderbewilligungPdfWriterService();
        $foerderbewilligungPdfFilename = $foerderbewilligungPdfWriterService->getFilenameCustom($funding, $funding->submit_date);
        
        $this->assertFileExists($foerderantragPdfWriterService->getUploadPath($fundingUid) . $foerderantragPdfFilename);
        $this->assertFileExists($foerderbewilligungPdfWriterService->getUploadPath($fundingUid) . $foerderbewilligungPdfFilename);

        $this->runAndAssertQueue();
        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, $validTestOwnerUser['email']);
        $this->assertMailSentToAt(0, $validTestFundingsupporter['contact_email']);
        $this->assertMailContainsAt(0, 'Download Förderlogo BMUV');
        $this->assertMailContainsAttachment($foerderbewilligungPdfFilename);
        $this->assertMailContainsAttachment($foerderantragPdfFilename);

        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingFoerderbewilligungDownload($fundingUid));
        $this->assertResponseOk();
        $this->assertContentType('application/pdf');
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingFoerderantragDownload($fundingUid));
        $this->assertResponseOk();
        $this->assertContentType('application/pdf');

        // 5) POST zuwendungsbestaetigung upload
        $this->post(Configure::read('AppConfig.htmlHelper')->urlFundingsUploadZuwendungsbestaetigung($fundingUid), [
            'referer' => '/',
            'Fundings' => [
                'files_fundinguploads_zuwendungsbestaetigungs' => [
                    new UploadedFile(
                        $uploadFileZuwendungsbestaetigung1,
                        filesize($uploadFileZuwendungsbestaetigung1),
                        UPLOAD_ERR_OK,
                        'test.jpg',
                        'image/jpeg',
                    ),
                ],
            ],
        ]);
        $this->assertResponseContains('Die Zuwendungsbestägigung wurde erfolgreich gespeichert.');
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        $this->assertCount(1, $funding->fundinguploads_zuwendungsbestaetigungs);
        foreach($funding->fundinguploads_zuwendungsbestaetigungs as $fundingupload) {
            $this->assertEquals(Fundingupload::TYPE_ZUWENDUNGSBESTAETIGUNG, $fundingupload->type);
            $this->assertFileExists($fundingupload->full_path);
            $this->get(Configure::read('AppConfig.htmlHelper')->urlFundinguploadDetail($fundingupload->id));
            $this->assertResponseOk();
        }

        // 6) POST zuwendungsbestaetigung delete
        $this->post(Configure::read('AppConfig.htmlHelper')->urlFundingsUploadZuwendungsbestaetigung($fundingUid), [
            'referer' => '/',
            'Fundings' => [
                'delete_fundinguploads_zuwendungsbestaetigungs' => [
                    $funding->fundinguploads_zuwendungsbestaetigungs[0]->id,
                ],
            ]
        ]);

        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);
        $this->assertCount(0, $funding->fundinguploads_zuwendungsbestaetigungs);

        // 7) CLEANUP everything including file uploads
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingsTable->save($funding);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('funding (UID: ' . $fundingUid . ') is submitted and cannot be deleted');
        
        $funding->submit_date = null;
        $fundingsTable->deleteCustom($fundingUid);

    }

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
            ],
        ]);

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

        // 6) VERIFY and trigger email with pdf
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

        $this->runAndAssertQueue();
        $this->assertMailCount(1);
        $this->assertMailSentToAt(0, $funding->owner_user->email);
        $this->assertMailSentToAt(0, $funding->fundingsupporter->contact_email);
        $this->assertMailContainsAttachment($verwendungsnachweisPdfFilename);

        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingVerwendungsnachweisDownload($fundingUid));
        $this->assertResponseOk();
        $this->assertContentType('application/pdf');

        // 7) CLEANUP files
        $filePath = Fundingupload::UPLOAD_PATH . $funding->uid;
        FolderService::deleteFolder($filePath);


    }

    public function testIndex(): void
    {
        $testWorkshopUid = 2;
        $this->loginAsOrga();
        $this->prepareWorkshopForFunding($testWorkshopUid);
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertResponseOk();
    }

    public function testDelete(): void
    {
        $this->loginAsOrga();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsEdit(2));
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->find()->where(['workshop_uid' => 2])->first();
        $this->get(Configure::read('AppConfig.htmlHelper')->urlFundingsDelete($funding->uid));
        $this->assertResponseCode(302);
        $this->assertRedirectContains(Configure::read('AppConfig.htmlHelper')->urlFundings());
        $this->assertFlashMessage('Der Förderantrag wurde erfolgreich gelöscht.');

        $deletedFunding = $fundingsTable->find()->where(['workshop_uid' => 2])->first();
        $this->assertEmpty($deletedFunding);

        $fundingsupportersTable = $this->getTableLocator()->get('Fundingsupporters');
        $fundingsupporter = $fundingsupportersTable->find()->where([$fundingsupportersTable->getPrimaryKey() => $funding->fundingsupporter_id])->first();
        $this->assertEmpty($fundingsupporter);

        $fundingdatasTable = $this->getTableLocator()->get('Fundingdatas');
        $fundingdata = $fundingdatasTable->find()->where([$fundingdatasTable->getPrimaryKey() => $funding->fundingdata_id])->first();
        $this->assertEmpty($fundingdata);

        $fundingbudgetplansTable = $this->getTableLocator()->get('Fundingbudgetplans');
        $fundingdatas = $fundingbudgetplansTable->find()->where([$fundingbudgetplansTable->aliasField('funding_uid') => $funding->uid])->toArray();
        $this->assertEmpty($fundingdatas);

        $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
        $fundinguploads = $fundinguploadsTable->find()->where([$fundinguploadsTable->aliasField('funding_uid') => $funding->uid])->toArray();
        $this->assertEmpty($fundinguploads);

    }

}
?>