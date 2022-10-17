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

$app->post('/starttime', function (Request $request, Response $response) {
    //Retrieving that body from the post URL
    $data = $request->getParsedBody();

    //Using redis to cache the $data (simulating inserting data into DB)
    $redisClient = $this->get('redisClient');
    $redisClient->set('start_time', json_encode($data), 'EX', 100);


    //Taking the response and producing JSON
    $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->get('/starttime', function (Request $request, Response $response) {

    //Retrieving data from our redis cache
    $redisClient = $this->get('redisClient');
    $results = $redisClient->get('start_time');

    $cookieValue = '';
    if (empty($_COOKIE["expiration"])) {
        $cookieName = "Expiration";
        $cookieValue = (string)time();
        $expires = time() + 60 * 60 * 24 * 30; // 30 days.
        setcookie($cookieName, $cookieValue, $expires, '/');
    }

    //Need this to turn JSON into string so I can encode the JSON again in the write() function
    $start_time = (json_decode($results));

    //Deliever a response with the start_time in JSON
    $response->getBody()->write(json_encode($start_time, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->post('/endtime', function (Request $request, Response $response) {
    //Retrieving that body from the post URL
    $data = $request->getParsedBody();

    //Using redis to cache the $data (simulating inserting data into DB)
    $redisClient = $this->get('redisClient');
    $redisClient->set('end_time', json_encode($data), 'EX', 100);



    //Taking the response and producing JSON
    $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->get('/endtime', function (Request $request, Response $response) {

    //Retrieving data from our redis cache
    $redisClient = $this->get('redisClient');
    $results = $redisClient->get('end_time');
    $end_time = (json_decode($results));
    //Deliever a response with the start_time in JSON
    $response->getBody()->write(json_encode($end_time, JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->get('/totaltime', function (Request $request, Response $response, $args) {

    //Retrieving data from our redis cache
    $redisClient = $this->get('redisClient');

    //Converting JSON string back into integer
    $start_time = $redisClient->get('start_time');
    $results = json_decode($start_time);
    $string = $results->start_time;
    $timestamp = strtotime($string);

    //Converting JSON string back into integer
    $end_time = $redisClient->get('end_time');
    $results2 = json_decode($end_time);
    $string2 = $results2->end_time;
    $timestamp2 = strtotime($string2);


    //Calculating total time by subtracting integers and converting back into string
    $total_time = date("H:i:s", ($timestamp2 - $timestamp));

    //Deliever a response with the start_time in JSON
    $response->getBody()->write(json_encode(['total_time' => $total_time], JSON_THROW_ON_ERROR));

    return $response->withHeader('Content-Type', 'application/json');

});

$app->run();
