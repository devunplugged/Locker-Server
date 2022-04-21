<?php

namespace App\Controllers;

use \Firebase\JWT\JWT;
use App\Models\PackageModel;
use App\Models\TaskModel;
use App\Models\CellModel;
use App\Models\CompanyModel;
use App\Models\ApiClientModel;
use App\Models\DetailModel;
use App\Models\DiagnosticModel;
use \chillerlan\QRCode\QRCode;
use App\Libraries\Fpdf\FpdfCode39;

class Dashboard extends BaseController
{
    public function keys()
    {
        $key = getenv('JWT_SECRET');
        $iat = time(); // current timestamp value
        $exp = $iat + (3600 * 24 * 365);
 
        $payload = array(
            "iss" => "ci4-auth-test.local",
            "aud" => "ci4-auth-test.local",
            "sub" => "Locker auth token",
            "iat" => $iat, //Time the JWT issued at
            "exp" => $exp, // Expiration time of token
            "client" => "locker",//$user['email'],
            "clientId" => 'YN7RNK', //temp. locker id
            'companyId' => 'G08ZX9', //temp. company id
            //to do
            //add some unique code that can be banned (?)
        );
         
        $token = JWT::encode($payload, $key);
 
        //echo "<h1>$token</h1>";

        return view('Views\token',['token' => $token]);
    }

    public function selectKeyToGenerate(){
        $companyModel = new CompanyModel();
        $companies = $companyModel->getAll();
        $apiClientModel = new ApiClientModel();
        $apiClients = $apiClientModel->getAll();
        return view('Views\select-token-type',['companies' => $companies, 'apiClients' => $apiClients]);
    }

    public function generateToken(){

    }

    public function package($packageId){
        $packageModel = new PackageModel();
        $package = $packageModel->getPackageByCode($packageId);

        if(empty($package)){
            return view('Views\package-wrong-code',['code' => $packageId]);
        }

        $inQR = (new QRCode)->render($package[0]->code);
        $outQR = (new QRCode)->render($package[0]->recipient_code);

        return view('Views\package',['package' => $package[0], 'inQR' => $inQR, 'outQR' => $outQR]);
    }

    public function test(){
        $packageModel = new PackageModel();
       // $test = $packageModel->getActivePackageByRecipientCodeAndLockerId('4410364593097', 1);

        return view('Views\test',['test' => user_id()]);
    }

    public function generatePackageForm(){
        return view('Views\package-generate');
    }

    public function generatePackage(){

        $packageCode = md5($this->request->getVar('locker_id') . $this->request->getVar('size') . time());
        $packageModel = new PackageModel();
        $data = [
            'code' => $packageCode,
            'size'    => $this->request->getVar('size'),
            'recipient_email' => $this->request->getVar('recipient_email'),
            'recipient_phone' => $this->request->getVar('recipient_phone'),
           // 'recipient_code' => $packageModel->generateRecipientCode(13),
            'status' => 'new',
            'locker_id' => decodeHashId($this->request->getVar('locker_id')),
            'cell_sort_id' => NULL,
            'company_id' => 6,
            'created_by' => 4,

        ];
        $id = $packageModel->create($data);

        return redirect()->to('http://172.16.16.128/codeigniter4/auth-test/public/dashboard/package/show/' . encodeHashId($id));

    }

    public function showPackage($packagId){
        $packageModel = new PackageModel();
        $package = $packageModel->get(decodeHashId($packagId));

        $qr = [];

        $qr['in'] = (new QRCode)->render($package->code);
        if($package->recipient_code)
            $qr['out'] = (new QRCode)->render($package->recipient_code);

        return view(
            'Views\package-show',
            [
                'package' => $package,
                'qr' => $qr
            ]
        );
    }

    public function listPackages(){
        $packageModel = new PackageModel();
        $packages = $packageModel->getPackagesAllDesc();
        return view(
            'Views\package-list',
            [
                'packages' => $packages
            ]
        );
    }

