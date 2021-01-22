<?php

namespace Users;

use userInfoModel;

require __DIR__."/model/userInfoModel.php";

class Info
{
    private $id;
    private $pdo;

    public function __construct(int $id, \PDO $pdo)
    {
        $this->id = $id;
        $this->pdo = $pdo;
    }

    public function listUsers(): array
    {
        return (new userInfoModel())->listUsers($this->pdo);
    }

    public function listUserInfo()
    {
        return (new userInfoModel())->listUserInfo($this->id, $this->pdo);
    }

    /**public function listContainers()
     * {
     * return (new \userInfoModel())->listContainers($this->id, $this->pdo);
     * }*/

    public function listVms(): array
    {
        return (new userInfoModel())->listVms($this->id, $this->pdo);
    }
}