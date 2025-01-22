<?php

namespace App\Config;

class Config
{
    private static $config = [];

    public static function loadEnv($envFile = __DIR__ . '/../../.env')
    {
        if (!file_exists($envFile)) {
            throw new \Exception("The .env file does not exist.");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignore comments (lines starting with # or ;)
            if (strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                continue;
            }

            // Parse key-value pairs
            list($key, $value) = explode('=', $line, 2);
            if (!empty($key)) {
                // Trim whitespace and remove extra quotes around values
                $key = trim($key);
                $value = trim($value);
                $value = preg_replace('/^"(.+)"$/', '$1', $value); // Remove quotes if present
                self::$config[$key] = $value;
            }
        }
    }

    // Get a configuration value by key
    public static function get($key)
    {
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        return null;
    }

    // Get all configuration values
    public static function getAll()
    {
        return self::$config;
    }
}
