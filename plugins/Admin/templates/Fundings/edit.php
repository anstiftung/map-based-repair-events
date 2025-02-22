<?php
declare(strict_types=1);

use App\Model\Entity\Funding;
use App\Model\Entity\Fundingupload;

$this->element('addScript', ['script' =>
    JS_NAMESPACE.".Helper.bindCancelButton();".
    JS_NAMESPACE.".Funding.initIsVerified('".json_encode($funding->verified_fields)."', true);".
    JS_NAMESPACE.".Helper.layoutEditButtons();
"]);
?>

<div class="admin edit">

<?php
    echo $this->Form->create($funding, [
        'novalidate' => 'novalidate',
        'id' => 'fundingForm',
    ]);
    echo $this->element('heading', ['first' => 'Förderantrag (UID: ' . $funding->uid . ') ' . $funding->workshop->name]);

    if ($funding->is_submitted) {
        echo $this->element('funding/submitInfo', ['funding' => $funding]);
        echo $this->Form->control('Fundings.reopen', ['type' => 'checkbox', 'label' => 'Förderstatus "eingereicht" zurücksetzen', 'class' => 'no-verify']);
    }

    echo $this->Form->hidden('referer', ['value' => $referer]);
    $this->Form->unlockField('referer');

    echo '<div class="flexbox">';

        if ($funding->workshop->funding_activity_proof_required) {
            echo $this->element('funding/fundingUploadsForm', [
                'fundinguploads' => $funding->fundinguploads_activity_proofs,
                'uploadType' => Fundingupload::TYPE_MAP_STEP_1[Fundingupload::TYPE_ACTIVITY_PROOF],
                'legend' => 'Uploads Aktivitätsnachweis',
            ]);

            echo '<fieldset>';
                echo '<legend>Status Aktivitätsnachweis</legend>';
                echo $this->element('funding/status/activityProofStatus', ['funding' => $funding]);
                echo $this->Form->control('Fundings.activity_proof_status', ['label' => 'Status', 'options' => Funding::STATUS_MAPPING_FOR_ADMIN_DROPDOWN, 'disabled' => $funding->is_submitted, 'class' => 'no-verify']);
               
                echo $this->Form->control('Fundings.activity_proof_comment', ['label' => 'Kommentar', 'disabled' => $funding->is_submitted, 'class' => 'no-verify']);

            echo '</fieldset>';
        }

        echo $this->element('funding/fundingUploadsForm', [
            'fundinguploads' => $funding->fundinguploads_freistellungsbescheids,
            'uploadType' => Fundingupload::TYPE_MAP_STEP_1[Fundingupload::TYPE_FREISTELLUNGSBESCHEID],
            'legend' => 'Upload Freistellungsbescheid',
        ]);
        
        echo '<fieldset>';
            echo '<legend>Status Freistellungsbescheid</legend>';
            echo $this->element('funding/status/freistellungsbescheidStatus', ['funding' => $funding]);
            echo $this->Form->control('Fundings.freistellungsbescheid_status', ['label' => 'Status', 'options' => Funding::STATUS_MAPPING_FOR_ADMIN_DROPDOWN, 'disabled' => $funding->is_submitted, 'class' => 'no-verify']);
            echo $this->Form->control('Fundings.freistellungsbescheid_comment', ['label' => 'Kommentar', 'disabled' => $funding->is_submitted, 'class' => 'no-verify']);
        echo '</fieldset>';

        echo $this->element('funding/blocks/blockWorkshop', ['funding' => $funding, 'disabled' => true]);
        echo $this->element('funding/blocks/blockOwnerUser', ['funding' => $funding, 'disabled' => true]);
        echo $this->element('funding/blocks/blockFundingsupporterOrganziation', ['funding' => $funding, 'disabled' => true]);
        echo $this->element('funding/blocks/blockFundingsupporterUser', ['funding' => $funding, 'disabled' => true]);
        echo $this->element('funding/blocks/blockFundingsupporterBank', ['funding' => $funding, 'disabled' => true]);
        echo $this->element('funding/blocks/blockDescription', ['funding' => $funding, 'disabled' => true]);

        echo '<fieldset>';
            echo '<legend>'.Funding::FIELDS_FUNDINGBUDGETPLAN_GROUPED_LABEL.'</legend>';

            echo '<div class="verification-wrapper ' . $funding->budgetplan_status_css_class . '">';
                echo '<p>' . $funding->budgetplan_status_human_readable . '</p>';
            echo '</div>';

            echo $this->element('funding/blocks/blockBudgetplanGrouped', ['funding' => $funding]);

        echo '</fieldset>';

        echo $this->element('funding/blocks/blockCheckboxes', ['funding' => $funding, 'disabled' => true]);

        if ($funding->is_submitted) {
            echo $this->element('funding/fundingUploadsForm', [
                'fundinguploads' => $funding->fundinguploads_zuwendungsbestaetigungs,
                'uploadType' => Fundingupload::TYPE_MAP_STEP_2[Fundingupload::TYPE_ZUWENDUNGSBESTAETIGUNG],
                'legend' => 'Upload Zuwendungsbestätigung',
            ]);
            
            echo '<fieldset>';
                echo '<legend>Status Zuwendungsbestätigung</legend>';
                echo $this->element('funding/status/zuwendungsbestaetigungStatus', ['funding' => $funding, 'additionalTextBefore' => '']);
                echo $this->Form->control('Fundings.zuwendungsbestaetigung_status', ['label' => 'Status', 'options' => Funding::STATUS_MAPPING_FOR_ADMIN_DROPDOWN, 'class' => 'no-verify']);
                echo $this->Form->control('Fundings.zuwendungsbestaetigung_comment', ['label' => 'Kommentar', 'class' => 'no-verify']);
            echo '</fieldset>';
        }


    echo '</div>';

    echo $this->element('cancelAndSaveButton', ['saveLabel' => 'Speichern']);
    echo $this->Form->end();
?>

</div>
