<?php
declare(strict_types=1);
namespace App\Services\Pdf;

use TCPDF;

abstract class AppTcpdfService extends TCPDF
{

    public string $backgroundImageFile = '';
    public int $fontSizeBig = 12;
    public int $fontSizeDefault = 10;
    public int $fontSizeSmall = 8;
    public bool $showPageNumbers = false;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        mb_internal_encoding($encoding);
        $this->SetFont('verdana');
        $this->SetFontSize($this->fontSizeDefault);
        $this->SetCreator('anstiftung');
        $this->SetAuthor('anstiftung');
        $this->SetPrintHeader(true);
        $this->SetPrintFooter(true);
        $this->SetLeftMargin(18);
        $this->SetRightMargin(18);
    }

    public function SetFontSizeBig(): void
    {
        $this->SetFontSize($this->fontSizeBig);
    }

    public function SetFontSizeDefault(): void
    {
        $this->SetFontSize($this->fontSizeDefault);
    }

    public function SetFontSizeSmall(): void
    {
        $this->SetFontSize($this->fontSizeSmall);
    }

    public function setDefaults(): void
    {
        $this->AddPage();
        $this->SetAutoPageBreak(true, 20);
    }

    public function drawLine(): void
    {
        $x1 = $this->getMargins()['left'];
        $x2 = $this->getPageWidth() - $this->getMargins()['right'];
        $this->Line($x1, $this->y, $x2, $this->y);
    }

    public function Header(): void
    {
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false, 0);

        $pageWidth = $this->getPageWidth();
        $pageHeight = $this->getPageHeight();

        $this->Image($this->backgroundImageFile, 0, 0, $pageWidth, $pageHeight);

        if ($this->GetPage() > 1) {
            $this->SetTopMargin(30);
        }
    }

    public function Footer(): void
    {
        if ($this->showPageNumbers && $this->getAliasNbPages() > 1) {
            $this->SetFont('verdana');
            $this->SetFontSizeSmall();
            $this->SetY(-15);
            $this->Cell(0, 10, 'Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages());
            $this->SetFontSizeDefault();
        }
    }


    public function getFundingReceiptlistAsTable(
        $data,
        $width = '100%',
        $widthColumnA = '20%',
        $widthColumnB = '20%',
        $widthColumnC = '20%',
        $widthColumnD = '20%',
        $widthColumnE = '20%',
        $widthColumnF = '20%',
        $alignmentColumnA = 'left',
        $alignmentColumnB = 'left',
        $alignmentColumnC = 'left',
        $alignmentColumnD = 'left',
        $alignmentColumnE = 'left',
        $alignmentColumnF = 'right',
        ): string
    {

        $html = '<table width="' . $width . '" border="0" cellpadding="1">';
            $i = 0;
            foreach($data as $value) {
                $style = '';
                if ($i % 2 == 1) {
                    $style .= 'background-color:#f0f0f0;';
                }

                $html .= '<tr>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnA.'" align="' . $alignmentColumnA . '">';
                        $html .= $value['columnA'];
                    $html .= '</td>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnB.'" align="' . $alignmentColumnB . '">';
                        $html .= $value['columnB'];
                    $html .= '</td>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnC.'" align="' . $alignmentColumnC . '">';
                        $html .= $value['columnC'];
                    $html .= '</td>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnD.'" align="' . $alignmentColumnD . '">';
                        $html .= $value['columnD'];
                    $html .= '</td>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnE.'" align="' . $alignmentColumnE . '">';
                        $html .= $value['columnE'];
                    $html .= '</td>';
                    $html .= '<td style="' . $style . '" width="'.$widthColumnF.'" align="' . $alignmentColumnF . '">';
                        $html .= $value['columnF'];
                    $html .= '</td>';
                $html .= '</tr>';
                $i++;
            }
        $html .= '</table>';
        return $html;
    }
    
    public function getFundingDataAsTable($data, $width = '100%', $widthColumnA = '30%', $widthColumnB = '70%', $alignmentColumnA = 'left', $alignmentColumnB = 'left'): string
    {
        $html = '<table width="' . $width . '" border="0" cellpadding="0">';
            foreach($data as $value) {
                $html .= '<tr>';
                    $html .= '<td width="'.$widthColumnA.'" align="' . $alignmentColumnA . '">';
                        $html .= $value['label'];
                    $html .= '</td>';
                    $html .= '<td width="'.$widthColumnB.'" align="' . $alignmentColumnB . '">';
                        $html .= $value['value'];
                    $html .= '</td>';
                $html .= '</tr>';
            }
        $html .= '</table>';
        return $html;
    }

}
