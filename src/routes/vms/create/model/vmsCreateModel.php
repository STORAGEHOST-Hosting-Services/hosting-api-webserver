<?php

namespace Vms;

use Config;
use PDO;

class vmsCreateModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createVm(array $vm_data)
    {
        // Declare variables
        $hostname = $this->getName($vm_data['os']);
        $ip = $this->getIp();

        $req = $this->pdo->prepare('INSERT INTO storagehost_hosting.vm(hostname, ip, power_status, os, instance_type, order_id) VALUES (:hostname, :ip, :power_status, :os, :instance_type, :order_id)');
        $req->execute(array(
            ':hostname' => $hostname,
            ':ip' => $ip,
            ':power_status' => false,
            ':os' => $vm_data['os'],
            ':instance_type' => $vm_data['instance_type'],
            ':order_id' => $vm_data['order_id']
        ));

        if ($req) {
            if ($vm_data['os'] == 'winsrv') {
                $hostname_status = $this->pdo->prepare('INSERT INTO storagehost_hosting.windows_hostname(name) VALUES (:name)');
                $hostname_status->bindParam(':name', $hostname);
                $hostname_status->execute();
            } else {
                $hostname_status = $this->pdo->prepare('INSERT INTO storagehost_hosting.linux_hostname(name) VALUES (:name)');
                $hostname_status->bindParam(':name', $hostname);
                $hostname_status->execute();
            }

            // Get the ID of the inserted instance
            $last_id = $this->pdo->lastInsertId();

            if ($vm_data['os'] == 'winsrv') {
                $ip_data = array(
                    'linux' => null,
                    'windows' => $last_id
                );
            } else {
                $ip_data = array(
                    'linux' => $last_id,
                    'windows' => null
                );
            }

            if ($hostname_status) {
                $ip_status = $this->pdo->prepare('INSERT INTO storagehost_hosting.ip(ip, linux_instance_id, windows_instance_id) VALUES (:ip, :linux_instance_id, :windows_instance_id)');
                $ip_status->execute(array(
                    ':ip' => $ip,
                    ':linux_instance_id' => 1,
                    ':windows_instance_id' => 1
                ));
                if ($ip_status) {
                    return array(
                        'status' => 'success',
                        'data' => $this->fetchVmData(),
                        'date' => time()
                    );
                } else {
                    return array(
                        'status' => 'error',
                        'message' => 'vm_creation_failed',
                        'step' => 1,
                        'date' => time()
                    );
                }
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'vm_creation_failed',
                    'step' => 3,
                    'date' => time()
                );
            }
        } else {
            // An error occurred
            return array(
                'status' => 'error',
                'message' => 'vm_creation_failed',
                'step' => 2,
                'date' => time()
            );
        }
    }

    private
    function getName(string $os): string
    {
        if ($os == "debian10" || $os == "centos8" || $os == "ubuntu2004") {
            // Linux
            $req = $this->pdo->prepare('SELECT * FROM linux_hostname ORDER BY id DESC LIMIT 1');
            $req->execute();

            $last_linux_hostname = $req->fetch(PDO::FETCH_ASSOC);

            // Increment the instance name by adding 1 to the number at the end
            $hostname = "";

            $id = (int)substr($last_linux_hostname['name'], 9);
            $id = $id + 1;

            if ($id < 10) {
                $hostname = "SLVHOSSOR0" . $id;
            } else {
                $hostname = "SLVHOSSOR" . $id;
            }

            return $hostname;
        } else {
            // Windows
            $req1 = $this->pdo->prepare('SELECT * FROM windows_hostname ORDER BY id DESC LIMIT 1');
            $req1->execute();

            $last_windows_hostname = $req1->fetch(PDO::FETCH_ASSOC);

            // Increment the instance name by adding 1 to the number at the end
            $hostname = "";

            $id = (int)$last_windows_hostname['id'];
            $id = $id + 1;

            if ($id < 10) {
                $hostname = "SWVHOSSOR0" . $id;
            } else {
                $hostname = "SWVHOSSOR" . $id;
            }

            return $hostname;
        }

    }

    private
    function getIp(): string
    {
        $req = $this->pdo->prepare('SELECT * FROM ip ORDER BY id DESC LIMIT 1');
        $req->execute();

        $ip = $req->fetch(PDO::FETCH_ASSOC);
        $last_ip = (int)substr($ip['ip'], 9);
        $last_ip = $last_ip + 1;

        // Add the newly generated IP end to the core
        return Config::SUBNET . $last_ip;

    }

    private function fetchVmData()
    {

    }
}