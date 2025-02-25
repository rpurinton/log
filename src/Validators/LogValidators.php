<?php

namespace RPurinton\Validators;

class LogValidators
{
    public static function validateLevel(string $level)
    {
        if (!in_array(strtoupper($level), ['TRACE', 'DEBUG', 'INFO', 'NOTICE', 'WARN', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'FATAL', 'OFF'])) {
            throw new \InvalidArgumentException('Invalid log level');
        }
    }

    public static function validateLogFile(?string $logFile): void
    {
        if ($logFile === null) {
            return;
        }

        if (stripos($logFile, 'http') === 0) {
            return;
        }

        if ($logFile === 'php://stdout') {
            return;
        }

        $logDir = dirname($logFile);
        if (!is_dir($logDir) || !is_writable($logDir)) {
            throw new \InvalidArgumentException("Log file directory ($logDir) does not exist or is not writable.");
        }
    }
}