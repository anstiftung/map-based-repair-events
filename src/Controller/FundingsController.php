<?php

namespace App\Controller;

use Cake\Core\Configure;
use App\Model\Table\WorkshopsTable;
use Cake\Database\Query;

class FundingsController extends AppController
{

    private function getContain() {
        return [
            'FundingAllPastEvents' => function (Query $q) {
                return $q->select(['workshop_uid', 'count' => $q->func()->count('*')])->groupBy('workshop_uid');
            },
            'FundingAllFutureEvents' => function (Query $q) {
                return $q->select(['workshop_uid', 'count' => $q->func()->count('*')])->groupBy('workshop_uid');
            }
        ];
    }

    public function index() {

        $this->set('metaTags', [
            'title' => 'Förderantrag',
        ]);

        $workshopsTable = $this->getTableLocator()->get('Workshops');
        if ($this->isAdmin()) {
            $workshops = $workshopsTable->getWorkshopsWithUsers(APP_OFF, $this->getContain());
        } else {
            $workshops = $workshopsTable->getWorkshopsForAssociatedUser($this->loggedUser->uid, APP_OFF, $this->getContain());
        }

        $workshopsWithFundingAllowed = [];
        $workshopsWithFundingNotAllowed = [];
        if ($this->isAdmin()) {
            foreach ($workshops as $workshop) {
                if ($workshop->funding_is_allowed) {
                    $workshopsWithFundingAllowed[] = $workshop;
                } else {
                    $workshopsWithFundingNotAllowed[] = $workshop;
                }
            }
        }

        unset($workshops);

        $this->set([
            'workshopsWithFundingAllowed' => $workshopsWithFundingAllowed,
            'workshopsWithFundingNotAllowed' => $workshopsWithFundingNotAllowed,
        ]);

    }

    public function edit() {

        $workshopUid = (int) $this->getRequest()->getParam('workshopUid');
        $workshopsTable = $this->getTableLocator()->get('Workshops');

        $workshop = $workshopsTable->find()->where([
            $workshopsTable->aliasField('uid') => $workshopUid,
            $workshopsTable->aliasField('status') => APP_ON,
        ])
        ->contain($this->getContain())
        ->first();

        $this->setReferer();

        if (!$workshop->funding_is_allowed) {
            $this->AppFlash->setFlashError('Förderantrag für diese Initiative nicht möglich.');
            return $this->redirect(Configure::read('AppConfig.htmlHelper')->urlFunding());
        }

        if (!empty($this->request->getData())) {

            $patchedEntity = $workshopsTable->getPatchedEntityForAdminEdit($workshop, $this->request->getData());
            $errors = $patchedEntity->getErrors();

            if (empty($errors)) {
                $entity = $this->stripTagsFromFields($patchedEntity, 'Workshop');
                if ($workshopsTable->save($entity)) {
                    $this->AppFlash->setFlashMessage('Förderantrag erfolgreich gespeichert.');
                }
            }

        }

        $this->set('metaTags', [
            'title' => 'Förderantrag für "' . h($workshop->name) . '"',
        ]);
        $this->set('workshop', $workshop);

    }

}