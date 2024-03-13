<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// AMQP connection
$connection = new AMQPStreamConnection('localhost', 5672, 'mqttu', 'mqttp');
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, true, false, false);

$channel->queue_declare('result_queue', false, true, false, false); // Ensure this line is present

// Callback to handle received results
$resultCallback = function ($msg) {
    $result = json_decode($msg->body, true);
    echo 'Received result: ', $result['result'], "\n";
};

$channel->basic_consume('result_queue', '', false, true, false, false, $resultCallback);

// Sending a task
$taskData = ['task' => 'calculate', 'data' => [/* task data */]];
$msg = new AMQPMessage(json_encode($taskData), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
$channel->basic_publish($msg, '', 'task_queue');
echo "Sent task\n";

// Waiting for results
while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();