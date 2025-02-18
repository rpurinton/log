# RPurinton Log Library

## Overview

The RPurinton Log Library is a robust logging solution designed to log messages to a file, a webhook, or PHP's `error_log`. It is highly configurable, allowing users to set log levels and destinations through environment variables or a configuration file.

## Features

- **Log Levels**: Supports multiple log levels including TRACE, DEBUG, INFO, NOTICE, WARN, ERROR, CRITICAL, ALERT, EMERGENCY, FATAL, and OFF.
- **Flexible Configuration**: Configuration can be loaded from a configuration library or environment variables.
- **Multiple Destinations**: Logs can be directed to a file, a webhook, or PHP's `error_log`.
- **Automatic Initialization**: The library initializes itself upon inclusion, ensuring that logging is ready to use immediately.

## Configuration

### Environment Variables

- `LOG_LEVEL`: Sets the threshold for logging. Messages below this level will not be logged.
- `LOG_FILE`: Specifies the destination for log messages. This can be a file path or a webhook URL.

### Configuration File

The library attempts to load configuration using the `Config::get()` method. If this fails, it falls back to environment variables.

## Usage

### Initialization

The library automatically initializes itself when included. This process involves loading and validating the configuration.

### Logging Messages

The library provides static methods for logging messages at various levels:

- `Log::trace($message, $context = [])`
- `Log::debug($message, $context = [])`
- `Log::info($message, $context = [])`
- `Log::notice($message, $context = [])`
- `Log::warn($message, $context = [])`
- `Log::error($message, $context = [])`
- `Log::critical($message, $context = [])`
- `Log::alert($message, $context = [])`
- `Log::emergency($message, $context = [])`
- `Log::fatal($message, $context = [])`

Each method accepts a message string and an optional context array.

## Error Handling

The library throws `LogException` for configuration errors or failures in writing logs. Ensure to handle these exceptions in your application to maintain robustness.

## Webhook Integration

If the log destination is a URL starting with "http", the library treats it as a webhook and sends log messages as POST requests with JSON payloads.

## File Logging

When logging to a file, ensure the directory exists and is writable. The library will throw an exception if it cannot write to the specified file.

## License

This library is open-source and available under the MIT License.