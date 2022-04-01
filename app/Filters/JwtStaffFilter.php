<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
//use \Firebase\JWT\JWT;
use App\Libraries\Packages\JwtHandler;
use App\Libraries\Logger\Logger;

class JwtStaffFilter implements FilterInterface
{
    use ResponseTrait;
    private $response;
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        //Logger::log(48, 'FILTER', 'jwtLockerFilter', 'locker', 0);
        //$key = getenv('JWT_SECRET');
        //$header = $request->getHeader("Authorization");
        //$token = null;
        $token = JwtHandler::extractFromHeader($request->getHeader("Authorization"));
        helper('errorMsg');
 
        // extract the token from the header
        /*if(!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }*/
 
        // check if token is null or empty
        if(is_null($token) || empty($token)) {
            $response = service('response');
            Logger::log(48, 'ERROR', 'token empty', 'locker', 0);
            return $response->setJSON(createErrorMsg(401, 1, ['generalErrors' => ['auth' => 'Access denied']]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }
 
        try {
            //$decoded = JWT::decode($token, $key, array("HS256"));
            //$request->decodedJwt = $decoded;
            $request->decodedJwt = JwtHandler::decode($token);
        } catch (\Exception $ex) {
            $response = service('response');
            return $response->setJSON(createErrorMsg(401, 2, ['generalErrors' => ['auth' => 'Access denied']]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if(!isset($request->decodedJwt->tokenId)){
            $response = service('response');
            return $response->setJSON(createErrorMsg(401, 3, ['generalErrors' => ['auth' => 'Access denied']]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if(!JwtHandler::isOnWhiteList($request->decodedJwt->tokenId)){
            $response = service('response');
            return $response->setJSON(createErrorMsg(401, 3, ['generalErrors' => ['auth' => 'Access denied']]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if($request->decodedJwt->client !== 'staff' && $request->decodedJwt->client !== 'company' &&$request->decodedJwt->client !== 'admin'){// 
            $response = service('response');
            return $response->setJSON(createErrorMsg(401, 3, ['generalErrors' => ['auth' => 'Access denied']]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        //Logger::log(48, 'SUCCESS', 'token correct', 'locker', 0);
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
