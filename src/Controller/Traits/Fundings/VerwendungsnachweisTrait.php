<?php
declare(strict_types=1);

namespace App\Controller\Traits\Fundings;

use Cake\I18n\DateTime;
use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use App\Model\Entity\Funding;
use App\Model\Entity\Fundingupload;

trait VerwendungsnachweisTrait {

    public function verwendungsnachweis(): ?Response
    {

        $fundingUid = (int) $this->getRequest()->getParam('uid');
        
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding = $fundingsTable->find()->where([
            $fundingsTable->aliasField('uid') => $fundingUid,
        ])->first();

        if (empty($funding)) {
            throw new NotFoundException;
        }

        if (!$funding->is_submitted || !($funding->is_money_transferred)) {
            $this->AppFlash->setFlashError('Der Förderantrag wurde noch nicht eingereicht oder das Geld wurde noch nicht überwiesen.');
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
        }
        
        $ownerCheckResult = $this->createdByOtherOwnerCheck($funding->workshop_uid);
        if ($ownerCheckResult !== false) {
            $this->AppFlash->setFlashError('Der Förderantrag wurde von einem anderen Nutzer (' . $ownerCheckResult->name . ') erstellt, der Verwendungsnachweis kann daher nicht erstellt werden.');
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
        }

        $this->setReferer();
        $funding = $fundingsTable->findOrCreateUsageproof($fundingUid);

        if ($funding->usageproof_is_submitted) {
            $this->AppFlash->setFlashError('Der Verwendungsnachweis wurde bereits eingereicht und kann nicht mehr bearbeitet werden.');
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
        }

        if (!empty($this->request->getData())) {
            $associations = ['Fundingusageproofs', 'Fundingreceiptlists', 'FundinguploadsPrMaterials'];

            $associationsWithoutValidation = $this->removeValidationFromAssociations($associations);

            if ($this->request->getData('Fundings.fundingusageproof.checkbox_a') == 0) {
                $this->request = $this->request->withData('Fundings.fundingusageproof.difference_declaration', '');
            }

            $patchedEntity = $this->patchFunding($funding, $associationsWithoutValidation);
            $patchedEntity->modified = DateTime::now();
            $patchedEntity->usageproof_status = Funding::STATUS_PENDING;
            $fundingsTable->save($patchedEntity);

            $fundingusageproofsTable = $this->getTableLocator()->get('Fundingusageproofs');
            $fundingusageproofsTable->save($patchedEntity->fundingusageproof);

            $fundingreceiptlistsTable = $this->getTableLocator()->get('Fundingreceiptlists');

            // DELETE fundingreceiptlists
            $flashMessages = ['Der Verwendungsnachweis wurde erfolgreich zwischengespeichert.'];
            $deletedCount = 0;
            foreach($patchedEntity->fundingreceiptlists as $index => $fundingreceiptlist) {
                if ($fundingreceiptlist->delete) {
                    $deletedCount++;
                    $fundingreceiptlistsTable->delete($fundingreceiptlist);
                    unset($patchedEntity->fundingreceiptlists[$index]);
                    $this->request = $this->request->withoutData('Fundings.fundingreceiptlists.' . $index);
                }
            }

            // remove all invalid fundingreceiptlists in order to avoid saving nothing
            foreach($patchedEntity->fundingreceiptlists as $index => $fundingreceiptlist) {
                if ($fundingreceiptlist->hasErrors()) {
                    unset($patchedEntity->fundingreceiptlists[$index]);
                }
            }
            $fundingreceiptlistsTable->saveMany($patchedEntity->fundingreceiptlists);

            if ($deletedCount > 0) {
                $flashMessages[] = $deletedCount . ' Beleg(e) wurde(n) erfolgreich gelöscht.';
                $this->AppFlash->setFlashMessage(join('<br />', $flashMessages));
                return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($funding->uid));
            }

            // ADD fundingreceiptlist
            if (!empty($this->request->getData('add_receiptlist'))) {
                $newFundingreceiptlistEntity = $fundingreceiptlistsTable->createNewUnvalidatedEmptyEntity($funding->uid);
                $fundingreceiptlistsTable->save($newFundingreceiptlistEntity);
                $flashMessages[] = 'Ein Beleg wurde erfolgreich hinzugefügt.';
                $this->AppFlash->setFlashMessage(join('<br />', $flashMessages));
                return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundingsUsageproof($funding->uid));
            }

            // START uploads
            $patchedEntity = $this->patchFunding($funding, $associations);
            $newFundinguploads = $this->handleNewFundinguploads($funding, $associations, $patchedEntity, Fundingupload::TYPE_MAP_STEP_3);
            $this->handleDeleteFundinguploads($funding, $associations, $patchedEntity, $newFundinguploads, Fundingupload::TYPE_MAP_STEP_3);
            $fundinguploadsTable = $this->getTableLocator()->get('Fundinguploads');
            $fundinguploadsTable->saveMany($patchedEntity->fundinguploads_pr_materials);
            $patchedEntity = $this->handleUpdateNewFundinguploadsWithIds($funding, $associations, $patchedEntity, Fundingupload::TYPE_MAP_STEP_3);
            // END uploads

            $this->AppFlash->setFlashMessage(join('<br />', $flashMessages));
            $funding = $this->patchFunding($funding, $associations);

            if ($funding->usageproof_is_submittable && !empty($this->request->getData('submit_usageproof'))) {
                $this->submitUsageproof($funding);
                $this->AppFlash->setFlashMessage('Der Verwendungsnachweis wurde erfolgreich eingereicht.<br />Die Bestätigung eines Admins ist noch ausstehend.');
                return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFundings());
            }

        }

        $this->set('metaTags', [
            'title' => 'Verwendungsnachweis für Förderantrag (UID: ' . $funding->uid . ')',
        ]);
        $this->set('funding', $funding);

        return null;
    }

    private function submitUsageproof($funding): void
    {
        $timestamp = DateTime::now();
        $fundingsTable = $this->getTableLocator()->get('Fundings');
        $funding->usageproof_submit_date = $timestamp;;
        $funding->usageproof_status = Funding::STATUS_PENDING;
        $fundingsTable->save($funding);
    }
}
