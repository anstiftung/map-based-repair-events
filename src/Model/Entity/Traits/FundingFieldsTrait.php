<?php
declare(strict_types=1);

namespace App\Model\Entity\Traits;

use App\Model\Table\FundingdatasTable;
use App\Model\Entity\Fundingbudgetplan;
use App\Model\Table\FundingusageproofsTable;
use App\Model\Table\FundingreceiptlistsTable;
use App\Model\Table\FundingbudgetplansTable;

trait FundingFieldsTrait {


    const FIELDS_WORKSHOP_LABEL = 'Stammdaten der Reparatur-Initiative';
    const FIELDS_OWNER_USER_LABEL = 'Personenbezogene Daten Ansprechpartner*in';
    const FIELDS_FUNDINGSUPPORTER_ORGANIZATION_LABEL = 'Stammdaten der Trägerorganisation';
    const FIELDS_FUNDINGSUPPORTER_USER_LABEL = 'Ansprechpartner*in der Trägerorganisation';
    const FIELDS_FUNDINGSUPPORTER_BANK_LABEL = 'Bankverbindung der Trägerorganisation';
    const FIELDS_FUNDINGDATA_DESCRIPTION_LABEL = 'Kurzbeschreibung Vorhaben';
    const FIELDS_FUNDINGBUDGETPLAN_LABEL = 'Kostenplan';
    const FIELDS_FUNDING_DATA_CHECKBOXES_LABEL = 'Einverständniserklärungen';
    const FIELDS_FUNDINGUSAGEPROOF_LABEL = 'Sachbericht';
    const FIELDS_FUNDINGRECEIPTLIST_LABEL = 'Belegliste';

    const FIELDS_WORKSHOP = [
        ['name' => 'name', 'options' => ['label' => 'Name der Initiative']],
        ['name' => 'street', 'options' => ['label' => 'Straße + Hausnummer']],
        ['name' => 'zip', 'options' => ['label' => 'PLZ']],
        ['name' => 'city', 'options' => ['label' => 'Stadt']],
        ['name' => 'adresszusatz', 'options' => ['label' => 'Adresszusatz']],
        ['name' => 'email', 'options' => ['label' => 'E-Mail']],
        ['name' => 'website', 'options' => ['label' => 'Website']],
    ];

    const FIELDS_OWNER_USER = [
        ['name' => 'firstname', 'options' => ['label' => 'Vorname']],
        ['name' => 'lastname', 'options' => ['label' => 'Nachname']],
        ['name' => 'email', 'options' => ['label' => 'E-Mail']],
        ['name' => 'street', 'options' => ['label' => 'Straße + Hausnummer', 'required' => true]],
        ['name' => 'zip', 'options' => ['label' => 'PLZ']],
        ['name' => 'city', 'options' => ['label' => 'Stadt', 'required' => true]],
        ['name' => 'phone', 'options' => ['label' => 'Telefon', 'required' => true]],
    ];

    const FIELDS_FUNDINGSUPPORTER_ORGANIZATION = [
        ['name' => 'name', 'options' => ['label' => 'Name']],
        ['name' => 'legal_form', 'options' => ['label' => 'Rechtsform']],
        ['name' => 'street', 'options' => ['label' => 'Straße + Hausnummer']],
        ['name' => 'zip', 'options' => ['label' => 'PLZ']],
        ['name' => 'city', 'options' => ['label' => 'Stadt']],
        ['name' => 'website', 'options' => ['label' => 'Website']],
    ];

    const FIELDS_FUNDINGSUPPORTER_USER = [
        ['name' => 'contact_firstname', 'options' => ['label' => 'Vorname']],
        ['name' => 'contact_lastname', 'options' => ['label' => 'Nachname']],
        ['name' => 'contact_function', 'options' => ['label' => 'Funktion']],
        ['name' => 'contact_phone', 'options' => ['label' => 'Telefon']],
        ['name' => 'contact_email', 'options' => ['label' => 'E-Mail']],
    ];

    const FIELDS_FUNDINGSUPPORTER_BANK = [
        ['name' => 'bank_account_owner', 'options' => ['label' => 'Kontoinhaber']],
        ['name' => 'bank_institute', 'options' => ['label' => 'Kreditinstitut']],
        ['name' => 'iban', 'options' => ['label' => 'IBAN']],
        ['name' => 'bic', 'options' => ['label' => 'BIC']],
    ];

    const FIELDS_FUNDINGDATA_DESCRIPTION = [
        ['name' => 'description', 'options' => ['label' =>  FundingdatasTable::DESCRIPTION_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 15, 'maxlength' => FundingdatasTable::DESCRIPTION_MAX_LENGTH, 'minlength' => FundingdatasTable::DESCRIPTION_MIN_LENGTH, 'class' => 'no-verify']],
    ];

