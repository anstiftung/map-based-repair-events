<?php
declare(strict_types=1);
use App\Model\Entity\Funding;
?>

<fieldset class="full-width">
    <legend><?php echo Funding::FIELDS_USAGEPROOF_QUESTIONS_LABEL; ?></legend>
    <?php
        echo '<div class="verification-wrapper ' . $funding->usageproof_questions_status_css_class . '">';
            echo '<p>' . $funding->usageproof_questions_status_human_readable . '</p>';
        echo '</div>';
    ?>
    <p style="margin-bottom:20px;">
        Inwiefern wurde die angedachte Wirkung/das Resultat des Projektes erreicht?
        <br />Wie sehr treffen die folgenden Aussagen zu:
    </p>
    <?php
        echo Funding::getRenderedFields(Funding::FIELDS_USAGEPROOF_QUESTIONS, 'fundingusageproof', $this->Form, $disabled);
    ?>
</fieldset>
