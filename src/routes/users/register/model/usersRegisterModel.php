<?php

/**
 * This file contains the required functions to insert valid form data in the database.
 * @author Cyril Buchs
 * @version 1.7
 */

namespace Users;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

include __DIR__."/../../../../config/PHPMailer/src/Exception.php";
include __DIR__."/../../../../config/PHPMailer/src/SMTP.php";
include __DIR__."/../../../../config/PHPMailer/src/PHPMailer.php";
include __DIR__."/../../../../config/Config.php";

$activation_key = md5(microtime(TRUE) * 100000);

class usersRegisterModel
{
    protected PDO $pdo;
    protected array $form_data;

    public function __construct(PDO $pdo, array $valid_form_data)
    {
        $this->pdo = $pdo;
        $this->form_data = $valid_form_data;
    }

    /**
     * Method to check if mail given by the user already exists in the database. If so, it will return an error message.
     * @return bool|null
     */
    public function checkEmailExistence(): ?bool
    {
        $email = $this->form_data['email'];
        try {
            $req = $this->pdo->prepare('SELECT email FROM storagehost_hosting.user WHERE email = :email');
            $req->bindParam(':email', $email);
            $req->execute();

            // Check if request rows are higher than 0. If yes, it means that the email exists
            if ($req->rowCount() > 0) {
                return false;
            } else {
                // Email does not exist, so account creating is OK
                return true;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return null;
    }

    /**
     * Method used to insert data in the DB.
     * @return array|bool
     */
    public function createUser()
    {
        global $activation_key;
        try {
            $req = $this->pdo->prepare('INSERT INTO storagehost_hosting.user(last_name, first_name, email, address, zip, city, country, phone, password, activation, activation_key) VALUES (:lastname, :firstname, :email, :address, :zip, :city, :country, :phone, :password, :activation, :activation_key)');
            $req->execute(
                array(
                    ':lastname' => $this->form_data['lastname'],
                    ':firstname' => $this->form_data['firstname'],
                    ':email' => $this->form_data['email'],
                    ':address' => $this->form_data['address'],
                    ':zip' => $this->form_data['zip'],
                    ':city' => $this->form_data['city'],
                    ':country' => $this->form_data['country'],
                    ':phone' => $this->form_data['phone'],
                    ':password' => $this->form_data['password'],

                    // Set a default account status at 0, who means account disabled
                    ':activation' => 0,
                    ':activation_key' => $activation_key
                )
            );
            if ($req) {
                $this->sendMail($activation_key);
                $payload = [];
                array_push($payload, array(
                    "status" => "success",
                    "data" => array(
                        'user_id' => $this->pdo->lastInsertId(),
                        'lastname' => $this->form_data['lastname'],
                        'firstname' => $this->form_data['firstname'],
                        'email' => $this->form_data['email'],
                        'address' => $this->form_data['address'],
                        'zip' => $this->form_data['zip'],
                        'city' => $this->form_data['city'],
                        'country' => $this->form_data['country'],
                        'phone' => $this->form_data['phone']
                    )
                ));

                return $payload;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e->getMessage());
        }
        return null;
    }

    /**
     * Method that will send an email to user when the insert pass is successful.
     * @param string $activation_key
     */
    private function sendMail(string $activation_key)
    {
        // Define some local vars
        $email = $this->form_data['email'];

        // Encode the email and the key as URL
        $encoded_email = urlencode($email);
        $encoded_key = urlencode($activation_key);

        // Set subject and body
        $subject = "STORAGEHOST - création de votre compte";
        $message = "Bonjour,<br/>
        Nous vous confirmons la réception de votre enregistrement sur le site Web de STORAGEHOST - Hosting Services.<br/><br/>
        <b>Votre compte requiert une activation.</b><br/><br/>
	    Merci de bien vouloir cliquer sur ce lien ou de le copier/coller dans un navigateur afin de l'activer :
	    <br/><br/>
        http://localhost/api/user/activation/email=" . $encoded_email . "&token=" . $encoded_key . "
        <br/>
        <br/>       
        ---------------<br/>
        Cet e-mail est généré automatiquement, merci de ne pas y répondre.<br/>
        En cas de problème, merci de contacter l'administrateur en créant un ticket sur https://helpdesk.storagehost.ch.";

        // Create new PHPMailer
        $mail = new PHPMailer(true);

        try {
            include "../config/includes/mail_settings.php";

            // Define sender and recipients settings
            $mail->setFrom("notifications.storagehost@gmail.com", 'STORAGEHOST - Hosting Services');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
        }
    }

}