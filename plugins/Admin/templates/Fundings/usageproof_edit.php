<?php
declare(strict_types=1);

use App\Model\Entity\Funding;
use App\Model\Entity\Fundingbudgetplan;

$this->element('addScript', ['script' =>
    JS_NAMESPACE.".Helper.bindCancelButton();".
    JS_NAMESPACE.".Funding.useDisabledRadioButtonsWorkaround();".
    JS_NAMESPACE.".Helper.layoutEditButtons();
"]);
?>

<div class="admin edit">

<?php
    echo $this->Form->create($funding, [
        'novalidate' => 'novalidate',
        'id' => 'fundingForm',
    ]);
    echo $this->element('heading', ['first' => 'Verwendungsnachweis (UID: ' . $funding->uid . ') ' . $funding->workshop->name]);

    echo $this->Form->hidden('referer', ['value' => $referer]);

    echo '<div class="flexbox">';

        echo $this->element('funding/blocks/usageproof/blockSachbericht', ['funding' => $funding, 'disabled' => true]);

        echo '<fieldset class="fundinglist fundingreceiptlist full-width">';
            echo '<legend>Belegliste</legend>';

            echo '<div class="verification-wrapper ' . $funding->receiptlist_status_css_class . '">';
                echo '<p>' . $funding->receiptlist_status_human_readable . '</p>';
            echo '</div>';
    
            echo '<div class="row row-readonly row-header full-width" style="margin-bottom:5px;">';
                foreach(Funding::FIELDS_FUNDINGRECEIPTLIST as $field) {
                    if (isset($field['hideInHeading']) || $field['name'] == 'type') {
                        continue;
                    }
                    echo '<span>' . ($field['options']['placeholder'] ?? '') . '</span>';
                }
            echo '</div>';

            foreach($funding->grouped_valid_receiptlists as $typeId => $fundingreceiptlists) {
                echo '<div class="fundingreceiptlists flexbox full-width" style="gap:5px;">';

                    echo '<div class="full-width""><b>' . Fundingbudgetplan::TYPE_MAP[$typeId] . '</b></div>';

                    foreach($fundingreceiptlists as $fundingreceiptlist) {
                        echo '<div class="row row-readonly full-width">';
                            echo '<span>';
                                echo $fundingreceiptlist->description;
                            echo '</span>';
                            echo '<span>';
                                echo $fundingreceiptlist->recipient;
                            echo '</span>';
                            echo '<span>';
                                echo $fundingreceiptlist->receipt_type;
                            echo '</span>';
                            echo '<span>';
                                echo $fundingreceiptlist->payment_date->format('d.m.Y');
                            echo '</span>';
                            echo '<span>';
                                echo $fundingreceiptlist->receipt_number;
                            echo '</span>';
                            echo '<span style="text-align:right;">';
                                echo $this->MyNumber->formatAsDecimal($fundingreceiptlist->amount) . ' €';
                            echo '</span>';
                        echo '</div>';
                    }

                    echo '<div class="flexbox full-width" style="margin-bottom:10px;">';
                        echo '<div style="flex-grow:1;">';
                            echo '<b>Summe</b>';
                        echo '</div>';
                        echo '<div style="align-self:flex-end;">';
                            echo '<b>' . $this->MyNumber->formatAsDecimal($funding->grouped_valid_receiptlists_totals[$typeId]) . ' €</b>';
                        echo '</div>';
                    echo '</div>';

                echo '</div>';
            }

            echo $this->element('funding/blocks/usageproof/blockReceiptlistTotals', ['funding' => $funding, 'disabled' => true]);

        echo '</fieldset>';

        echo '<div class="flexbox questions">';
            echo $this->element('funding/blocks/usageproof/blockQuestions', ['funding' => $funding, 'disabled' => true]);
        echo '</div>';

        echo $this->element('funding/blocks/usageproof/blockPrMaterial', ['funding' => $funding, 'disabled' => true]);

        echo $this->element('funding/blocks/usageproof/blockCheckboxes', ['funding' => $funding, 'disabled' => true]);


        echo '<fieldset>';
            echo '<legend>Status Verwendungsnachweis</legend>';
            echo $this->element('funding/status/usageproofStatus', ['funding' => $funding, 'additionalTextBefore' => '']);
            echo $this->Form->control('Fundings.usageproof_status', ['label' => 'Status', 'options' => Funding::STATUS_MAPPING_FOR_USAGEPROOF, 'disabled' => !$funding->usageproof_is_submitted]);
            echo $this->Form->control('Fundings.usageproof_comment', ['label' => 'Kommentar', 'disabled' => !$funding->usageproof_is_submitted]);
        echo '</fieldset>';

    echo '</div>';

    echo $this->element('cancelAndSaveButton', ['saveLabel' => 'Speichern']);
    echo $this->Form->end();
?>

</div>
