<?php


class userInfoModel
{
    /**public function listContainers(int $id, PDO $pdo)
     * {
     * // Search for containers using the user ID
     * $req = $pdo->prepare('SELECT * FROM storagehost_hosting.containers WHERE user_id = :id');
     * $req->bindParam(':id', $id);
     * $req->execute();
     *
     * return $req->fetchAll(PDO::FETCH_ASSOC);
     * }
     * @param PDO $pdo
     * @return array
     */

    public function listUsers(PDO $pdo): array
    {
        // Get all users
        $req = $pdo->prepare('SELECT id, last_name, first_name, email, address, zip, city, phone, activation FROM storagehost_hosting.user');
        $req->execute();

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listUserInfo(int $id, PDO $pdo)
    {
        // Get user by ID
        $req = $pdo->prepare('SELECT id, last_name, first_name, email, address, zip, city, phone, activation FROM storagehost_hosting.user WHERE user.id = :id');
        $req->bindParam(':id', $id);
        $req->execute();

        $data = $req->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return $data;
        } else {
            return "";
        }
    }

    public function listVms(int $id, PDO $pdo): array
    {
        // Search for containers using the user ID
        $req = $pdo->prepare('SELECT hostname, ip, power_status, os, instance_type, order_id FROM storagehost_hosting.vm WHERE user_id = :id');
        $req->bindParam(':id', $id);
        $req->execute();

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }

}