<?php

namespace Evangelos\MessageBird\Api;

class MessageBird
{
    public static function postMessage()
    {
        try {
            $app = App::getInstance();



            App::prepareResponse(App::REQUEST_SUCCESS, ['asdddd', 'ed23']);
        } catch (\Exception $ex) {
            App::prepareResponse($ex->getCode(), '', $ex->getMessage());
        }
    }
}
