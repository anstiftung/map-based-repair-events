<?php
declare(strict_types=1);
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\EventInterface;
use ArrayObject;
use App\Controller\Component\StringComponent;

class FundingusageproofsTable extends Table
{

    const MAIN_DESCRIPTION_MIN_LENGTH = 250;
    const MAIN_DESCRIPTION_MAX_LENGTH = 1500;
    const MAIN_DESCRIPTION_ERROR_MESSAGE = self::MAIN_DESCRIPTION_MIN_LENGTH . ' bis ' . self::MAIN_DESCRIPTION_MAX_LENGTH . ' Zeichen';

    const DIFFERENCE_DECLARATION_MIN_LENGTH = 140;
    const DIFFERENCE_DECLARATION_MAX_LENGTH = 1000;
    const DIFFERENCE_DECLARATION_ERROR_MESSAGE = self::DIFFERENCE_DECLARATION_MIN_LENGTH . ' bis ' . self::DIFFERENCE_DECLARATION_MAX_LENGTH . ' Zeichen';

    const QUESTION_TEXT_A_MIN_LENGTH = 50;
    const QUESTION_TEXT_A_MAX_LENGTH = 500;
    const QUESTION_TEXT_A_ERROR_MESSAGE = self::QUESTION_TEXT_A_MIN_LENGTH . ' bis ' . self::QUESTION_TEXT_A_MAX_LENGTH . ' Zeichen';

    const QUESTION_TEXT_B_MAX_LENGTH = 1500;
    const QUESTION_TEXT_B_ERROR_MESSAGE = ' max. ' . self::QUESTION_TEXT_B_MAX_LENGTH . ' Zeichen';

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Timestamp');
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        $data = StringComponent::cleanAllStringsInData($data);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->lengthBetween(
            'main_description',
            [
                self::MAIN_DESCRIPTION_MIN_LENGTH,
                self::MAIN_DESCRIPTION_MAX_LENGTH,
            ],
            self::MAIN_DESCRIPTION_ERROR_MESSAGE,
        );

        $validator->lengthBetween(
            'question_text_a',
            [
                self::QUESTION_TEXT_A_MIN_LENGTH,
                self::QUESTION_TEXT_A_MAX_LENGTH,
            ],
            self::QUESTION_TEXT_A_ERROR_MESSAGE,
        );
        
        $validator->allowEmptyString('question_text_b');
        $validator->maxLength(
            'question_text_b',
            self::QUESTION_TEXT_B_MAX_LENGTH,
            self::QUESTION_TEXT_A_ERROR_MESSAGE,
        );

        $validator
            ->add('difference_declaration', 'valid', [
                'rule' => function ($value, $context) {
                    if ($context['data']['checkbox_a'] == 0) {
                        return true;
                    }
                    $descriptionLength = mb_strlen($value);
                    return $descriptionLength >= self::DIFFERENCE_DECLARATION_MIN_LENGTH && $descriptionLength <= self::DIFFERENCE_DECLARATION_MAX_LENGTH;
                },
                'message' => self::DIFFERENCE_DECLARATION_ERROR_MESSAGE,
            ]);

        return $validator;
    }

}

?>