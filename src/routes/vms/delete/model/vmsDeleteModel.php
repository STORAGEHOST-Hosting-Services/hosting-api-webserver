<?php

namespace Vms;

use Config;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class vmsDeleteModel
{
    private PDO $pdo;
    private int $id;
    private string $email;

    public function __construct(PDO $pdo, int $id, string $email)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->email = $email;
    }

    public function getVm()
    {
        try {
            $req = $this->pdo->prepare("SELECT * FROM storagehost_hosting.vm WHERE id = :id");
            $req->bindParam(':id', $this->id);
            $req->execute();

            if ($req->rowCount() > 0) {
                $this->sendMail($req->fetch(PDO::FETCH_ASSOC));
                return $this->deleteVm();
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'vm_not_found',
                    'date' => time()
                );
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Method that will send an email to user when the insert pass is successful.
     * @param array $data
     */
    private function sendMail(array $data)
    {
        // Set subject and body
        $subject = "STORAGEHOST - suppression de service";
        $message = "Bonjour,<br/>
        Vous avez demandé la suppression du/d'un service que vous avez chez STORAGEHOST - Hosting Services.<br/>
        Voici les informations de la machine dont vous avez demandé la suppression :<br/><br/>
        Nom d'hôte : " . $data['hostname'] . ",<br/>
        IP : " . $data['ip'] . ",<br/>
        OS : " . $data['os'] . ",<br/>
        Type d'instance : " . $data['instance_type'] . ",<br/>
        N° de commande : " . $data['order_id'] . "<br/>
        La suppression du service peut prendre jusqu'à une heure. En revanche, sa disparition sur le panel est instantanée.
	    <br/>
	    <strong>Si vous n'avez pas demandé la suppression de ce service ou si une erreur est constatée, merci d'appeler immédiatement le +41 77 506 19 14.</strong>
        ---------------<br/>
        Cet e-mail est généré automatiquement, merci de ne pas y répondre.<br/>";

        // Create new PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Define server settings
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = Config::MAIL_SERVER;
            $mail->SMTPAuth = true;
            $mail->Username = Config::MAIL_USERNAME;
            $mail->Password = Config::MAIL_PASSWORD;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Define sender and recipients settings
            $mail->setFrom("notifications.storagehost@gmail.com", 'STORAGEHOST - Hosting Services');
            $mail->addAddress($this->email);
            $mail->addCC('admin@storagehost.ch');

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
        }
    }

    /**
     * Method used to delete a VM from the DB.
     * @return array|boolean
     */
    private function deleteVm()
    {
        try {
            $req = $this->pdo->prepare("DELETE FROM storagehost_hosting.vm WHERE id = :id");
            $req->bindParam(':id', $this->id);
            $req->execute();

            if ($req) {
                // Delete the VM from the hypervisor
                return $this->deleteVmFromHypervisor();
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'error_while_deleting_vm',
                    'date' => time()
                );
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    private function deleteVmFromHypervisor(): array
    {
        // Add the VM data in the text file
        $handle = fopen('../routes/Service/VmInteraction/delete/vm_deletion.txt', 'a+');
        if (flock($handle, LOCK_EX)) {

        }

        return array(
            'status' => 'success',
            'message' => 'vm_deleted',
            'date' => time()
        );
    }
}