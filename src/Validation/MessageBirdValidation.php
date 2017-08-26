<?php

namespace Evangelos\MessageBird\Validation;

use Evangelos\MessageBird\Api\App;
use Evangelos\MessageBird\Api\MessageBird;
use Evangelos\MessageBird\Exceptions\MessageBirdException;

class MessageBirdValidation
{
    /** Recipients can contain one at most + sign at the beginning digits followed by a maximum of 15 digits.*/
    const RECIPIENT_MATCH_REGEX = '/^\+{0,1}[0-9]{1,15}$/';

    const ERR_MSG_FIELD_IS_EMPTY = '%s field is empty.';
    const ERR_MSG_FIELD_IS_NOT_SET = '%s field is not set in the body of the request.';
    const ERR_MSG_RECIPIENT_NOT_VALID = 'Recipient #%d is not valid';
    const ERR_MSG_MESSAGE_TOO_LONG = 'Message is too long.';
    const ERR_MSG_ORIGINATOR_NEGATIVE = 'Originator field is numeric and less than 0.';
    const ERR_MSG_ORIGINATOR_ALPHANUMERIC_TOO_LONG = 'Originator field is alphanumeric and more than 11 characters.';

    /**
     * Validates the JSON fields of the body of the POST request.
     * First, checks whether the fields that are expected are set and they are not empty.
     * Then, validates the expected fields one by one based on their respective requirements.
     *
     * @param \stdClass $bodyData
     * @throws MessageBirdException
     */
    public function validatePostRequestBodyFields($bodyData)
    {
        $this->checkIfExpectedFieldsArePresentAndNotEmpty($bodyData);
        $this->validateOriginatorBodyField($bodyData->originator);
        $this->validateRecipientsBodyField($bodyData->recipients);
        $this->validateMessageBodyField($bodyData->message);
    }

    /**
     * Validates the recipients body field. API accepts comma-separated recipients that they need to
     * be checked individually.
     * Valid recipient contains one at most + at the beginning followed by a maximum of 15 digits.
     *
     * @param $recipients
     * @throws MessageBirdException
     */
    private function validateRecipientsBodyField($recipients)
    {
        $explodedRecipients = explode(',', $recipients);
        foreach ($explodedRecipients as $key => $explodedRecipient) {
            if (!preg_match(self::RECIPIENT_MATCH_REGEX, $explodedRecipient)) {
                throw new MessageBirdException(sprintf(self::ERR_MSG_RECIPIENT_NOT_VALID, $key));
            }
        }
    }

    /**
     * Validates message body field.
     *
     * @param $message
     * @throws MessageBirdException
     */
    private function validateMessageBodyField($message)
    {
        $messageLength = strlen($message);
        if ($messageLength > MessageBird::PLAIN_SMS_MAX_LENGTH) {
            throw new MessageBirdException(self::ERR_MSG_MESSAGE_TOO_LONG);
        }
        if (App::isStringUnicode($message)) {
            if ($messageLength > MessageBird::UNICODE_SMS_MAX_LENGTH) {
                throw new MessageBirdException(self::ERR_MSG_MESSAGE_TOO_LONG);
            }
        }
    }

    /**
     * Validates originator body field.
     *
     * @param $originator
     * @throws MessageBirdException
     */
    private function validateOriginatorBodyField($originator)
    {
        if (is_numeric($originator)) {
            if (intval($originator) < 0) {
                throw new MessageBirdException(self::ERR_MSG_ORIGINATOR_NEGATIVE);
            }
        } else {
            if (ctype_alnum($originator) && strlen($originator) > 11) {
                throw new MessageBirdException(self::ERR_MSG_ORIGINATOR_ALPHANUMERIC_TOO_LONG);
            }
        }
    }

    /**
     * Checks whether the expected fields of the body are present and not empty.
     *
     * @param $bodyData
     * @throws MessageBirdException
     */
    private function checkIfExpectedFieldsArePresentAndNotEmpty($bodyData)
    {
        foreach (self::expectedBodyFields() as $expectedBodyField) {
            if (!isset($bodyData->$expectedBodyField)) {
                throw new MessageBirdException(sprintf(self::ERR_MSG_FIELD_IS_NOT_SET, $expectedBodyField));
            }
            if (!strlen($bodyData->$expectedBodyField)) {
                throw new MessageBirdException(sprintf(self::ERR_MSG_FIELD_IS_EMPTY, $expectedBodyField));
            }
        }
    }

    /**
     * Returns an array of JSON fields that must be present in the POST body.
     * @return array
     */
    private function expectedBodyFields()
    {
        return array(
            'recipients',
            'originator',
            'message'
        );
    }
}
