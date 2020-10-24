<?php

/**
 *  2020-10-23 - @rchaube 
 * 
 * Class ConnsPool
 * 
 * Uses to manage the pool of clients connecting to the web server 
 * alongwith their associated metadata
 * 
 * Adapted from the works of Sergey Zuc React-PHP Socker server & the amazing people at ReactPHP ( https://reactphp.org/ )
 *
*/

use React\Socket\ConnectionInterface;


class ConnsPool
{
    private $connections;

    /**
	 * Constructor. Initializes an object data map
	 *
	 * @param none
	 * @return void
	 */
    public function __construct()
    {
        $this->connections = new SplObjectStorage();
    }

    /**
	 * Add connection to pool and fireoff the callback listeners
	 *
	 * @param ConnectionInterface $connection
	 * @return void
	 */
    public function add(ConnectionInterface $connection)
    {
        // initiate the callback listeners
        $this->initEvents($connection);
    }

    /**
	 * Handle the incoming data and fire off the requested commands
	 *
	 * @param ConnectionInterface $connection
	 * @return void
	 */
    private function initEvents(ConnectionInterface $connection)
    {
        // callback event that is fired when some data comes into the connection
        $connection->on('data', function ($data) use ($connection) {

            // Handle empty data
            if (trim($data) == '') {
                $connection->write('Empty input, ignoring..');
                return;
            }

            if (trim($data) == '^c') {
                $connection->write('Closing server');
                exit(1);
            }

            // Break apart the data using regex match to eke out command, topic and optionally the message
            // Ignore malfromed requests for now
            preg_match("/(.*?)\s+(.*?)\s+(.*)/", $data, $arr);
            $command = strtoupper(trim($arr[1]));
            $topic = trim($arr[2]);
            $msg = isset($arr[3])?$arr[3]:'';

            // Handle empty topic
            if(strlen($topic) == 0){
                throw new Exception("Please provide the argument: topic");
            }

            // Handle the various commands 
            if($command == 'SUBSCRIBE'){
                $this->addSub($topic, $connection);
                return;
            }else if($command == 'PUBLISH'){
                $this->pub($topic, $msg, $connection);
            }else{
                // Handle everything else
                $connection->write('Unknown command, ignoring..' . PHP_EOL);
                return;
            }

        });

        // callback event that is fired when the connection is closed
        $connection->on('close', function() use ($connection){
            $this->connections->offsetUnset($connection);
        });
    }

    /**
	 * Set the meta data in the object data map for the connection
	 *
	 * @param ConnectionInterface $connection
     * @param Arrat $data containing key value pairs
	 * @return void
	 */
    private function setConnectionData(ConnectionInterface $connection, $data)
    {
        $this->connections->offsetSet($connection, $data);
    }

    /**
	 * Get the meta data associated with the connection
	 *
	 * @param ConnectionInterface $connection
	 * @return Array containing key value pairs 
	 */
    private function getConnectionData(ConnectionInterface $connection)
    {
        return $this->connections->offsetGet($connection);
    }

    /**
	 * Subsscribe method: Add the connectiing client to the requested topic
	 * 
     * @param String $topic
	 * @param ConnectionInterface $connection
	 * @return Array containing key value pairs
     * Throws exception on error 
	 */
    private function addSub($topic, ConnectionInterface $connection)
    {
        // Handle empty topic
        if(strlen($topic) == 0){
            throw new Exception("Topic not provided". PHP_EOL);
        }

        // clean up topic
        $topic = str_replace(["\n", "\r"], "", $topic);

	    try {

            // set the connection metadata
            $this->setConnectionData($connection, ['topic' => $topic]);
            
            // let the client know
            $connection->write('Successfully subscribed you to TOPIC: '.$topic. PHP_EOL);

        } catch(Exception $e){

            // notify client of failure
            $connection->write('Unable to subscribe. Server Issue '. PHP_EOL);

            // notify admin admin/developers 
            throw new Exception('Error subscribing. Reason: '. $e->getMessage() . PHP_EOL);
            
            // notify full exception stack 
            if ($e->getPrevious()) {
                throw new Exception($e->getPrevious()->getMessage() . PHP_EOL);
            }

            // kill the server
            exit(1);
        }

    }

     /**
	 * Publish method: write the message to all clients who are listening on the topic
	 *
     * @param String $topic - the topic or channel to publish to
     * @param String $msg - message
	 * @param ConnectionInterface $connection
	 * @return void
     * Throws exception on error 
	 */
    private function pub($topic, $msg, ConnectionInterface $except) {

        // Handle empty topic
        if(strlen($topic) == 0){
            throw new Exception("Unable to publish. Topic has not been provided.". PHP_EOL);
        }

        // clean up topic
        $topic = str_replace(["\n", "\r"], "", $topic);

        try {
            // loop over connections in the pool
            foreach ($this->connections as $conn) {

                // pull connection metadata to see which topic they are subscribed to
                $conndata = $this->getConnectionData($conn);

                // Publish to all those listening on the topic except the publisher
                if( ($conn != $except) && ($conndata['topic'] == $topic)){
                    $conn->write($msg.PHP_EOL);
                }
            }

            // notify publisher that it worked 
            $except->write('Successfully published to TOPIC: '.$topic. PHP_EOL);

        }catch(Exception $e){

            // let the client know
            $except->write('Unable to publish. Server Issue.' . PHP_EOL);

            //notify full exception stack 
            if ($e->getPrevious()) {
                $connection->write($e->getPrevious()->getMessage() . PHP_EOL);
            }

            // kill the server
            exit(1);
        }


    }




}
