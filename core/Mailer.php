<?php
namespace core;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
/**
 * Класс реализует оповещение на сайте
 */
class Mailer{
	/**
	 * Отправляет сообщение
	 * @param  [array] $params массив параметров (['emails'], subject, body)
	 * @return [mixed] true в случае удачной отправки либо сообщение об ошибке  
	 */
	public static function send($params, $attachments = []){
		if (!Config::$isSendEmails) return true;
        
        $config = [
            'email' => Setting::get('email_settings', 'email'),
            'username' => Setting::get('email_settings', 'username'),
            'password' => Setting::get('email_settings', 'password'),
            'host' => Setting::get('email_settings', 'host'),
        ];
        
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->isHTML(true);  
		$mail->SMTPDebug = 0;
		$mail->Host = $config['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $config['username'];
		$mail->Password = $config['password'];
		$mail->setFrom($config['email'], 'Tahos.ru');
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port = 465;
		if (is_array($params['emails'])){
			foreach($params['emails'] as $email) $mail->addAddress($email);
		} else $mail->addAddress($params['emails']);
		$mail->addReplyTo($config['email'], 'Tahos.ru');
		$mail->Body = $params['body'];
		$mail->Subject = $params['subject'];
		$mail->CharSet = 'UTF-8';
		if (!empty($attachments)){
			foreach($attachments as $a) $mail->addAttachment($a);
		}
		if ($mail->send()) return true;
		else return $mail->ErrorInfo;
	}
}
