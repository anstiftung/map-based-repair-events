<?php

namespace App\Controller;

use Cake\Core\Configure;
use App\Model\Entity\Funding;
use Cake\Utility\Inflector;
use App\Model\Entity\Fundingupload;
use Cake\Http\Exception\NotFoundException;
use App\Controller\Component\StringComponent;

class FundingsController extends AppController
{

    public function index() {

        $this->set('metaTags', [
            'title' => 'Förderantrag',
        ]);

        $workshopsTable = $this->getTableLocator()->get('Workshops');
        if ($this->isAdmin()) {
            $workshops = $workshopsTable->getWorkshopsWithUsers(APP_OFF, $workshopsTable->getFundingContain());
        } else {
            $workshops = $workshopsTable->getWorkshopsForAssociatedUser($this->loggedUser->uid, APP_OFF, $workshopsTable->getFundingContain());
        }

        foreach ($workshops as $workshop) {
            $workshop->funding_exists = !empty($workshop->workshop_funding);
            $workshop->funding_created_by_different_owner = $workshop->funding_exists && $workshop->workshop_funding->owner != $this->loggedUser->uid;
            if (!empty($workshop->owner_user)) {
                $workshop->owner_user = $workshop->ower_user->revertPrivatizeData();
            }
            $orgaTeam = $workshopsTable->getOrgaTeam($workshop);
            $orgaTeamReverted = [];
            if (!empty($orgaTeam)) {
                foreach($orgaTeam as $orgaUser) {
                    $orgaUser->revertPrivatizeData();
                    $orgaTeamReverted[] = $orgaUser;
                }
            }
            $workshop->orga_team = $orgaTeamReverted;
        }

        $workshopsWithFundingAllowed = [];
        $workshopsWithFundingNotAllowed = [];
        foreach ($workshops as $workshop) {
            if ($workshop->funding_is_allowed) {
                $workshopsWithFundingAllowed[] = $workshop;
            } else {
                $workshopsWithFundingNotAllowed[] = $workshop;
            }
        }
        unset($workshops);

        $this->set([
            'workshopsWithFundingAllowed' => $workshopsWithFundingAllowed,
            'workshopsWithFundingNotAllowed' => $workshopsWithFundingNotAllowed,
        ]);

    }

    private function getBasicErrorMessages($funding): array {
        $errors = ['Zugriff auf diese Seite nicht möglich.'];
        if (!empty($funding) && $funding->workshop->status == APP_DELETED) {
            $errors[] = 'Die Initiative ist gelöscht.';
        }
        return $errors;

    }

