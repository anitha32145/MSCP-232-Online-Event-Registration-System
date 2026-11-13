<?php
// config/db.php
// ------------------------------------------------------------
// Connects PHP to the MySQL database "event_registration" using PDO.
// Every page that needs data calls db() to get the connection.
//
// XAMPP's default MySQL login is user "root" with an empty password.
// If you ever set a password for root in XAMPP, put it in DB_PASS.

define('DB_HOST', 'localhost');
define('DB_NAME', 'event_registration');
define('DB_USER', 'root');
define('DB_PASS', '');

// Returns the database connection. It is created only once per page request.
// Pages that use it must catch PDOException and show a friendly message.
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // report problems as exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows come back as name => value
            PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
        ]);
    }

    return $pdo;
}