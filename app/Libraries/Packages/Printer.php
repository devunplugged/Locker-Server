<?php

namespace App\Libraries\Packages;

use App\Libraries\Packages\Package;
use App\Libraries\Fpdf\FpdfExtended;
use \chillerlan\QRCode\QRCode;

class Printer
{

    private $package;
    private $pdf;

    public function setPackage($packageId)
    {
        $this->package = new Package($packageId);
    }

    public function newDocument()
    {
        $this->pdf = new FpdfExtended('P', 'mm', [104.648, 153.416]); //'P', 'mm', [88, 125]
        //custom font is needed for polish letters
        //font has to be generated first from ttf; http://www.fpdf.org/makefont/ or makefont in fpdf src
        //font has to be placed in fonts folder in fpdf src
        $this->pdf->AddFont('CustomFont','','Aller_Rg.php');
        $this->pdf->AddPage();
        $this->pdf->SetFont('CustomFont','',8);
    }

    private function fixEncoding($str)
    {
        return iconv('UTF-8', 'ISO-8859-2', $str);
    }

    public function generatePackageDocument()
    {

        if (!$this->package->package) {
            throw new \Exception("Invalid package ID");
        }

        $packageAddresses = $this->package->getAddress();
        $sendersName = isset($packageAddresses['senders_name']) ? $this->fixEncoding($packageAddresses['senders_name']) : '-';
        $sendersPostcode = isset($packageAddresses['senders_postcode']) ? $this->fixEncoding($packageAddresses['senders_postcode']) : '-';
        $sendersCity = isset($packageAddresses['senders_city']) ? $this->fixEncoding($packageAddresses['senders_city']) : '-';
        $sendersStreet = isset($packageAddresses['senders_street']) ? $this->fixEncoding($packageAddresses['senders_street']) : '-';
        $sendersBuilding = isset($packageAddresses['senders_building']) ? $this->fixEncoding($packageAddresses['senders_building']) : '-';
        $sendersApartment = isset($packageAddresses['senders_apartment']) ? $this->fixEncoding($packageAddresses['senders_apartment']) : '-';
        $sendersPhone = isset($packageAddresses['senders_phone']) ? $this->fixEncoding($packageAddresses['senders_phone']) : '-';
        $sendersEmail = isset($packageAddresses['senders_email']) ? $this->fixEncoding($packageAddresses['senders_email']) : '-';

        $recipientsName = isset($packageAddresses['recipients_name']) ? $this->fixEncoding($packageAddresses['recipients_name']) : '-';
        $recipientsPostcode = isset($packageAddresses['recipients_postcode']) ? $this->fixEncoding($packageAddresses['recipients_postcode']) : '-';
        $recipientsCity = isset($packageAddresses['recipients_city']) ? $this->fixEncoding($packageAddresses['recipients_city']) : '-';
        $recipientsStreet = isset($packageAddresses['recipients_street']) ? $this->fixEncoding($packageAddresses['recipients_street']) : '-';
        $recipientsBuilding = isset($packageAddresses['recipients_building']) ? $this->fixEncoding($packageAddresses['recipients_building']) : '-';
        $recipientsApartment = isset($packageAddresses['recipients_apartment']) ? $this->fixEncoding($packageAddresses['recipients_apartment']) : '-';
        $recipientsPhone = isset($packageAddresses['recipients_phone']) ? $this->fixEncoding($packageAddresses['recipients_phone']) : '-';
        $recipientsEmail = isset($packageAddresses['recipients_email']) ? $this->fixEncoding($packageAddresses['recipients_email']) : '-';

        $this->newDocument();

        $this->pdf->SetXY(5, 5);
        // $this->pdf->SetFontSize(20);
        // $this->pdf->Cell(95, 30, 'ss', 1);

        //paczka
        $this->pdf->SetXY(5, 5);
        $this->pdf->SetFontSize(12);
        $this->pdf->Cell(55, 10, 'Paczka: ' . hashId($this->package->package->id), 1, 2);
        $this->pdf->SetFontSize(8);
        $this->pdf->Cell(55, 6, 'Paczkomat: ' . hashId($this->package->package->locker_id), 1, 2);
        $this->pdf->Cell(55, 6, 'Ref. kod: ' . $this->package->package->ref_code, 1, 2);

        //QR
        $local_name = time().'.png';
        $outQR = (new QRCode)->render($this->package->package->code, ROOTPATH . "public/tmp/" . $local_name);
        $this->pdf->Image(base_url() . "/tmp/" . $local_name, 65, 3, 30, 30);
        unlink(ROOTPATH . "public/tmp/" . $local_name);


        //kod ref
        $this->pdf->SetXY(70, 30);
        $this->pdf->Cell(55, 6, $this->package->package->code, 0, 2);

        //ramka paczki
        $this->pdf->SetXY(5, 5);
        $this->pdf->Cell(95, 30, '', 1, 2);

        //nadawca
        $this->pdf->SetXY(5, 35);
        $this->pdf->SetFontSize(12);
        $this->pdf->Cell(62, 6, 'Nadawca', 0, 2);
        $this->pdf->SetFontSize(8);
        
        $this->pdf->Cell(62, 5, 'Nazwa: ' . $sendersName, 0, 2);
        $this->pdf->Cell(62, 5, 'Poczta: ' . $sendersPostcode . ' ' . $sendersCity, 0, 2);
        $this->pdf->Cell(62, 5, 'Adres: ' . $sendersStreet . ' ' . $sendersBuilding . ' / ' . $sendersApartment, 0, 2);
        $this->pdf->Cell(62, 5, 'Telefon: ' . $sendersPhone, 0, 2);
        $this->pdf->Cell(62, 5, 'E-mail: ' . $sendersEmail, 0, 2);

        //ramka nadawcy
        $this->pdf->SetXY(5, 35);
        $this->pdf->Cell(48, 31, '', 1);

        //odbiorca
        $this->pdf->SetXY(53, 35);
        $this->pdf->SetFontSize(12);
        $this->pdf->Cell(63, 6, 'Odbiorca', 0, 2);
        $this->pdf->SetFontSize(8);
        
        $this->pdf->Cell(63, 5, 'Nazwa: ' . $recipientsName, 0, 2);
        $this->pdf->Cell(63, 5, 'Poczta: ' . $recipientsPostcode . ' ' . $recipientsCity, 0, 2);
        $this->pdf->Cell(63, 5, 'Adres: ' . $recipientsStreet . ' ' . $recipientsBuilding . ' / ' . $recipientsApartment, 0, 2);
        $this->pdf->Cell(63, 5, 'Telefon: ' . $recipientsPhone, 0, 2);
        $this->pdf->Cell(63, 5, 'E-mail: ' . $recipientsEmail, 0, 2);

        //ramka odbiorcy
        $this->pdf->SetXY(53, 35);
        $this->pdf->Cell(47, 31, '', 1, 1);

        //notka rodo
        $this->pdf->SetFontSize(6);
        $this->pdf->SetXY(5, 66);
        $this->pdf->MultiCell(95, 2, 'Administratorem danych osobowych jest Delta z siedziba ul. Graniczna 10 Poznań. Wiecej informacji na delta.poznan.pl', 1, 2);
    }

    public function output()
    {
        if (!$this->package->package) {
            throw new \Exception("No document to output");
        }

        $this->pdf->Output();
    }
}
