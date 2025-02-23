<?php

declare(strict_types=1);

namespace RPurinton;

use RPurinton\Exceptions\LogException;

/**
 * Class Log
 *
 * A final class for logging messages to either a file, a webhook, or PHP's error_log.
 * The configuration is initialized via environment variables or a config file.
 */
final class Log
{
    /**
     * Log level threshold.
     *
     * @var string|null
     */
    private static ?string $logLevel = null;

    /**
     * Log output destination, either a file path or a webhook URL.
     *
     * @var string|null
     */
    private static ?string $logFile = null;

    /**
     * Determines whether the log destination is a webhook.
     *
     * @var bool
     */
    private static bool $isWebhook = false;

    /**
     * Use PHP's error_log if LOG_FILE is not set.
     *
     * @var bool
     */
    private static bool $useErrorLog = false;

    /**
     * Initialize the Log configuration.
     *
     * Attempts to load configuration from the Config library, and if that fails
     * or required values are missing, falls back to loading from environment variables.
     * Finally, validates the loaded configuration.
     *
     * @return void
     * @throws LogException if the configuration does not pass validation.
     */
    public static function init(): void
    {
        self::loadConfiguration();
        self::validate();
    }

    /**
     * Load configuration from the Config library or fallback to environment variables.
     *
     * This method first attempts to load configuration using Config::get().
     * If an exception is thrown or the required keys ("level" and "file") are missing,
     * it falls back to reading configuration from environment variables.
     *
     * @return void
     */
    private static function loadConfiguration(): void
    {
        self::$logLevel = null;
        self::$logFile = null;
        self::$useErrorLog = false;
        self::$isWebhook = false;

        try {
            $config = Config::get('Log', ['level' => 'string']);
            $configLevel = $config['level'];
            self::$logLevel = ($configLevel && trim($configLevel) !== '') ? $configLevel : 'OFF';
            if (isset($config['file']) && $config['file'] !== null) {
                $configFile = $config['file'];
                if ($configFile === false || trim($configFile) === '') {
                    self::$useErrorLog = true;
                } else {
                    self::$logFile = $configFile;
                }
                return;
            }
        } catch (\Throwable $e) {
            // Swallow exception and fallback to env vars.
        }
        if (self::$logLevel === null) {
            $envLevel = getenv('LOG_LEVEL');
            self::$logLevel = ($envLevel && trim($envLevel) !== '') ? $envLevel : 'OFF';
        }
        if (self::$logFile === null) {
            $envFile = getenv('LOG_FILE');
            if ($envFile === false || trim($envFile) === '') {
                self::$useErrorLog = true;
            } else {
                self::$logFile = $envFile;
            }
        }
    }

    /**
     * Validate the loaded log configuration.
     *
     * - If no log file is set, the logger falls back to PHP's error_log.
     * - If a log file is set and its value starts with "http", it is treated as a webhook.
     * - Otherwise, verifies that the directory for the log file exists and is writable.
     *
     * @return void
     * @throws LogException if the log file directory does not exist or is not writable.
     */
    private static function validate(): void
    {
        if (self::$logFile === null) {
            self::$useErrorLog = true;
        } else {
            if (stripos(self::$logFile, 'http') === 0) {
                self::$isWebhook = true;
            } else {
                $logDir = dirname(self::$logFile);
                if (!is_dir($logDir) || !is_writable($logDir)) {
                    throw new LogException("Log file directory ($logDir) does not exist or is not writable.");
                }
            }
        }
    }

