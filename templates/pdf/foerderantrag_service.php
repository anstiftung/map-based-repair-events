<?php
declare(strict_types=1);

use App\Model\Entity\Fundingbudgetplan;
use App\Model\Entity\Funding;
use Cake\Core\Configure;

$pdf->setDefaults();
$pdf->Ln(50);

$pdf->SetFontSizeBig();
$html = '<b>Förderantrag vom ' . $timestamp->i18nFormat(Configure::read('DateFormat.de.DateNTimeLongWithSeconds')) . ' - UID: ' . $funding->uid . '</b>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->SetFontSizeDefault();
$pdf->Ln(15);

$i = 0;
foreach($blocks as $block) {
    $html = '<b>' . $block['name'] . '</b>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(3);
    
    $html = $pdf->getFundingDataAsTable($block['fields']);
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(3);

    if ($i == 4) {
        $pdf->addPage();
        $pdf->Ln(5);
    }

    $i++;
}

$html = '<b>' . Funding::FIELDS_FUNDINGDATA_DESCRIPTION_LABEL . '</b>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

$html = '<p>' . $description['description']['value'] . '</p>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

$html = '<b>' . Funding::FIELDS_FUNDINGBUDGETPLAN_LABEL . '</b>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

$html = '';
$tableArgs = ['100%', '85%', '15%', 'left', 'right'];

foreach($funding->grouped_valid_budgetplans as $typeId => $fundingbudgetplans) {
    $html .= '<b>' . Fundingbudgetplan::TYPE_MAP[$typeId] . '</b><br />';
    $preparedDataForTable = [];
    foreach($fundingbudgetplans as $fundingbudgetplan) {
        $preparedDataForTable[] = [
            'label' => $fundingbudgetplan->description,
            'value' => $this->MyNumber->formatAsDecimal($fundingbudgetplan->amount) . ' €',
        ];
    }
    $html .= $pdf->getFundingDataAsTable($preparedDataForTable, ...$tableArgs);
    $preparedSumDataForTable = [
        [
            'label' => '',
            'value' => '<b>' . $this->MyNumber->formatAsDecimal($funding->grouped_valid_budgetplans_totals[$typeId]) . ' €</b>',
        ],
    ];
    $html .= $pdf->getFundingDataAsTable($preparedSumDataForTable, ...$tableArgs);
}
$pdf->writeHTML($html, true, false, true, false, '');

$preparedSumDataForTable = [
    [
        'label' => '<b>Kosten gesamt</b>',
        'value' => '<b>' . $this->MyNumber->formatAsDecimal($funding->budgetplan_total) . ' €</b>',
    ],
];

$html = $pdf->getFundingDataAsTable($preparedSumDataForTable, ...$tableArgs);
$pdf->writeHTML($html, true, false, true, false, '');

if ($funding->budgetplan_total > Funding::MAX_FUNDING_SUM) {
    $preparedSumDataForTable = [
        [
            'label' => 'Maximaler Förderbetrag',
            'value' =>  $this->MyNumber->formatAsDecimal(Funding::MAX_FUNDING_SUM) . ' €',
        ],
    ];
    $html = $pdf->getFundingDataAsTable($preparedSumDataForTable, ...$tableArgs);
    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Ln(10);
$html = '<b>' . Funding::FIELDS_FUNDING_DATA_CHECKBOXES_LABEL . '</b>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(3);

$html = '';
foreach($checkboxes as $checkbox) {
    if ($funding->fundingdata[$checkbox['name']] == 1) {
        $html .= 'Bestätigt';
    } else {
        $html .= 'Nicht bestätigt';
    }
    $html .= ': "' .$checkbox['label'] . '"<br /><br />';
}

$pdf->SetFontSizeDefault();
$pdf->writeHTML($html, true, false, true, false, '');
