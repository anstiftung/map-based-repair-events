<?php
declare(strict_types=1);
use Cake\Core\Configure;
?>
<?php echo $this->element('email/tableHead'); ?>
    <tbody>
        <?php echo $this->element('email/greeting'); ?>
        <tr>
            <td>
                <p>
                    Der Status deines Verwendungsnachweises wurde geändert auf:<br />
                    <b><?php echo $funding->usageproof_status_human_readable; ?></b>
                </p>
                <?php if ($funding->usageproof_comment != '') { ?>
                    <p>
                        Kommentar: <b><?php echo $funding->usageproof_comment; ?></b>
                    </p>
                <?php } ?>
                <p>
                    <?php
                        echo $this->MyHtml->link(
                            'Verwendungsnachweis bearbeiten',
                            Configure::read('AppConfig.serverName') . $this->MyHtml->urlFundingsUsageproof($funding->uid),
                        );
                    ?>
                </p>
            </td>
        </tr>
    </tbody>
<?php echo $this->element('email/tableFoot'); ?>
