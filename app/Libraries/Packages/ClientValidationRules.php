<?php

namespace App\Libraries\Packages;

use App\Libraries\Logger\Logger;

class ClientValidationRules
{

    public function getSaveRules($type)
    {
        // /['locker', 'company', 'staff', 'supervisor', 'admin']
        $methodName = $type . 'SaveRules';
        if (!method_exists($this, $methodName)) {
            throw new \Exception('Client not allowed');
        }
        return $this->$methodName();
    }

    public function getUpdateRules($type)
    {
        // /['locker', 'company', 'staff', 'supervisor', 'admin']
        $methodName = $type . 'UpdateRules';
        if (!method_exists($this, $methodName)) {
            throw new \Exception('Client not allowed');
        }
        return $this->$methodName();
    }

    public function adminSaveRules()
    {
        //Logger::log(3, 'adminSaveRules', 'ClientValidationRules');
        return [
            'company_id'    => [
                'rules' => 'required|max_length[64]|company_exists',
                'errors' => [
                    'required' => 'Firma jest wymaganym polem',
                    'company_exists' => 'Nie znaleziono wybranej firmy',
                ],
            ],
            'name'          => [
                'rules' => 'required|max_length[255]|is_unique[apiclients.name]',
                'errors' => [
                    'required' => 'Nazwa konta jest wymagana',
                    'is_unique' => 'Ta nazwa jest już zajęta',
                ]
            ],
            'type'          => [
                'rules' => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors' => [
                    'allowed_client_type' => 'This client type is not allowed',
                ],
            ],
            'first_name'       => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Imię jest wymaganym polem',
                ],
            ],
            'sur_name'          => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Nazwisko jest wymaganym polem',
                ],
            ],
            'street'        => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Ulica jest wymaganym polem',
                ],
            ],
            'city'          => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Miasto jest wymaganym polem',
                ],
            ],
            'post_code'     => [
                'rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]',
                'errors' => [
                    'required' => 'Kod pocztowy jest wymaganym polem',
                    'max_length' => 'To pole może mieć maks. 6 znaków',
                    'regex_match' => 'Podaj kod pocztowy we właściwym formacie: 00-000',
                ],
            ],
            'phone'     => [
                'rules' => 'required|max_length[255]',
                'errors' => [
                    'required' => 'Telefon jest wymaganym polem',
                ]
            ],
            'email'     => [
                'rules' => 'required|max_length[255]|valid_email',
                'errors' => 'E-mail jest wymaganym polem',
            ],
        ];
    }

    public function adminUpdateRules()
    {
        //Logger::log(3, 'adminUpdateRules', 'ClientValidationRules');
        return [
            'id'            => ['rules' => 'required|is_not_unique_hash[apiclients.id]'],
            'company_id'    => [
                'rules' => 'required|max_length[64]|company_exists',
                'errors' => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique_except_hash[apiclients.name,id,{id}]'],
            'type'          => [
                'rules' => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors' => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'first_name'       => ['rules' => 'required|max_length[255]'],
            'sur_name'          => ['rules' => 'required|max_length[255]'],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'phone'     => ['rules' => 'required|max_length[255]'],
            'email'     => ['rules' => 'required|max_length[255]|valid_email'],
        ];
    }

    public function companySaveRules()
    {
        //Logger::log(3, 'companySaveRules', 'ClientValidationRules');
        return [
            /*'company_id'    => [
                'rules'     => 'required|max_length[64]|company_exists',
                'errors'    => [ 'company_exists' => 'Company doesn\'t exist ']
            ],*/
            'name'          => ['rules' => 'required|max_length[255]|is_unique[apiclients.name]'],
            'type'          => [
                'rules'     => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors'    => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'geolocate'     => ['rules' => 'permit_empty|max_length[255]'],
            'works_from'     => ['rules' => 'required|max_length[255]'],
            'works_to'     => ['rules' => 'required|max_length[255]'],
            'phone'     => ['rules' => 'required|max_length[255]'],
            'email'     => ['rules' => 'required|max_length[255]|valid_email'],
        ];
    }

    public function companyUpdateRules()
    {
        //Logger::log(3, 'companyUpdateRules', 'ClientValidationRules');
        return [
            'id'    => [
                'rules'     => 'required|max_length[64]|is_not_unique_hash[apiclients.id]',
                'errors'    => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique_except_hash[apiclients.name,id,{id}]'],
            'type'          => [
                'rules'     => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors'    => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'geolocate'     => ['rules' => 'permit_empty|max_length[255]'],
            'works_from'    => ['rules' => 'required|max_length[255]'],
            'works_to'      => ['rules' => 'required|max_length[255]'],
            'phone'         => ['rules' => 'required|max_length[255]'],
            'email'         => ['rules' => 'required|max_length[255]|valid_email'],
            'regenerate_servicecodes'     => ['rules' => 'permit_empty|max_length[255]'],
        ];
    }
    public function supervisorSaveRules()
    {
    }

    public function staffSaveRules()
    {
        return [
            'company_id'    => [
                'rules' => 'required|max_length[64]|company_exists',
                'errors' => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique[apiclients.name]'],
            'type'          => [
                'rules' => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors' => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'first_name'       => ['rules' => 'required|max_length[255]'],
            'sur_name'          => ['rules' => 'required|max_length[255]'],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'phone'     => ['rules' => 'required|max_length[255]'],
            'email'     => ['rules' => 'required|max_length[255]|valid_email'],
        ];
    }

    public function staffUpdateRules()
    {
        //Logger::log(3, 'staffUpdateRules', 'ClientValidationRules');
        return [
            'id'            => ['rules' => 'required|is_not_unique_hash[apiclients.id]'],
            'company_id'    => [
                'rules' => 'required|max_length[64]|company_exists',
                'errors' => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique_except_hash[apiclients.name,id,{id}]'],
            'type'          => [
                'rules' => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors' => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'first_name'       => ['rules' => 'required|max_length[255]'],
            'sur_name'          => ['rules' => 'required|max_length[255]'],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'phone'     => ['rules' => 'required|max_length[255]'],
            'email'     => ['rules' => 'required|max_length[255]|valid_email'],
        ];
    }

    public function lockerSaveRules()
    {
        //Logger::log(3, 'lockerSaveRules', 'ClientValidationRules');
        return [
            'company_id'    => [
                'rules'     => 'required|max_length[64]|company_exists',
                'errors'    => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique[apiclients.name]'],
            'type'          => [
                'rules'     => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors'    => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'geolocate'     => ['rules' => 'required|max_length[255]'],
            'request_interval' => ['rules' => 'required|numeric'],
        ];
    }

    public function lockerUpdateRules()
    {
        //Logger::log(3, 'lockerUpdateRules', 'ClientValidationRules');
        return [
            'id'            => ['rules' => 'required|locker_exists[apiclients.id]'],
            'company_id'    => [
                'rules'     => 'required|max_length[64]|company_exists',
                'errors'    => ['company_exists' => 'Company doesn\'t exist ']
            ],
            'name'          => ['rules' => 'required|max_length[255]|is_unique_except_hash[apiclients.name,id,{id}]'],
            'type'          => [
                'rules'     => 'required|max_length[16]|allowed_client_type', //|can_set_type
                'errors'    => ['allowed_client_type' => 'This client type is not allowed']
            ],
            'street'        => ['rules' => 'required|max_length[255]'],
            'city'          => ['rules' => 'required|max_length[255]'],
            'post_code'     => ['rules' => 'required|max_length[6]|regex_match[/\d{2}-\d{3}/]'],
            'geolocate'     => ['rules' => 'required|max_length[255]'],
            'request_interval' => ['rules' => 'required|numeric'],
        ];
    }
}
