<?php

namespace Vms;

require "model/vmsInfoModel.php";

class Info
{

    private $id;
    private $pdo;

    public function __construct(int $id, \PDO $pdo)
    {
        $this->id = $id;
        $this->pdo = $pdo;
    }

    public function listVms()
    {
        return (new vmsInfoModel())->listVms($this->id, $this->pdo);
    }
}