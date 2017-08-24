<?php

require __DIR__ . '/../vendor/autoload.php';

use \Evangelos\MessageBird\Api\App;

date_default_timezone_set('Europe/Amsterdam');

error_reporting(E_ERROR);

$app = App::getInstance();

$app->post('/message', \Evangelos\MessageBird\Api\MessageBird::class . ':postMessage')->setName('POST message');
$app->get('/message', \Evangelos\MessageBird\Api\MessageBird::class . ':getMessage')->setName('GET message');

$app->run();

