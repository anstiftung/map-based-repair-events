<?php
declare(strict_types=1);
namespace App\Model\Entity;

use App\Model\Entity\Traits\FundingAdminFilterTrait;
use App\Model\Entity\Traits\FundingFieldsTrait;
use App\Model\Entity\Traits\FundingStatusTrait;
use Cake\ORM\Entity;
use App\Model\Table\FundingdatasTable;
use App\Model\Table\FundingusageproofsTable;

class Funding extends Entity
{

    use FundingAdminFilterTrait;
    use FundingFieldsTrait;
    use FundingStatusTrait;

    const MAX_FUNDING_SUM = 3000;
    
    public static function getRenderedFields($fields, $entityString, $form, $disabled, $entity = null): string
    {
        $renderedFields = '';
        $fieldsToBeFormattedWithToDigits = ['amount'];
        foreach($fields as $field) {
            if ($entity !== null && in_array($field['name'], $fieldsToBeFormattedWithToDigits)) {
                $value = $entity[$field['name']];
                if ($value !== null) {
                    $field['options']['value'] = number_format((float) $value, 2, '.', '');
                }
            }
            $field['options']['disabled'] = $disabled;
            $preparedEntityString = 'Fundings.' . $entityString . '.' . $field['name'];
            $renderedFields .= $form->control($preparedEntityString, $field['options']);
        }
        return $renderedFields;
    }

    public function _getBudgetplanTotal(): float
    {
        $total = 0;
        foreach($this->fundingbudgetplans as $fundingbudgetplan) {
            if ($fundingbudgetplan->is_valid) {
                $total += $fundingbudgetplan->amount;
            }
        }
        return $total;
    }

    public function _getBudgetplanTotalWithLimit(): float
    {
        $total = $this->budgetplan_total;
        if ($total > self::MAX_FUNDING_SUM) {
            return self::MAX_FUNDING_SUM;
        }
        return $total;
    }

    public function _getGroupedValidBudgetplans(): array
    {
        $result = [];
        foreach($this->fundingbudgetplans as $fundingbudgetplan) {
            if ($fundingbudgetplan->is_valid) {
                $result[$fundingbudgetplan->type][] = $fundingbudgetplan;
            }
        }
        return $result;
    }

    public function _getGroupedValidBudgetplansTotals(): array
    {
        $result = [];
        foreach($this->grouped_valid_budgetplans as $typeId => $fundingbudgetplans) {
            $total = 0;
            foreach($fundingbudgetplans as $fundingbudgetplan) {
                $total += $fundingbudgetplan->amount;
            }
            $result[$typeId] = $total;
        }
        return $result;
    }

    public function _getBudgetplanStatus(): int
    {
        foreach($this->fundingbudgetplans as $fundingbudgetplan) {
            if ($fundingbudgetplan->is_valid && $fundingbudgetplan->type == Fundingbudgetplan::TYPE_A) {
                return self::STATUS_DATA_OK;
            }
        }
        return self::STATUS_BUDGETPLAN_DATA_MISSING;
    }

    public function _getCheckboxesStatus(): int
    {
        $checkboxes = array_map(function($checkbox) {
            return $checkbox['name'];
        }, self::FIELDS_FUNDING_DATA_CHECKBOXES);

        foreach($checkboxes as $checkbox) {
            if (!$this->fundingdata->$checkbox) {
                return self::STATUS_CHECKBOXES_MISSING;
            }
        }
        return self::STATUS_CHECKBOXES_OK;

    }

    public function _getCheckboxesStatusCssClass(): string
    {
        if ($this->checkboxes_status == self::STATUS_CHECKBOXES_MISSING) {
            return 'is-pending';
        }
        if ($this->checkboxes_status == self::STATUS_CHECKBOXES_OK) {
            return 'is-verified';
        }
        return '';
    }

