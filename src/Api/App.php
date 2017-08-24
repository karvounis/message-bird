<?php

namespace Evangelos\MessageBird\Api;

use Slim\Slim;

/**
 * Class App
 * @package Evangelos\MessageBird\Api
 */
class App
{
    const REQUEST_SUCCESS = '200';

    /** @var Slim */
    protected static $app = null;

    /**
     * Singleton Implementation
     * @return Slim
     */
    public static function getInstance()
    {
        if (is_null(self::$app)) {
            self::$app = new Slim();
            self::addDependencies();
        }
        return self::$app;
    }

    /**
     * Adds dependencies to the container as singletons
     */
    private static function addDependencies()
    {
        self::$app->container->singleton('messageBird', function () {
            $messageBirdIni = parse_ini_file(__DIR__ . '/../../config/MessageBird.ini');
            return $messageBirdIni;
        });
    }

    /**
     * Prepares the response of the API.
     *
     * @param $code
     * @param string $message
     * @param string $data
     * @throws \Exception
     */
    public static function prepareResponse($code, $data = '', $message = '')
    {
        $obj = new \stdClass();
        $obj->code = $code;
        $obj->data = $data;
        $obj->message = $message;

        $encodedObj = json_encode($obj);
        if (!$encodedObj) {
            $errorMsg = json_last_error_msg();
            throw new \Exception($errorMsg);
        }
        $app = App::getInstance();
        $app->response->setBody($encodedObj);
        self::setStatusAndHeader($app, $code);
    }

    /**
     * @param Slim $app
     * @param $code
     */
    public static function setStatusAndHeader($app, $code)
    {
        $app->response->setStatus($code);
        $app->response->headers->set('Content-Type', 'application/json');
    }
}