<?php
declare(strict_types=1);
namespace App\Model\Entity;

use Cake\ORM\Entity;

class Fundingupload extends Entity
{

    const UPLOAD_PATH = ROOT . DS . 'files_private' . DS . 'fundings' . DS;

    const TYPE_ACTIVITY_PROOF = 1;
    const TYPE_FREISTELLUNGSBESCHEID = 2;
    const TYPE_ZUWENDUNGSBESTAETIGUNG = 3;
    const TYPE_PR_MATERIAL = 4;

    const TYPE_MAP_STEP_1 = [
        self::TYPE_ACTIVITY_PROOF => 'activity_proofs',
        self::TYPE_FREISTELLUNGSBESCHEID => 'freistellungsbescheids',
    ];

    const TYPE_MAP_STEP_2 = [
        self::TYPE_ZUWENDUNGSBESTAETIGUNG => 'zuwendungsbestaetigungs',
    ];

    const TYPE_MAP_STEP_3 = [
        self::TYPE_PR_MATERIAL => 'pr_materials',
    ];

    public function _getFullPath(): string
    {
        return self::UPLOAD_PATH . $this->funding_uid . DS . $this->filename;
    }

}
