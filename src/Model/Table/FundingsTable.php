<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Routing\Router;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\FactoryLocator;

class FundingsTable extends Table
{

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('Workshops', [
            'foreignKey' => 'workshop_uid'
        ]);
        $this->belongsTo('OwnerUsers', [
            'className' => 'Users',
            'foreignKey' => 'owner'
        ]);
        $this->belongsTo('Supporters', [
            'foreignKey' => 'supporter_id'
        ]);
    }

    public function getSchema(): TableSchemaInterface
    {
        return parent::getSchema()->setColumnType('verified_fields', 'json');
    }

    public function findOrCreateCustom($workshopUid) {

        $funding = $this->find()->where([
            $this->aliasField('workshop_uid') => $workshopUid,
            $this->aliasField('owner') => Router::getRequest()?->getAttribute('identity')?->uid,
        ])->first();

        if (empty($funding)) {
            $supportersTable = FactoryLocator::get('Table')->get('Supporters');
            $supporterEntity = $supportersTable->newEmptyEntity();
            $supporterEntity->name = '';
            $supporter = $supportersTable->save($supporterEntity);
            $associations = ['Supporters'];
            $newEntity = $this->newEntity([
                'workshop_uid' => $workshopUid,
                'status' => APP_ON,
                'owner' => Router::getRequest()?->getAttribute('identity')?->uid,
                'supporter_id' => $supporter->id,
            ]);
            $funding = $this->save($newEntity, ['associated' => $associations]);
        }

        $funding = $this->find()->where([
            $this->aliasField('id') => $funding->id,
            $this->aliasField('owner') => Router::getRequest()?->getAttribute('identity')?->uid,
        ])->contain([
            'Workshops.Countries',
            'OwnerUsers.Countries',
            'Supporters',
        ])->first();

        if (!empty($funding)) {
            $funding->owner_user->revertPrivatizeData();
        }
        return $funding;
    }

}

?>