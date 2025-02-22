<?php
declare(strict_types=1);

namespace App\Services\PdfWriter;

use App\Services\PdfWriter\PdfWriterService;
use App\Model\Entity\Fundingupload;
use App\Services\Pdf\AnstiftungTcpdfService;
use Cake\ORM\TableRegistry;

class FoerderbewilligungPdfWriterService extends PdfWriterService
{

    public function __construct()
    {
        $this->setPdfLibrary(new AnstiftungTcpdfService());
    }

    public function getFilenameCustom($funding, $timestamp): string
    {
        return 'Foerderbewilligung_anstiftung_bmuv_' . $funding->uid . '_' . $timestamp->i18nFormat('yyyyMMdd_HHmmss') . '.pdf';
    }

    public function getUploadPath($fundingUid): string
    {
        return Fundingupload::UPLOAD_PATH . $fundingUid . DS . 'attachments' . DS;
    }

    public function prepareAndSetData($fundingUid, $timestamp): void
    {

        $fundingsTable = TableRegistry::getTableLocator()->get('Fundings');
        $funding = $fundingsTable->getUnprivatizedFundingWithAllAssociations($fundingUid);

        $filename = $this->getFilenameCustom($funding, $timestamp);
        $this->setFilename($this->getUploadPath($funding->uid) . $filename);
        
        $this->setData([
            'funding' => $funding,
            'timestamp' => $timestamp,
        ]);

    }

}

