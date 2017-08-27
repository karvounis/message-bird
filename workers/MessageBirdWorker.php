<?php

require __DIR__ . '/../vendor/autoload.php';

use \Evangelos\MessageBird\Api\App;
use \Evangelos\MessageBird\Api\MessageBird;

//Simple worker that pops a message from Redis Queue and sends it to MessageBird.

$redisClient = App::getRedisClient();
$messageBirdClient = App::getMessageBirdClient();

echo 'Starting worker' . PHP_EOL;

$redisClient->connect();

while (true) {
    sleep(1);
    $redisQueuePopResponse = $redisClient->blpop([MessageBird::REDIS_QUEUE_NAME], 10);

    if (!is_null($redisQueuePopResponse)) {
        $firstQueuedMessage = $redisQueuePopResponse[1];
        $messageToSendDecoded = json_decode($firstQueuedMessage);
        echo $firstQueuedMessage . PHP_EOL;

        $messageToSend = MessageBird::prepareMessageBirdMessage($messageToSendDecoded->originator,
            $messageToSendDecoded->recipients, $messageToSendDecoded->body, $messageToSendDecoded->typeDetails,
            $messageToSendDecoded->datacoding, $messageToSendDecoded->type);
        $messageBirdClient->messages->create($messageToSend);
    }
    echo 'Waiting for a job' . PHP_EOL;
}
