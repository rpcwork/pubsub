<?php

/**
 * 2020-10-23 - @rchaube 
 * 
 * Webserver Config loader
 *
 * Used to load the configuration settings from the .env file
 * 
 */

 // load the vendor libs
require __DIR__ . '/vendor/autoload.php';

// pull in the .env config settings
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
