<?php
declare(strict_types=1);
namespace Admin\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\Event\EventInterface;
use App\Mailer\AppMailer;
use App\Services\PdfWriter\FoerderbewilligungPdfWriterService;
use Cake\I18n\DateTime;
use App\Services\PdfWriter\FoerderantragPdfWriterService;
use League\Csv\Writer;
use App\Model\Entity\Funding;
use Cake\Http\Response;

class FundingsController extends AdminAppController
{

    public bool $searchName = false;
    public bool $searchText = false;
    public bool $searchUid = false;
    public bool $searchStatus = false;

    public function beforeFilter(EventInterface $event): void
    {
        $this->addSearchOptions([
            'Workshops.name' => [
                'name' => 'Workshops.name',
                'searchType' => 'search'
            ],
            'FundingStatus' => [
                'searchType' => 'custom',
                'conditions' => Funding::getAdminFilterConditions(),
                'extraDropdown' => true,
            ],
        ]);
        $this->generateSearchConditions('opt-1');
        parent::beforeFilter($event);
    }

    public function foerderbewilligungPdf($fundingUid): void
    {
        $pdfWriterService = new FoerderbewilligungPdfWriterService();
        $pdfWriterService->prepareAndSetData($fundingUid, DateTime::now());
        die($pdfWriterService->writeInline());
    }

    public function foerderantragPdf($fundingUid): void
    {
        $pdfWriterService = new FoerderantragPdfWriterService();
        $pdfWriterService->prepareAndSetData($fundingUid, DateTime::now());
        die($pdfWriterService->writeInline());
    }

    public function bankExport(): Response
    {

        $this->disableAutoRender();
        $fundingsTable = $this->getTableLocator()->get('Fundings');

        $fundings = $fundingsTable->find('all',
            conditions: [
                $fundingsTable->aliasField('submit_date IS NOT NULL'),
                $fundingsTable->aliasField('money_transfer_date IS NULL'),
                $fundingsTable->aliasField('owner NOT IN') => [1824,1825,4722], // exclude admins
            ],
            contain: [
                'OwnerUsers',
                'Fundingsupporters',
                'Fundingbudgetplans',
            ],
            order: [
                $fundingsTable->aliasField('submit_date') => 'ASC',
            ])->toArray();

        $validFundings = [];
        foreach($fundings as $funding) {
            $funding->owner_user->revertPrivatizeData();
            $validFundings[] = $funding;
        }

        $writer = Writer::createFromString();
        $writer->setDelimiter(';');

        $writer->insertOne($this->getCsvHeader());
        $records = [];
        foreach($validFundings as $funding) {
            $record = [];
            $record = [
                'EUR', // Währung
                number_format($funding->budgetplan_total_with_limit, 2, ',', ''), // VorzBetrag
                'Antrag-' . $funding->uid, // RechNr
                date('dm'), // Belegdatum
                '', // InterneRechNr
                substr($funding->fundingsupporter->name, 0, 40), // LieferantName
                $funding->fundingsupporter->city, // LieferantOrt
                '70001', // LieferantKonto
                '', // BU
                '5010', // Konto
                '', // Kontobezeichnung
                'Reparaturförderung', // Ware/Leistung
                date('d.m.Y'), // Fällig_am
                '', // gezahlt_am
                '', // UStSatz
                '', // USt-IdNr.Kunde
                '', // Kunden-Nr.
                '', // KOST1
                '', // KOST2
                '', // KOSTmenge
                '', // Kurs
                '', // Skonto
                '', // Nachricht
                '', // Skto_Fällig_am
                '', // BankKonto
                '', // BankBlz
                $funding->fundingsupporter->bank_institute, // Bankname
                $funding->fundingsupporter->iban, // BankIban
                $funding->fundingsupporter->bic, // BankBic
                '', // Skto_Proz
                '', // Leistungsdatum
            ];
            $records[] = $record;
        }
        $writer->insertAll($records);

        $response = $this->response;
        $response = $response->withStringBody($writer->toString());
        $response = $response->withCharset('UTF-8');
        $response = $response->withDownload('bankexport-' . DateTime::now()->i18nFormat('yyyyMMdd_HHmmss') .  '.csv');
        return $response;

    }

    private function getCsvHeader(): array
    {
        return [
            'Währung',
            'VorzBetrag',
            'RechNr',
            'Belegdatum',
            'InterneRechNr',
            'LieferantName',
            'LieferantOrt',
            'LieferantKonto',
            'BU',
            'Konto',
            'Kontobezeichnung',
            'Ware/Leistung',
            'Fällig_am',
            'gezahlt_am',
            'UStSatz',
            'USt-IdNr.Lieferant',
            'Kunden-Nr.',
            'KOST1',
            'KOST2',
            'KOSTmenge',
            'Kurs',
            'Skonto',
            'Nachricht',
            'Skto_Fällig_am',
            'BankKonto',
            'BankBlz',
            'Bankname',
            'BankIban',
            'BankBic',
            'Skto_Proz',
            'Leistungsdatum',
        ];
    }
    
