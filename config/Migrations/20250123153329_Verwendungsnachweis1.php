<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use App\Model\Entity\Funding;

class Verwendungsnachweis1 extends AbstractMigration
{
    public function change(): void
    {

        $sql = "ALTER TABLE `fundings` ADD `usageproof_status` int(10) DEFAULT " . Funding::STATUS_DATA_MISSING . " AFTER `zuwendungsbestaetigung_comment`,
                ADD `usageproof_comment` text AFTER `usageproof_status`,
                ADD `fundingusageproof_id` int UNSIGNED DEFAULT NULL AFTER `fundingdata_id`;
        ";
        $this->execute($sql);

        $query = "CREATE TABLE `fundingusageproofs` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `main_description` text,
            `difference_declaration` text,
            `payback_ok` TINYINT UNSIGNED NOT NULL DEFAULT '0',
            `checkbox_a` TINYINT UNSIGNED NOT NULL DEFAULT '0',
            `checkbox_b` TINYINT UNSIGNED NOT NULL DEFAULT '0',
            `checkbox_c` TINYINT UNSIGNED NOT NULL DEFAULT '0',
            `checkbox_d` TINYINT UNSIGNED NOT NULL DEFAULT '0',
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `modified` datetime DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->execute($query);

        $query = "CREATE TABLE `fundingreceiptlists` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `funding_uid` int UNSIGNED DEFAULT NULL,
            `type` int(10) DEFAULT 0,
            `description` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
            `recipient` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
            `receipt_type` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
            `payment_date` DATE NULL DEFAULT NULL,
            `receipt_number` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
            `amount` decimal(10,2) UNSIGNED NULL DEFAULT NULL,
            `created` datetime DEFAULT CURRENT_TIMESTAMP,
            `modified` datetime DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->execute($query);

        $sql = "ALTER TABLE `fundings` ADD `usageproof_submit_date` DATETIME NULL DEFAULT NULL AFTER `money_transfer_date`;";
        $this->execute($sql);

        $sql = "ALTER TABLE `fundingusageproofs`
            ADD `question_radio_a` int UNSIGNED DEFAULT NULL AFTER `checkbox_c`,
            ADD `question_radio_b` int UNSIGNED DEFAULT NULL AFTER `question_radio_a`,
            ADD `question_radio_c` int UNSIGNED DEFAULT NULL AFTER `question_radio_b`,
            ADD `question_radio_d` int UNSIGNED DEFAULT NULL AFTER `question_radio_c`,
            ADD `question_radio_e` int UNSIGNED DEFAULT NULL AFTER `question_radio_d`,
            ADD `question_radio_f` int UNSIGNED DEFAULT NULL AFTER `question_radio_e`,
            ADD `question_text_a` text AFTER `question_radio_f`,
            ADD `question_text_b` text AFTER `question_text_a`;
            ";
        $this->execute($sql);

    }
}
