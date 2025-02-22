<?php
declare(strict_types=1);
namespace App\Model\Table;

use Cake\Routing\Router;
use Cake\Database\Schema\TableSchemaInterface;
use App\Model\Entity\Fundingupload;
use App\Services\FolderService;
use Cake\Http\Exception\NotFoundException;
use Cake\Validation\Validator;
use Laminas\Diactoros\UploadedFile;
use Cake\ORM\TableRegistry;
use App\Model\Entity\Funding;

class FundingsTable extends AppTable
{

    const FUNDINGBUDGETPLANS_COUNT_VISIBLE = 5;
    const FUNDINGBUDGETPLANS_COUNT_HIDDEN = 10;
    const FUNDINGBUDGETPLANS_COUNT = self::FUNDINGBUDGETPLANS_COUNT_VISIBLE + self::FUNDINGBUDGETPLANS_COUNT_HIDDEN;

    const FUNDINGRECEIPTLISTS_COUNT_INITIAL = 1;

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('Workshops', [
            'foreignKey' => 'workshop_uid',
        ]);
        $this->belongsTo('Fundingdatas', [
            'foreignKey' => 'fundingdata_id',
        ]);
        $this->belongsTo('Fundingsupporters', [
            'foreignKey' => 'fundingsupporter_id',
        ]);
        $this->hasMany('Fundingbudgetplans', [
            'foreignKey' => 'funding_uid',
            'dependent' => true,
        ]);
        $this->hasMany('FundinguploadsActivityProofs', [
            'className' => 'Fundinguploads',
            'foreignKey' => 'funding_uid',
            'conditions' => [
                'FundinguploadsActivityProofs.type' => Fundingupload::TYPE_ACTIVITY_PROOF,
            ],
            'dependent' => true,
        ]);
        $this->hasMany('FundinguploadsFreistellungsbescheids', [
            'className' => 'Fundinguploads',
            'foreignKey' => 'funding_uid',
            'conditions' => [
                'FundinguploadsFreistellungsbescheids.type' => Fundingupload::TYPE_FREISTELLUNGSBESCHEID,
            ],
            'dependent' => true,
        ]);
        $this->hasMany('FundinguploadsZuwendungsbestaetigungs', [
            'className' => 'Fundinguploads',
            'foreignKey' => 'funding_uid',
            'conditions' => [
                'FundinguploadsZuwendungsbestaetigungs.type' => Fundingupload::TYPE_ZUWENDUNGSBESTAETIGUNG,
            ],
            'dependent' => true,
        ]);
        $this->hasMany('FundinguploadsPrMaterials', [
            'className' => 'Fundinguploads',
            'foreignKey' => 'funding_uid',
            'conditions' => [
                'FundinguploadsPrMaterials.type' => Fundingupload::TYPE_PR_MATERIAL,
            ],
            'dependent' => true,
        ]);
        $this->belongsTo('Fundingusageproofs', [
            'foreignKey' => 'fundingusageproof_id',
        ]);
        $this->hasMany('Fundingreceiptlists', [
            'foreignKey' => 'funding_uid',
            'dependent' => true,
        ]);

    }

    public function getSchema(): TableSchemaInterface
    {
        return parent::getSchema()->setColumnType('verified_fields', 'json');
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator->add('fundinguploads_activity_proofs', 'fileCount', [
            'rule' => function ($value, $context) {
                if (count($value) > 5) {
                    return 'Insgesamt sind maximal 5 Dateien erlaubt.';
                }
                return true;
            },
        ]);

        $validator->add('fundinguploads_freistellungsbescheids', 'fileCount', [
            'rule' => function ($value, $context) {
                if (count($value) > 1) {
                    return 'Es ist nur eine Datei erlaubt.';
                }
                return true;
            },
        ]);

        $validator->add('fundinguploads_zuwendungsbestaetigungs', 'fileCount', [
            'rule' => function ($value, $context) {
                if (count($value) > 1) {
                    return 'Es ist nur eine Datei erlaubt.';
                }
                return true;
            },
        ]);

        $validator->add('fundinguploads_pr_materials', 'fileCount', [
            'rule' => function ($value, $context) {
                if (count($value) > 5) {
                    return 'Insgesamt sind maximal 5 Dateien erlaubt.';
                }
                return true;
            },
        ]);

        $validator->add('files_fundinguploads_activity_proofs', 'fileTypeAndSize', [
            'rule' => [$this, 'validateFileTypeAndSize'],
        ]);
        $validator->add('files_fundinguploads_freistellungsbescheids', 'fileTypeAndSize', [
            'rule' => [$this, 'validateFileTypeAndSize'],
        ]);
        $validator->add('files_fundinguploads_zuwendungsbestaetigungs', 'fileTypeAndSize', [
            'rule' => [$this, 'validateFileTypeAndSize'],
        ]);
        $validator->add('files_fundinguploads_pr_materials', 'fileTypeAndSize', [
            'rule' => [$this, 'validateFileTypeAndSize'],
        ]);
        return $validator;
    }

    public function validateFileTypeAndSize($value, $context): true|string
    {
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5 MB in bytes

        $errorMessages = [];
        foreach ($value as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($file->getSize() > $maxSize) {
                $errorMessages[] = $file->getClientFilename() . ': Datei muss weniger als 5 MB groß sein.';
            }

            if (!in_array($file->getClientMediaType(), $allowedMimeTypes)) {
                $errorMessages[] = $file->getClientFilename() . ': Nur PDF, JPG und PNG-Dateien sind erlaubt.';
            }

        }

        if (!empty($errorMessages)) {
            return implode(' / ', $errorMessages);
        }
        return true;
    }

    public function getUnprivatizedFundingWithAllAssociations($fundingUid): Funding
    {
        $funding = $this->find(contain: [
            'Workshops',
            'OwnerUsers',
            'Fundingsupporters',
            'Fundingdatas',
            'Fundingbudgetplans',
            'FundinguploadsActivityProofs',
            'FundinguploadsFreistellungsbescheids',
            'FundinguploadsZuwendungsbestaetigungs',
            'FundinguploadsPrMaterials',
            'Fundingusageproofs',
            'Fundingreceiptlists',
        ])->where([
            $this->aliasField('uid') => $fundingUid,
        ])->first();
        $funding->owner_user->revertPrivatizeData();
        return $funding;
    }

    public function deleteCustom($fundingUid): void
    {

        $funding = $this->find()->where([
            $this->aliasField('uid') => $fundingUid,
        ])->contain(['Fundingsupporters', 'Fundingdatas'])->first();

        if (empty($funding)) {
            throw new NotFoundException('funding (UID: '.$fundingUid.') was not found');
        }
        if ($funding->is_submitted) {
            throw new NotFoundException('funding (UID: '.$fundingUid.') is submitted and cannot be deleted');
        }
        
        $this->delete($funding);

        $fundingsupportersTable = TableRegistry::getTableLocator()->get('Fundingsupporters');
        $fundingsupportersTable->delete($funding->fundingsupporter);

        $fundingdatasTable = TableRegistry::getTableLocator()->get('Fundingdatas');
        $fundingdatasTable->delete($funding->fundingdata);

        // fundinguploads and fundingbudgetplans are deleted automatically by dependent option

        $filePath = Fundingupload::UPLOAD_PATH . $funding->uid;
        FolderService::deleteFolder($filePath);

    }

    public function findWithUsageproofAssociations($fundingUid): Funding
    {
        $associations = [
            'Fundingusageproofs',
            'Fundingreceiptlists',
            'Fundingbudgetplans',
            'FundinguploadsPrMaterials',
        ];

        $funding = $this->find()->where([
            $this->aliasField('uid') => $fundingUid,
        ])
        ->contain($associations)
        ->first();

        return $funding;
    }

    public function findOrCreateUsageproof($fundingUid): Funding
    {
        $funding = $this->findWithUsageproofAssociations($fundingUid);
        
        if (empty($funding->fundingusageproof_id)) {
            $associations = ['Fundingusageproofs', 'Fundingreceiptlists'];
            $fundingusageproofsTable = TableRegistry::getTableLocator()->get('Fundingusageproofs');
            $fundingusageproofEntity = $fundingusageproofsTable->newEmptyEntity();
            $fundingusageproofEntity->main_description = '';
            $fundingusageproofEntity->difference_declaration = '';
            $fundingusageproofEntity = $fundingusageproofsTable->save($fundingusageproofEntity);

            $funding->fundingusageproof_id = $fundingusageproofEntity->id;
            $funding = $this->save($funding, ['associated' => $associations]);

            $fundingreceiptlistsTable = TableRegistry::getTableLocator()->get('Fundingreceiptlists');
            $i = 0;
            while($i < self::FUNDINGRECEIPTLISTS_COUNT_INITIAL) {
                $fundingreceiptlistEntity = $fundingreceiptlistsTable->createNewUnvalidatedEmptyEntity($fundingUid);
                $fundingreceiptlistEntity = $fundingreceiptlistsTable->save($fundingreceiptlistEntity);
                $i++;
            }

        }

        $funding = $this->findWithUsageproofAssociations($fundingUid);
        return $funding;

    }

    public function findOrCreateCustom($workshopUid): Funding
    {

        $funding = $this->find()->where([
            $this->aliasField('workshop_uid') => $workshopUid,
            $this->aliasField('owner') => Router::getRequest()?->getAttribute('identity')?->uid,
        ])->first();

        if (empty($funding)) {

            $fundingsupportersTable = TableRegistry::getTableLocator()->get('Fundingsupporters');
            $fundingsupporterEntity = $fundingsupportersTable->newEmptyEntity();
            $fundingsupporterEntity->name = '';
            $fundingsupporterEntity = $fundingsupportersTable->save($fundingsupporterEntity);
            
            $fundingdatasTable = TableRegistry::getTableLocator()->get('Fundingdatas');
            $fundingdataEntity = $fundingdatasTable->newEmptyEntity();
            $fundingdataEntity->description = '';
            $fundingdataEntity = $fundingdatasTable->save($fundingdataEntity);

            $associations = ['Fundingsupporters', 'Fundingdatas'];
            $newEntity = $this->newEntity([
                'workshop_uid' => $workshopUid,
                'status' => APP_ON,
                'owner' => Router::getRequest()?->getAttribute('identity')?->uid,
                'fundingdata_id' => $fundingdataEntity->id,
                'fundingsupporter_id' => $fundingsupporterEntity->id,
            ]);
            $funding = $this->save($newEntity, ['associated' => $associations]);
        }

        $workshopsTable = TableRegistry::getTableLocator()->get('Workshops');
        $funding = $this->find()->where([
            $this->aliasField('uid') => $funding->uid,
            $this->aliasField('owner') => Router::getRequest()?->getAttribute('identity')?->uid,
        ])->contain([
            'Workshops' => $workshopsTable->getFundingContain(),
            'OwnerUsers',
            'Fundingbudgetplans',
            'Fundingdatas',
            'Fundingsupporters',
            'FundinguploadsActivityProofs',
            'FundinguploadsFreistellungsbescheids',
            'FundinguploadsZuwendungsbestaetigungs',
        ])->first();

        if (empty($funding->fundingbudgetplans)) {
            $fundingbudgetplansTable = TableRegistry::getTableLocator()->get('Fundingbudgetplans');
            $fundingbudgetplanEntities = [];
            for ($i = 0; $i < self::FUNDINGBUDGETPLANS_COUNT; $i++) {
                $fundingbudgetplanEntity = $fundingbudgetplansTable->newEmptyEntity();
                $fundingbudgetplanEntity->funding_uid = $funding->uid;
                $fundingbudgetplanEntity = $fundingbudgetplansTable->save($fundingbudgetplanEntity, ['validate' => false]);
                $fundingbudgetplanEntities[] = $fundingbudgetplanEntity;
            }
            $funding->fundingbudgetplans = $fundingbudgetplanEntities;
        }

        if (!empty($funding)) {
            $funding->owner_user->revertPrivatizeData();
        }
        return $funding;
    }

}

?>