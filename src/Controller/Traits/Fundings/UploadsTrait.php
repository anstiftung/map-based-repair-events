<?php
declare(strict_types=1);

namespace App\Controller\Traits\Fundings;

use App\Model\Entity\Funding;
use App\Model\Entity\Fundingupload;
use App\Controller\Component\StringComponent;

trait UploadsTrait {

    private function handleUpdateNewFundinguploadsWithIds($funding, $associations, $patchedEntity, $uploadTypes): Funding
    {
        foreach($uploadTypes as $uploadTypeId => $uploadType) {
            if (!empty($this->request->getData('Fundings.fundinguploads_' . $uploadType))) {
                // patch id for new fundinguploads
                $fundinguploadsFromDatabase = $this->getTableLocator()->get('Fundinguploads')->find()->where([
                    'Fundinguploads.funding_uid' => $funding->uid,
                ])->toArray();
                $updatedFundinguploads = [];
                foreach($this->request->getData('Fundings.fundinguploads_' . $uploadType) as $fundingupload) {
                    foreach($fundinguploadsFromDatabase as $fundinguploadFromDatabaseEntity) {
                        if ($fundingupload['filename'] == $fundinguploadFromDatabaseEntity->filename) {
                            $fundingupload['id'] = $fundinguploadFromDatabaseEntity->id;
                            $updatedFundinguploads[] = $fundingupload;
                        }
                    }
                }
                $this->request = $this->request->withData('Fundings.fundinguploads_' . $uploadType, $updatedFundinguploads);
                $patchedEntity = $this->patchFunding($funding, $associations);
            }
        }
        return $patchedEntity;
    }

    private function handleDeleteFundinguploads($funding, $associations, $patchedEntity, $newFundinguploads, $uploadTypes): void
    {

        foreach($uploadTypes as $uploadTypeId => $uploadType) {
            $deleteFundinguploads = $this->request->getData('Fundings.delete_fundinguploads_' . $uploadType);
            if (!empty($deleteFundinguploads)) {
                $remainingFundinguploads = $this->request->getData('Fundings.fundinguploads_' . $uploadType) ?? [];
                foreach($deleteFundinguploads as $fundinguploadId) {
                    $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
                    $fundingupload = $fundinguploadsTable->find()->where([
                        $fundinguploadsTable->aliasField('id') => $fundinguploadId,
                        $fundinguploadsTable->aliasField('funding_uid') => $funding->uid,
                        $fundinguploadsTable->aliasField('owner') => $this->loggedUser->uid,
                        $fundinguploadsTable->aliasField('type') => $uploadTypeId,
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
                    $this->request = $this->request->withData('Fundings.fundinguploads_' . $uploadType, $remainingFundinguploads);
                    $patchedEntity = $this->patchFunding($funding, $associations);
                }
            }

            $fundinguploadsErrors = $patchedEntity->getError('fundinguploads_' . $uploadType);
            if (!empty($fundinguploadsErrors)) {
                $patchedEntity->setError('files_fundinguploads_' . $uploadType . '[]', $fundinguploadsErrors);
            } else {
                $patchedEntity = $this->patchFundingStatusIfNewUploadWasUploadedOrDeleted($newFundinguploads[$uploadType] ?? [], $patchedEntity, $uploadType, $deleteFundinguploads);
            }

        }

    }

    private function handleNewFundinguploads($funding, $associations, $patchedEntity, $uploadTypes): array
    {
        $newFundinguploads = [];
        $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
        foreach($uploadTypes as $uploadTypeId => $uploadType) {
            $filesFundinguploadsErrors = $patchedEntity->getError('files_fundinguploads_' . $uploadType);
            if (!empty($filesFundinguploadsErrors)) {
                $patchedEntity->setError('files_fundinguploads_' . $uploadType . '[]', $filesFundinguploadsErrors);
            } else {
                $filesFileuploads = $this->request->getData('Fundings.files_fundinguploads_' . $uploadType);
                if (!empty($filesFileuploads)) {
                    $savedFundinguploads = $fundinguploadsTable->find()->where([
                        $fundinguploadsTable->aliasField('funding_uid') => $funding->uid,
                        $fundinguploadsTable->aliasField('owner') => $this->loggedUser->uid,
                        $fundinguploadsTable->aliasField('type') => $uploadTypeId,
                    ]);
                    foreach ($filesFileuploads as $fileupload) {
                        if ($fileupload->getError() !== UPLOAD_ERR_OK) {
                            continue;
                        }

                        // page reload bug fix
                        // if F5 is pressed, uploads would be saved again
                        $alreadyUploaded = false;
                        foreach($savedFundinguploads as $savedFundingupload) {
                            $hashedContentCurrentUpload = md5(file_get_contents($fileupload->getStream()->getMetadata('uri')));
                            $hashedContentSavedFundingupload = md5(file_get_contents(Fundingupload::UPLOAD_PATH . $funding->uid . DS . $savedFundingupload->filename));
                            if ($hashedContentCurrentUpload === $hashedContentSavedFundingupload) {
                                $alreadyUploaded = true;
                                continue;
                            }
                        }

                        if ($alreadyUploaded) {
                            continue;
                        }

                        $filename = $fileupload->getClientFilename();
                        $filename =  StringComponent::slugifyAndKeepCase(pathinfo($filename, PATHINFO_FILENAME)) . '_' . bin2hex(random_bytes(5)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                        $newFundinguploads[$uploadType][] = [
                            'filename' => $filename,
                            'funding_uid' => $funding->uid,
                            'type' => $uploadTypeId,
                            'owner' => $this->loggedUser->uid,
                        ];
    
                        $filePath = Fundingupload::UPLOAD_PATH . $funding->uid . DS . $filename;
                        if (!is_dir(dirname($filePath))) {
                            mkdir(dirname($filePath), 0777, true);
                        }
                        $fileupload->moveTo($filePath);
                    }
                    
                    if (!empty($newFundinguploads[$uploadType])) {
                        $this->request = $this->request->withData('Fundings.fundinguploads_' . $uploadType, array_merge($this->request->getData('Fundings.fundinguploads_' . $uploadType) ?? [], $newFundinguploads[$uploadType]));
                    }
                    $patchedEntity = $this->patchFunding($funding, $associations);
                }
            }
        }
        return $newFundinguploads;
    }

}