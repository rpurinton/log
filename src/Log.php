<?php

declare(strict_types=1);

namespace RPurinton;

use RPurinton\{Config, Webhook};
use RPurinton\Helpers\LogHelpers;
use RPurinton\Validators\LogValidators;
use RPurinton\Exceptions\LogException;

/**
 * Class Log
 * Handles logging messages to a file, stdout, webhook, or PHP's error_log.
 */
final class Log
{
    private static ?string $logLevel = null;
    private static ?string $logFile = null;
    private static bool $isWebhook = false;
    private static bool $useErrorLog = false;

    /**
     * Initialize the Log configuration.
     * @throws LogException if the configuration does not pass validation.
     */
    public static function init(): void
    {
        self::loadConfiguration();
    }

    /**
     * Initialize and install the exception and error handlers.
     */
    public static function install(): void
    {
        self::init();
        self::handleExceptions();
        self::handleErrors();
    }

    /**
     * Handle uncaught exceptions and log them.
     */
    public static function handleExceptions(): void
    {
        set_exception_handler(function (\Throwable $e) {
            self::fatal($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            exit(1);
        });
    }

    /**
     * Handle PHP errors and log them.
     */
    public static function handleErrors(): void
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            self::warn($errstr, ['errno' => $errno, 'file' => $errfile, 'line' => $errline]);
            return true;
        });
    }

    private static function loadConfiguration(): void
    {
        self::$logLevel = null;
        self::$logFile = null;
        self::$useErrorLog = false;
        self::$isWebhook = false;

        try {
            $config = Config::get('Log', ['level' => LogValidators::validateLevel(...)]);

            self::$logLevel = $config['level'] ?? 'OFF';
            self::$logFile = $config['file'] ?? null;

            if (self::$logFile === 'error_log()') {
                self::$useErrorLog = true;
            }

            if (empty(self::$logFile)) {
                self::$useErrorLog = true;
            }
        } catch (\Throwable $e) {
            // Fallback to env vars
        }

        if (self::$logLevel === null) {
            self::$logLevel = getenv('LOG_LEVEL') ?: 'OFF';
        }
        if (self::$logFile === null) {
            self::$logFile = getenv('LOG_FILE') ?: null;
        }
        if (self::$logFile === 'error_log()') {
            self::$useErrorLog = true;
        }
        if (empty(self::$logFile)) {
            self::$useErrorLog = true;
        } else {
            self::$isWebhook = stripos(self::$logFile, 'http') === 0;
            if (!self::$isWebhook) {
                LogValidators::validateLogFile(self::$logFile);
            }
        }
    }

    public static function trace(string $message, array $context = []): void
    {
        self::write('TRACE', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::write('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function notice(string $message, array $context = []): void
    {
        self::write('NOTICE', $message, $context);
    }

    public static function warn(string $message, array $context = []): void
    {
        self::write('WARN', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write('CRITICAL', $message, $context);
    }

    public static function alert(string $message, array $context = []): void
    {
        self::write('ALERT', $message, $context);
    }

    public static function emergency(string $message, array $context = []): void
    {
        self::write('EMERGENCY', $message, $context);
    }

    public static function fatal(string $message, array $context = []): void
    {
        self::write('FATAL', $message, $context);
    }


    /**
     * Write a log message if the current log level threshold allows it.
     * @param string $level The log level.
     * @param string $message The log message.
     * @param array $context The log context.
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        if (LogHelpers::getLevel(self::$logLevel) <= LogHelpers::getLevel($level)) {
            if (self::$isWebhook) {
                self::sendToWebhook($level, $message, $context);
                return;
            }
            $formatted = sprintf("%s [%s] %s %s", date('Y-m-d H:i:s'), strtoupper($level), $message, json_encode($context));
            self::$useErrorLog ? error_log($formatted) : self::writeToFile($formatted . PHP_EOL);
        }
    }

    /**
     * Writes the log content to the log file.
     * @param string $content The log content to write.
     * @throws LogException if writing to the file fails.
     */
    private static function writeToFile(string $content): void
    {
        try {
            if (self::$logFile === 'php://stdout') {
                echo $content;
            } else {
                if (file_put_contents(self::$logFile, $content, FILE_APPEND | LOCK_EX) === false) {
                    throw new \RuntimeException('Failed to write to log file.');
                }
            }
        } catch (\Throwable $e) {
            error_log('Error writing to log file: ' . $e->getMessage());
        }
    }

    /**
     * Sends the log payload to the configured webhook URL.
     * @param string $level The log level.
     * @param string $message The log message.
     * @param array $context The log context.
     * @throws LogException if the webhook request fails.
     */
    private static function sendToWebhook(string $level, string $message, array $context): void
    {
        $embed = [
            'title' => "<t:" . time() . ":R> $level",
            'description' => $message,
            'color' => LogHelpers::getColor($level),
            'timestamp' => date('c'),
            'footer' => ['text' => 'RPurinton\Log'],
            'fields' => array_map(fn($k, $v) => ['name' => $k, 'value' => $v, 'inline' => true], array_keys($context), $context),
        ];
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
}
