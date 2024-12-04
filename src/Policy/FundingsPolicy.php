<?php
declare(strict_types=1);

namespace App\Policy;

use AssetCompress\Factory;
use Cake\Http\ServerRequest;
use Authorization\Policy\RequestPolicyInterface;
use Authorization\Policy\ResultInterface;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class FundingsPolicy implements RequestPolicyInterface
{

    public function canAccess($identity, ServerRequest $request): bool|ResultInterface
    {

        if (Configure::read('AppConfig.fundingsEnabled') === false) {
            return false;
        }

        if (is_null($identity)) {
            return false;
        }

        if (!($identity->isAdmin() || $identity->isOrga())) {
            return false;
        }

        $fundingsTable = FactoryLocator::get('Table')->get('Fundings');

        if (in_array($request->getParam('action'), ['uploadDetail', 'download', 'uploadZb'])) {

            if  ($identity->isAdmin()) {
                return true;
            }
            
            $fundingUid = (int) $request->getParam('uid');
            $entity = $fundingsTable->find()->where([
                $fundingsTable->aliasField('uid') => $fundingUid,
                $fundingsTable->aliasField('owner') => $identity->uid,
            ])->first();

            if (empty($entity)) {
                return false;
            }

            return true;

        }

        if (in_array($request->getParam('action'), ['delete'])) {
            $fundingUid = (int) $request->getParam('uid');
            $entity = $fundingsTable->find()->where([
                $fundingsTable->aliasField('uid') => $fundingUid,
                $fundingsTable->aliasField('owner') => $identity->uid,
            ])->first();

            if (empty($entity)) {
                return false;
            }

            if ($entity->is_submitted) {
                return false;
            }

            return true;
        }

        if (in_array($request->getParam('action'), ['edit'])) {

            $workshopUid = (int) $request->getParam('uid');

            // only approved orgas are allowed to edit fundings
            $workshopsTable = FactoryLocator::get('Table')->get('Workshops');
            $workshop = $workshopsTable->getWorkshopForIsUserInOrgaTeamCheck($workshopUid);
            if ($workshopsTable->isUserInOrgaTeam($identity, $workshop)) {
                return true;
            }

            return false;
        }

        return true;

    }

}