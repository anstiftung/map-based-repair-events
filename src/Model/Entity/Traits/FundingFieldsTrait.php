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
    const FIELDS_FUNDINGBUDGETPLAN_GROUPED_LABEL = 'Eingereichter Kostenplan';
    const FIELDS_FUNDING_DATA_CHECKBOXES_LABEL = 'Einverständniserklärungen';
    const FIELDS_FUNDINGUSAGEPROOF_LABEL = 'Sachbericht';
    const FIELDS_FUNDINGRECEIPTLIST_LABEL = 'Belegliste';
    const FIELDS_USAGEPROOF_CHECKBOXES_LABEL = 'Bestätigungen';
    const FIELDS_FUNDINGRECEIPTLIST_CHECKBOXES_LABEL = 'Bestätigungen Belegliste';
    const FIELDS_USAGEPROOF_QUESTIONS_LABEL = 'Mini-Fragebogen';

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
    ];

    const FIELDS_FUNDINGRECEIPTLIST = [
        ['name' => 'id', 'options' => ['type' => 'hidden'], 'hideInHeading' => true],
        ['name' => 'type', 'options' => ['type' => 'select', 'placeholder' => 'Ausgabenbereich', 'options' => Fundingbudgetplan::TYPE_MAP, 'empty' => 'Ausgabenbereich wählen...', 'label' => false, 'class' => 'no-select2']],
        ['name' => 'description', 'options' => ['label' => false, 'placeholder' => 'Zahlungsgrund/Gegenstand/Maßnahme', 'maxlength' => FundingreceiptlistsTable::DESCRIPTION_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::DESCRIPTION_MIN_LENGTH]],
        ['name' => 'recipient', 'options' => ['label' => false, 'placeholder' => 'Zahlungsempfänger*in', 'maxlength' => FundingreceiptlistsTable::RECIPIENT_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECIPIENT_MIN_LENGTH]],
        ['name' => 'receipt_type', 'options' => ['label' => false, 'placeholder' => 'Belegart', 'maxlength' => FundingreceiptlistsTable::RECEIPT_TYPE_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECEIPT_TYPE_MIN_LENGTH]],
        ['name' => 'payment_date', 'options' => ['label' => false, 'placeholder' => 'Zahlungsdatum', 'type' => 'text', 'class' => 'datepicker-input']],
        ['name' => 'receipt_number', 'options' => ['label' => false, 'placeholder' => 'Belegnummer', 'maxlength' => FundingreceiptlistsTable::RECEIPT_NUMBER_MAX_LENGTH, 'minlength' => FundingreceiptlistsTable::RECEIPT_NUMBER_MIN_LENGTH]],
        ['name' => 'amount', 'options' => ['label' => false, 'placeholder' => 'Summe ', 'type' => 'number', 'step' => '0.01']],
        ['name' => 'delete', 'options' => ['type' => 'checkbox', 'label' => 'löschen?', 'class' => 'receiptlist-delete-checkbox'], 'hideInHeading' => true],
    ];

    const FIELDS_FUNDINGRECEIPTLIST_PAYBACK_CHECKBOX = [
        ['name' => 'payback_ok', 'options' => ['type' => 'checkbox', 'label' => 'Hiermit bestätige ich, den <b>Restbetrag von {RESTBETRAG} €</b> mit dem <b>Verwendungsnachweis UID: {UID}</b> innerhalb von 14 Tagen auf auf das Konto <b>{KONTONUMMER}</b> zurück zu überweisen', 'escape' => false]],
        ['name' => 'checkbox_a', 'options' => ['type' => 'checkbox', 'label' => 'Ja, die tatsächlich angefallenen Ausgaben weichen erheblich vom Kostenplan des eingereichten Förderantrags ab.', 'escape' => false]],
        ['name' => 'difference_declaration', 'options' => ['label' =>  'Erklärung zu Abweichung der Belegliste' . '<br />' . FundingusageproofsTable::DIFFERENCE_DECLARATION_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 10, 'maxlength' => FundingusageproofsTable::DIFFERENCE_DECLARATION_MAX_LENGTH, 'minlength' => FundingusageproofsTable::DIFFERENCE_DECLARATION_MIN_LENGTH, 'escape' => false]],
    ];

    const USAGEPROOF_QUESTION_OPTIONS = [
        0 => 'trifft voll zu',
        1 => 'trifft eher zu',
        2 => 'trifft eher nicht zu',
        3 => 'trifft überhaupt nicht zu',
        4 => 'keine Angabe',
    ];

    const FIELDS_USAGEPROOF_QUESTIONS = [
        ['name' => 'question_radio_a', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Durch die Förderung können/konnten wir Anschaffungen tätigen, die unser Spektrum an Reparier-Angeboten erweitern</b><br /><br />', 'escape' => false]],
        ['name' => 'question_radio_b', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Durch die Förderung können/konnten wir Fähigkeiten und Kompetenzen erwerben oder ausbauen, die unser Spektrum an Reparier-Angeboten erweitern</b><br /><br />', 'escape' => false]],
        ['name' => 'question_radio_c', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Durch die Förderung können/konnten wir mehr Veranstaltungen verwirklichen</b><br /><br />', 'escape' => false]],
        ['name' => 'question_radio_d', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Es kommen seitdem mehr Menschen zu den Veranstaltungen</b><br /><br />', 'escape' => false]],
        ['name' => 'question_radio_e', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Die Förderung trägt dazu bei, unsere Reparatur-Initiative/Selbsthilfewerkstatt zu sichern/zu verstetigen</b><br /><br />', 'escape' => false]],
        ['name' => 'question_text_a', 'options' => ['label' =>  '<b>Welche Reparaturen können jetzt begleitet werden, die früher nicht möglich waren, z.B. nach Qualifikationen, Fortbildungen oder durch neue Geräte? Nenne bitte  Beispiele und ordne sie nach Fachbereichen (wie Tischlerei, Elektronik, Schneiderhandwerk, Leder oder IT) oder nach speziellen Tätigkeiten</b><br />' . FundingusageproofsTable::QUESTION_TEXT_A_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 10, 'maxlength' => FundingusageproofsTable::QUESTION_TEXT_A_MAX_LENGTH, 'minlength' => FundingusageproofsTable::QUESTION_TEXT_A_MIN_LENGTH, 'escape' => false]],
        ['name' => 'question_radio_f', 'options' => ['type' => 'radio', 'options' => self::USAGEPROOF_QUESTION_OPTIONS, 'label' => '<b>Wie zufrieden wart ihr mit der Durchführung des Förderprogramms?</b><br /><br />', 'escape' => false]],
        ['name' => 'question_text_b', 'options' => ['label' =>  '<b>Wenn du uns noch etwas mitteilen möchtest... (optional)</b><br />' . FundingusageproofsTable::QUESTION_TEXT_B_ERROR_MESSAGE, 'type' => 'textarea', 'rows' => 10, 'maxlength' => FundingusageproofsTable::QUESTION_TEXT_B_MAX_LENGTH, 'escape' => false]],
    ];

    const FIELDS_USAGEPROOF_CHECKBOXES = [
        ['name' => 'checkbox_b', 'options' => ['type' => 'checkbox', 'label' => 'Hiermit bestätige ich die Richtigkeit der oben gemachten Angaben.', 'escape' => false]],
        ['name' => 'checkbox_c', 'options' => ['type' => 'checkbox', 'label' => 'Hiermit bestätige ich, dass alle Belege als Papierdokumente vorhanden sind und ihre Nummerierung der digitalen Liste entspricht, sowie auf Anfrage vollständig und zeitnah vorgelegt werden.', 'escape' => false]],
        ['name' => 'checkbox_d', 'options' => ['type' => 'checkbox', 'label' => 'Hiermit bestätige ich, dass das hochgeladene PR-Material verwendet werden darf.', 'escape' => false]],
    ];

}