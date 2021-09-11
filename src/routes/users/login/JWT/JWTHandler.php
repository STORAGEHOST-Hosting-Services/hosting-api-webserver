<?php


namespace Users;

use DomainException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use InvalidArgumentException;
use UnexpectedValueException;

class JWTHandler
{
    protected string $jwt_secrect;
    protected $token;
    protected int $issuedAt;
    protected int $expire;
    protected $jwt;

    public function __construct()
    {
        // set your default time-zone
        date_default_timezone_set('Europe/Zurich');
        $this->issuedAt = time();

        // Token Validity (3600 second = 1hr)
        $this->expire = $this->issuedAt + 3600;

        // Set your secret or signature
        $this->jwt_secrect = "StoragehostSecretSignature";
    }

    // ENCODING THE TOKEN
    public function _jwt_encode_data($iss, $data): string
    {

        $this->token = array(
            //Adding the identifier to the token (who issue the token)
            "iss" => $iss,
            "aud" => $iss,
            // Adding the current timestamp to the token, for identifying that when the token was issued.
            "iat" => $this->issuedAt,
            // Token expiration
            "exp" => $this->expire,
            // Payload
            "data" => $data
        );

        $this->jwt = JWT::encode($this->token, $this->jwt_secrect);
        return $this->jwt;
    }

    public function _jwt_decode_data($jwt_token): array
    {
        try {
            $decode = JWT::decode($jwt_token, $this->jwt_secrect, array('HS256'));
            return [
                "auth" => 1,
                "data" => $decode->data
            ];
        } catch (ExpiredException | UnexpectedValueException | InvalidArgumentException | DomainException $e) {
            return $this->_errMsg($e->getMessage());
        }

    }

    //DECODING THE TOKEN

    protected function _errMsg($msg): array
    {
        return [
            "auth" => 0,
            "message" => $msg
        ];
    }
}