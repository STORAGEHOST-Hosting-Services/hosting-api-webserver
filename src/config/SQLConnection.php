<?php


class SQLConnection
{
    /**
     * PDO instance
     */
    private $pdo;
    private $username;
    private $password;
    private $host;
    private $dbname;

    /**
     * return in instance of the PDO object that connects to the SQLite database
     * @return PDO
     */
    public function connect()
    {
        if ($this->pdo == null) {
            $this->getCrendentials();
            $this->pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8mb4", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $this->pdo;
    }

    /**
     * Get data from the INI configuration file and assign them to local vars
     */

    private function getCrendentials()
    {
        $db = parse_ini_file('db.ini');
        $this->username = $db['user'];
        $this->password = $db['password'];
        $this->host = $db['host'];
        $this->dbname = $db['dbname'];
    }
}