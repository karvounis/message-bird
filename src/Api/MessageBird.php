<?php

namespace Evangelos\MessageBird\Api;

use Evangelos\MessageBird\Validation\MessageBirdValidation;
use MessageBird\Client;
use MessageBird\Exceptions\AuthenticateException;
use MessageBird\Exceptions\BalanceException;
use MessageBird\Objects\Message;

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

    const UDH_BEGINNING = '050003';

    const REQUEST_SUCCESSFUL = 'Request successful';
    const WRONG_LOGIN = 'Wrong login';
    const NOT_ENOUGH_BALANCE = 'Not enough balance';

    public static function postMessage()
    {
        try {
            $app = App::getInstance();
            $bodyDecoded = json_decode($app->request->getBody());
            $messageBirdValidation = new MessageBirdValidation();
            $messageBirdValidation->validatePostRequestBodyFields($bodyDecoded);

            $messageBirdClient = $app->container->get('messageBird');
            $messageBirdResponseIds = self::sendMessageThroughMessageBird($messageBirdClient, $bodyDecoded);
            App::prepareResponse(App::REQUEST_SUCCESS, $messageBirdResponseIds, self::REQUEST_SUCCESSFUL);
        } catch (AuthenticateException $e) {
            App::prepareResponse(App::REQUEST_BAD_REQUEST, '', self::WRONG_LOGIN);
        } catch (BalanceException $e) {
            App::prepareResponse(App::REQUEST_BAD_REQUEST, '', self::NOT_ENOUGH_BALANCE);
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
     * Method that sends the messages to the recipient(s) through MessageBird.
     *
     * @param Client $messageBirdClient
     * @param $requestBody
     * @return array Ids of MessageBird Response(s)
     */
    public static function sendMessageThroughMessageBird($messageBirdClient, $requestBody)
    {
        $message = $requestBody->message;
        $isMessageUnicode = App::isStringUnicode($message);
        $doesMessageNeedToBeChunked = self::doesMessageNeedToBeChunked($message, $isMessageUnicode);
        $recipients = explode(',', $requestBody->recipients);
        $messageBirdResponseIds = [];
        if (!$doesMessageNeedToBeChunked) {
            $messageBirdResponseIds[] = self::sendSingleMessageToMessageBird($messageBirdClient,
                $requestBody->originator, $recipients, $message, [], Message::DATACODING_PLAIN, Message::TYPE_SMS);
        } else {
            $chunkedMessages = self::getChunkedMessages($message, $isMessageUnicode);
            $referenceNumber = self::getRandomReferenceNumber();
            foreach ($chunkedMessages as $key => $chunkedMessage) {
                $udh = self::calculateUDH($referenceNumber, count($chunkedMessages), $key + 1);
                $messageBirdResponseIds[] = self::sendSingleMessageToMessageBird($messageBirdClient,
                    $requestBody->originator, $recipients, $chunkedMessage, ['udh' => $udh], Message::DATACODING_PLAIN,
                    Message::TYPE_BINARY);
            }
        }
        return $messageBirdResponseIds;
    }

    /**
     * Sends a single message to MessageBird. Returns the id of the MessageBird response.
     *
     * @param Client $messageBirdClient
     * @param $originator
     * @param $recipients
     * @param $message
     * @param $typeDetails
     * @param $dataCoding
     * @param $type
     * @return string
     */
    private function sendSingleMessageToMessageBird(
        $messageBirdClient,
        $originator,
        $recipients,
        $message,
        $typeDetails,
        $dataCoding,
        $type
    ) {
        $messageToSend = self::prepareMessageBirdMessage($originator, $recipients, $message, $typeDetails, $dataCoding,
            $type);
        $messageBirdResponse = $messageBirdClient->messages->create($messageToSend);
        return $messageBirdResponse->getId();
    }

    /**
     * Returns a random generated reference number as 1 octet (00-FF).
     *
     * @return string
     */
    private static function getRandomReferenceNumber()
    {
        return sprintf('%02X', mt_rand(0, 0xFF));
    }

    /**
     * Calculates the User Data Header - UDH.
     *
     * @param $referenceNumber
     * @param $numberOfChunks
     * @param $chunkNumber
     * @return int
     */
    private static function calculateUDH($referenceNumber, $numberOfChunks, $chunkNumber)
    {
        return self::UDH_BEGINNING . $referenceNumber . '0' . $numberOfChunks . '0' . $chunkNumber;
    }

    /**
     * Gets a message and returns an array of its chunked counterparts.
     *
     * @param $message
     * @param bool $isMessageUnicode
     * @return array
     */
    private static function getChunkedMessages($message, $isMessageUnicode = false)
    {
        if ($isMessageUnicode) {
            return App::strSplitUnicode($message, self::UNICODE_SMS_CHUNKED_MESSAGE_MAX_LENGTH);
        }
        return str_split($message, self::PLAIN_SMS_CHUNKED_MESSAGE_MAX_LENGTH);
    }

    /**
     * Method that decided whether the message needs to be chunked.
     *
     * @param $message
     * @param bool $isMessageUnicode
     * @return bool
     */
    public static function doesMessageNeedToBeChunked($message, $isMessageUnicode = false)
    {
        $messageLength = strlen($message);
        if ($messageLength > self::PLAIN_SMS_SINGLE_MESSAGE_MAX_LENGTH) {
            return true;
        }
        if ($isMessageUnicode && $messageLength > self::UNICODE_SMS_SINGLE_MESSAGE_MAX_LENGTH) {
            return true;
        }
        return false;
    }
}
