<?php

namespace RPurinton\Validators;

class LogValidators
{
    public static function validateLevel(string $level): void
    {
        $validLevels = ['TRACE', 'DEBUG', 'INFO', 'NOTICE', 'WARN', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'FATAL', 'OFF'];
        if (!in_array(strtoupper($level), $validLevels)) {
            throw new \InvalidArgumentException('Invalid log level');
        }
    }

    public static function validateLogFile(?string $logFile): void
    {
        if ($logFile === null || stripos($logFile, 'http') === 0 || $logFile === 'php://stdout') {
            return;
        }

        $logDir = dirname($logFile);
        if (!is_dir($logDir) || !is_writable($logDir)) {
            throw new \InvalidArgumentException("Log file directory ($logDir) does not exist or is not writable.");
        }
    }
}
