<?php
namespace core;

class YandexCaptcha{
    private const SERVER_KEY = 'ysc2_qfL1HRHage6wmGOtaKOt13P1vsXIgas7R1YKM2fL86589d0d';
    public const SITE_KEY = 'ysc1_qfL1HRHage6wmGOtaKOt1odQNtNZSU914mq594bVd6d38268';
    public static bool $useCaptcha = false;

    public function __construct(){
        self::$useCaptcha = true;
    }

    /**
     * @throws \Exception
     */
    public function check($token): bool
    {
        $args = http_build_query([
            "secret" => self::SERVER_KEY,
            "token" => $token,
            "ip" => $_SERVER['REMOTE_ADDR'],
        ]);
        $response = Provider::getCurlUrlData("https://smartcaptcha.yandexcloud.net/validate?$args");
        $result = json_decode($response);

        if ($result->status == 'failed'){
            throw new \Exception("Allow access due to an error: message={$result->message}\n");
        }
        return $result->status === "ok";
    }

    public static function show($name = ''){
        self::$useCaptcha = true;
        echo '
            <div data-key="'.$name.'" class="yandex-captcha"></div>
        ';
    }

}