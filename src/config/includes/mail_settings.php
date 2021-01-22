<?php

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