    private function createdByOtherOwnerCheck($workshopUid) {
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
            return 'Der Förderantrag wurde bereits von einem anderen Nutzer (' . $owner->name . ') erstellt.';
        }
        return '';
    }

    public function edit() {

        $workshopUid = (int) $this->getRequest()->getParam('workshopUid');

        $createdByOtherOwnerCheckMessage = $this->createdByOtherOwnerCheck($workshopUid);
        if ($createdByOtherOwnerCheckMessage != '') {
            $this->AppFlash->setFlashError($createdByOtherOwnerCheckMessage);
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
        }

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->findOrCreateCustom($workshopUid);

        $this->setReferer();

        $basicErrors = $this->getBasicErrorMessages($funding);
        if (!$funding->workshop->funding_is_allowed) {
            $basicErrors[] = 'Die Initiative erfüllt die Voraussetzungen für eine Förderung nicht.';
        }

        if (count($basicErrors) > 1) {
            $this->AppFlash->setFlashError(implode(' ', $basicErrors));
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
        }

        if (!empty($this->request->getData())) {

            $associations = ['Workshops', 'OwnerUsers', 'Fundingdatas', 'Fundingsupporters', 'FundinguploadsActivityProofs', 'FundinguploadsFreistellungsbescheids', 'Fundingbudgetplans'];
            $associationsWithoutValidation = $this->removeValidationFromAssociations($associations);
            $singularizedAssociations = array_map(function($association) {
                return Inflector::singularize(Inflector::tableize($association));
            }, $associations);
            $associations['OwnerUsers'] = ['validate' => 'funding'];

            foreach($singularizedAssociations as $association) {
                $dataKey = 'Fundings.'.$association;
                if (in_array($dataKey, ['Fundings.workshop', 'Fundings.owner_user'])) {
                    // cleaning cannot be done in entity because of allowedBasicHtmlFields
                    foreach ($this->request->getData($dataKey) as $field => $value) {
                        $cleanedValue = strip_tags($value);
                        $this->request = $this->request->withData($dataKey . '.' . $field, $cleanedValue);
                    }
                }
            }

            if (!array_key_exists('verified_fields', $this->request->getData('Fundings'))) {
                $this->request = $this->request->withData('Fundings.verified_fields', []);
            }

            $addressStringOwnerUser = $this->request->getData('Fundings.owner_user.zip') . ' ' . $this->request->getData('Fundings.owner_user.city') . ', ' . $this->request->getData('Fundings.owner_user.country_code');
            $this->updateCoordinates($funding->owner_user, 'owner_user', $addressStringOwnerUser);

            $addressStringWorkshop = $this->request->getData('Fundings.workshop.street') . ', ' . $this->request->getData('Fundings.workshop.zip') . ' ' . $this->request->getData('Fundings.workshop.city') . ', ' . $this->request->getData('Fundings.workshop.country_code');
            $this->updateCoordinates($funding->workshop, 'workshop', $addressStringWorkshop);

            $patchedEntity = $this->patchFunding($funding, $associations);
            $errors = $patchedEntity->getErrors();
            $filesFundinguploadsErrors = $patchedEntity->getError('files_fundinguploads_activity_proofs');
            $newFundinguploads = [];
            if (!empty($filesFundinguploadsErrors)) {
                $patchedEntity->setError('files_fundinguploads_activity_proofs[]', $filesFundinguploadsErrors);
            } else {
                $filesFileuploads = $this->request->getData('Fundings.files_fundinguploads_activity_proofs');
                if (!empty($filesFileuploads)) {
                    foreach ($filesFileuploads as $fileupload) {
                        if ($fileupload->getError() !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $filename = $fileupload->getClientFilename();
                        $filename =  StringComponent::slugifyAndKeepCase(pathinfo($filename, PATHINFO_FILENAME)) . '_' . bin2hex(random_bytes(5)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                        $newFundinguploads[] = [
                            'filename' => $filename,
                            'funding_uid' => $funding->uid,
                            'type' => Fundingupload::TYPE_ACTIVITY_PROOF,
                            'owner' => $this->loggedUser->uid,
                            'status' => Funding::STATUS_PENDING,
                        ];
                        $filePath = Fundingupload::UPLOAD_PATH . $funding->uid . DS . $filename;
                        if (!is_dir(dirname($filePath))) {
                            mkdir(dirname($filePath), 0777, true);
                        }
                        $fileupload->moveTo($filePath);
                    }
                    
                    $this->request = $this->request->withData('Fundings.fundinguploads_activity_proofs', array_merge($this->request->getData('Fundings.fundinguploads_activity_proofs') ?? [], $newFundinguploads));
                    $patchedEntity = $this->patchFunding($funding, $associations);
                }
            }

            $deleteFundinguploads = $this->request->getData('Fundings.delete_fundinguploads_activity_proofs');
            if (!empty($deleteFundinguploads)) {
                $remainingFundinguploads = $this->request->getData('Fundings.fundinguploads_activity_proofs') ?? [];
                foreach($deleteFundinguploads as $fundinguploadId) {
                    $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
                    $fundingupload = $fundinguploadsTable->find()->where([
                        $fundinguploadsTable->aliasField('id') => $fundinguploadId,
                        $fundinguploadsTable->aliasField('funding_uid') => $funding->uid,
                        $fundinguploadsTable->aliasField('owner') => $this->loggedUser->uid,
                        ])->first();
                    if (!empty($fundingupload)) {
                        $fundinguploadsTable->delete($fundingupload);
                        if (file_exists($fundingupload->full_path)) {
                            unlink($fundingupload->full_path);
                        }
                        $remainingFundinguploads = array_filter($remainingFundinguploads, function($fundingupload) use ($fundinguploadId) {
                            return $fundingupload['id'] != $fundinguploadId;
                        });
                    }
                    $this->request = $this->request->withData('Fundings.fundinguploads_activity_proofs' ?? [], $remainingFundinguploads);
                    $patchedEntity = $this->patchFunding($funding, $associations);
                }
            }

            $fundinguploadsErrors = $patchedEntity->getError('fundinguploads_activity_proofs');
            if (!empty($fundinguploadsErrors)) {
                $patchedEntity->setError('files_fundinguploads_activity_proofs[]', $fundinguploadsErrors);
            }

            if (!empty($errors)) {
                $patchedEntity = $this->getPatchedFundingForValidFields($errors, $workshopUid, $associationsWithoutValidation);
            }
            $patchedEntity = $this->patchFundingStatusIfActivityProofWasUploaded($newFundinguploads, $patchedEntity);

            // remove all invalid fundingbudgetplans in order to avoid saving nothing
            foreach($patchedEntity->fundingbudgetplans as $index => $fundingbudgetplan) {
                if ($fundingbudgetplan->hasErrors()) {
                    unset($patchedEntity->fundingbudgetplans[$index]);
                }
            }

            $patchedEntity->owner_user->private = $this->updatePrivateFieldsForFieldsThatAreNotRequiredInUserProfile($patchedEntity->owner_user->private);
            $fundingsTable->save($patchedEntity, ['associated' => $associationsWithoutValidation]);
            $this->AppFlash->setFlashMessage('Der Förderantrag wurde erfolgreich zwischengespeichert.');

            if (!empty($this->request->getData('Fundings.fundinguploads_activity_proofs'))) {
                // patch id for new fundinguploads
                $fundinguploadsFromDatabase = $this->getTableLocator()->get('Fundinguploads')->find()->where([
                    'Fundinguploads.funding_uid' => $funding->uid,
                ])->toArray();
                $updatedFundinguploads = [];
                foreach($this->request->getData('Fundings.fundinguploads_activity_proofs') as $fundingupload) {
                    foreach($fundinguploadsFromDatabase as $fundinguploadFromDatabaseEntity) {
                        if ($fundingupload['filename'] == $fundinguploadFromDatabaseEntity->filename) {
                            $fundingupload['id'] = $fundinguploadFromDatabaseEntity->id;
                            $updatedFundinguploads[] = $fundingupload;
                        }
                    }
                }
                $this->request = $this->request->withData('Fundings.fundinguploads_activity_proofs' ?? [], $updatedFundinguploads);
                $patchedEntity = $this->patchFunding($funding, $associations);
            }
        }

        $this->set('metaTags', [
            'title' => 'Förderantrag (UID: ' . $funding->uid . ')',
        ]);
        $this->set('funding', $funding);

    }

    private function patchFunding($funding, $associations) {
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $patchedEntity = $fundingsTable->patchEntity($funding, $this->request->getData(), [
            'associated' => $associations,
        ]);
        return $patchedEntity;
    }

    private function updatePrivateFieldsForFieldsThatAreNotRequiredInUserProfile($privateFields) {
        $fields = ['street', 'city', 'phone'];
        $existingArray = array_map('trim', explode(',', $privateFields));
        $updatedArray = array_unique(array_merge($existingArray, $fields));
        return implode(',', $updatedArray);
    }

    private function patchFundingStatusIfActivityProofWasUploaded($newFundinguploads, $patchedEntity) {
        $errors = $patchedEntity->getErrors('files_fundinguploads_activity_proofs') + $patchedEntity->getErrors('fundinguploads_activity_proofs');
        if (empty($errors) && !empty($newFundinguploads)) {
            $newStatus = Funding::STATUS_PENDING;
            $this->request = $this->request->withData('Fundings.activity_proof_status', $newStatus);
            $patchedEntity->activity_proof_status = $newStatus;
        }
        return $patchedEntity;
    }

    private function updateCoordinates($entity, $index, $addressString) {
        if ($entity->use_custom_coordinates) {
            return false;
        }
        $geoData = $this->geoService->getGeoDataByAddress($addressString);
        $this->request = $this->request->withData('Fundings.'.$index.'.lat', $geoData['lat']);
        $this->request = $this->request->withData('Fundings.'.$index.'.lng', $geoData['lng']);
        $this->request = $this->request->withData('Fundings.'.$index.'.province_id', $geoData['provinceId'] ?? 0);
    }

    public function delete()
    {
        $fundingUid = (int) $this->request->getParam('fundingUid');
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingsTable->deleteCustom($fundingUid);
        $this->AppFlash->setFlashMessage('Der Förderantrag wurde erfolgreich gelöscht.');
        $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
    }

    private function getPatchedFundingForValidFields($errors, $workshopUid, $associationsWithoutValidation) {
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
        $data['Fundings']['verified_fields'] = $patchedVerifiedFieldsWithoutErrorFields;

        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $fundingForSaving = $fundingsTable->findOrCreateCustom($workshopUid);
        $patchedEntity = $fundingsTable->patchEntity($fundingForSaving, $data, [
            'associated' => $associationsWithoutValidation,
        ]);
        return $patchedEntity;
    }

    private function removeValidationFromAssociations($associations) {

        $result = array_map(function($association) {
            return ['validate' => false];
        }, array_flip($associations));

        // some association's data should not be saved if invalid
        foreach($result as $entity => $value) {
            if (in_array($entity, ['Fundingbudgetplans'])) {
                $result[$entity] = ['validate' => 'default'];
            }
        }

        return $result;

    }


    public function uploadDetail() {

        $fundinguploadUid = $this->getRequest()->getParam('fundinguploadId');
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