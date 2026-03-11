<?php
// Database configuration for OAHMS

define('DB_HOST', 'localhost');
define('DB_NAME', 'u916560929_oldage_home');
define('DB_USER', 'u916560929_oldage_home');
define('DB_PASS', 'HgxvNstJ28C@JSZ');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get shared PDO database connection.
 *
 * @return PDO
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}

