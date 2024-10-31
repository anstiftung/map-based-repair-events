<?php
echo $this->element('list',
    [
        'objects' => $objects,
        'heading' => 'Förderanträge',
        'editMethod' => ['url' => 'urlFundingsAdminEdit'],
        'fields' => [
            ['name' => 'id', 'label' => 'ID'],
            ['name' => 'workshop.name', 'label' => 'Initiative', 'filterParam' => 'Worknews.workshop_uid'],
            ['name' => 'owner_user.name', 'label' => 'Owner', 'sortable' => false],
            ['name' => 'activity_proof_filename', 'label' => 'Aktivitätsnachweis'],
            ['name' => 'activity_proof_ok', 'label' => 'AN OK'],
            ['name' => 'created', 'type' => 'datetime', 'label' => 'erstellt'],
            ['name' => 'modified', 'type' => 'datetime', 'label' => 'geändert'],
        ],
    ]
    );
?>
