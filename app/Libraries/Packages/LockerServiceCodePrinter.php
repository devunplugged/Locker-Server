<?php

namespace App\Libraries\Packages;


use App\Libraries\Fpdf\FpdfExtended;
use \chillerlan\QRCode\QRCode;
use App\Libraries\Packages\LockerServiceCode;

class LockerServiceCodePrinter
{

    private $lockerId;
    private $pdf;
    private $lockerServiceCode;

    public function setLocker($lockerId)
    {

        $this->lockerId = $lockerId;
        $this->lockerServiceCode = new LockerServiceCode($this->lockerId);
    }

    public function newDocument()
    {
        $this->pdf = new FpdfExtended(); //'P', 'mm', [88, 125]
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial', 'B', 8);
    }

    public function generateLockerCodesDocument()
    {

        $this->isValidInput();

        $codes = $this->lockerServiceCode->getCodes();

        $this->newDocument();


        $this->pdf->SetXY(10, 10);
        $this->pdf->SetFontSize(24);
        $this->pdf->Cell(0, 25, 'Kody Serwisowe', 1);

        $this->pdf->SetXY(10, 35);



        for($i = 0; $i < count($codes); $i++){


            $this->pdf->Cell(0, 40, '', 1, 1);

            $currentX = $this->pdf->GetX();
            $currentY = $this->pdf->GetY();

            $local_name = time() . $i . '.png';
            (new QRCode)->render($codes[$i]->code, ROOTPATH . "public/tmp/" . $local_name);

            $this->pdf->Image(base_url() . "/tmp/" . $local_name, 15, $this->pdf->GetY() - 35, 28, 28);
            unlink(ROOTPATH . "public/tmp/" . $local_name);

            $this->pdf->SetXY(60, $this->pdf->GetY() - 36);
            $this->pdf->Cell(0, 25, 'Skrytka ' . $codes[$i]->value, 0, 0);
            $this->pdf->SetXY(60, $this->pdf->GetY() + 7);
            $this->pdf->SetFontSize(12);
            $this->pdf->Cell(0, 25, 'Kod: ' . $codes[$i]->code, 0);
            $this->pdf->SetFontSize(24);
            $this->pdf->SetXY($currentX, $currentY);
        }

    }

    public function output()
    {

        $this->isValidInput();

        $this->pdf->Output();
    }

    private function isValidInput()
    {
        if (!$this->lockerId) {
            throw new \Exception("No document to output");
        }

        if (!$this->lockerServiceCode) {
            throw new \Exception("LockerServiceCode not initialized");
        }
    }
}
