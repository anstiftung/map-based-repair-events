<?php
declare(strict_types=1);

namespace App\Services\PdfWriter;

use App\Model\Entity\Funding;
use App\Services\PdfWriter\PdfWriterService;
use App\Model\Entity\Fundingupload;
use App\Services\Pdf\ReparaturInitiativenTcpdfService;
use Cake\ORM\TableRegistry;

class FoerderantragPdfWriterService extends PdfWriterService
{

    public function __construct()
    {
        $this->setPdfLibrary(new ReparaturInitiativenTcpdfService());
    }

    public function getFilenameCustom($funding, $timestamp): string
    {
        return 'Foerderantrag_anstiftung_bmuv_' . $funding->uid . '_' . $timestamp->i18nFormat('yyyyMMdd_HHmmss') . '.pdf';
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
        
        $blocks = [
            [
                'name' => Funding::FIELDS_WORKSHOP_LABEL,
                'fields' => $this->getPreparedFields(Funding::FIELDS_WORKSHOP, $funding->workshop),
            ],
            [
                'name' => Funding::FIELDS_OWNER_USER_LABEL,
                'fields' => $this->getPreparedFields(Funding::FIELDS_OWNER_USER, $funding->owner_user),
            ],
            [
                'name' => Funding::FIELDS_FUNDINGSUPPORTER_ORGANIZATION_LABEL,
                'fields' => $this->getPreparedFields(Funding::FIELDS_FUNDINGSUPPORTER_ORGANIZATION, $funding->fundingsupporter),
            ],
            [
                'name' => Funding::FIELDS_FUNDINGSUPPORTER_USER_LABEL,
                'fields' => $this->getPreparedFields(Funding::FIELDS_FUNDINGSUPPORTER_USER, $funding->fundingsupporter),
            ],
            [
                'name' => Funding::FIELDS_FUNDINGSUPPORTER_BANK_LABEL,
                'fields' => $this->getPreparedFields(Funding::FIELDS_FUNDINGSUPPORTER_BANK, $funding->fundingsupporter),
            ],
        ];
        
        $this->setData([
            'blocks' => $blocks,
            'description' => $this->getPreparedFields(Funding::FIELDS_FUNDINGDATA_DESCRIPTION, $funding->fundingdata),
            'checkboxes' => $this->getPreparedFields(Funding::FIELDS_FUNDING_DATA_CHECKBOXES, $funding->fundingdata),
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

