<?php
declare(strict_types=1);
namespace Admin\Controller;

use Cake\Http\Exception\NotFoundException;
use App\Model\Table\BrandsTable;

class BrandsController extends AdminAppController
{

    public BrandsTable $Brand;
    
    public function __construct($request = null, $response = null)
    {
        parent::__construct($request, $response);
        $this->Brand = $this->getTableLocator()->get('Brands');
    }

    public function insert(): void
    {
        $brand = [
            'name' => 'Neue Marke',
            'owner' => $this->isLoggedIn() ? $this->loggedUser->uid : 0,
            'status' => APP_OFF
        ];
        $entity = $this->Brand->newEntity($brand);
        $brand = $this->Brand->save($entity);
        $this->AppFlash->setFlashMessage('Marke erfolgreich erstellt.');
        $this->redirect($this->getReferer());
    }

    public function edit($id): void
    {

        if (empty($id)) {
            throw new NotFoundException;
        }

        $brand = $this->Brand->find('all', conditions: [
            'Brands.id' => $id,
            'Brands.status >= ' . APP_DELETED
        ])->first();

        if (empty($brand)) {
            throw new NotFoundException;
        }

        $this->set('id', $brand->id);

        $this->setReferer();

        if (!empty($this->request->getData())) {

            $patchedEntity = $this->Brand->patchEntity(
                $brand,
                $this->request->getData(),
                ['validate' => true]
            );

            if (!($patchedEntity->hasErrors())) {
                $this->saveObject($patchedEntity);
            } else {
                $brand = $patchedEntity;
            }
        }

        $this->set('brand', $brand);

        $metaTags = ['title' => 'Marke bearbeiten'];
        $this->set('metaTags', $metaTags);

    }

    public function index(): void
    {
        parent::index();

        $conditions = [
            'Brands.status > ' . APP_DELETED
        ];
        $conditions = array_merge($this->conditions, $conditions);

        $query = $this->Brand->find('all',
        conditions: $conditions,
        contain: [
            'OwnerUsers'
        ]);
        $objects = $this->paginate($query, [
            'order' => [
                'Brands.name' => 'ASC'
            ]
        ]);

        foreach($objects as $object) {
            if ($object->owner_user) {
                $object->owner_user->revertPrivatizeData();
            }
        }

        $this->set('objects', $objects);

        $metaTags = [
            'title' => 'Marken'
        ];
        $this->set('metaTags', $metaTags);

    }

}