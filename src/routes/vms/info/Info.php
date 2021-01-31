<?php

namespace Vms;

use PDO;

require __DIR__."/model/vmsInfoModel.php";

class Info
{

    private int $id;
    private PDO $pdo;

    public function __construct(int $id, PDO $pdo)
    {
        $this->id = $id;
        $this->pdo = $pdo;
    }

    public function listVms(): array
    {
        return (new vmsInfoModel($this->id, $this->pdo))->listVms();
    }
}