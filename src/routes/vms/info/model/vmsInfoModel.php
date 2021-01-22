<?php

namespace Vms;

use PDO;

class vmsInfoModel
{
    public function listVms(int $id, PDO $pdo): array
    {
        $req = $pdo->prepare('SELECT * FROM storagehost_hosting.vm');
        $req->execute();

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}