<?php

namespace core\Sms;

interface ISms{
    public function sendSms($number, string $text);
}