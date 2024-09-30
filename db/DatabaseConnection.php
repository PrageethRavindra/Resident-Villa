<?php
class DatabaseConnection {
    private $servername = "localhost:3305"; // Your server name (including port if needed)
    private $username = "root";             // Your MySQL username
    private $password = "123@prageeth";      // Your MySQL password
    private $dbname = "resident_villa";      // Your database name
    public $conn;

    // Constructor to automatically connect when the object is created
    public function __construct() {
        // Create connection
        $this->conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);

        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    // Close the connection
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
