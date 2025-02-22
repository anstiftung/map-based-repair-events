<?php
declare(strict_types=1);

use App\Model\Entity\Funding;

if ($funding->money_transfer_date === null) {
    return;
}

$submitInfo = '';
if ($funding->usageproof_submit_date !== null) {
    $submitInfo = ' eingereicht am ' . $funding->usageproof_submit_date->format('d.m.Y H:i');
}

if ($funding->usageproof_exists) {
    echo $this->element('funding/status/usageproofStatus', [
        'funding' => $funding,
        'additionalTextBefore' => 'Verwendungsnachweis' . $submitInfo. ': ',
    ]);
}

if ($funding->usageproof_status == Funding::STATUS_DATA_MISSING) {
    echo '<div style="margin-top:10px;margin-left:10px;">';
        echo $this->Html->link(
            'Verwendungsnachweis ' . ($funding->usageproof_exists ? 'bearbeiten' : 'erstellen'),
            $this->Html->urlFundingsUsageproof($funding->uid),
            [
                'class' => 'button',
            ],
        );
    echo '</div>';
}

if ($funding->usageproof_status == Funding::STATUS_VERIFIED_BY_ADMIN) {
    echo '<div class="download-links">';

        echo '<span style="margin-right:5px;margin-left:10px;"><i class="fa-solid fa-arrow-turn-up fa-rotate-90"></i> Downloads:</span>';

        echo $this->Html->link(
            'Verwendungsnachweis',
            $this->Html->urlFundingVerwendungsnachweisDownload($funding->uid),
            [
                'target' => '_blank',
            ],
        );
    
        echo '</div>';
}

if ($funding->usageproof_status == Funding::STATUS_REJECTED_BY_ADMIN) {
    echo '<div style="margin-top:10px;margin-right:5px;margin-left:10px;"><i class="fa-solid fa-arrow-turn-up fa-rotate-90"></i>';
        echo $this->Html->link(
            'Verwendungsnachweis bearbeiten',
            $this->Html->urlFundingsUsageproof($funding->uid),
            [
                'class' => 'button',
                'style' => 'margin-left:10px;',
            ],
        );
    echo '</div>';
}

