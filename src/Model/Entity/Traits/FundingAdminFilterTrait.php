<?php
declare(strict_types=1);

namespace App\Model\Entity\Traits;

trait FundingAdminFilterTrait {

    
    public static function getAdminFilterOptions(): array {
        return [
            'to-be-verified-by-admins' =>  self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[self::STATUS_PENDING],
            'rejected-by-admins' => self::STATUS_MAPPING_CHANGEABLE_BY_ADMIN[self::STATUS_REJECTED_BY_ADMIN],
            'no-data-verified' => 'Noch keine Felder bestätigt',
            'not-submitted' => 'Förderantrag nicht eingereicht',
            'submitted' => 'Förderantrag eingereicht und noch nicht überwiesen',
            'submitted-and-money-transferred' => 'Förderantrag überwiesen',
            'submittable-but-not-submitted' => 'Förderantrag vollständig, aber noch nicht eingereicht',
            'at-least-one-verfied-by-admin-and-not-all-fields-verified' => 'mind. ein "bestätigt von Admin", aber noch nicht 32 Felder ausgefüllt',
        ];
    }

    public static function getAdminFilterConditions(): array {
        return [
            'to-be-verified-by-admins' => 'Fundings.activity_proof_status = ' . self::STATUS_PENDING . 
                                            ' OR Fundings.freistellungsbescheid_status = ' . self::STATUS_PENDING . 
                                            ' OR (Fundings.submit_date IS NOT NULL AND Fundings.zuwendungsbestaetigung_status = ' . self::STATUS_PENDING . ') '.
                                            ' OR Fundings.usageproof_status = ' . self::STATUS_PENDING,
            'rejected-by-admins' => 'Fundings.activity_proof_status = ' . self::STATUS_REJECTED_BY_ADMIN . 
                                    ' OR Fundings.freistellungsbescheid_status = ' . self::STATUS_REJECTED_BY_ADMIN . 
                                    ' OR (Fundings.submit_date IS NOT NULL AND Fundings.zuwendungsbestaetigung_status = ' . self::STATUS_REJECTED_BY_ADMIN . ') ' . 
                                    ' OR Fundings.usageproof_status = ' . self::STATUS_REJECTED_BY_ADMIN,
            'no-data-verified' => 'Fundings.verified_fields IS NULL',
            'not-submitted' => 'Fundings.submit_date IS NULL',
            'submitted' => 'Fundings.submit_date IS NOT NULL AND Fundings.money_transfer_date IS NULL',
            'submitted-and-money-transferred' => 'Fundings.submit_date IS NOT NULL AND Fundings.money_transfer_date IS NOT NULL',
            'submittable-but-not-submitted' => function($funding) {
                return $funding->is_submittable && !$funding->is_submitted;
            },
            'at-least-one-verfied-by-admin-and-not-all-fields-verified' => function($funding) {
                return $funding->admin_fields_verified_count > 0 && $funding->user_fields_verified_count < $funding->user_fields_count;
            },
        ];
    }

}