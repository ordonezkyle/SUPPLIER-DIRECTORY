<?php
// config.php - database connection for XAMPP environment
// Adjust credentials if you set a root password in phpMyAdmin
// start session early for auth
session_start();

// simple administrator credentials - change as needed
// the password is stored as a hash; to create your own, run:
// php -r "echo password_hash('yourpass', PASSWORD_DEFAULT);"
$admin_user = 'admin';
$admin_pass_hash = '$2y$10$ms0ce5iiN.vt.xH6KjWTFeEHsdwUznbD5IEhw3WfTJKWbULZMPLG2';

$host = '127.0.0.1';
$db   = 'peza_scms';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
