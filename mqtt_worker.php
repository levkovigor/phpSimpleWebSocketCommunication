<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;

function getCurrentTimestamp($millis = false): int {
    return intval(floor(microtime(true) * ($millis ? 1000 : 1)));
}

$sid = 0;

$connection = new AMQPStreamConnection('localhost', 5672, 'mqttu', 'mqttp');
$channel = $connection->channel();

$exchangeName = 'broadcast_exchange';
$queueName = 'worker_' . getmypid();
$bindingKey = 'task';

$channel->exchange_declare($exchangeName, 'topic', false, true, false);
$channel->queue_declare($queueName, false, false, true, false);
$channel->queue_bind($queueName, $exchangeName, $bindingKey);
$checkwait = false;

$callback = function ($msg) {
	global $checkwait;
    $taskData = json_decode($msg->body, true);
	if (isset($taskData['sid'])){
		if ($taskData['sender'] != getmypid()) {
			echo "Task processed by ". getmypid() .": Sender ". $taskData['sender']. " -> ". $taskData['sid']."\n";
		}
		$checkwait = true;
	} 
};

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

function sendResultBack($sid) {
    global $channel, $exchangeName, $bindingKey;
    $taskData = ['task' => 'heartbeat', 'sender' => getmypid(), 'sid' => $sid];
    $msg = new AMQPMessage(json_encode($taskData));
    $channel->basic_publish($msg, $exchangeName, $bindingKey);
}

$ts = getCurrentTimestamp(true);

while ($channel->is_consuming()) {
	do { 
		$checkwait = false;
		try {
			$channel->wait(null, true, 0.1);
		} catch (Exception $e) {
			echo $e;			
		}
	} while($checkwait);
    /*$pp = getCurrentTimestamp(true);
    if ($pp - $ts >= 2000) {
        //echo getmypid() . " - sending...\n";
        sendResultBack();
        $ts = $pp;
    }*/
	sleep(10);
	echo " >>> ".$sid. " -> ". getmypid() . " - sending...\n";
	sendResultBack($sid);
	$sid++;
}

$channel->close();
$connection->close();
