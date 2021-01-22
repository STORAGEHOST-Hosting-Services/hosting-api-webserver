<?php

namespace Vms;

use PDO;

require __DIR__."/model/vmsDeleteModel.php";

class Delete
{
    private PDO $pdo;
    private int $id;
    private array $user_data;

    /**
     * delete constructor.
     * @param PDO $pdo
     * @param int $id
     */
    public function __construct(PDO $pdo, int $id, array $user_data)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->user_data = $user_data;
    }

    public function deleteVm()
    {
        return (new vmsDeleteModel($this->pdo, $this->id, $this->user_data['email']))->getVm();
    }
}