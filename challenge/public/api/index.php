<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../vendor/autoload.php';

// DIC
$container = new Container();
$container->set('redisClient', function () {
    return new Predis\Client(['host' => 'redis']);
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->setBasePath('/api');
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->post('/times', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    // Redis usage example:
    /** @var \Predis\Client $redisClient */
    $redisClient = $this->get('redisClient');
    $oldName = $redisClient->get('data');
    if (is_string($oldName)) {
        $data = $oldName;
    } else {
        $redisClient->set('start_time', $data, 'EX', 10);
        $data = $data;
    }

    $response->getBody()->write(json_encode([$data], JSON_THROW_ON_ERROR));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/times', function (Request $request, Response $response) {



    $response->getBody()->write(json_encode([
      $data
    ], JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/hello/{name}', function (Request $request, Response $response, $args) {

    // Redis usage example:
    /** @var \Predis\Client $redisClient */
    $redisClient = $this->get('redisClient');
    $oldName = $redisClient->get('name');
    if (is_string($oldName)) {
        $name = $oldName;
    } else {
        $redisClient->set('name', $args['name'], 'EX', 10);
        $name = $args['name'];
    }

    // Setting a cookie example:
    $cookieValue = '';
    if (empty($_COOKIE["FirstSalutationTime"])) {
        $cookieName = "FirstSalutationTime";
        $cookieValue = (string)time();
        $expires = time() + 60 * 60 * 24 * 30; // 30 days.
        setcookie($cookieName, $cookieValue, $expires, '/');
    }

    // Response example:
    $response->getBody()->write(json_encode([
        'name' => $name,
        'salutation' => "Hello, $name!",
        'first_salutation_time' => $_COOKIE["FirstSalutationTime"] ?? $cookieValue,
    ], JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