    public function _getCheckboxesStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING[$this->checkboxes_status];
    }

    public function _getBudgetplanStatusCssClass(): string
    {
        if ($this->budgetplan_status == self::STATUS_BUDGETPLAN_DATA_MISSING) {
            return 'is-pending';
        }
        if ($this->budgetplan_status == self::STATUS_DATA_OK) {
            return 'is-verified';
        }
        return '';
    }

    public function _getBudgetplanStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING[$this->budgetplan_status];
    }

    public function _getActivityProofStatusCssClass(): string
    {
        if (!empty($this->workshop) && !$this->workshop->funding_activity_proof_required) {
            return '';
        }
        return $this->getAdminStatusCssClass('activity_proof_status');
    }

    public function _getUsageproofStatusCssClass(): string
    {
        return $this->getAdminStatusCssClass('usageproof_status');
    }

    public function _getFreistellungsbescheidStatusCssClass(): string
    {
        return $this->getAdminStatusCssClass('freistellungsbescheid_status');
    }

    public function _getZuwendungsbestaetigungStatusCssClass(): string
    {
        return $this->getAdminStatusCssClass('zuwendungsbestaetigung_status');
    }

    private function getAdminStatusCssClass($statusField): string
    {
        if ($this->$statusField == self::STATUS_UPLOAD_MISSING || $this->$statusField == self::STATUS_DATA_MISSING) {
            return 'is-missing';
        }
        if ($this->$statusField == self::STATUS_PENDING) {
            return 'is-pending';
        }
        if ($this->$statusField == self::STATUS_VERIFIED_BY_ADMIN) {
            return 'is-verified';
        }
        if ($this->$statusField == self::STATUS_REJECTED_BY_ADMIN) {
            return 'is-rejected';
        }
        return '';
    }

    public function _getUsageproofExists(): bool
    {
        return $this->fundingusageproof_id !== null;
    }

    public function _getUsageproofDescriptionsStatus(): int
    {
        if (!empty($this->fundingusageproof)) {
            $length = mb_strlen($this->fundingusageproof->main_description);
            $isValidMainDescription = isset($this->fundingusageproof->main_description)
                && $length >= FundingusageproofsTable::MAIN_DESCRIPTION_MIN_LENGTH
                && $length <= FundingusageproofsTable::MAIN_DESCRIPTION_MAX_LENGTH;

            $length = mb_strlen($this->fundingusageproof->sub_description);
            $isValidSubDescription = isset($this->fundingusageproof->sub_description)
                && $length >= FundingusageproofsTable::SUB_DESCRIPTION_MIN_LENGTH
                && $length <= FundingusageproofsTable::SUB_DESCRIPTION_MAX_LENGTH;

                if ($isValidMainDescription && $isValidSubDescription) {
                return self::STATUS_DATA_OK;
            };
        }
        return self::STATUS_DESCRIPTIONS_MISSING;
    }

    public function _getUsageproofDescriptionsStatusCssClass(): string
    {
        if ($this->usageproof_descriptions_status == self::STATUS_DATA_OK) {
            return 'is-verified';
        }
        return 'is-pending';
    }

    public function _getUsageproofDescriptionsStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING[$this->usageproof_descriptions_status];
    }

    public function _getReceiptlistStatusHumanReadable(): string
    {
        // TODO add logic here
        return 'is-pending';
    }

    public function _getReceiptlistStatusCssClass(): string
    {
        // TODO add logic here
        return 'is-pending';
    }

    public function _getReceiptlistStatus(): int
    {
        // TODO add logic here
        return self::STATUS_RECEIPTLIST_DATA_MISSING;
    }


    public function _getDescriptionStatus(): int
    {
        $length = mb_strlen($this->fundingdata->description);
        $isValid = isset($this->fundingdata->description)
            && $length >= FundingdatasTable::DESCRIPTION_MIN_LENGTH
            && $length <= FundingdatasTable::DESCRIPTION_MAX_LENGTH;
        if ($isValid) {
            return self::STATUS_DATA_OK;
        };
        return self::STATUS_DESCRIPTIONS_MISSING;
    }

    public function _getDescriptionStatusCssClass(): string
    {
        if ($this->description_status == self::STATUS_DATA_OK) {
            return 'is-verified';
        }
        return 'is-pending';
    }

    public function _getUsageproofStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[$this->usageproof_status];
    }

    public function _getDescriptionStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING[$this->description_status];
    }

    public function _getActivityProofStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[$this->activity_proof_status];
    }

    public function _getFreistellungsbescheidStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[$this->freistellungsbescheid_status];
    }

    public function _getZuwendungsbestaetigungStatusHumanReadable(): string
    {
        return self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[$this->zuwendungsbestaetigung_status];
    }

    public static function getFieldsCount(): int
    {
        return count(self::FIELDS_WORKSHOP)
              + count(self::FIELDS_OWNER_USER)
              + count(self::FIELDS_FUNDINGSUPPORTER_ORGANIZATION)
              + count(self::FIELDS_FUNDINGSUPPORTER_USER)
              + count(self::FIELDS_FUNDINGSUPPORTER_BANK)
              + count(self::FIELDS_FUNDINGDATA_DESCRIPTION)
              + 1 // fundingbudgetplan
              + 1 // checkboxes count as 1
              ;
    }

    public function _getUserFieldsVerifiedCount(): int
    {
        $count = 0;

        if ($this->verified_fields !== null) {
            $count = count($this->verified_fields);
        }

        if ($this->description_status == self::STATUS_DATA_OK) {
            $count++;
        }

        if ($this->budgetplan_status == self::STATUS_DATA_OK) {
            $count++;
        }

        if ($this->checkboxes_status == self::STATUS_CHECKBOXES_OK) {
            $count++;
        }

        return $count;
    }

    public function _getAdminFieldsVerifiedCount(): int {
        
        $count = 0;

        if ($this->workshop->funding_activity_proof_required && $this->activity_proof_status == self::STATUS_VERIFIED_BY_ADMIN) {
            $count++;
        }

        if ($this->freistellungsbescheid_status == self::STATUS_VERIFIED_BY_ADMIN) {
            $count++;
        }

        return $count;
    }

    public function _getUserFieldsCount(): int {
        return self::getFieldsCount();
    }

    public function _getAdminFieldsCount(): int {
        $count = 0;
        if ($this->workshop->funding_activity_proof_required) {
            $count++;
        };
        $count++; // freistellungsbescheid
        return $count;

    }

    public function _getAllFieldsCount(): int {
        return $this->user_fields_count + $this->admin_fields_count;
    }

    public function _getAllFieldsVerifiedCount(): int {
        return $this->user_fields_verified_count + $this->admin_fields_verified_count;
    }

    public function _getUserFieldsVerified(): bool {
        return $this->user_fields_verified_count == $this->user_fields_count;
    }

    public function _getIsSubmittable(): bool {
        return $this->all_fields_verified_count == $this->all_fields_count;
    }

    public function _getIsSubmitted(): bool {
        return $this->submit_date !== null;
    }

    public function _getIsMoneyTransferred(): bool {
        return $this->money_transfer_date !== null;
    }

    public function _getSubmitDateFormatted(): string {
        if ($this->submit_date !== null) {
            return $this->submit_date->format('d.m.Y H:i');
        }
        return '';
    }

    public function _getActivityProofsCount(): int {
        return count($this->fundinguploads_activity_proofs);
    }

    public function _getFreistellungsbescheidsCount(): int {
        return count($this->fundinguploads_freistellungsbescheids);
    }

    public function _getZuwendungsbestaetigungsCount(): int {
        return count($this->fundinguploads_zuwendungsbestaetigungs);
    }

}