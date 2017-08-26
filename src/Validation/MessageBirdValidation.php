<?php

namespace Evangelos\MessageBird\Validation;

use Evangelos\MessageBird\Api\MessageBird;
use Evangelos\MessageBird\Exceptions\MessageBirdException;

class MessageBirdValidation
{
    /** Recipients can contain one at most + sign at the beginning digits followed by a maximum of 15 digits.*/
    const RECIPIENT_MATCH_REGEX = '/^\+{0,1}[0-9]{1,15}$/';

    /**
     * Validates the JSON fields of the body of the POST request.
     * First, checks whether the fields that are expected are set and they are not empty.
     * Then, validates the expected fields one by one based on their respective requirements.
     *
     * @param \stdClass $bodyData
     * @throws MessageBirdException
     */
    public static function validatePostRequestBodyFields($bodyData)
    {
        self::checkIfExpectedFieldsArePresentAndNotEmpty($bodyData);
        self::validateOriginatorBodyField($bodyData->originator);
        self::validateRecipientsBodyField($bodyData->recipients);
        self::validateMessageBodyField($bodyData->message);
    }

    /**
     * Validates the recipients body field. API accepts comma-separated recipients that they need to
     * be checked individually.
     * Valid recipient contains one at most + at the beginning followed by a maximum of 15 digits.
     *
     * @param $recipients
     * @throws MessageBirdException
     */
    public static function validateRecipientsBodyField($recipients)
    {
        $explodedRecipients = explode(',', $recipients);
        foreach ($explodedRecipients as $key => $explodedRecipient) {
            if (!preg_match(self::RECIPIENT_MATCH_REGEX, $explodedRecipient)) {
                throw new MessageBirdException('Recipient #' . $key . ' is not valid');
            }
        }
    }

    /**
     * Validates message body field.
     *
     * @param $message
     * @throws MessageBirdException
     */
    public static function validateMessageBodyField($message)
    {
        $messageLength = strlen($message);
        if ($messageLength > MessageBird::PLAIN_SMS_MAX_LENGTH) {
            throw new MessageBirdException('Message is longer than allowed.');
        }
        if (strlen($message) != strlen(utf8_decode($message))) {
            if ($messageLength > MessageBird::UNICODE_SMS_MAX_LENGTH) {
                throw new MessageBirdException('Message is longer than allowed.');
            }
        }
    }

    /**
     * Validates originator body field.
     *
     * @param $originator
     * @throws MessageBirdException
     */
    public static function validateOriginatorBodyField($originator)
    {
        if (is_numeric($originator)) {
            if (intval($originator) < 0) {
                throw new MessageBirdException('Originator field is numeric and less than 0.');
            }
        } else {
            if (ctype_alnum($originator) && strlen($originator) > 11) {
                throw new MessageBirdException('Originator field is alphanumeric and more than 11 characters.');
            }
        }
    }

    /**
     * Checks whether the expected fields of the body are present and not empty.
     *
     * @param $bodyData
     * @throws MessageBirdException
     */
    private static function checkIfExpectedFieldsArePresentAndNotEmpty($bodyData)
    {
        foreach (self::expectedBodyFields() as $expectedBodyField) {
            if (!isset($bodyData->$expectedBodyField)) {
                throw new MessageBirdException($expectedBodyField . ' field is not present in the body of the request.');
            }
            if (!strlen($bodyData->$expectedBodyField)) {
                throw new MessageBirdException($expectedBodyField . ' field is empty.');
            }
        }
    }

    /**
     * Returns an array of JSON fields that must be present in the POST body.
     * @return array
     */
    private static function expectedBodyFields()
    {
        return array(
            'recipients',
            'originator',
            'message'
        );
    }
}