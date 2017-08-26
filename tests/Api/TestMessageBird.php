<?php

namespace Evangelos\MessageBird\Validation\Tests;

use Evangelos\MessageBird\Api\MessageBird;

class TestMessageBird extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if the message of the body needs to be chunked.
     * All the test cases provided by the method provideMessageNeedsToBeChunked() are expected to return true.
     *
     * @dataProvider provideMessageNeedsToBeChunked
     * @param $message
     * @param $isUnicode
     */
    public function testMessageNeedsToBeChunked($message, $isUnicode)
    {
        $this->assertTrue(MessageBird::doesMessageNeedToBeChunked($message, $isUnicode));
    }

    /**
     * Tests if the message of the body needs to be chunked.
     * All the test cases provided by the method provideMessageDoesNotNeedToBeChunked() are expected to return false.
     *
     * @dataProvider provideMessageDoesNotNeedToBeChunked
     * @param $message
     * @param $isUnicode
     */
    public function testMessageDoesNotNeedToBeChunked($message, $isUnicode)
    {
        $this->assertFalse(MessageBird::doesMessageNeedToBeChunked($message, $isUnicode));
    }

    public function provideMessageNeedsToBeChunked()
    {
        return [
            [
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec gravida leo vel tellus molestie, in consectetur lorem vehicula. Vivamus egLorem ipsum dolor sit amet',
                false
            ],
            [
                'Lorem ípsum dolor sit ámét, iďqúe impetus át per, usu ců mutat coňveňire? Nec ať iisque pónderum.',
                true
            ]
        ];
    }

    public function provideMessageDoesNotNeedToBeChunked()
    {
        return [
            [
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
                false
            ],
            [
                'Lorem ípsum dolor sit ámét, iďqúe impetus át per',
                true
            ]
        ];
    }
}
