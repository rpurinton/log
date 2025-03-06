#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use RPurinton\Log;

function testLogging()
{
    echo "Testing with webhook...\n";
    Log::init();
    Log::error('This is an error message', ['foo' => 'bar']);

    echo "Testing console log...\n";
    putenv("LOG_FILE=php://stdout");
    Log::init();
    Log::error('This is an error message', ['foo' => 'bar']);

    echo "Testing with error_log()...\n";
    putenv("LOG_FILE=error_log()");
    Log::init();
    Log::error('This is an error_log');

    echo "Testing Error Handling...\n";
    Log::install();
    $foo = 1 / 0;
}

testLogging();
