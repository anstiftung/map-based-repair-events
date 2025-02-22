<?php
declare(strict_types=1);

namespace App\Services\PdfWriter;

use App\Model\Entity\Funding;
use App\Services\PdfWriter\PdfWriterService;
use App\Model\Entity\Fundingupload;
use Cake\ORM\TableRegistry;
use App\Services\Pdf\ReparaturInitiativenTcpdfService;

class VerwendungsnachweisPdfWriterService extends PdfWriterService
{

    public function __construct()
    {
        $pdfLibrary = new ReparaturInitiativenTcpdfService();
        $pdfLibrary->showPageNumbers = true;
        $this->setPdfLibrary($pdfLibrary);
    }

    public function getFilenameCustom($funding, $timestamp): string
    {
        return 'Verwendungsnachweis_anstiftung_bmuv_' . $funding->uid . '_' . $timestamp->i18nFormat('yyyyMMdd_HHmmss') . '.pdf';
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
            'sachbericht' => $this->getPreparedFields(Funding::FIELDS_FUNDINGUSAGEPROOF, $funding->fundingusageproof),
            'checkboxes' => $this->getPreparedFields(Funding::FIELDS_USAGEPROOF_CHECKBOXES, $funding->fundingusageproof),
            'timestamp' => $timestamp,
            'funding' => $funding,
        ]);

    }

    private function getPreparedFields($definedFields, $entity): array
    {
        $preparedFields = [];
        foreach($definedFields as $workshopField) {
            $workshopFieldName = $workshopField['name'];
            $preparedFields[$workshopFieldName] = [
                'name' => $workshopFieldName,
                'label' => $workshopField['options']['label'],
                'value' => $entity[$workshopFieldName],
            ];
        }
        return $preparedFields;
    }

}

