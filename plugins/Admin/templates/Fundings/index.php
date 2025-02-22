<?php
declare(strict_types=1);
echo $this->element('list',
    [
        'objects' => $objects,
        'heading' => 'Förderanträge',
        'editMethod' => ['url' => 'urlFundingsAdminEdit'],
        'deleteMethod' => '/admin/intern/ajaxDeleteFunding',
        'optionalSearchForms' => [
            ['options' => $fundingStatus, 'value' => 'FundingStatus', 'label' => 'Status'],
        ],
        'emailFields' => [
            [
                'label' => 'Träger',
                'field' => 'fundingsupporter.contact_email',
            ],
            [
                'label' => 'Owner',
                'field' => 'owner_user.email',
            ],
        ],
        'fields' => [
            ['name' => 'uid', 'label' => 'UID'],
            ['label' => 'Initiative', 'template' => 'list/fundings/name'],
            ['label' => 'AN', 'template' => 'list/fundings/activityProof'],
            ['label' => 'FB', 'template' => 'list/fundings/freistellungsbescheid'],
            ['label' => 'bestätigte Felder', 'template' => 'list/fundings/verifiedFields'],
            ['label' => 'Summe', 'template' => 'list/fundings/budgetplan', 'sortable' => false],
            ['label' => 'eingereicht', 'template' => 'list/fundings/submitInfo'],
            ['label' => 'ZB', 'template' => 'list/fundings/zuwendungsbestaetigung'],
            ['label' => 'überwiesen', 'template' => 'list/fundings/moneyTransferInfo'],
            ['label' => 'VN', 'template' => 'list/fundings/usageproof'],
            ['label' => 'Restbetrag', 'template' => 'list/fundings/receiptlistDifference'],
            ['name' => 'created', 'type' => 'datetime', 'label' => 'erstellt'],
            ['name' => 'modified', 'type' => 'datetime', 'label' => 'geändert'],
        ],
    ]
    );
?>
