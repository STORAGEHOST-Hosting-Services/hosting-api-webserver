<?php

namespace Containers;

require __DIR__ . "/model/containersInfoModel.php";

class Info
{

    private $id;
    private $pdo;

    public function __construct(int $id, \PDO $pdo)
    {
        $this->id = $id;
        $this->pdo = $pdo;
    }

    public function listContainers()
    {
        return (new \containersInfoModel())->listContainers($this->id, $this->pdo);
    }
}