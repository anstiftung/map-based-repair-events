<?php
declare(strict_types=1);

if ($object->fundingusageproof && $object->receiptlist_difference > 0) {
    echo '<div style="text-align:right;border-radius:3px;">';
        echo $this->MyNumber->formatAsDecimal($object->receiptlist_difference) . ' €';
    echo '</div>';
}
