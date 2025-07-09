<?php

if (!class_exists('USER')) {
    class USER {
        private $host = 'localhost';
        private $dbname = 'sanda_chat';
        private $user = 'root';
        private $pass = '';
    
        public function getConnection() {
            try {
                $conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $conn;
            } catch(PDOException $e) {
                die("<h1>Server Down!!</h1><p>" . $e->getMessage() . "</p>");
            }
        }
    }
}

?>
