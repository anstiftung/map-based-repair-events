<?php
echo $this->element('list',
    [
        'objects' => $objects,
        'heading' => 'Förderanträge',
        'editMethod' => ['url' => 'urlFundingsAdminEdit'],
        'deleteMethod' => '/admin/intern/ajaxDeleteFunding',
        'fields' => [
            ['name' => 'uid', 'label' => 'UID'],
            ['name' => 'workshop.name', 'label' => 'Initiative', 'sortable' => false],
            ['label' => 'AN', 'template' => 'list/fundings/activityProof'],
            ['label' => 'FB', 'template' => 'list/fundings/freistellungsbescheid'],
            ['label' => 'bestätigte Felder', 'template' => 'list/fundings/verifiedFields'],
            ['name' => 'owner_user.name', 'label' => 'Owner', 'sortable' => false],
            ['label' => 'eingereicht', 'template' => 'list/fundings/submitInfo'],
            ['name' => 'created', 'type' => 'datetime', 'label' => 'erstellt'],
            ['name' => 'modified', 'type' => 'datetime', 'label' => 'geändert'],
        ],
    ]
    );
?>
