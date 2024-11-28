<?php
    use App\Model\Entity\Funding;
?>

<fieldset>
    <legend>Personenbezogene Daten Ansprechpartner*in (UID: <?php echo $funding->owner_user->uid; ?>)</legend>
    <?php if (!$disabled) { ?>
        <p style="margin-bottom:10px;padding:5px;">
            Überprüfe deine persönlichen Daten.
        </p>
    <?php } ?>

    <?php
        echo Funding::getRenderedFields(Funding::FIELDS_OWNER_USER, 'owner_user', $this->Form, $disabled);
    ?>

</fieldset>
