<?php

namespace Vms;

use Config;
use PDO;
use PHPMailer\PHPMailer\Exception;

class vmsCreateModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function createVm(array $vm_data, array $user_data)
    {
        var_dump($user_data);
        // Declare variables
        $hostname = $this->getName($vm_data['os']);
        $ip = $this->getIp();

        $req = $this->pdo->prepare('INSERT INTO storagehost_hosting.vm(hostname, ip, power_status, os, instance_type, order_id, user_id) VALUES (:hostname, :ip, :power_status, :os, :instance_type, :order_id, :user_id)');
        $req->execute(array(
            ':hostname' => $hostname,
            ':ip' => $ip,
            ':power_status' => false,
            ':os' => $vm_data['os'],
            ':instance_type' => $vm_data['instance_type'],
            ':order_id' => $vm_data['order_id'],
            ':user_id' => $user_data['data']['id']
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

            // Get the ID of the inserted instance (VM table)
            $last_id = $this->pdo->lastInsertId();
            //var_dump($last_id);

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
                    ':linux_instance_id' => $ip_data['linux'],
                    ':windows_instance_id' => $ip_data['windows']
                ));
                if ($ip_status) {
                    // VM insertion is complete in the database, add the data in a text file for PowerShell service
                    try {
                        $this->addVmData($hostname, $ip, $vm_data['instance_type'], $user_data['data']['id']);

                        return array(
                            'status' => 'success',
                            'data' => array(
                                'hostname' => $hostname,
                                'ip' => $ip,
                                'os' => $vm_data['os'],
                                'instance_type' => $vm_data['instance_type'],
                                'order_id' => $vm_data['order_id'],
                            ),
                            'date' => time()
                        );
                    } catch (Exception $exception) {
                        return array(
                            'status' => 'error',
                            'message' => $exception->getMessage(),
                            'step' => 4,
                            'date' => time()
                        );
                    }
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

    private function fetchVmData(int $id)
    {
        try {
            $req = $this->pdo->prepare('SELECT * FROM storagehost_hosting.vm WHERE id = :id');
            $req->bindParam(':id', $id);
            $req->execute();
            return ($req->fetchAll());
        } catch (\PDOException $exception) {
            return array(
                'status' => 'error',
                'message' => $exception->getMessage(),
                'date' => time()
            );
        }
    }

    /**
     * Method used to add VM data in the text file under /src/routes/Service/VmInteraction/create/vm_creation.txt
     * @param string $hostname
     * @param string $ip
     * @param string $instance_type
     * @param int $user_id
     * @throws \Exception
     */
    private function addVmData(string $hostname, string $ip, string $instance_type, int $user_id)
    {
        // Set instance type
        $vcpu = 0;
        $ram = 0;
        $storage = 0;

        switch ($instance_type) {
            case "s1s":
                $vcpu = 1;
                $ram = 1024;
                $storage = 25000;
                break;
            case "s1m":
                $vcpu = 2;
                $ram = 2048;
                $storage = 50000;
                break;
            case "s1l":
                $vcpu = 2;
                $ram = 4096;
                $storage = 75000;
                break;
            case "s2s":
                $vcpu = 4;
                $ram = 8096;
                $storage = 150000;
                break;
            case "s2m":
                $vcpu = 4;
                $ram = 16384;
                $storage = 200000;
                break;
            case "s2l":
                $vcpu = 6;
                $ram = 32768;
                $storage = 300000;
                break;
        }

        // Open text file for writing
        $handle = fopen(__DIR__.'/../../../Service/VmInteraction/create/vm_creation.txt', 'a+');
        //$handle = fopen('vm_creation.txt', 'a+');
        if (flock($handle, LOCK_EX)) {
            fwrite($handle, $hostname . ",");
            fwrite($handle, $ip . ",");
            fwrite($handle, $vcpu . ",");
            fwrite($handle, $ram . ",");
            $req = fwrite($handle, $storage . PHP_EOL);
            flock($handle, LOCK_UN);
        } else {
            throw new \Exception('Cannot lock text file!');
        }

        if ($req) {
            fclose($handle);
            return true;
        } else {
            throw new \Exception('Failed to write data in the text file!');
        }
    }
}