<?php
// --------------------------------------------------------------------------------
// [ INIT ] ****************************

// CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: http://localhost:5173');
    header('Access-Control-Allow-Methods: PUT, DELETE'); // Specify allowed methods
    header('Access-Control-Allow-Credentials: true');
    
    // Check if the request includes additional headers and include them in the response
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
    }

    exit; // Stop further execution
}

// For regular requests
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

// Initial files
require __DIR__ . '/vendor/autoload.php'; // Classes Autoloader.
require __DIR__ . '/app/config/config.php'; // Initial configs.

// Use of initial libraries

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RequestHandlerInterface as Handler;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Model\Utilities\Log;

// Instantiating of the App
$app = AppFactory::create();

// Error middleware
$errorMiddleware = function ($request, $exception, $displayErrorDetails) use ($app)
{
  $statusCode = 500;
  $errorMessage = $exception->getMessage();
  $requestedUrl = $request->getUri()->getPath();
  $httpMethod = $request->getMethod();
  
  $response = $app->getResponseFactory()->createResponse($statusCode);
  $response->getBody()->write(json_encode(['error' => $errorMessage]));
  
  $logMessage = "Error: " . $errorMessage . " (METHOD: " . $httpMethod . ", URL: " . $requestedUrl . ")";
  Log::WriteLog('index_error.txt', $logMessage);
    return $response->withHeader('Content-Type', 'application/json');
};


// Add error middleware
$app->addErrorMiddleware(true, true, true)->setDefaultErrorHandler($errorMiddleware);

// Add parse body
$app->addBodyParsingMiddleware();

// --------------------------------------------------------------------------------
// [ SERVER ] ****************************

/////////////////////////////////////////////////////////////
#region - - - TEST ROUTES - - -

$app->get('/test', function (Request $request, Response $response)
{
    Log::WriteLog('test.txt', 'a');
    $payload = json_encode(array('method' => 'GET', 'msg' => "GET /test working (.htacces file is present). - - - RPG PROJECT"));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/test_browser', function (Request $request, Response $response)
{
    $payload = json_encode($_SERVER['HTTP_USER_AGENT']);
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});
#endregion

/////////////////////////////////////////////////////////////
#region - - - PUBLIC ROUTES - - -

// Returns your APP's name, which is set at the config.php file. By default the APP has no name and it's up to you to define one.
$app->get('/app_name', \Model\Services\Manager::class . '::ReturnAppName');

$app->get('[/]', \Model\Services\Manager::class . '::ReturnToFront');

$app->get('/get_random_coords', \Model\Services\MapManager::class . '::ReturnRandomCoords'); // CURRENTLY UNUSED

$app->get('/get_clip_by_coords', \Model\Services\MapManager::class . '::ReturnClip');

$app->get('/next_turn_pic', function($request, $response)
{
    return \Model\Services\MapManager::ReturnArrival($request, $response, true);
});

$app->get('/next_turn_coords', function($request, $response)
{
    return \Model\Services\MapManager::ReturnArrival($request, $response, false);
});

$app->run();