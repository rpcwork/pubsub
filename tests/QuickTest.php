<?php

/**
 * 2020-10-23 - @rchaube 
 *  QuickTest functional test class
 * 
 * HOW TO RUN THE TESTS
 *  1. Start Server:    cd /var/www/pubsub/src;php ws.php;
 *  2. Run Tests:       cd /var/www/pubsub/;./vendor/bin/phpunit tests;
 * 
 * Adapted and Exended from React-PHP test suite available at ( https://reactphp.org/ )
 * 
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/class.ConnsPool.php';
require_once __DIR__ . '/../src/class.Telnet.php';

use React\EventLoop\Factory;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;
use React\Socket\LimitingServer;
use React\Promise\Deferred;
use React\Stream\DuplexResourceStream;
use Clue\React\Block;
use PHPUnit\Framework\TestCase;

class QuickTest extends TestCase
{
    // set this to +1 second for approximately every 2000 miles you are away from the server in San Francisco
    // minimum 1 sec
    const TIMEOUT = 1.0;

        /**
         * Test out code that connects to web server 
         *
         * @param none
         * @return void
         */
        public function testConnectionToWebServer(): void
        {
                // Create an instance of Factory method 
                $loop = Factory::create();

                // Create instance of connector using the factory instance
                $connector = new Connector($loop);

                // Connect to the host@port
                $connection = Block\await($connector->connect($_ENV['HOST'].':'.$_ENV['PORT']), $loop,  self::TIMEOUT);

                // ensure we get the correct instance back
                $this->assertInstanceOf('React\Socket\ConnectionInterface', $connection);

                // close connection
                $connection->close();
        }

        /**
         * Test out code that subscribes clients to the requested topic
         * employs the Telnet client class
         *
         * @param none
         * @return void
         */
        public function testSubscribe(): void
        {
                // Create instance of the telnet class and open a connection
                $tclient = new Telnet($_ENV['HOST'].':'.$_ENV['PORT'],self::TIMEOUT);

                $topic = 'hiking';

                $msg = 'SUBSCRIBE '.$topic;

                $expectedresponse = 'Successfully subscribed you to TOPIC: '.$topic;

                // subscribe to topic
                $response =  $tclient->exec($msg);

                // ensure that we get back the expected response
                $this->assertEquals($expectedresponse, $response);

                // disconnect the telnet cleint
                $tclient->disconnect();

                return ;

        }

        /**
         * Test out code that publishes message to the requested topic 
         * employs the Telnet client class
         *
         * @param none
         * @return void
         */
        public function testPublish(): void
        {
                // Create instance of the telnet class and open connection 1
                $tclient1 = new Telnet($_ENV['HOST'].':'.$_ENV['PORT'],self::TIMEOUT);
                $topic = 'hiking';
                $msg = 'SUBSCRIBE '.$topic;

                // subscribe to topic
                $response =  $tclient1->exec($msg);

                // clear the buffer
                $tclient1->clearBuffer();

                // Create instance of the telnet class and open connection 2
                $tclient2 = new Telnet($_ENV['HOST'].':'.$_ENV['PORT'],self::TIMEOUT);
                $topic = 'fitness';
                $msg = 'SUBSCRIBE '.$topic;

                // subscribe to topic
                $response =  $tclient2->exec($msg);

                // clear the buffer
                $tclient2->clearBuffer();


                // Create instance of the telnet class and open connection 3
                $tclient3 = new Telnet($_ENV['HOST'].':'.$_ENV['PORT'],self::TIMEOUT);
                $topic = 'hiking';
                $str = 'Hello_Hikers';
                $msg = 'PUBLISH '.$topic.' '.$str;

                // publish message to topic
                $responsepub =  $tclient3->exec($msg);

                // Ensure we are getting the correct response back
                $expectedresponse = 'Successfully published to TOPIC: '.$topic;
                $this->assertEquals($expectedresponse, $responsepub);

                // get the buffer from telnet client1
                $responsesub = $tclient1->readwithwait();

                // Ensure we are getting the correct response back
                $expectedresponse = $str;
                $this->assertEquals($expectedresponse, $responsesub);

                // Get the buffer from telnet client2 and wnsure we are getting the correct response back
                $responsenotsub = $tclient2->readwithwait();
                $this->assertNotEquals($expectedresponse, $responsenotsub);
                
                // disconnect the telnet clients
                $tclient1->disconnect();
                $tclient2->disconnect();
                $tclient3->disconnect();

                return;

        }


}
