<?php
declare(strict_types=1);

use App\Model\Entity\Funding;

$this->element('addScript', ['script' =>
    JS_NAMESPACE.".Helper.bindCancelButton();".
    JS_NAMESPACE.".Funding.initIsMissing();".
    JS_NAMESPACE.".Funding.initTextareaCounter();"
]);
echo $this->element('jqueryTabsWithoutAjax', [
    'links' => $this->Html->getUserBackendNaviLinks($loggedUser->uid, true, $loggedUser->isOrga()),
    'selected' => $this->Html->urlFundings(),
]);
?>

<div class="profile ui-tabs custom-ui-tabs ui-widget-content">
    <div class="ui-tabs-panel">
        <?php
            echo $this->element('heading', ['first' => $metaTags['title']]);

            echo $this->Form->create($funding, [
                'novalidate' => 'novalidate',
                'url' => $this->Html->urlFundingsUsageproof($funding->uid),
                'type' => 'file',
                'id' => 'fundingForm',
            ]);

                echo $this->Form->hidden('referer', ['value' => $referer]);
                $this->Form->unlockField('referer');
                $this->Form->unlockField('add_receiptlist');
                $this->Form->unlockField('submit_usageproof');

                echo $this->element('funding/blocks/usageproof/blockMainInfo');
                echo $this->element('funding/blocks/usageproof/blockColorCodes');
                echo $this->element('funding/status/usageproofStatus', [
                    'funding' => $funding,
                    'additionalTextBefore' => '',
                ]);

                echo '<div class="flexbox">';
                    echo $this->element('funding/blocks/usageproof/blockSachbericht', ['funding' => $funding, 'disabled' => false]);
                echo '</div>';

                echo $this->element('cancelAndSaveButton', [
                    'saveLabel' => 'Zwischenspeichern',
                    'hideCancelButton' => true,
                ]);
                echo '<div class="sc" style="margin-bottom:10px;"></div>';

                echo '<div class="flexbox">';
                    echo $this->element('funding/blocks/usageproof/blockReceiptlist', ['funding' => $funding, 'disabled' => false]);
                echo '</div>';

                echo $this->element('cancelAndSaveButton', [
                    'saveLabel' => 'Zwischenspeichern',
                    'hideCancelButton' => true,
                ]);
                echo '<div class="sc"></div>';

                echo '<div class="flexbox" style="margin-top:10px;">';
                    echo '<fieldset>';
                        echo '<legend>'.Funding::FIELDS_FUNDINGBUDGETPLAN_GROUPED_LABEL.'</legend>';
                        echo $this->element('funding/blocks/blockBudgetplanGrouped', ['funding' => $funding]);
                    echo '</fieldset>';
                echo '</div>';

                echo '<div class="flexbox questions">';
                    echo $this->element('funding/blocks/usageproof/blockQuestions', ['funding' => $funding, 'disabled' => false]);
                echo '</div>';

                echo $this->element('funding/blocks/usageproof/blockPrMaterial', ['funding' => $funding, 'disabled' => false]);

                echo '<div class="flexbox">';
                    echo $this->element('funding/blocks/usageproof/blockCheckboxes', ['funding' => $funding, 'disabled' => false]);
                echo '</div>';

                echo $this->element('cancelAndSaveButton', [
                    'saveLabel' => 'Zwischenspeichern',
                ]);

                echo '<div class="extra-submit-button-wrapper">';
                    echo $this->Form->button('Verwendungsnachweis einreichen', [
                        'type' => 'button',
                        'id' => 'submit-usageproof-button-' . $funding->uid,
                        'class' => 'rounded red ' . (!$funding->usageproof_is_submittable ? 'disabled' : ''),
                        'disabled' => !$funding->usageproof_is_submittable,
                    ]);
                    if ($funding->usageproof_is_submittable) {
                        $this->element('addScript', ['script' =>
                            JS_NAMESPACE.".Funding.bindSubmitUsageproofButton(".$funding->uid.");"
                        ]);
                    }

                echo '</div>';


            echo $this->Form->end();

        ?>

    </div>
</div>