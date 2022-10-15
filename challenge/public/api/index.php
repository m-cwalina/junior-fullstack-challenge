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
    //Retrieving that body from the post URL
    $data = $request->getParsedBody();

    //Using redis to cache the $data (simulating inserting data into DB)
    $redisClient = $this->get('redisClient');
    if (is_string($data)) {
      $start_time = $data;
    } else {
        $redisClient->set('start_time', json_encode($data), 'EX', 20);
        $start_time = $data;
    }

    //Taking the response and producing JSON
    $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->get('/times', function (Request $request, Response $response, $args) {

    //Retrieving data from our redis cache
    $redisClient = $this->get('redisClient');
    $start_time = $redisClient->get('start_time');

    //Deliever a response with the start_time in JSON
    $response->getBody()->write(json_encode($start_time, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->post('/endtimes', function (Request $request, Response $response) {
    //Retrieving that body from the post URL
    $data = $request->getParsedBody();

    //Using redis to cache the $data (simulating inserting data into DB)
    $redisClient = $this->get('redisClient');
    if (is_string($data)) {
      $end_time = $data;
    } else {
        $redisClient->set('end_time', json_encode($data), 'EX', 20);
        $end_time = $data;
    }

    //Taking the response and producing JSON
    $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->get('/endtimes', function (Request $request, Response $response, $args) {

    //Retrieving data from our redis cache
    $redisClient = $this->get('redisClient');
    $end_time = $redisClient->get('end_time');

    //Deliever a response with the start_time in JSON
    $response->getBody()->write(json_encode($end_time, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->run();
