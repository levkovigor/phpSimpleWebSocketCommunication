<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'mqttu', 'mqttp');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

// Worker callback to process task
$callback = function ($msg) {
    $taskData = json_decode($msg->body, true);
    $result = performTask($taskData['data']);
    sendResultBack($result);
    echo "Task processed\n";
};

$channel->basic_consume('task_queue', '', false, true, false, false, $callback);

function performTask($data) {
    // Placeholder for task processing logic
    return 'result_of_calculation';
}

function sendResultBack($result) {
    global $channel;
    $resultData = ['result' => $result];
    $msg = new AMQPMessage(json_encode($resultData), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    $channel->basic_publish($msg, '', 'result_queue');
}

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
