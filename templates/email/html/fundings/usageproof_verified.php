<?php
declare(strict_types=1);
?>
<?php echo $this->element('email/tableHead'); ?>
    <tbody>
        <?php echo $this->element('email/greeting'); ?>
        <tr>
            <td>
                <p>
                    Der Verwendungsnachweis wurde von einem Admin bestätigt.<br />
                    Im Anhang findet ihr den Verwendungsnachweis.
                </p>
            </td>
        </tr>
    </tbody>
<?php echo $this->element('email/tableFoot'); ?>
