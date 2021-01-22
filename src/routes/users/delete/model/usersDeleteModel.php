<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class usersDeleteModel
{
    private PDO $pdo;
    private int $id;

    public function __construct(PDO $pdo, int $id)
    {
        $this->pdo = $pdo;
        $this->id = $id;
    }

    public function getUser()
    {
        try {
            $req = $this->pdo->prepare("SELECT * FROM storagehost_hosting.user WHERE id = :id");
            $req->bindParam(':id', $this->id);
            $req->execute();

            if ($req->rowCount() > 0) {
                $this->sendMail($req->fetch(PDO::FETCH_ASSOC));
                return $this->deleteUser();
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'user_not_found',
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
        $subject = "STORAGEHOST - suppression de compte";
        $message = "Bonjour,<br/>
        Le compte ayant pour ID $this->id a été supprimé du système. Voici ses informations :<br/><br/>
        Prénom : " . $data['last_name'] . ",<br/>
        Nom : " . $data['first_name'] . ",<br/>
        Email : " . $data['email'] . ",<br/>
        Addresse : " . $data['address'] . ",<br/>
        ZIP : " . $data['zip'] . ",<br/>
        Ville : " . $data['city'] . ",<br/>
        Téléphone : " . $data['phone'] . ",<br/>
	    <br/>
        ---------------<br/>
        Cet e-mail est généré automatiquement, merci de ne pas y répondre.<br/>
        En cas de problème, merci de contacter l'administrateur en créant un ticket sur https://helpdesk.storagehost.ch.";

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
            $mail->addAddress('admin@storagehost.ch');

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
     * Method used to delete a user from the DB.
     * @return array|boolean
     */
    private function deleteUser()
    {
        try {
            $req = $this->pdo->prepare("DELETE FROM storagehost_hosting.user WHERE id = :id");
            $req->bindParam(':id', $this->id);
            $req->execute();

            if ($req) {
                // Check if the user exists in the DB
                return array(
                    'status' => 'success',
                    'message' => 'user_deleted',
                    'date' => time()
                );
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'error_while_deleting_user',
                    'date' => time()
                );
            }
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}