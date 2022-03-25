<?php
namespace App\Libraries\Packages;

use App\Models\ApiClientModel;
use App\Models\TokenWhitelistModel;
use \Firebase\JWT\JWT;
use App\Libraries\Logger\Logger;

class JwtHandler{

    public static function extractFromHeader($header){

        $token = null;

        // extract the token from the header
        if(!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        return $token;
    }

    public static function isOnWhiteList($tokenId){

        $tokenWhitelistModel = new TokenWhitelistModel();
        return $tokenWhitelistModel->isOnWhitelist($tokenId);

    }

    public static function decode($token){

        $key = JWT_SECRET;
        return JWT::decode($token, $key, array("HS256"));

    }

    public static function generateForClient($clientId){

        $apiClientModel = new ApiClientModel();
        Logger::log(56, $clientId, "generateForClient clientId");
        $client = $apiClientModel->get($clientId);

        $key = JWT_SECRET;
        $iat = time(); // current timestamp value
        $exp = $iat + (3600 * 24 * 365 * 100);
 
        //save token identifier in whitelist
        $tokenWhitelistModel = new TokenWhitelistModel();
        $whitelistId = $tokenWhitelistModel->create($client->id);

        $payload = array(
            "iss" => "ci4-auth-test.local",
            "aud" => "ci4-auth-test.local",
            "sub" => "Locker auth token",
            "iat" => $iat, //Time the JWT issued at
            "exp" => $exp, // Expiration time of token
            "client" => $client->type,//$user['email'],
            "clientId" => $client->id, //temp. locker id
            'companyId' => $client->company_id, //temp. company id
            // unique code that can be banned
            'tokenId' => $whitelistId,
        );
         
        return JWT::encode($payload, $key);

    }
}