<?php
require __DIR__ . '/vendor/autoload.php';

$int = getmypid(); 

use Amp\ByteStream\StreamException;
use Amp\Loop;
use Amp\Websocket;

Loop::run(function () {
    $errors = 0;

    $options = (new Websocket\Options)
        ->withMaximumMessageSize(32 * 1024 * 1024)
        ->withMaximumFrameSize(32 * 1024 * 1024)
        ->withValidateUtf8(true);

    $connection = yield Websocket\connect('ws://localhost:4551/chat');
	
	Loop::repeat(2000, function () use ($connection) {
		//printf("SENT: HELLO FORM ".$int."\n");
        $connection->send('HELLO FORM '.getmypid());
    });
	
    while (true) {
        try {
            $message = yield $connection->receive();
            $payload = yield $message->buffer();
            printf("Received: %s\n", $payload);
        } catch (StreamException $e) {
            // Handle stream exceptions (e.g., connection closed)
            break;
        }
    }

    $connection->close();
    
	Loop::stop();

});

