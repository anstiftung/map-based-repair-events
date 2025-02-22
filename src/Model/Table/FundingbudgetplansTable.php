<?php
declare(strict_types=1);
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use App\Model\Entity\Fundingbudgetplan;
use App\Controller\Component\StringComponent;
use Cake\Event\EventInterface;
use ArrayObject;

class FundingbudgetplansTable extends Table
{

    const DESCRIPTION_MIN_LENGTH = 5;
    const DESCRIPTION_MAX_LENGTH = 100;
    const DESCRIPTION_ERROR_MESSAGE = self::DESCRIPTION_MIN_LENGTH . ' bis ' . self::DESCRIPTION_MAX_LENGTH . ' Zeichen';

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
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator
            ->add('type', 'valid', [
                'rule' => function ($value, $context) {
                    if (!empty($context['data']['description']) || !empty($context['data']['amount'])) {
                        return array_key_exists($value, Fundingbudgetplan::TYPE_MAP);
                    }
                    return true;
                },
                'message' => 'Förderbereich auswählen',
            ]);

        $validator
            ->add('description', 'valid', [
                'rule' => function ($value, $context) {
                    $descriptionLength = mb_strlen($value);
                    $mainCheck = $descriptionLength >= self::DESCRIPTION_MIN_LENGTH && $descriptionLength <= self::DESCRIPTION_MAX_LENGTH;

                    $typeIsEmpty = empty($context['data']['type']);
                    if ($typeIsEmpty) {
                        if ($value == '') {
                            return true;
                        }
                        return $mainCheck;
                    }

                    return $mainCheck;
                },
                'message' => self::DESCRIPTION_ERROR_MESSAGE,
            ]);

        $validator
            ->add('amount', 'valid', [
                'rule' => function ($value, $context) {
                    $mainCheck = $value > 0;
                    
                    $typeIsEmpty = empty($context['data']['type']);
                    if ($typeIsEmpty) {
                        if ($value == '') {
                            return true;
                        }
                        return $mainCheck;
                    }

                    return $mainCheck;
                },
                'message' => 'Betrag muss größer als 0 sein',
            ]);

        return $validator;
    }    

}

?>