<?php
declare(strict_types=1);
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use App\Model\Entity\Fundingbudgetplan;
use App\Controller\Component\StringComponent;
use Cake\Event\EventInterface;
use ArrayObject;
use App\Model\Entity\Fundingreceiptlist;
use Cake\I18n\Date;

class FundingreceiptlistsTable extends Table
{

    const DESCRIPTION_MIN_LENGTH = 2;
    const DESCRIPTION_MAX_LENGTH = 150;
    const DESCRIPTION_ERROR_MESSAGE = self::DESCRIPTION_MIN_LENGTH . ' bis ' . self::DESCRIPTION_MAX_LENGTH . ' Zeichen';

    const RECIPIENT_MIN_LENGTH = 5;
    const RECIPIENT_MAX_LENGTH = 100;
    const RECIPIENT_ERROR_MESSAGE = self::RECIPIENT_MIN_LENGTH . ' bis ' . self::RECIPIENT_MAX_LENGTH . ' Zeichen';

    const RECEIPT_TYPE_MIN_LENGTH = 5;
    const RECEIPT_TYPE_MAX_LENGTH = 100;
    const RECEIPT_TYPE_ERROR_MESSAGE = self::RECEIPT_TYPE_MIN_LENGTH . ' bis ' . self::RECEIPT_TYPE_MAX_LENGTH . ' Zeichen';

    const RECEIPT_NUMBER_MIN_LENGTH = 1;
    const RECEIPT_NUMBER_MAX_LENGTH = 30;
    const RECEIPT_NUMBER_ERROR_MESSAGE = self::RECEIPT_NUMBER_MIN_LENGTH . ' bis ' . self::RECEIPT_NUMBER_MAX_LENGTH . ' Zeichen';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
        $this->belongsTo('Fundings', [
            'foreignKey' => 'funding_uid'
        ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $data = StringComponent::cleanAllStringsInData($data);
        foreach($data as $key => $value) {
            if ($key == 'payment_date' && $value != '') {
                try {
                    $data[$key] = new Date($value);
                } catch (\Exception $e) {
                    $data[$key] = $value;
                }
            }
        }
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator
            ->add('type', 'valid', [
                'rule' => function ($value, $context) {
                    return array_key_exists($value, Fundingbudgetplan::TYPE_MAP); // using budgetplan is ok here
                },
                'message' => 'Ausgabenbereich auswählen',
            ]);

        $validator
            ->add('description', 'valid', [
                'rule' => function ($value, $context) {
                    $length = mb_strlen($value);
                    return $length >= self::DESCRIPTION_MIN_LENGTH && $length <= self::DESCRIPTION_MAX_LENGTH;
                },
                'message' => self::DESCRIPTION_ERROR_MESSAGE,
            ]);

        $validator
            ->add('recipient', 'valid', [
                'rule' => function ($value, $context) {
                    $length = mb_strlen($value);
                    return $length >= self::RECIPIENT_MIN_LENGTH && $length <= self::RECIPIENT_MAX_LENGTH;
                },
                'message' => self::RECIPIENT_ERROR_MESSAGE,
            ]);

        $validator
            ->add('receipt_type', 'valid', [
                'rule' => function ($value, $context) {
                    $length = mb_strlen($value);
                    return $length >= self::RECEIPT_TYPE_MIN_LENGTH && $length <= self::RECEIPT_TYPE_MAX_LENGTH;
                },
                'message' => self::RECEIPT_TYPE_ERROR_MESSAGE,
            ]);

        $validator->date('payment_date', ['dmy'], 'Bitte gib ein gültiges Datum (TT.MM.JJJJ) ein');

        $validator
            ->add('payment_date', 'range', [
                'rule' => function ($value, $context) {
                    if ($value != '') {
                        $formattedValue = $value->format('Y-m-d');
                        return $formattedValue >= '2025-01-09' && $formattedValue <= '2026-02-28';
                    }
                    return true;
                },
                'message' => 'Das Datum muss zwischen 09.01.2025 und 28.02.2026 liegen.',
            ]);

        $validator
            ->add('receipt_number', 'valid', [
                'rule' => function ($value, $context) {
                    $length = mb_strlen($value);
                    return $length >= self::RECEIPT_NUMBER_MIN_LENGTH && $length <= self::RECEIPT_NUMBER_MAX_LENGTH;
                },
                'message' => self::RECEIPT_NUMBER_ERROR_MESSAGE,
            ]);

        $validator
            ->add('amount', 'valid', [
                'rule' => function ($value, $context) {
                    return $value > 0;
                },
                'message' => 'Betrag muss größer als 0 sein',
            ]);

        return $validator;
    }

    public function getCountForFunding($fundingUid): int
    {
        return $this->find()->where([
            'funding_uid' => $fundingUid,
        ])->count();
    }

    public function createNewUnvalidatedEmptyEntity($fundingUid): Fundingreceiptlist
    {
        $newReceiptlistData = [
            'funding_uid' => $fundingUid,
            'description' => '',
            'amount' => null,
            'type' => 0,
        ];
        return $this->newEntity($newReceiptlistData, ['validate' => false]);
    }

}

?>