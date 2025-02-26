#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use RPurinton\Log;

function testLogging()
{
    echo "Testing with webhook...\n";
    Log::init();
    Log::error('This is an error message', ['foo' => 'bar']);

    echo "Testing log file...\n";
    $log_file = __DIR__ . '/test.log';
    putenv("LOG_FILE=$log_file");
    Log::init();
    Log::error('This is an error message', ['foo' => 'bar']);
    echo (file_get_contents($log_file));
    @unlink($log_file);

    echo "Testing console log...\n";
    putenv("LOG_FILE=php://stdout");
    Log::init();
    Log::error('This is an error message', ['foo' => 'bar']);

    echo "Testing Error Handling...\n";
    Log::install();
    $foo = 1 / 0;
}

testLogging();
