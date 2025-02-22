<?php
declare(strict_types=1);

$this->element('addScript', ['script' =>
    JS_NAMESPACE.".Helper.bindCancelButton();".
    JS_NAMESPACE.".Funding.initIsMissing();".
    JS_NAMESPACE.".Funding.initTextareaCounter();".
    JS_NAMESPACE.".Funding.initIsVerified('".json_encode($funding->verified_fields)."', false);".
    JS_NAMESPACE.".Funding.updateProgressBar(" . $funding->all_fields_verified_count . ", ".$funding->all_fields_count.");"
]);
echo $this->element('jqueryTabsWithoutAjax', [
    'links' => $this->Html->getUserBackendNaviLinks($loggedUser->uid, true, $loggedUser->isOrga()),
    'selected' => $this->Html->urlFundings(),
]);

echo $this->element('funding/fundingEndDateNTimeNotice');

?>

<div class="profile ui-tabs custom-ui-tabs ui-widget-content">
    <div class="ui-tabs-panel">
        <?php
            echo $this->element('heading', ['first' => $metaTags['title']]);
            echo $this->element('funding/blocks/blockMainInfo');
            echo $this->element('funding/blocks/blockColorCodes');

            echo $this->Form->create($funding, [
                'novalidate' => 'novalidate',
                'url' => $this->Html->urlFundingsEdit($funding->workshop->uid),
                'type' => 'file',
                'id' => 'fundingForm',
            ]);

                echo $this->Form->hidden('referer', ['value' => $referer]);
                $this->Form->unlockField('referer');
                $this->Form->unlockField('submit_funding');
                $this->Form->unlockField('verified_fields');
                echo $this->Form->hidden('Fundings.workshop.use_custom_coordinates');
                echo $this->Form->hidden('Fundings.owner_user.use_custom_coordinates');

                echo $this->element('funding/blocks/blockProgressBar', ['funding' => $funding]);

                echo '<div class="flexbox">';
                    echo $this->element('funding/blocks/blockActivityProof', ['funding' => $funding]);
                    echo $this->element('funding/blocks/blockFreistellungsbescheid', ['funding' => $funding]);
                    echo $this->element('funding/blocks/blockWorkshop', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockOwnerUser', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockFundingsupporterOrganziation', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockFundingsupporterUser', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockFundingsupporterBank', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockDescription', ['funding' => $funding, 'disabled' => false]);
                    echo $this->element('funding/blocks/blockBudgetplan', ['funding' => $funding]);
                    echo $this->element('funding/blocks/blockCheckboxes', ['funding' => $funding, 'disabled' => false]);
                echo '</div>';

                echo $this->element('funding/blocks/blockProgressBar', ['funding' => $funding]);

                echo $this->element('cancelAndSaveButton', [
                    'saveLabel' => 'Förderantrag zwischenspeichern',
                ]);

                echo '<div class="extra-submit-button-wrapper">';
                    echo $this->Form->button('Förderantrag einreichen', [
                        'type' => 'button',
                        'id' => 'submit-funding-button-' . $funding->uid,
                        'class' => 'rounded red ' . (!$funding->is_submittable ? 'disabled' : ''),
                        'disabled' => !$funding->is_submittable,
                    ]);
                    if ($funding->is_submittable) {
                        $this->element('addScript', ['script' =>
                            JS_NAMESPACE.".Funding.bindSubmitFundingButton(".$funding->uid.");"
                        ]);
                    }
                echo '</div>';
    
            echo $this->Form->end();

        ?>

    </div>
</div>