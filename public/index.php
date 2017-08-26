<?php

require __DIR__ . '/../vendor/autoload.php';

use \Evangelos\MessageBird\Api\App;
use \Evangelos\MessageBird\Api\MessageBird;

date_default_timezone_set('Europe/Amsterdam');

error_reporting(E_ERROR);

$app = App::getInstance();

$app->post('/message', MessageBird::class . ':postMessage')->setName('POST message');

$app->run();
