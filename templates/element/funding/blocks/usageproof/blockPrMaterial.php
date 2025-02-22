<?php
declare(strict_types=1);

use App\Model\Entity\Fundingupload;

echo '<fieldset class="fundinguploads">';

    echo '<legend>PR-Material hochladen (optional)</legend>';

    echo '<div style="padding:10px;">';
        echo '<p>';
            echo 'Hier kannst du zusätzliches PR-Material hochladen. Maximal fünf Dateien können hochgeladen werden.';
        echo '</p>';
    echo '</div>';

    echo $this->element('funding/blocks/upload/listUploadsAndUploadForm', [
        'uploadType' => Fundingupload::TYPE_MAP_STEP_3[Fundingupload::TYPE_PR_MATERIAL],
        'fundinguploads' => $funding->fundinguploads_pr_materials,
        'showUploadForm' => !$disabled,
        'validationMessage' => 'Nur PDF, JPG und PNG-Dateien sind erlaubt. Jede Datei muss kleiner als 5 MB sein.',
        'multiple' => 'multiple',
    ]);

echo '</fieldset>';
