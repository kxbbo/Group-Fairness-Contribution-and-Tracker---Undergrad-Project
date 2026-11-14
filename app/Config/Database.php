<?php

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $host = getenv('FAIRSHARE_DB_HOST') ?: '127.0.0.1';
            $port = getenv('FAIRSHARE_DB_PORT') ?: '3306';
            $database = getenv('FAIRSHARE_DB_NAME') ?: 'fairshare_db';
            $username = getenv('FAIRSHARE_DB_USER') ?: 'root';
            $password = getenv('FAIRSHARE_DB_PASSWORD');
            $password = $password === false ? '' : $password;

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $host,
                $port,
                $database
            );

            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }
}
