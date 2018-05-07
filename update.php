<?php
use infrajs\mail\Mail;
use infrajs\access\Access;
use infrajs\ans\Ans;
use infrajs\path\Path;
use infrajs\load\Load;

Access::test(true);

Path::mkdir('~auto/');
Path::mkdir('~auto/.mail/');
Path::mkdir('~auto/.mail/to/');
Path::mkdir('~auto/.mail/from/');

$from = 'noreplay@'.$_SERVER['HTTP_HOST'];
$headers = 'From: '.$from."\r\n";
$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
$headers .= 'Reply-To: aky@list.ru'."\r\n";
//echo 'Нативная проверка<br>';
//$r=mail('info@itlife-studio.ru','Проверка с сервера '.$_SERVER['HTTP_HOST'],'Текст проверочного сообщения',$headers);
//var_dump($r);

//return;//нельзя зачастую лимит стоит сколько писем за раз можно отправлять
//echo '<br>Сложная проверка<br>';
$ans = array();
if (empty(Access::$conf['admin']['support'])) return Ans::err($ans, 'У администратора не указан email support');


$body = Path::theme('-mail/update.tpl');
$body = file_get_contents($body);
$body = str_replace( 
	array("{host}", "{date}"), 
	array($_SERVER['HTTP_HOST'], date('j.m.Y')), 
	$body
);
$subject = 'Выполнено обновление '.$_SERVER['HTTP_HOST'];
$email_from = 'noreplay@'.$_SERVER['HTTP_HOST'];
$r = Mail::toSupport($subject, $email_from, $body);

if (!$r) error_log('-mail: При обновлении не удалось отправить тестовое письмо.');