    public function edit($uid): void
    {

        if (empty($uid)) {
            throw new NotFoundException;
        }

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $workshopsTable = $this->getTableLocator()->get('Workshops');
        $funding = $fundingsTable->find('all',
        conditions: [
            $fundingsTable->aliasField('uid') => $uid,
        ],
        contain: [
            'Workshops' => $workshopsTable->getFundingContain(),
            'OwnerUsers',
            'Fundingdatas',
            'Fundingbudgetplans',
            'Fundingsupporters',
            'FundinguploadsActivityProofs' => function($q) {
                return $q->orderBy(['FundinguploadsActivityProofs.created' => 'DESC']);
            },
            'FundinguploadsFreistellungsbescheids' => function($q) {
                return $q->orderBy(['FundinguploadsFreistellungsbescheids.created' => 'DESC']);
            },
            'FundinguploadsZuwendungsbestaetigungs' => function($q) {
                return $q->orderBy(['FundinguploadsZuwendungsbestaetigungs.created' => 'DESC']);
            },
        ])->first();

        if ($funding->owner_user) {
            $funding->owner_user->revertPrivatizeData();
        }

        if (empty($funding)) {
            throw new NotFoundException;
        }

        $this->set('uid', $funding->uid);

        $this->setReferer();

        if (!empty($this->request->getData())) {
            $associtions =  ['associated' => ['FundinguploadsActivityProofs', 'FundinguploadsFreistellungsbescheids']];

            $patchedEntity = $fundingsTable->patchEntity($funding, $this->request->getData(), $associtions);
            if (!($patchedEntity->hasErrors())) {

                $this->sendEmails($patchedEntity);

                if (!empty($this->request->getData('Fundings.reopen'))) {
                    $patchedEntity->submit_date = null;
                }
    
                $fundingsTable->save($patchedEntity, $associtions);
                $this->redirect($this->getReferer());
            } else {
                $funding = $patchedEntity;
            }
        }

        $this->set('funding', $funding);
    }

    private function sendEmails($funding): void
    {
        $email = new AppMailer();
        if ($funding->isDirty('freistellungsbescheid_status')) {
            $email->viewBuilder()->setTemplate('fundings/freistellungsbescheid_status_changed');
            $email->setSubject('Der Status deines Freistellungsbescheides wurde geändert')
            ->setTo($funding->owner_user->email)
            ->setViewVars([
                'funding' => $funding,
                'data' => $funding->owner_user,
            ]);
            $email->addToQueue();
        }

        if ($funding->isDirty('activity_proof_status')) {
            $email->viewBuilder()->setTemplate('fundings/activity_proof_status_changed');
            $email->setSubject('Der Status deines Aktivitätsnachweises wurde geändert')
            ->setTo($funding->owner_user->email)
            ->setViewVars([
                'funding' => $funding,
                'data' => $funding->owner_user,
            ]);
            $email->addToQueue();
        }

        if ($funding->isDirty('zuwendungsbestaetigung_status')) {
            $email->viewBuilder()->setTemplate('fundings/zuwendungsbestaetigung_status_changed');
            $email->setSubject('Der Status deiner Zuwendungsbestätigung wurde geändert')
            ->setTo($funding->owner_user->email)
            ->setViewVars([
                'funding' => $funding,
                'data' => $funding->owner_user,
            ]);
            $email->addToQueue();
        }

    }

    public function index(): void
    {
        parent::index();

        $this->paginate['limit'] = 1000;

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $workshopsTable = $this->getTableLocator()->get('Workshops');


        $query = $fundingsTable->find('all',
        conditions: $this->conditions,
        contain: [
            'Workshops' => $workshopsTable->getFundingContain(),
            'OwnerUsers',
            'Fundingdatas',
            'Fundingsupporters',
            'FundinguploadsActivityProofs',
            'FundinguploadsFreistellungsbescheids',
            'FundinguploadsZuwendungsbestaetigungs',
            'Fundingbudgetplans',
        ]);

        $uids = [];
        foreach($query->toArray() as $object) {
            if (!empty($this->afterFindCallbacks)) {
                foreach($this->afterFindCallbacks as $afterFindCallback) {
                    if ($afterFindCallback($object)) {
                        $uids[] = $object->uid;
                    }
                }
            }
        }
        
        $clonedQuery = clone $query;
        if (!empty($uids)) {
            $clonedQuery->where([$fundingsTable->aliasField('uid IN') => $uids]);
        }

        // TODO Sorting not yet working
        $objects = $this->paginate($clonedQuery, [
            'order' => [
                'Fundings.submit_date' => 'ASC',
                'Fundings.created' => 'DESC'
            ],
        ]);

        foreach($objects as $object) {
            if ($object->owner_user) {
                $object->owner_user->revertPrivatizeData();
            }
        }

        $this->set('objects', $objects);
        $this->set('fundingStatus', Funding::getAdminFilterOptions());

    }

}
?>