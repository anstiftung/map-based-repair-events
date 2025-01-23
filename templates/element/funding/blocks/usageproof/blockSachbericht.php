<?php
declare(strict_types=1);

use App\Model\Entity\Funding;

echo '<fieldset class="full-width">';
    echo '<legend>' . Funding::FIELDS_FUNDINGUSAGEPROOF_LABEL . '</legend>';
    echo '<div class="verification-wrapper ' . $funding->usageproof_status_css_class . '">';
        echo '<p>' . $funding->usageproof_status_human_readable . '</p>';
    echo '</div>';
    echo '<div>';
        if (!$disabled) {
            echo '<p style="margin-bottom:10px;padding:5px;">';
                echo 'Hilfetext Sachbericht';
            echo '</p>';
        }
        echo Funding::getRenderedFields(Funding::FIELDS_FUNDINGUSAGEPROOF, 'fundingusageproof', $this->Form, $disabled);
    echo '</div>';
echo '</fieldset>';
