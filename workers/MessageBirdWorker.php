<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/chargeover/redlock-php/src/RedLock.php';

use \Evangelos\MessageBird\Api\App;
use \Evangelos\MessageBird\Api\MessageBird;

define("REDIS_LOCK_KEY", "queue-lock");

echo 'Starting worker' . PHP_EOL;

//Simple worker that pops a message from Redis Queue and sends it to MessageBird.
$redisClient = App::getRedisClient();
$redisClient->connect();
$messageBirdClient = App::getMessageBirdClient();
$redLock = getRedLock();

// Infinite loop that checks if the lock has been released. If not, locks it for 1 second.
// Then, pops a message from Redis queue and sends it to MessageBird API
while (true) {
    $lock = $redLock->lock(REDIS_LOCK_KEY, 1000);
    if ($lock) {
        $redisQueuePopResponse = $redisClient->blpop([MessageBird::REDIS_QUEUE_NAME], 10);
        if (!is_null($redisQueuePopResponse)) {
            $firstQueuedMessage = $redisQueuePopResponse[1];
            $messageToSendDecoded = json_decode($firstQueuedMessage);
            echo 'Sending message: ' . $firstQueuedMessage . PHP_EOL;

            $messageToSend = MessageBird::prepareMessageBirdMessage($messageToSendDecoded->originator,
                $messageToSendDecoded->recipients, $messageToSendDecoded->body, $messageToSendDecoded->typeDetails,
                $messageToSendDecoded->datacoding, $messageToSendDecoded->type);
            $messageBirdClient->messages->create($messageToSend);
        }
    }
    sleep(1);
    echo 'Waiting for a job' . PHP_EOL;
}

/**
 * Returns an instance of RedLock. Used to lock keys in Redis
 * @return RedLock
 */
function getRedLock()
{
    $redisIni = parse_ini_file(__DIR__ . '/../config/Redis.ini');
    $servers = [
        [$redisIni['host'], $redisIni['port'], 0.01]
    ];
    return new RedLock($servers);
}
