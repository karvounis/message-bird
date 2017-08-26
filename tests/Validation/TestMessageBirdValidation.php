<?php

namespace Evangelos\MessageBird\Validation\Tests;

use Evangelos\MessageBird\Validation\MessageBirdValidation;

class TestMessageBirdValidation extends \PHPUnit_Framework_TestCase
{
    /** @var MessageBirdValidation */
    private $instance = null;

    public function setUp()
    {
        $this->instance = new MessageBirdValidation();
    }

    /**
     * Method that tests invalid fields in the POST body request
     * @expectedException \Exception
     * @dataProvider provideInvalidPostBody
     * @param $bodyData
     */
    public function testInvalidPostRequestBodyFields($bodyData)
    {
        $this->instance->validatePostRequestBodyFields($bodyData);
    }

    /**
     * Method that tests Valid fields in the POST body request
     *
     * @dataProvider provideValidPostBody
     * @param $bodyData
     */
    public function testValidPostRequestBodyFields($bodyData)
    {
        $this->assertNull($this->instance->validatePostRequestBodyFields($bodyData));
    }

    public function provideValidPostBody()
    {
        return [
            'Recipients: Simple numeric' => [
                $this->getBody('+123456789', 'Evangelos', 'Simple short plain message')
            ],
            'Recipients: Two numerics, one with a + sign' => [
                $this->getBody('123456789,+123456789', 'Evangelos', 'Simple short plain message')
            ],
            'Originator: Numeric' => [
                $this->getBody('+123456789', '1234', 'Simple short plain message')
            ],
            'Originator: AlphaNumeric' => [
                $this->getBody('+123456789', '1234Evan', 'Simple short plain message')
            ],
            'Message: Simple' => [
                $this->getBody('123456789', 'Evangelos', 'Simple short plain message')
            ],
            'Message: Unicode' => [
                $this->getBody('123456789', 'Evangelos', 'This is a test message with a smiling emoji 😀.')
            ],
        ];
    }

    /**
     * Provides a collection of invalid POST request bodies.
     * @return array
     */
    public function provideInvalidPostBody()
    {
        return [
            'Recipients: Not set' => [
                $this->getBodyUnsetField('+123456789', 'Evangelos', 'Simple short plain message', 'recipients')
            ],
            'Recipients: Two + signs at the beginning' => [
                $this->getBody('++123456789', 'Evangelos', 'Simple short plain message')
            ],
            'Recipients: empty' => [$this->getBody('123456789,,123456', 'Evangelos', 'Simple short plain message')],
            'Recipients: alphanumeric' => [
                $this->getBody('123456789,123f456', 'Evangelos', 'Simple short plain message')
            ],
            'Originator: numeric less than zero' => [
                $this->getBody('123456789', '-12345', 'Simple short plain message')
            ],
            'Originator: Not set' => [
                $this->getBodyUnsetField('+123456789', 'Evangelos', 'Simple short plain message', 'originator')
            ],
            'Originator: alphanumeric length more than 11' => [
                $this->getBody('123456789', 'Evangelos123455456', 'Simple short plain message')
            ],
            'Simple Message: Length more than 1377' => [
                $this->getBody('123456789', 'Evangelos',
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec gravida leo vel tellus molestie, in consectetur lorem vehicula. Vivamus egestas ante vel mi dignissim, ut pharetra nisl suscipit. Proin dapibus, ipsum at aliquam tristique, ligula nunc ullamcorper arcu, sit amet placerat sapien sapien ornare metus. Nullam molestie volutpat elit, quis vestibulum lectus sagittis at. Vestibulum porta orci justo, vel convallis massa volutpat vel. Vestibulum dapibus dolor at nulla aliquet euismod. Aenean aliquet ante sem, eu varius orci faucibus non.Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Integer nec vulputate orci, vel mollis lorem. Pellentesque tellus felis, lacinia eget magna sed, porta pretium tellus. Nunc vitae nisl lobortis, iaculis elit vel, consectetur risus. Curabitur sollicitudin ligula ullamcorper imperdiet interdum. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Maecenas malesuada nisi ut nulla bibendum euismod. Integer bibendum, turpis a maximus mattis, odio nisi tempor mi, dignissim vulputate urna felis eget enim. Donec scelerisque, felis id ultricies rutrum, urna velit finibus urna, sit amet aliquam velit mi sed est.Quisque eu lacus eget purus ornare venenatis. Donec nec magna vel urna feugiat hendrerit. Suspendisse tellus sem, rutrum vitae vestibulum vitae, ullamcorper non mi. Aliquam velit felis, dictum a ligula id, malesuada rhoncus leo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.')
            ],
            'Simple Message: Empty' => [
                $this->getBody('123456789', 'Evangelos', '')
            ],
            'Unicode Message: Length more than 603' => [
                $this->getBody('123456789', 'Evangelos',
                    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec gravida leo vel tellus molestie, in consectetur lorem vehicula. Vivamus egestas ante vel mi dignissim, ut pharetra nisl suscipit. Proin dapibus, ipsum at aliquam tristique, ligula nunc ullamcorper arcu, sit amet placerat sapien sapien ornare metus. Nullam molestie volutpat elit, quis vestibulum lectus sagittis at. Vestibulum porta orci justo, vel convallis massa volutpat vel. Vestibulum dapibus dolor at nulla aliquet euismod. Aenean aliquet ante sem, eu varius orci faucibus non.Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Εθμ vελιτ ιθδιcο ιισqθε αν, εαμ ετιαμ οπορτεατ δελιcατα ιν.')
            ],
        ];
    }

    private function getBody($recipients, $originator, $message)
    {
        $bodyData = new \stdClass();
        $bodyData->recipients = $recipients;
        $bodyData->originator = $originator;
        $bodyData->message = $message;
        return $bodyData;
    }

    private function getBodyUnsetField($recipients, $originator, $message, $fieldToUnset)
    {
        $bodyData = $this->getBody($recipients, $originator, $message);
        unset($bodyData->$fieldToUnset);
        return $bodyData;
    }
}