    /**
     * Get numeric log level based on its string representation.
     *
     * @param string|null $level The log level string (optional).
     * @return int Corresponding numeric log level.
     * @throws LogException if an invalid log level is provided.
     */
    public static function getLevel(string $level = null): int
    {
        // Assume init() has been called.
        if ($level === null) $level = self::$logLevel ?? 'OFF';
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
    private static function getColor(string $level): int
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

    /**
     * Write a log message if the current log level threshold allows it.
     *
     * Formats the message with a timestamp and level, then sends it either to PHP's error_log,
     * a webhook, or writes it to a file.
     *
     * @param string $level   The log level of the message.
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function write(string $level, string $message, array $context = []): void
    {
        if (self::getLevel() <= self::getLevel($level)) {
            if (self::$isWebhook) {
                self::sendToWebhook($level, $message, $context);
                return;
            }
            $date      = date('Y-m-d H:i:s');
            $levelUp   = strtoupper($level);
            $formatted = "$date [$levelUp] $message";
            if (!empty($context)) $formatted .= ' ' . json_encode($context);

            if (self::$useErrorLog) error_log($formatted);
            else self::writeToFile($formatted . PHP_EOL);
        }
    }

    /**
     * Sends the log payload to the configured webhook URL.
     *
     * @param string $payload The formatted log payload.
     * @return void
     * @throws LogException if the webhook request fails.
     */
    private static function sendToWebhook(string $level, string $message, array $context): void
    {
        $embed = [
            'title'       => "<t:" . time() . ":R> $level",
            'description' => $message,
            'color'       => self::getColor($level),
            'timestamp'   => date('c'),
            'footer'      => ['text' => 'RPurinton\Log'],
            'fields'      => [],
        ];
        if (!empty($context)) {
            foreach ($context as $key => $value) {
                $embed['fields'][] = ['name' => $key, 'value' => $value, 'inline' => true];
            }
        }
        $payload = ['embeds' => [$embed]];
        try {
            $result = Webhook::post(self::$logFile, $payload);
            if ($result !== '') {
                throw new LogException('Failed to send log to the webhook: ' . $result);
            }
        } catch (\Throwable $e) {
            error_log('Error sending log to webhook: ' . $e->getMessage());
        }
    }

    /**
     * Writes the log content to the log file.
     *
     * @param string $content The formatted log content.
     * @return void
     * @throws LogException if writing to the file fails.
     */
    private static function writeToFile(string $content): void
    {
        try {
            $written = file_put_contents(self::$logFile, $content, FILE_APPEND | LOCK_EX);
            if ($written === false) {
                throw new \RuntimeException('Failed to write to log file.');
            }
        } catch (\Throwable $e) {
            error_log('Error writing to log file: ' . $e->getMessage());
        }
    }

    /**
     * Logs a message with TRACE level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function trace(string $message, array $context = []): void
    {
        self::write('TRACE', $message, $context);
    }

    /**
     * Logs a message with DEBUG level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    /**
     * Logs a message with INFO level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    /**
     * Logs a message with NOTICE level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        self::write('NOTICE', $message, $context);
    }

    /**
     * Logs a message with WARN level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function warn(string $message, array $context = []): void
    {
        self::write('WARN', $message, $context);
    }

    /**
     * Logs a message with ERROR level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    /**
     * Logs a message with CRITICAL level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    /**
     * Logs a message with ALERT level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        self::write('ALERT', $message, $context);
    }

    /**
     * Logs a message with EMERGENCY level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::write('EMERGENCY', $message, $context);
    }
    /**
     * Logs a message with FATAL level.
     *
     * @param string $message The log message.
     * @param array $context  Additional context data (optional).
     * @return void
     */
    public static function fatal(string $message, array $context = []): void
    {
        self::write('FATAL', $message, $context);
    }

    /**
     *  Initializes and installs the exception and error handlers.
     * 
     * @return void
     */
    public static function install(): void
    {
        self::init();
        self::handleExceptions();
        self::handleErrors();
    }

    public static function handleExceptions(): void
    {
        set_exception_handler(function (\Throwable $e) {
            self::fatal($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            exit(1);
        });
    }

    public static function handleErrors(): void
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            self::warn($errstr, ['file' => $errfile, 'line' => $errline]);
            return true;
        });
    }
}
