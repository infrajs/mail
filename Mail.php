<?php
namespace infrajs\mail;
use infrajs\access\Access;
use infrajs\nostore\Nostore;
use infrajs\path\Path;
use akiyatkin\fs\FS;

use PHPMailer\PHPMailer\PHPMailer;

class Mail {
	static public $conf = array(
		'from' => false
	);
	static public function toAdmin($subject, $from, $body, $debug = false)
	{
		//письмо админу
		$conf = Access::$conf['admin'];
		if ($debug) {
			if ($conf['support']) {
				$emailto = $conf['support'];
			} else {
				throw new Exception('Нет support в .infra.json '.$subject);
			}
		} else {
			$emailto = $conf['email'];
		}

		return Mail::sent($subject, $from, $emailto, $body);
	}
	static public function toSupport($subject, $from, $body) //depricated
	{
		//письмо в Техническую поддержку 
		$conf = Access::$conf['admin'];
		$emailto = $conf['support'];
		if (!$emailto) {
			$emailto = $conf['email'];
		}

		return Mail::sent($subject, $from, $emailto, $body);
	}

	static public function html($subject, $body, $replay_to = true, $email_to = true, $debug = false) { //from to
		$mail = new PHPMailer();

		$conf = Mail::$conf;
		if (empty($conf['from'])) $conf['from'] = 'noreplay@'.$_SERVER['HTTP_HOST'];
		$mail->CharSet = 'UTF-8';
	    
	    if (!$debug) $debug = $conf['debug'];
	    $mail->SMTPDebug = $debug;
	    
	    if ($conf['isSMTP']) {
	    	$mail->isSMTP();	
	    }
	    $mail->Port = $conf['smtpport'];
	    $mail->Host = $conf['smtp'];
	    if (!empty($conf['smtplogin'])) {
		    $mail->SMTPAuth   = true;
		    $mail->Username   = $conf['smtplogin'];
		    $mail->Password   = $conf['smtppassword'];
		    $mail->SMTPSecure = 'tls';
	    } else {
	    	$mail->SMTPAuth   = false;
	    	$mail->SMTPAutoTLS = false;
	    	$mail->SMTPSecure = false;
	    }
	    if (!empty($conf['options'])) {
	    	$mail->SMTPOptions = $conf['options'];
	    }
	    $mail->setFrom($conf['from']);


	    if ($email_to === true) $email_to = Access::$conf['admin']['email'];
		$p = explode(',', $email_to);	
		for ($i = 0, $l = sizeof($p); $i < $l; ++$i) {
			$email_to = $p[$i];
			$p2 = explode('<', $email_to);
			if (sizeof($p2) > 1) {
				$name_to = trim($p2[0]);
				$p3 = explode('>', $p2[1]);
				$email_to = trim($p3[0]);
			} else {
				$name_to = '';
				$email_to = trim($p2[0]);
			}
			$mail->addAddress($email_to, $name_to);     // Add a recipient
		}





	    if ($replay_to === true) {
	    	if (!empty(Mail::$conf['replay'])) $replay_to = Mail::$conf['replay'];
	    	else $replay_to = Access::$conf['admin']['email'];
	    }
		$p = explode(',', $replay_to);
		$replay_to = $p[0];
		$p = explode('<', $replay_to);
		if (sizeof($p) > 1) {
			$name_from = trim($p[0]);
			$p = explode('>', $p[1]);
			$replay_to = trim($p[0]);
		} else {
			$name_from = '';
			$replay_to = trim($p[0]);
		}
	    $mail->addReplyTo($replay_to, $name_from);



	    // Content
	    $mail->isHTML(true);                                  // Set email format to HTML
	    $mail->Subject = $subject;
	    $mail->Body    = $body;

	    $r = $mail->send();
	    $res = $r ? 'ok' : 'er';
	    $body .= "\n\n".$mail->ErrorInfo;
		$esubj = Path::encode($subject);
	    
		$file = '~auto/.mail/'.$email_to.' from '.$replay_to.' '.$res.' '.$esubj.' '.date('j F Y H-i').'.txt';
		//@ - ошибка возникает если работать с data символической ссылкой
		@file_put_contents(Path::resolve($file), $body);
	    
	    return $r;
	}
	static public function sent($origsubject, $email_from, $email_to, $body)
	{
		if ($email_from === true) {
			$email_from = 'noreplay@'.$_SERVER['HTTP_HOST'];
		}
		$p = explode(',', $email_from);
		$email_from = $p[0];
		$p = explode('<', $email_from);
		if (sizeof($p) > 1) {
			$name_from = trim($p[0]);
			$p = explode('>', $p[1]);
			$email_from = trim($p[0]);
		} else {
			$name_from = '';
			$email_from = trim($p[0]);
		}

		Nostore::on();
		$subject = Mail::encode($origsubject);
		if (Mail::$conf['from']) {
			$from = Mail::encode($name_from).' <'.Mail::$conf['from'].'>';
		} else {
			$from = Mail::encode($name_from).' <'.$email_from.'>';
		}

		
		$headers = 'From: '.$from."\r\n";
		
		$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
		$headers .= 'Reply-To: '.$email_from."\r\n";

		$esubj = Path::encode($origsubject);
		
		$p = explode(',', $email_to);
		$r = true;
		for ($i = 0, $l = sizeof($p);$i < $l;++$i) {
			$email_to = $p[$i];
			$p2 = explode('<', $email_to);
			if (sizeof($p2) > 1) {
				$name_to = trim($p2[0]);
				$p3 = explode('>', $p2[1]);
				$email_to = trim($p3[0]);
			} else {
				$name_to = '';
				$email_to = trim($p2[0]);
			}
			$to = Mail::encode($name_to).' <'.$email_to.'>';
			if ($r) $r = @mail($to, $subject, $body, $headers);
			$res = $r ? 'ok' : 'er';
			$file1 = '~auto/.mail/from/'.$res.' '.$email_from.' to '.$email_to.' '.$esubj.' '.date('j F Y H-i').'.txt';
			$file2 = '~auto/.mail/to/'.$res.' '.$email_to.' from '.$email_from.' '.$esubj.' '.date('j F Y H-i').'.txt';
			/*$mail = array();
			$mail['subject'] = $origsubject;
			$mail['email_from'] = $email_from;
			$mail['email_to'] = $email_to;
			$mail['time'] = date('j F Y H i');
			$mail['body'] = $body;*/

			//@ - ошибка возникает если работать с data символической ссылкой
			@file_put_contents(Path::resolve($file1), $body);
			@file_put_contents(Path::resolve($file2), $body);
		}
		return $r;
	}
	static public function encode($str)
	{
		return '=?UTF-8?B?'.base64_encode($str).'?=';
	}
	static public function check($email)
	{
		if (!$email) return false;
		return preg_match('/^([0-9a-zA-Z]([-.\w]*[0-9a-zA-Z])*@([0-9a-zA-Z][-\w]*[0-9a-zA-Z]\.)+[a-zA-Z]{2,9})$/', $email);
	}
	static public function fromAdmin($subject, $to, $body)
	{
		//письмо от админa
		$conf = Access::$conf['admin'];
		$from = $conf['email'];

		return Mail::sent($subject, $from, $to, $body);
	}
}




function infra_mail_fromSupport($subject, $to, $body)
{
	//письмо от админa
	$conf = Access::$conf['admin'];
	$from = $conf['support'];
	if (!$from) {
		$from = $conf['email'];
	}

	return Mail::sent($subject, $from, $to, $body);
}


function infra_mail_admin($subject, $body, $debug = false)
{
	//письмо админу от админа
	$conf = Access::$conf['admin'];
	$from = $conf['email'];
	if ($debug) {
		if ($conf['support']) {
			$to = $conf['support'];
		} else {
			$subject = 'Нет support в .config.json '.$subject;
			echo $subject;
			exit;
		}
	} else {
		$to = $from;
	}

	return Mail::sent($subject, $from, $to, $body);
}
