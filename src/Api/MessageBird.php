<?php

namespace Evangelos\MessageBird\Api;

use Evangelos\MessageBird\Validation\MessageBirdValidation;
use MessageBird\Client;
use MessageBird\Exceptions\AuthenticateException;
use MessageBird\Exceptions\BalanceException;
use MessageBird\Objects\Message;
use Slim\Slim;

/**
 * Class MessageBird
 * @package Evangelos\MessageBird\Api
 */
class MessageBird
{
    const PLAIN_SMS_MAX_LENGTH = 1377;
    const PLAIN_SMS_SINGLE_MESSAGE_MAX_LENGTH = 160;
    const PLAIN_SMS_CHUNKED_MESSAGE_MAX_LENGTH = 153;

    const UNICODE_SMS_MAX_LENGTH = 603;
    const UNICODE_SMS_SINGLE_MESSAGE_MAX_LENGTH = 70;
    const UNICODE_SMS_CHUNKED_MESSAGE_MAX_LENGTH = 67;

    public static function postMessage()
    {
        try {
            $app = App::getInstance();
            $body = $app->request->getBody();
            $bodyDecoded = json_decode($body);
            MessageBirdValidation::validatePostRequestBodyFields($bodyDecoded);

            $messageBirdClient = $app->container->get('messageBird');

            self::sendMessageThroughMessageBird($messageBirdClient, $bodyDecoded);
            App::prepareResponse(App::REQUEST_SUCCESS);
        } catch (AuthenticateException $e) {
            App::prepareResponse(App::REQUEST_BAD_REQUEST, '', 'Wrong login');
        } catch (BalanceException $e) {
            App::prepareResponse(App::REQUEST_BAD_REQUEST, '', 'Not enough balance');
        } catch (\Exception $ex) {
            App::prepareResponse(App::REQUEST_BAD_REQUEST, '', $ex->getMessage());
        }
    }

    /**
     * Returns a Message object.
     *
     * @param $originator
     * @param array $recipients
     * @param $body
     * @param array $typeDetails
     * @param null|string $dataCoding
     * @param string $type
     * @return Message
     */
    protected static function prepareMessageBirdMessage(
        $originator,
        array $recipients,
        $body,
        $typeDetails = [],
        $dataCoding = Message::DATACODING_PLAIN,
        $type = Message::TYPE_SMS
    ) {
        $message = new Message();
        $message->originator = $originator;
        $message->recipients = $recipients;
        $message->body = $body;
        $message->typeDetails = $typeDetails;
        $message->datacoding = $dataCoding;
        $message->type = $type;
        return $message;
    }

    /**
     * @param Client $messageBirdClient
     * @param $requestBody
     */
    public static function sendMessageThroughMessageBird($messageBirdClient, $requestBody)
    {
        $bodyMessage = $requestBody->message;
        $isMessageUnicode = App::isStringUnicode($bodyMessage);
        $doesMessageNeedToBeChunked = self::doesMessageNeedToBeChunked($bodyMessage, $isMessageUnicode);
//        $bodyMessageSplit = App::strSplitUnicode($bodyMessage, self::SMS_LENGTH);
//        foreach ($bodyMessageSplit as $item) {
//            $message = self::prepareMessageBirdMessage($requestBody->originator, array($requestBody->recipient),
//                $item, ['udh' => 'HEADER'], Message::DATACODING_UNICODE);
////            $messageBirdResponse = $messageBirdClient->messages->create($message);
//            sleep(1);
//        }
    }

    public static function doesMessageNeedToBeChunked($message, $isUnicode = false)
    {
        $messageLength = strlen($message);
        if ($messageLength > self::PLAIN_SMS_SINGLE_MESSAGE_MAX_LENGTH) {
            return true;
        }
        if ($isUnicode && $messageLength > self::UNICODE_SMS_SINGLE_MESSAGE_MAX_LENGTH) {
            return true;
        }
        return false;
    }
}
