<?php
declare(strict_types=1);
?>

<fieldset style="margin-top:20px;">
    <legend>Hilfe</legend>
    <p>
        Sachbericht, Belegliste und Bestätigungen müssen korrekt ausgefüllt werden, bis alle Blöcke grün sind.<br />
        Danach kann der Verwendungsnachweis eingereicht werden und wird anschließend von einem Admin bestätigt.<br />
        <?php echo $this->Html->link('Hilfe / Erklärungen zum Verwendungsnachweis', $this->Html->urlPageDetail('verwendungsnachweis'), ['target' => '_blank']); ?>
    </p>
</fieldset>