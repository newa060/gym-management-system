<?php
/**
 * Database connection handler.
 * Provides a PDO connection using [DB_ACCESS_LAYER] with prepared statements.
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Establishes and retrieves a shared database connection instance using [DB_ACCESS_LAYER].
 *
 * @return PDO Active PDO database connection object.
 */
function getDbConnection() {
    static $database_connection = null;

    if ($database_connection === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $connection_options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $database_connection = new PDO($dsn, DB_USER, DB_PASS, $connection_options);
        } catch (PDOException $exception) {
            // Terminate script execution on connection failure to avoid exposing credentials or partial state
            die("Database connection failed: " . htmlspecialchars($exception->getMessage()));
        }
    }

    return $database_connection;
}
