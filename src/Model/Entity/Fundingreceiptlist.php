<?php
declare(strict_types=1);
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Fundingreceiptlist extends Entity
{

    protected function _getIsValid(): bool
    {
        return $this->type > 0 && $this->description != '' && $this->amount > 0;
    }

    protected function _getIsNotEmpty(): bool
    {
        return $this->type > 0 || $this->description != '' || $this->amount > 0;
    }

}
