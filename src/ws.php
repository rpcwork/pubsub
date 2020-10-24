<?php

/**
 * 2020-10-23 - @rchaube 
 * 
 * Webserver spawner using ReactPHP which is a low-level library that provides event driven, non-blocking I/O with PHP
 * 
 *****************************************************
 * How to Use this web server
 *****************************************************
 * 1. START web server. It will listen on on the IP Address and Port configured in .env 
 *    cd /var/www/pubsub/src; php ws.php
 *  
 * 2. SUBSCRIBE clients to different topics from different hosts
 *    telnet 216.98.11.106 8080
 *    SUBSCRIBE 123
 *    or
 *    SUBSCRIBE abc
 * 
 * 3. PUBLISH to topics clients have subscribed to
 *    Should only be seen by clients subscribed to abc
 *    PUBLISH abc hi-abc
 *    following should only be seen by clients subscribed to 123
 *    PUBLISH 123 hi-123
***************************************************** 
 */

use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\ConnectionInterface;
use React\Socket\LimitingServer;

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/class.ConnsPool.php';

// Create an instance of Factory method
$loop = Factory::create();

// Create server instance using the factory loop
$server = new Server($_ENV['HOST'].':'.$_ENV['PORT'], $loop);

// Create an instance of LimitingServer so that we have tracking of connections
$server = new LimitingServer($server, null);

// Spawn the Connection Pools
$pool = new ConnsPool();

// Set up callback for this connection
$server->on('connection', function(ConnectionInterface $connection) use ($pool){
    $pool->add($connection);
});

// In case of error, just print out errors for diagnosis
$server->on('error', 'printf');

// Let the server admin know server is ready to start
echo 'Listening on ' . $server->getAddress() . PHP_EOL;

// Get the server going
$loop->run();
