<?php
namespace core;

use Firebase\JWT\JWT;
require_once ('vendor/autoload.php');

class Authorize{
    private static $key = 't805U9skyWyM';
    public static function getJWT($fields){
        $payload = array(
            "iss" => $fields['user_id'],
            "aud" => $fields['login'],
            "iat" => 1356999524,
            "nbf" => 1357000000
        );
        $jwt = JWT::encode($payload, self::$key);
        return $jwt;
    }
    
    public static function getJWTInfo($jwt): array
    {
        try{
            $decoded = JWT::decode($jwt, self::$key, array('HS256'));
        }
        catch(\UnexpectedValueException $e){
            return [];
        }
        return [
            'user_id' => $decoded->iss,
            'login' => $decoded->aud
        ];
    }
}