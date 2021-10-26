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
	public static $config = [
		'email' => 'test@tahos.ru',
		'username' => 'test@tahos.ru',
		'password' => 'Anton12345',
		'host' => 'smtp.mail.ru',
	];
	/**
	 * Отправляет сообщение
	 * @param  [array] $params массив параметров (['emails'], subject, body)
	 * @return [mixed] true в случае удачной отправки либо сообщение об ошибке  
	 */
	public static function send($params, $attachments = []){
		if (!Config::$isSendEmails) return true;
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->isHTML(true);  
		$mail->SMTPDebug = 0;
		$mail->Host = self::$config['host'];
		$mail->SMTPAuth = true;
		$mail->Username = self::$config['username'];
		$mail->Password = self::$config['password'];
		$mail->setFrom(self::$config['email'], 'Tahos.ru');     
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port = 465;
		if (is_array($params['emails'])){
			foreach($params['emails'] as $email) $mail->addAddress($email);
		} else $mail->addAddress($params['emails']);
		$mail->addReplyTo(self::$config['email'], 'Tahos.ru');
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
