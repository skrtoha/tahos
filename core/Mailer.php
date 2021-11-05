<?php
namespace core;
use PHPMailer\PHPMailer\PHPMailer;

require_once($_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php');
/**
 * Класс реализует оповещение на сайте
 */
class Mailer{
    private $email, $username, $password, $host;
    
    const TYPE_EMAIL_PRICE = 'email_price';
    const TYPE_INFO = 'info';
    const TYPE_SUBSCRIBE = 'subscribe';

    public function __construct($type){
        $settings = json_decode(Setting::get('email', $type));
        $this->email = $settings->email;
        $this->username = $settings->username;
        $this->password = $settings->password;
        $this->host = $settings->host;
    }
    
	public function send($params, $attachments = []){
		if (!Config::$isSendEmails) return true;
  
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->isHTML(true);  
		$mail->SMTPDebug = 0;
		$mail->Host = $this->host;
		$mail->SMTPAuth = true;
		$mail->Username = $this->username;
		$mail->Password = $this->password;
		$mail->setFrom($this->email, 'Tahos.ru');
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port = 465;
		if (is_array($params['emails'])){
			foreach($params['emails'] as $email) $mail->addAddress($email);
		} else $mail->addAddress($params['emails']);
		$mail->addReplyTo($this->email, 'Tahos.ru');
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
