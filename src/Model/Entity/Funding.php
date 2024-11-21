<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Funding extends Entity
{

    const UPLOAD_PATH = ROOT . DS . 'files_private' . DS . 'fundings' . DS;

    const STATUS_PENDING = 10;
    const STATUS_VERIFIED = 20;
    const STATUS_REJECTED = 30;
    const STATUS_BUDGETPLAN_DATA_MISSING = 40;
    const STATUS_DATA_OK = 50;

    const STATUS_MAPPING = [
        self::STATUS_PENDING => 'Bestätigung von Admin ausstehend',
        self::STATUS_VERIFIED => 'von Admin bestätigt',
        self::STATUS_REJECTED => 'von Admin beanstandet',
        self::STATUS_BUDGETPLAN_DATA_MISSING => 'Du musst mindestens einen Eintrag hinzufügen',
        self::STATUS_DATA_OK => 'Daten sind vollständig und ok',
    ];

    const FIELDS_WORKSHOP = [
        ['name' => 'name', 'options' => ['label' => 'Name der Initiative']],
        ['name' => 'street', 'options' => ['label' => 'Straße + Hausnummer']],
        ['name' => 'zip', 'options' => ['label' => 'PLZ']],
        ['name' => 'city', 'options' => ['label' => 'Stadt']],
        ['name' => 'adresszusatz', 'options' => ['label' => 'Adresszusatz']],
        ['name' => 'email', 'options' => ['label' => 'E-Mail']],
    ];

    const FIELDS_OWNER_USER = [
        ['name' => 'firstname', 'options' => ['label' => 'Vorname']],
        ['name' => 'lastname', 'options' => ['label' => 'Nachname']],
        ['name' => 'email', 'options' => ['label' => 'E-Mail']],
        ['name' => 'street', 'options' => ['label' => 'Straße + Hausnummer']],
        ['name' => 'zip', 'options' => ['label' => 'PLZ']],
        ['name' => 'city', 'options' => ['label' => 'Stadt']],
        ['name' => 'phone', 'options' => ['label' => 'Telefon']],
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
    ];

    const FIELDS_FUNDINGBUDGETPLAN = [
        ['name' => 'id', 'options' => ['type' => 'hidden']],
        ['name' => 'type', 'options' => ['type' => 'select', 'options' => Fundingbudgetplan::TYPE_MAP, 'empty' => 'Förderbereich wählen...', 'label' => false, 'class' => 'no-select2']],
        ['name' => 'description', 'options' => ['label' => false, 'placeholder' => 'Maßnahme/Gegenstand', 'class' => 'no-verify']],
        ['name' => 'amount', 'options' => ['label' => false, 'placeholder' => 'Kosten', 'type' => 'number', 'step' => '0.01',]],
    ];

    public static function getRenderedFields($fields, $entity, $form) {
        $renderedFields = '';
        foreach($fields as $field) {
            $renderedFields .= $form->control('Fundings.' . $entity . '.' . $field['name'], $field['options']);
        }
        return $renderedFields;
    }

    public function _getActivityProofStatusIsVerified() {
        return $this->activity_proof_status == self::STATUS_VERIFIED;
    }

    public function _getBudgetplanStatus() {
        $hasValidRecord = false;
        foreach($this->fundingbudgetplans as $fundingbudgetplan) {
            if ($fundingbudgetplan->type > 0 && $fundingbudgetplan->description != '' && $fundingbudgetplan->amount > 0) {
                $hasValidRecord = true;
            }
        }
        return $hasValidRecord ? self::STATUS_DATA_OK : self::STATUS_BUDGETPLAN_DATA_MISSING;
    }

    public function _getBudgetplanStatusCssClass() {
        if ($this->budgetplan_status == self::STATUS_BUDGETPLAN_DATA_MISSING) {
            return 'is-pending';
        }
        if ($this->budgetplan_status == self::STATUS_DATA_OK) {
            return 'is-verified';
        }
        return '';
    }

    public function _getBudgetplanStatusHumanReadable() {
        return self::STATUS_MAPPING[$this->budgetplan_status];
    }

    public function _getActivityProofStatusCssClass() {

        if (!empty($this->workshop) && !$this->workshop->funding_activity_proof_required) {
            return '';
        }

        if ($this->activity_proof_status == self::STATUS_PENDING) {
            return 'is-pending';
        }
        if ($this->activity_proof_status == self::STATUS_VERIFIED) {
            return 'is-verified';
        }
        if ($this->activity_proof_status == self::STATUS_REJECTED) {
            return 'is-rejected';
        }
        return '';
    }

    public function _getActivityProofStatusHumanReadable() {
        return self::STATUS_MAPPING[$this->activity_proof_status];
    }

    public static function getFieldsCount() {
        return count(self::FIELDS_WORKSHOP)
              + count(self::FIELDS_OWNER_USER)
              + count(self::FIELDS_FUNDINGSUPPORTER_ORGANIZATION)
              + count(self::FIELDS_FUNDINGSUPPORTER_USER)
              + count(self::FIELDS_FUNDINGSUPPORTER_BANK)
              + 1 // fundingbudgetplan
              ;
    }

    public function _getVerifiedFieldsCount(): int {
        $result = 0;

        if ($this->verified_fields !== null) {
            $result = count($this->verified_fields);
        }

        if ($this->workshop->funding_activity_proof_required && $this->activity_proof_status == self::STATUS_VERIFIED) {
            $result++;
        }

        return $result;
    }

    public function _getAllFieldsVerified(): bool {
        return $this->verified_fields_count == $this->required_fields_count;
    }

    public function _getFundinguploadsActivityProofs(): array {
        if ($this->fundinguploads === null) {
            return [];
        }
        return array_filter($this->fundinguploads, function($upload) {
            return $upload->type == Fundingupload::TYPE_ACTIVITY_PROOF;
        });
    }

    public function _getActivityProofsCount(): int {
        return count($this->fundinguploads_activity_proofs);
    }

    public function _getRequiredFieldsCount(): int {
        $result = self::getFieldsCount();
        if ($this->workshop->funding_activity_proof_required) {
            $result++;
        };
        return $result;
    }

}