    public function listTasks(){
        $taskModel = new TaskModel();
        $tasks = $taskModel->getTasksAllDesc();
        return view(
            'Views\task-list',
            [
                'tasks' => $tasks
            ]
        );
    }

    public function locker($lockerId){
        $cellModel = new CellModel();

        $lockerId = decodeHashId($lockerId);
        //$cells = $cellModel->getLockerCells($lockerIdHash);
        //$cellsAndPackages = ;

        $diagnosticModel = new DiagnosticModel();
        //$diagnostics = ;

        return view(
            'Views\locker',
            [
                'lockerIdHash' => $lockerId,
                'cellsAndPackages' => $cellModel->getLockerCellsAndPackages($lockerId),
                'diagnostics' => $diagnosticModel->getLastForLocker($lockerId)
            ]
        );
    }

    public function print(){
        helper('url');

        $pdf = new FpdfCode39();//'P', 'mm', [88, 125]
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',8);
        
        //A set
        $code='CODE 39';
        $pdf->SetXY(10,10);
        $pdf->Write(5,'A set: "'.$code.'"');
        $pdf->Code39(10,15,$code,1,15);
        

        //B set
        $code='Code 39';
        $pdf->SetXY(10,40);
        $pdf->Write(5,'B set: "'.$code.'"');
        $pdf->Code39(10,45,$code,1,15);
        

        //C set
        $code='12345678901234567890';
        $pdf->SetXY(10,70);
        $pdf->Write(5,'C set: "'.$code.'"');
        $pdf->Code39(10,75,$code,1,15);
        

        //A,C,B sets
        $code='ABCDEFG1234567890AbCdEf';
        $pdf->SetXY(10,100);
        $pdf->Write(5,'ABC sets combined: "'.$code.'"');
        $pdf->Code39(10,105,$code,1,15);
        
        $local_name = time().'.png';
        $outQR = (new QRCode)->render('TestValue123456798', ROOTPATH . "public/tmp/" . $local_name);
        
        
        $pdf->Image(base_url() . "/tmp/" . $local_name, 10, 150, 30, 30);
        unlink(ROOTPATH . "public/tmp/" . $local_name);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output();

    }

    public function settings($lockerId){
        $detailModel = new DetailModel();
        $details = $detailModel->getDetails(decodeHashId($lockerId));

        return view(
            'Views\locker-settings',
            [
                'lockerId' => $lockerId,
                'details' => $details,
            ]
        );
    }

    public function saveSettings(){

        $detailModel = new DetailModel();
        /*$detail = new \App\Entities\Detail();
        $detail->client_id = decodeHashId($this->request->getVar('locker_id'));
        $detail->name = 'request_interval';
        $detail->value = $this->request->getVar('request_interval');*/
        $requestInterval = $detailModel->getValue(decodeHashId($this->request->getVar('locker_id')), 'request_interval');

        if($this->request->getVar('request_interval') != $requestInterval){
            $detailModel->saveSingle(decodeHashId($this->request->getVar('locker_id')), 'request_interval', $this->request->getVar('request_interval'));
        }

        return redirect()->to('http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/settings/edit/' . $this->request->getVar('locker_id') . '?saved=1');

    }

    public function resetCells($lockerId){
        $cellModel = new CellModel();
        $cellModel->resetLocekCells(decodeHashId($lockerId));
        return redirect()->to('http://172.16.16.128/codeigniter4/auth-test/public/dashboard/locker/' . $lockerId);
    }

    public function pickLocker(){

        $apiClientModel = new ApiClientModel();
        $lockers = $apiClientModel->getAllLockers();

        return view(
            'Views\locker-picker',
            [
                'lockers' => $lockers,
            ]
        );

    }

    public function lockerRemote($id){
        echo "remote $id " . decodeHashId($id);

        //$cellModel = new CellModel();
        //$cellsAndPackages = $cellModel->getLockerCellsAndPackages($lockerId);

        return view(
            'Views\locker-remote',
            [
                'lockerId' => $id
            ]
        );
    }

    
}
