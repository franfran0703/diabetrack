<?php

class Database {
    private $conn;

    public function connect() {
        $this->conn = null;

        $env = parse_ini_file(__DIR__ . '/../.env');

        $host    = $env['DB_HOST'] ?? 'localhost';
        $db_name = $env['DB_NAME'] ?? 'diabetrack';
        $username = $env['DB_USER'] ?? 'diabetracker';
        $password = $env['DB_PASS'] ?? 'Diabetrack01';

        try {
            $this->conn = new PDO(
                'mysql:host=' . $host . ';dbname=' . $db_name . ';charset=utf8mb4',
                $username,
                $password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            die('Database unavailable. Please check your configuration.');
        }

        return $this->conn;
    }
}