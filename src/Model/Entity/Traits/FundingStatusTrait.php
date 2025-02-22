<?php
declare(strict_types=1);

namespace App\Model\Entity\Traits;

trait FundingStatusTrait {

    const STATUS_PENDING = 10;
    const STATUS_VERIFIED_BY_ADMIN = 20;
    const STATUS_REJECTED_BY_ADMIN = 30;
    const STATUS_BUDGETPLAN_DATA_MISSING = 40;
    const STATUS_DATA_OK = 50;
    const STATUS_DESCRIPTION_MISSING = 60;
    const STATUS_CHECKBOXES_MISSING = 70;
    const STATUS_CHECKBOXES_PENDING = 75;
    const STATUS_CHECKBOXES_OK = 80;
    const STATUS_UPLOAD_MISSING = 90;
    const STATUS_DESCRIPTIONS_PENDING = 100;
    const STATUS_DESCRIPTIONS_MISSING = 105;
    const STATUS_DATA_MISSING = 110;
    const STATUS_RECEIPTLIST_DATA_MISSING = 120;
    const STATUS_RECEIPTLIST_DATA_PENDING = 130;
    const STATUS_QUESTIONS_MISSING = 140;
    const STATUS_QUESTIONS_PENDING = 150;
    const STATUS_QUESTIONS_OK = 160;

    const STATUS_MAPPING_FOR_USAGEPROOF = [
        self::STATUS_DATA_MISSING => 'wurde noch nicht eingereicht',
        self::STATUS_PENDING => 'Bestätigung von Admin ausstehend',
        self::STATUS_VERIFIED_BY_ADMIN => 'von Admin bestätigt',
        self::STATUS_REJECTED_BY_ADMIN => 'von Admin beanstandet',
    ];
    
    const STATUS_MAPPING_CHANGEABLE_BY_ADMIN = [
        self::STATUS_UPLOAD_MISSING => 'wurde noch nicht hochgeladen',
        self::STATUS_DATA_MISSING => 'wurde noch nicht erstellt',
        self::STATUS_PENDING => 'Bestätigung von Admin ausstehend',
        self::STATUS_VERIFIED_BY_ADMIN => 'von Admin bestätigt',
        self::STATUS_REJECTED_BY_ADMIN => 'von Admin beanstandet',
    ];

    const STATUS_MAPPING_FOR_ADMIN_DROPDOWN = [
        self::STATUS_PENDING => 'Bestätigung von Admin ausstehend',
        self::STATUS_VERIFIED_BY_ADMIN => 'von Admin bestätigt',
        self::STATUS_REJECTED_BY_ADMIN => 'von Admin beanstandet',
    ];

    const STATUS_MAPPING = [
        self::STATUS_BUDGETPLAN_DATA_MISSING => 'Du musst mindestens eine investive Maßnahme hinzufügen',
        self::STATUS_DATA_OK => 'Die eingegebenen Daten sind ok',
        self::STATUS_DESCRIPTION_MISSING => 'Die Beschreibung ist unvollständig',
        self::STATUS_CHECKBOXES_MISSING => 'Bitte bestätige alle Checkboxen',
        self::STATUS_CHECKBOXES_PENDING => 'Bitte bestätige alle Checkboxen',
        self::STATUS_CHECKBOXES_OK => 'Alle Checkboxen bestätigt',
        self::STATUS_DESCRIPTIONS_PENDING => 'Der Sachbericht ist unvollständig',
        self::STATUS_DESCRIPTIONS_MISSING => 'Der Sachbericht wurde noch nicht erstellt',
        self::STATUS_RECEIPTLIST_DATA_MISSING => 'Die Belegliste wurde noch nicht erstellt',
        self::STATUS_RECEIPTLIST_DATA_PENDING => 'Die Belegliste ist unvollständig',
        self::STATUS_QUESTIONS_MISSING => 'Der Mini-Fragebogen wurde noch nicht beantwortet',
        self::STATUS_QUESTIONS_PENDING => 'Der Mini-Fragebogen ist noch nicht vollständig beantwortet',
        self::STATUS_QUESTIONS_OK => 'Der Mini-Fragebogen wurde vollständig beantwortet',
    ];
    
}