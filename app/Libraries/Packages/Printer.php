<?php
namespace App\Libraries\Packages;

use App\Models\PackageModel;
use App\Libraries\Fpdf\FpdfExtended;
use \chillerlan\QRCode\QRCode;

class Printer{

    private $package;
    private $pdf;

    public function setPackage($packageId){
       
        $packageModel = new PackageModel();
        $this->package = $packageModel->get($packageId);

    }

    public function newDocument(){
        $this->pdf = new FpdfExtended();//'P', 'mm', [88, 125]
        $this->pdf->AddPage();
        $this->pdf->SetFont('Arial','B',8);
    }

    public function generatePackageDocument(){
        
        if(!$this->package){
            throw new \Exception("Invalid package ID");
        }

        $this->newDocument();

        $this->pdf->SetXY(5, 5);
        $this->pdf->Cell(125, 88, '', 1);

        $this->pdf->SetXY(5, 5);
        $this->pdf->SetFontSize(24);
        $this->pdf->Cell(23, 25, 'Delta', 1);
        
        $this->pdf->SetXY(28, 5);
        $this->pdf->Cell(72, 25, '', 1);
        //A set
        //$code='CODE 39';
        //$this->pdf->SetXY(35,5);
        //$this->pdf->Write(5,'A set: "'.$code.'"');
        $this->pdf->Code39(29,7,$this->package->code,1,15);
        
/*
        //B set
        $code='Code 39';
        $this->pdf->SetXY(10,40);
        $this->pdf->Write(5,'B set: "'.$code.'"');
        $this->pdf->Code39(10,45,$code,1,15);
        

        //C set
        $code='12345678901234567890';
        $this->pdf->SetXY(10,70);
        $this->pdf->Write(5,'C set: "'.$code.'"');
        $this->pdf->Code39(10,75,$code,1,15);
        

        //A,C,B sets
        $code='ABCDEFG1234567890AbCdEf';
        $this->pdf->SetXY(10,100);
        $this->pdf->Write(5,'ABC sets combined: "'.$code.'"');
        $this->pdf->Code39(10,105,$code,1,15);
 */       
        $local_name = time().'.png';
        $imagePath = ROOTPATH . "writable/tmp/" . $local_name;
        $outQR = (new QRCode)->render($this->package->code, $imagePath);
        
        
        $this->pdf->Image($imagePath, 100, 3, 30, 30);
        unlink($imagePath);

    }

    public function output(){

        if(!$this->package){
            throw new \Exception("No document to output");
        }

        $this->pdf->Output();

    }
}