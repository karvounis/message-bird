# Message Bird

Implementation of a simple API that posts messages to Message Bird.

## Technologies used
* PHP 7.1
* NGINX
* Redis 3.2.10
* Docker

## Setup
1. Execute `composer update` to download project's dependencies.
2. Execute `php vendor/bin/phing` and provide the values that you are asked. 
This is used to generate the config files and the docker-compose.yml with values that we do not want to commit.
You can see that 2 new files appear in the config/ : MessageBird.ini and Redis.ini and the docker-compose.yml.
3. Execute `docker-compose up -d`. It will pull and create 3 containers: nginx, php-fpm and redis.
Now, you are ready to accept connections to the API and push messages to a redis queue.
4. Make a POST request to http://0.0.0.0:%%NGINX_PORT%%/message. If it is successful you should see a respond with message: 'Request successful'.
That results in a message pushed into the Redis queue, waiting to be picked up by a worker.
5. Now, you need to instantiate a worker in order to start sending messages to MessageBird.
You can execute `docker exec -it messagebird_php_1 php /usr/share/nginx/html/workers/MessageBirdWorker.php`. 
This command starts a worker with an interactive shell. 


## How it works
### API
The API itself has the /message endpoint where it accepts POST requests. 
First, it validates the fields of the request body.
If everything, is valid, pushes the message to be sent to MessageBird to a Redis queue.
Goal of the API is to queue the message and respond as fast as possible to the request.

### Message Worker
The worker is pretty simple. It is responsible for popping messages from Redis queue and sending them to MessageBird API.
