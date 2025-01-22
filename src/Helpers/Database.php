<?php

namespace App\Helpers;

use PDO;
use PDOException;
use App\Config\Config;
class Database
{
    private static $connection;

    public static function getConnection()
    {
        if (!self::$connection) {
            try {
                // Load environment variables from the .env file (only once)
                if (empty(self::$connection)) {
                    Config::loadEnv(); // Load .env file to set environment variables
                }
                // Retrieve the database configuration from the environment variables
                $dbHost = Config::get('DB_HOST');
                $dbName = Config::get('DB_NAME');
                $dbUser = Config::get('DB_USER');
                $dbPassword = Config::get('DB_PASSWORD');

                $dsn = "mysql:host=$dbHost;dbname=$dbName";
                self::$connection = new PDO(
                    $dsn,
                    $dbUser,
                    $dbPassword
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$connection;
    }
}
