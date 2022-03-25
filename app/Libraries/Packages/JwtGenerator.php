<?php
namespace App\Libraries\Packages;

use App\Models\ApiClientModel;
use App\Models\TokenWhitelistModel;
use \Firebase\JWT\JWT;


class JwtGenerator{
    private $client;

    public function __construct($clientId){
        $apiClientModel = new ApiClientModel();
        $this->client = $apiClientModel->getByHash($clientId);
    }

    public function generateToken(){

        $key = getenv('JWT_SECRET');
        $iat = time(); // current timestamp value
        $exp = $iat + (3600 * 24 * 365 * 100);
 
        //save token identifier in whitelist
        $tokenWhitelistModel = new TokenWhitelistModel();
        $whitelistId = $tokenWhitelistModel->create($this->client->id_hash);

        $payload = array(
            "iss" => "ci4-auth-test.local",
            "aud" => "ci4-auth-test.local",
            "sub" => "Locker auth token",
            "iat" => $iat, //Time the JWT issued at
            "exp" => $exp, // Expiration time of token
            "client" => $this->client->type,//$user['email'],
            "clientId" => $this->client->id_hash, //temp. locker id
            'companyId' => $this->client->company_id, //temp. company id
            // unique code that can be banned
            'tokenId' => $whitelistId,
        );
         
        return JWT::encode($payload, $key);

    }
}