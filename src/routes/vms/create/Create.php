<?php

namespace Vms;

use PDO;

include __DIR__."/model/vmsCreateModel.php";

class Create
{
    private array $vm_data;
    private array $user_data;
    private PDO $pdo;
    private array $valid_vm_data;
    private string $error_vm_data;

    public function __construct(array $vm_data, array $user_data, PDO $pdo)
    {
        $this->vm_data = $vm_data;
        $this->user_data = $user_data;
        $this->pdo = $pdo;
        $this->error_vm_data = "";
    }

    public function validateData(): array
    {
        // Check if all data are present
        if ($this->vm_data && $this->vm_data['username'] && $this->vm_data['password'] && $this->vm_data['instance_type'] && $this->vm_data['os'] && $this->vm_data['order_id'] && $this->user_data) {

            if (filter_var($this->vm_data['username'], FILTER_SANITIZE_STRING)) {
                $this->valid_vm_data['username'] = $this->vm_data['username'];
            } else {
                $this->error_vm_data = "bad_username";
            }

            // Check if password follows AD security requirements
            $uppercase = preg_match('@[A-Z]@', $this->vm_data['password']);
            $lowercase = preg_match('@[a-z]@', $this->vm_data['password']);
            $number = preg_match('@[0-9]@', $this->vm_data['password']);

            if ($uppercase && $lowercase && $number && strlen($this->vm_data['password']) >= 8) {
                // Add password in the valid array
                $this->valid_vm_data['password'] = $this->vm_data['password'];
            } else {
                $this->error_vm_data = "bad_password";
            }

            // Check if the provided values match instance type
            $instance_type = $this->vm_data['instance_type'];
            if ($instance_type == "s1s" || $instance_type == "s1m" || $instance_type == "s1l" || $instance_type == "s2s" || $instance_type == "s2m" || $instance_type == "s2l") {
                $this->valid_vm_data['instance_type'] = $instance_type;
            } else {
                $this->error_vm_data = "bad_instance_type";
            }

            // Check the OS
            $os = $this->vm_data['os'];
            if ($os == "debian10" || $os == "centos8" || $os == "ubuntu2004" || $os == "winsrv") {
                $this->valid_vm_data['os'] = $os;
            } else {
                $this->error_vm_data = "bad_os";
            }

            // Add order ID
            $this->valid_vm_data['order_id'] = $this->vm_data['order_id'];

            if (empty($this->error_vm_data)) {
                //return $this->error_vm_data;
                var_dump($this->createVm());
            } else {
                //var_dump($this->valid_vm_data);
                return array(
                    'status' => 'error',
                    'message' => 'couille',
                    'date' => time()
                );
            }
        } else {
            return array(
                'status' => 'error',
                'message' => 'missing_requested_parameter',
                'date' => time()
            );
        }
    }

    private function createVm()
    {
        return (new vmsCreateModel($this->pdo))->createVm($this->valid_vm_data);
    }

    private function getOrderExistence()
    {

    }
}