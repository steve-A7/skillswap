<?php

class DatabaseConnection {
    private $host;
    private $username;
    private $password;
    private $database;
    private $connection;

    public function __construct(
        $host = "127.0.0.1",
        $username = "root",
        $password = "",
        $database = "skillswap_db"
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->connect();
    }

    private function connect() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->connection;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
