<?php
declare(strict_types=1);

namespace App\Controller\Traits\Fundings;

use Cake\Core\Configure;

trait IndexTrait {

    public function index(): void
    {

        if (Configure::read('debug') && $this->isAdmin()) {
            ini_set('memory_limit', '712M');
        }

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
            if (!empty($workshop->workshop_funding->owner_user)) {
                $workshop->workshop_funding->owner_user->revertPrivatizeData();
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
}
