<?php

require __DIR__ . '/vendor/autoload.php';

use RPurinton\Log;

Log::install();

Log::trace('This is a trace message');
Log::debug('This is a debug message');
Log::info('This is an info message');
Log::warn('This is a warning message');
Log::error('This is an error message', ['foo' => 'bar']);

// force an error
$foo = 1 / 0;
