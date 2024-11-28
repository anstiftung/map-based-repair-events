<?php
namespace Admin\Controller;

use Cake\Event\EventInterface;

class QueuedJobsController extends AdminAppController
{

    public function beforeFilter(EventInterface $event)
    {

        $this->searchUid = false;
        $this->searchText = false;
        $this->searchName = false;
        $this->searchStatus = false;
        
        $this->addSearchOptions([
            'QueuedJobs.data' => [
                'name' => 'QueuedJobs.data',
                'searchType' => 'search'
            ],
        ]);
        
        parent::beforeFilter($event);
    }

    public function index()
    {
        parent::index();
        $queuedJobsTable = $this->getTableLocator()->get('QueuedJobs');
        
        $query = $queuedJobsTable->find(conditions: $this->conditions);

        $objects = $this->paginate($query, ['order' => [
            $queuedJobsTable->aliasField('created') => 'DESC',
        ]]);
        $this->set('objects', $objects);

    }

}
?>