    const FIELDS_FUNDINGBUDGETPLAN = [
        ['name' => 'id', 'options' => ['type' => 'hidden']],
        ['name' => 'type', 'options' => ['type' => 'select', 'options' => Fundingbudgetplan::TYPE_MAP, 'empty' => 'Förderbereich wählen...', 'label' => false, 'class' => 'no-select2']],
        ['name' => 'description', 'options' => ['label' => false, 'placeholder' => 'Maßnahme/Gegenstand (' . FundingbudgetplansTable::DESCRIPTION_ERROR_MESSAGE . ')', 'class' => 'no-verify', 'maxlength' => FundingbudgetplansTable::DESCRIPTION_MAX_LENGTH, 'minlength' => FundingbudgetplansTable::DESCRIPTION_MIN_LENGTH]],
        ['name' => 'amount', 'options' => ['label' => false, 'placeholder' => 'Kosten in € ', 'type' => 'number', 'step' => '0.01']],
    ];

    const FIELDS_FUNDING_DATA_CHECKBOXES = [
        ['name' => 'checkbox_a', 'options' => ['type' => 'checkbox', 'class' => 'no-verify', 'label' => 'Mit der zu bewilligende Maßnahme wurde noch nicht begonnen und wird auch nicht vor Erhalt des Bewilligungsbescheides begonnen.', 'escape' => false]],
        ['name' => 'checkbox_b', 'options' => ['type' => 'checkbox', 'class' => 'no-verify', 'label' => 'Die zugrundeliegende <a href="/seite/richtlinie" target="_blank">Förderrichtlinie</a> habe ich/haben wir zur Kenntnis genommen.', 'escape' => false]],
        ['name' => 'checkbox_c', 'options' => ['type' => 'checkbox', 'class' => 'no-verify', 'label' => 'Mit der Einreichung erkläre ich mein Einverständnis, dass vorstehende Daten erhoben und elektronisch gespeichert werden, sowie das Einverständnis betroffener Dritter dazu eingeholt zu haben.<br /><i>Die Erhebung, Verarbeitung und Nutzung vorstehender personenbezogener Daten sind nur zulässig, wenn der Betroffene (Antragsteller) eingewilligt hat. Für den Fall, dass hierzu die Einwilligung verweigert wird, kann der Antrag nicht bearbeitet und die beantragte Förderung damit nicht bewilligt werden.</i>', 'escape' => false]],
    ];

    const FIELDS_FUNDINGUSAGEPROOF = [
        ['name' => 'main_description', 'options' => ['label' =>  'Sachbericht' . '<br />' . FundingusageproofsTable::MAIN_DESCRIPTION_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 13, 'maxlength' => FundingusageproofsTable::MAIN_DESCRIPTION_MAX_LENGTH, 'minlength' => FundingusageproofsTable::MAIN_DESCRIPTION_MIN_LENGTH, 'class' => 'no-verify', 'escape' => false]],
        ['name' => 'sub_description', 'options' => ['label' =>  'Zusammenfassung' . '<br />' . FundingusageproofsTable::SUB_DESCRIPTION_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 3, 'maxlength' => FundingusageproofsTable::SUB_DESCRIPTION_MAX_LENGTH, 'minlength' => FundingusageproofsTable::SUB_DESCRIPTION_MIN_LENGTH, 'class' => 'no-verify', 'escape' => false]],
    ];

    const FIELDS_FUNDINGRECEIPTLIST = [
        ['name' => 'id', 'options' => ['type' => 'hidden'], 'hideInHeading' => true],
        ['name' => 'type', 'options' => ['type' => 'select', 'options' => Fundingbudgetplan::TYPE_MAP, 'empty' => 'Ausgabenbereich wählen...', 'label' => false, 'class' => 'no-select2']],
        ['name' => 'description', 'options' => ['label' => false, 'placeholder' => 'Zahlungsgrund/Gegenstand/Maßnahme', 'maxlength' => FundingreceiptlistsTable::DESCRIPTION_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::DESCRIPTION_MIN_LENGTH]],
        ['name' => 'recipient', 'options' => ['label' => false, 'placeholder' => 'Zahlungsempfänger*in', 'maxlength' => FundingreceiptlistsTable::RECIPIENT_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECIPIENT_MIN_LENGTH]],
        ['name' => 'receipt_type', 'options' => ['label' => false, 'placeholder' => 'Belegart', 'maxlength' => FundingreceiptlistsTable::RECEIPT_TYPE_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECEIPT_TYPE_MIN_LENGTH]],
        ['name' => 'payment_date', 'options' => ['label' => false, 'placeholder' => 'Zahlungsdatum', 'type' => 'text', 'class' => 'datepicker-input']],
        ['name' => 'receipt_number', 'options' => ['label' => false, 'placeholder' => 'Belegnummer', 'maxlength' => FundingreceiptlistsTable::RECEIPT_NUMBER_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECEIPT_NUMBER_MIN_LENGTH]],
        ['name' => 'amount', 'options' => ['label' => false, 'placeholder' => 'Summe ', 'type' => 'number', 'step' => '0.01']],
        ['name' => 'delete', 'options' => ['type' => 'checkbox', 'label' => 'löschen?', 'class' => 'receiptlist-delete-checkbox'], 'hideInHeading' => true],
    ];

    const FIELDS_FUNDINGRECEIPTLIST_DIFFERENCE_CHECKBOX = [
        ['name' => 'difference_refund_ok', 'options' => ['type' => 'checkbox', 'label' => 'Hiermit bestätige ich, den Restbetrag auf das Konto xxx zu überweisen', 'escape' => false]],
    ];


}