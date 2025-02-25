<?php

namespace RPurinton\Helpers;

use RPurinton\Exceptions\LogException;

class LogHelpers
{
    /**
     * Get numeric log level based on its string representation.
     *
     * @param string|null $level The log level string (optional).
     * @return int Corresponding numeric log level.
     * @throws LogException if an invalid log level is provided.
     */
    public static function getLevel(string $level = null): int
    {
        $level = strtoupper($level);
        $levels = [
            'TRACE'     => 0,
            'DEBUG'     => 1,
            'INFO'      => 2,
            'NOTICE'    => 3,
            'WARN'      => 4,
            'ERROR'     => 5,
            'CRITICAL'  => 6,
            'ALERT'     => 7,
            'EMERGENCY' => 8,
            'FATAL'     => 9,
            'OFF'       => 10,
        ];
        if (!array_key_exists($level, $levels)) {
            throw new LogException('Invalid log level: ' . $level);
        }
        return $levels[$level];
    }

    /**
     * Returns a color based on the log level.
     * Used for embeds in Discord webhooks.
     * 
     * @param string $level The log level.
     * @return int The color value.
     */
    public static function getColor(string $level): int
    {
        return match (strtoupper($level)) {
            'TRACE' => 0x9b59b6,
            'DEBUG' => 0x3498db,
            'INFO' => 0x2ecc71,
            'NOTICE' => 0x1abc9c,
            'WARN' => 0xf1c40f,
            'ERROR' => 0xe74c3c,
            'CRITICAL' => 0x8e44ad,
            'ALERT' => 0xe67e22,
            'EMERGENCY' => 0xc0392b,
            default => 0x95a5a6,
        };
    }
}