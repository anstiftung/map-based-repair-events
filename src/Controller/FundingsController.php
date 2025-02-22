<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use App\Model\Entity\Funding;
use Cake\Utility\Inflector;
use App\Model\Entity\Fundingupload;
use Cake\Http\Exception\NotFoundException;
use App\Controller\Traits\Fundings\EditTrait;
use App\Controller\Traits\Fundings\IndexTrait;
use App\Controller\Traits\Fundings\UploadsTrait;
use App\Controller\Traits\Fundings\VerwendungsnachweisTrait;
use Cake\I18n\DateTime;
use App\Services\PdfWriter\FoerderbewilligungPdfWriterService;
use App\Services\PdfWriter\FoerderantragPdfWriterService;
use Cake\Http\Response;
use App\Model\Entity\User;
use App\Services\PdfWriter\VerwendungsnachweisPdfWriterService;

class FundingsController extends AppController
{

    use EditTrait;
    use IndexTrait;
    use UploadsTrait;
    use VerwendungsnachweisTrait;

    private function getBasicErrorMessages($funding): array
    {
        $errors = ['Zugriff auf diese Seite nicht möglich.'];
        if (!empty($funding) && $funding->workshop->status == APP_DELETED) {
            $errors[] = 'Die Initiative ist gelöscht.';
        }
        return $errors;

    }

    private function createdByOtherOwnerCheck($workshopUid): false|User
    {
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->find()->where([
            $fundingsTable->aliasField('workshop_uid') => $workshopUid,
            'NOT' => [
                $fundingsTable->aliasField('owner') => $this->loggedUser->uid,
            ],
        ])->contain(['OwnerUsers']);
        if ($funding->count() > 0) {
            $owner = $funding->first()->owner_user;
            $owner->revertPrivatizeData();
            return $owner;
        }
        return false;
    }

    private function patchFunding($funding, $associations): Funding
    {
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $patchedEntity = $fundingsTable->patchEntity($funding, $this->request->getData(), [
            'associated' => $associations,
        ]);
        return $patchedEntity;
    }

    private function updatePrivateFieldsForFieldsThatAreNotRequiredInUserProfile($privateFields): string
    {
        $fields = ['street', 'city', 'phone'];
        $existingArray = array_map('trim', explode(',', $privateFields));
        $updatedArray = array_unique(array_merge($existingArray, $fields));
        return implode(',', $updatedArray);
    }

    private function patchFundingStatusIfNewUploadWasUploadedOrDeleted($newFundinguploads, $patchedEntity, $uploadType, $deleteFundinguploads): Funding
    {
        if (!empty($newFundinguploads) || !empty($deleteFundinguploads)) {
            $newStatus = Funding::STATUS_PENDING;
            $singularizedUploadType = Inflector::singularize($uploadType);
            if (!empty($deleteFundinguploads)) {
                $noUploadAvailable = count($patchedEntity->{'fundinguploads_' . $uploadType}) == 0;
                if ($noUploadAvailable) {
                    $newStatus = Funding::STATUS_UPLOAD_MISSING;
                }
            }
            $this->request = $this->request->withData('Fundings.' . $singularizedUploadType . '_status', $newStatus);
            $patchedEntity->{$singularizedUploadType . '_status'} = $newStatus;
        }
        return $patchedEntity;
    }

    private function updateCoordinates($entity, $index, $addressString): void
    {
        if (!$entity->use_custom_coordinates) {
            $geoData = $this->geoService->getGeoDataByAddress($addressString);
            $this->request = $this->request->withData('Fundings.'.$index.'.lat', $geoData['lat']);
            $this->request = $this->request->withData('Fundings.'.$index.'.lng', $geoData['lng']);
            $this->request = $this->request->withData('Fundings.'.$index.'.province_id', $geoData['provinceId'] ?? 0);
        }
    }

    public function delete(): void
    {
        $fundingUid = (int) $this->request->getParam('uid');
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingsTable->deleteCustom($fundingUid);
        $this->AppFlash->setFlashMessage('Der Förderantrag wurde erfolgreich gelöscht.');
        $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
    }

