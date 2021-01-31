<?php

namespace Vms;

use PDO;
use PDOException;

class vmsInfoModel
{
    private int $order_id;
    private PDO $pdo;

    public function __construct(int $id, PDO $pdo)
    {
        $this->order_id = $id;
        $this->pdo = $pdo;
    }

    public function listVms(): array
    {
        try {
            $req = $this->pdo->prepare('SELECT hostname, ip, power_status, os, instance_type, order_id FROM storagehost_hosting.vm WHERE user_id = :user_id');
            $req->bindParam(':order_id', $this->order_id);
            $req->execute();

            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            return array(
                'status' => 'error',
                'message' => $exception->getMessage(),
                'date' => time()
            );
        }
    }
}