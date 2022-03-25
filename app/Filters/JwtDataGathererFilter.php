<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\Packages\RequestData;
use App\Libraries\Logger\Logger;
use App\Libraries\Packages\JwtRequestData;

class JwtDataGathererFilter implements FilterInterface
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

        try{
            $jwtRequestData = new JwtRequestData($request);
            $jwtRequestData->getData();
        }catch(\Exception $e){
            $response = service('response');
            return $response->setJSON(createErrorMsg(401, 112, ['auth' => $e->getMessage()]))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }
        /*
        $response = service('response');
            return $response->setJSON(createErrorMsg(401, 3, ['auth' => 'Access denied']))->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
            */
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
