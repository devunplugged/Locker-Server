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
        $this->pdf = new FpdfExtended(); //'P', 'mm', [88, 125]
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial', 'B', 8);
    }

    public function generatePackageDocument()
    {

        if (!$this->package->package) {
            throw new \Exception("Invalid package ID");
        }

        $packageAddresses = $this->package->getAddress();
        $sendersName = isset($packageAddresses['senders_name']) ? $packageAddresses['senders_name'] : '-';
        $sendersPostcode = isset($packageAddresses['senders_postcode']) ? $packageAddresses['senders_postcode'] : '-';
        $sendersCity = isset($packageAddresses['senders_city']) ? $packageAddresses['senders_city'] : '-';
        $sendersStreet = isset($packageAddresses['senders_street']) ? $packageAddresses['senders_street'] : '-';
        $sendersBuilding = isset($packageAddresses['senders_building']) ? $packageAddresses['senders_building'] : '-';
        $sendersApartment = isset($packageAddresses['senders_apartment']) ? $packageAddresses['senders_apartment'] : '-';
        $sendersPhone = isset($packageAddresses['senders_phone']) ? $packageAddresses['senders_phone'] : '-';
        $sendersEmail = isset($packageAddresses['senders_email']) ? $packageAddresses['senders_email'] : '-';

        $recipientsName = isset($packageAddresses['recipients_name']) ? $packageAddresses['recipients_name'] : '-';
        $recipientsPostcode = isset($packageAddresses['recipients_postcode']) ? $packageAddresses['recipients_postcode'] : '-';
        $recipientsCity = isset($packageAddresses['recipients_city']) ? $packageAddresses['recipients_city'] : '-';
        $recipientsStreet = isset($packageAddresses['recipients_street']) ? $packageAddresses['recipients_street'] : '-';
        $recipientsBuilding = isset($packageAddresses['recipients_building']) ? $packageAddresses['recipients_building'] : '-';
        $recipientsApartment = isset($packageAddresses['recipients_apartment']) ? $packageAddresses['recipients_apartment'] : '-';
        $recipientsPhone = isset($packageAddresses['recipients_phone']) ? $packageAddresses['recipients_phone'] : '-';
        $recipientsEmail = isset($packageAddresses['recipients_email']) ? $packageAddresses['recipients_email'] : '-';

        $this->newDocument();

        $this->pdf->SetXY(10, 10);
        $this->pdf->Cell(125, 88, '', 1);

        //paczka
        $this->pdf->SetXY(10, 10);
        $this->pdf->SetFontSize(18);
        $this->pdf->Cell(95, 10, 'Paczka: ' . hashId($this->package->package->id), 1, 2);
        $this->pdf->SetFontSize(8);
        $this->pdf->Cell(45, 6, 'Paczkomat: ' . hashId($this->package->package->locker_id), 1, 0);
        $this->pdf->Cell(50, 6, 'Kod: ' . $this->package->package->code, 1, 2);

        //ramka paczki
        $this->pdf->SetXY(10, 10);
        $this->pdf->Cell(95, 30, '', 1, 2);

        //nadawca
        $this->pdf->SetXY(10, 40);
        $this->pdf->SetFontSize(12);
        $this->pdf->Cell(62, 6, 'Nadawca', 0, 2);
        $this->pdf->SetFontSize(8);

        $this->pdf->Cell(62, 6, 'Nazwa: ' . $sendersName, 0, 2);
        $this->pdf->Cell(62, 6, 'Poczta: ' . $sendersPostcode . ' ' . $sendersCity, 0, 2);
        $this->pdf->Cell(62, 6, 'Adres: ' . $sendersStreet . ' ' . $sendersBuilding . ' / ' . $sendersApartment, 0, 2);
        $this->pdf->Cell(62, 6, 'Telefon: ' . $sendersPhone, 0, 2);
        $this->pdf->Cell(62, 6, 'E-mail: ' . $sendersEmail, 0, 2);

        //ramka nadawcy
        $this->pdf->SetXY(10, 40);
        $this->pdf->Cell(62, 36, '', 1);

        //odbiorca
        $this->pdf->SetXY(72, 40);
        $this->pdf->SetFontSize(12);
        $this->pdf->Cell(63, 6, 'Odbiorca', 0, 2);
        $this->pdf->SetFontSize(8);

        $this->pdf->Cell(63, 6, 'Nazwa: ' . $recipientsName, 0, 2);
        $this->pdf->Cell(63, 6, 'Poczta: ' . $recipientsPostcode . ' ' . $recipientsCity, 0, 2);
        $this->pdf->Cell(63, 6, 'Adres: ' . $recipientsStreet . ' ' . $recipientsBuilding . ' / ' . $recipientsApartment, 0, 2);
        $this->pdf->Cell(63, 6, 'Telefon: ' . $recipientsPhone, 0, 2);
        $this->pdf->Cell(63, 6, 'E-mail: ' . $recipientsEmail, 0, 2);

        //ramka odbiorcy
        $this->pdf->SetXY(72, 40);
        $this->pdf->Cell(63, 36, '', 1);


        $local_name = time() . '.png';
        (new QRCode)->render($this->package->package->code, ROOTPATH . "public/tmp/" . $local_name);


        $this->pdf->Image(base_url() . "/tmp/" . $local_name, 105, 10, 30, 30);
        unlink(ROOTPATH . "public/tmp/" . $local_name);
    }

    public function output()
    {
        if (!$this->package->package) {
            throw new \Exception("No document to output");
        }

        $this->pdf->Output();
    }
}
