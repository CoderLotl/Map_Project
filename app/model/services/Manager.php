<?php

namespace Model\Services;

use Model\Utilities\Log;

class Manager
{
    public static function ReturnAppName($request, $response)
    {        
        $payload = APP_NAME;
        return self::ReturnResponse($request, $response, $payload);
    }

    public static function ReturnToFront($request, $response)
    {
        $payload = file_get_contents('./client/dist/index.html');
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private static function GetRequest($request)
    {        
        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }        
        
        return $params;
    }

    private static function ReturnResponse($request, $response, $payload, $status = 200)
    {        
        $response->getBody()->write(json_encode(['response' => $payload]));        
        return $response->withStatus($status);
    }
}