    private function getPatchedFundingForValidFields($errors, $workshopUid, $associationsWithoutValidation): Funding
    {
        $data = $this->request->getData();
        $verifiedFieldsWithErrors = [];
        foreach ($errors as $entity => $fieldErrors) {
            if (!in_array($entity, ['workshop', 'owner_user', 'fundingsupporter'])) {
                continue;
            }
            $fieldNames = array_keys($fieldErrors);
            foreach($fieldNames as $fieldName) {
                $verifiedFieldsWithErrors[] = Inflector::dasherize('fundings-' . $entity . '-' . $fieldName);
                unset($data['Fundings'][$entity][$fieldName]);
            }
        }
        // never save "verified" if field has error
        $verifiedFields = $data['Fundings']['verified_fields'];
        $patchedVerifiedFieldsWithoutErrorFields = array_diff($verifiedFields, $verifiedFieldsWithErrors);
        $data['Fundings']['verified_fields'] = array_values($patchedVerifiedFieldsWithoutErrorFields);

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingForSaving = $fundingsTable->findOrCreateCustom($workshopUid);
        $patchedEntity = $fundingsTable->patchEntity($fundingForSaving, $data, [
            'associated' => $associationsWithoutValidation,
        ]);
        return $patchedEntity;
    }

    private function removeValidationFromAssociations($associations): array
    {
        $result = array_map(function($association) {
            return ['validate' => false];
        }, array_flip($associations));
        // some association's data should not be saved if invalid
        foreach($result as $entity => $value) {
            if (in_array($entity, ['Fundingbudgetplans', 'Fundingreceiptlists'])) {
                $result[$entity] = ['validate' => 'default'];
            }
        }
        return $result;
    }

    public function uploadZuwendungsbestaetigung(): void
    {

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingUid = $this->getRequest()->getParam('uid');
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        if (!$funding->is_submitted) {
            throw new NotFoundException('Förderantrag (UID: '.$fundingUid.') wurde noch nicht eingereicht.');
        }

        $this->setReferer();

        if (!empty($this->request->getData())) {
            $associations = ['FundinguploadsZuwendungsbestaetigungs'];
            $patchedEntity = $this->patchFunding($funding, $associations);
            $newFundinguploads = $this->handleNewFundinguploads($funding, $associations, $patchedEntity, Fundingupload::TYPE_MAP_STEP_2);
            $this->handleDeleteFundinguploads($funding, $associations, $patchedEntity, $newFundinguploads, Fundingupload::TYPE_MAP_STEP_2);

            $patchedEntity->modified = DateTime::now();
            $fundingsTable->save($patchedEntity);
            $this->AppFlash->setFlashMessage('Die Zuwendungsbestägigung wurde erfolgreich gespeichert.');
            $patchedEntity = $this->handleUpdateNewFundinguploadsWithIds($funding, $associations, $patchedEntity, Fundingupload::TYPE_MAP_STEP_2);

        }

        $this->set('metaTags', [
            'title' => 'Upload Zuwendungsbestätigung zu Förderantrag UID: ' . $funding->uid . ')',
        ]);
        $this->set('funding', $funding);

    }

    public function download(): Response
    {

        $fundingUid = $this->getRequest()->getParam('uid');
        $type = $this->getRequest()->getParam('type');

        $databaseField = 'submit_date';
        if ($type == 'foerderantrag') {
            $pdfWriterService = new FoerderantragPdfWriterService();
        }
        if ($type == 'foerderbewilligung') {
            $pdfWriterService = new FoerderbewilligungPdfWriterService();
        }
        if ($type == 'verwendungsnachweis') {
            $pdfWriterService = new VerwendungsnachweisPdfWriterService();
            $databaseField = 'usageproof_submit_date';
        }

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->find()->where([
            $fundingsTable->aliasField('uid') => $fundingUid,
            $fundingsTable->aliasField($databaseField . ' IS NOT NULL'),
        ])->first();

        if (empty($funding)) {
            throw new NotFoundException;
        }

        if (isset($pdfWriterService)) {
            $filename = $pdfWriterService->getFilenameCustom($funding, $funding->{$databaseField});
            $filenameWithPath = $pdfWriterService->getUploadPath($funding->uid) . $filename;

            $response = $this->response->withFile($filenameWithPath);
            $response = $response->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
            return $response;
        }
        
        throw new NotFoundException;

    }

    public function uploadDetail(): Response
    {

        $fundinguploadUid = $this->getRequest()->getParam('uid');
        $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
        $fundingupload = $fundinguploadsTable->find('all',
        conditions: [
            $fundinguploadsTable->aliasField('id') => $fundinguploadUid,
        ])->first();

        if (empty($fundingupload)) {
            throw new NotFoundException;
        }

        $response = $this->response->withFile($fundingupload->full_path);
        $response = $response->withHeader('Content-Disposition', 'inline; filename="' . $fundingupload->filename . '"');
        return $response;

    }

}