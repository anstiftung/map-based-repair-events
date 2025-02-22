<?php
declare(strict_types=1);

use App\Model\Entity\Fundingbudgetplan;

foreach($funding->grouped_valid_budgetplans as $typeId => $fundingbudgetplans) {
    echo '<div class="fundingbudgetplans flexbox full-width" style="gap:5px;">';

        echo '<div class="full-width""><b>' . Fundingbudgetplan::TYPE_MAP[$typeId] . '</b></div>';

        foreach($fundingbudgetplans as $fundingbudgetplan) {
            echo '<div class="flexbox full-width">';
                echo '<div style="flex-grow:1;">';
                    echo $fundingbudgetplan->description;
                echo '</div>';
                echo '<div style="align-self:flex-end;">';
                    echo $this->MyNumber->formatAsDecimal($fundingbudgetplan->amount) . ' €';
                echo '</div>';
            echo '</div>';
        }

        echo '<div class="flexbox full-width" style="margin-bottom:10px;">';
            echo '<div style="flex-grow:1;">';
                echo '<b>Summe</b>';
            echo '</div>';
            echo '<div style="align-self:flex-end;">';
                echo '<b>' . $this->MyNumber->formatAsDecimal($funding->grouped_valid_budgetplans_totals[$typeId]) . ' €</b>';
            echo '</div>';
        echo '</div>';

    echo '</div>';
}

echo '<div class="flexbox full-width" style="margin-bottom:10px;font-size:14px;">';
    echo '<div style="flex-grow:1;">';
        echo '<b>Kosten gesamt</b>';
    echo '</div>';
    echo '<div style="align-self:flex-end;">';
        echo '<b>' . $this->MyNumber->formatAsDecimal($funding->budgetplan_total) . ' €</b>';
    echo '</div>';
echo '</div>';