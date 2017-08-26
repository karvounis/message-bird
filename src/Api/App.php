<?php

namespace Evangelos\MessageBird\Api;

use MessageBird\Client;
use Slim\Slim;

/**
 * Class App
 * @package Evangelos\MessageBird\Api
 */
class App
{
    const REQUEST_SUCCESS = '200';
    const REQUEST_BAD_REQUEST = '400';

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
            return new Client($messageBirdIni['api_key']);
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
     * Checks whether a given string is unicode.
     * @param $str
     * @return bool
     */
    public static function isStringUnicode($str)
    {
        if (strlen($str) != strlen(utf8_decode($str))) {
            return true;
        }
        return false;
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

    public static function strSplitUnicode($str, $length = 0)
    {
        if ($length > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $length) {
                $ret[] = mb_substr($str, $i, $length, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}