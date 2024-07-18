<?php

header("Content-type: text/plain; charset=utf-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

define('MODX_API_MODE',      true);
define('MODX_BASE_PATH',     $dir);
define('MODX_SITE_URL',      'https://mailsend.skat59.ru/');
define('MODX_BASE_URL',      'https://mailsend.skat59.ru/');
define('PARENT_SITR_URL',    'https://www.skat59.ru/');
define('TITLE_PARENT',       'ООО «СКАТ» - надёжный поставщик спецтехники на Западном Урале');
// Настройка отправителя
define('SEND_USER', "Центр спецтехники ООО «СКАТ»");
define('SEND_EMAIL', 'ofis@skat59.ru');
define('SEND_PASSWORD', 'U2w9O7z5');
define('SMTP_HOST', 'ssl://smtp.yandex.ru');
define('SMTP_PORT', 465);
// Пауза
define('SLEEP', 2);

$dir = str_replace('\\','/',dirname(__FILE__)) . '/';

include_once($dir . "index.php");

// заполнитель
$pad = 30;
// Текст крона
$output = "";
// Группа
$groupID = 0;

// С кодировками пока так, но что-то надо делать!!!
function debugDecode($text = "", $input = "windows-1251", $output = "utf-8") {
	return $text;//iconv($output, $input, $text);
}

// Функция сбора данных
function outputFn($msg = "") {
	global $output;
	$msg = debugDecode($msg);
	$output .= $msg;
	echo $msg;
}

// Получение токена для файлов
function gen_token(string $assets = "") {
	$token = md5(microtime() . $assets . microtime(true) . MODX_SITE_URL);
	return $token;
}

// Парсинг контента
function parseContentMsg($content) {
	$return = array();
	$re = '/<img.*src="(.+)"/Usi';
	$matches = array();
	$text = "";
	preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
	foreach($matches as $arr):
		$uid = gen_token($arr[1]);
		$subst = "cid:" . $uid;
		$re = "/" . preg_quote($arr[1], '/') . "/Usi";
		/** Порядок наполнения массива строгий **/
		$arr[] = $uid;
		$arr[] = pathinfo($arr[1], PATHINFO_BASENAME);
		$content = preg_replace($re, $subst, $content);
		$return[] = $arr;
	endforeach;
	$content = $content . "\n" . '<p style="text-align: center;">Телефон для обратной связи: +7(342)270-00-10 доб. 3005
<br>Или просто напишите нам: <a href="mailto:' . SEND_EMAIL . '">' . SEND_EMAIL . '</a></p>
<p>&nbsp;</p>
<p style="text-align: right;"><b>С огромным уважением к Вам<br /> &nbsp;<a href="' . PARENT_SITR_URL . '" target="_blank">' . SEND_USER . '</a></b></p';
	$text = strip_tags($content);
	$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));
	$arr_return = array(
		"title"   => "",
		"content" => debugDecode($content),
		"text"    => debugDecode($text),
		"files"   => array(),
		"matches" => $return
	);
	return $arr_return;
}

// Получаем документ
function getDocument($object) {
	$content_arr = array();
	if($object["rows"]):
		foreach($object["rows"] as $doc):
			$content = $doc["content"];
			$files = $doc["files"];
			$files_arr = array();
			if($files) {
				$files = json_decode($files, true);
				if($files["fieldValue"]):
					foreach($files["fieldValue"] as $file):
						$file["title"] = debugDecode($file["title"]) . '.' . pathinfo($file["file"], PATHINFO_EXTENSION);
						$files_arr[] = $file;
					endforeach;
				endif;
			}
			$content_arr = parseContentMsg($content);
			$content_arr["group_id"] = $doc["groups_send"];
			$content_arr["title"] = debugDecode($doc["pagetitle"]);
			$content_arr["files"] = $files_arr;
		endforeach;
	endif;
	return $content_arr;
}

// Получаем все настройки сайта
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

// Дальше можно делать, что угодно
// Письма группе
$mailArray = array();

// Письма разработчикам.
$mailerDev = array();

// Настройки отправки
// По этим же адресам письма с результатом крона
$d_ch = $modx->config['dispatch_checkers'];
$mailerDev = json_decode($d_ch);
// Получить Отправлять Или Нет основное письмо

// Заполним данные разработчиков
$index = 0;
foreach ($mailerDev as $checker):
	$mailerDev[$index]->user = debugDecode($checker->user);
	$mailerDev[$index]->id = $index;
	$mailerDev[$index]->groups = '0';
	$mailerDev[$index]->unsubscribe = "0";
	$mailerDev[$index]->token = 'developer';
	$mailerDev[$index]->option = (int) $checker->option;
	++$index;
endforeach;

// Имя сайта
$site_name = debugDecode($modx->config['site_name']);

// Получение записи по дате
// В cron мы получаем только одну запись за текущий день
$time = time() + $modx->config['server_offset_time'];

$current = strtotime(date("d-m-Y 0:00:00", $time));
$next = strtotime(date('d-m-Y 0:00:00', $current + 86400));

// Старт скрипта
outputFn("<table>\n<tbody>\n");
outputFn("<tr>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\"><strong>Start script execution:</strong></td>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\">" . date('d-m-Y H:i:s', $time) . "</td>
</tr>
");
// Кпнец работы скрипта
outputFn("<tr>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\"><strong>Ending script execution:</strong></td>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\">%ENDSCRIPT%" . "</td>
</tr>
");
// Начало выбора
outputFn("<tr>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\"><strong>Start time for mailing selection:</strong></td>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\">" . date("d-m-Y G:i:s", $current) . "</td>
</tr>
");
// Конец выбора
outputFn("<tr>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\"><strong>Final time for selecting a mailing:</strong></td>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\">" . date("d-m-Y G:i:s", $next) . "</td>
</tr>
");

outputFn("<tr>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\"><strong>Number of addresses:</strong></td>
	<td style=\"border: 1px solid #ccc;padding: 1px 14px;\">" . count($mailArray) . "</td>
</tr>
");
outputFn("</tbody>\n</table><br />\n");

// выбрать нужное сообщение, заголовок, файлы, дату отправки
// Выбираем только один документ
$evoPage = $modx->runSnippet('DocLister',
	array(
		'parents'           => '9',
		'display'           => '1',
		'tvList'            => 'date_send,groups_send,files',
		'tvPrefix'          => '',
		'orderBy'           => 'date_send DESC',
		'sortBy'            => 'date_send',
		'sortDir'      		=> 'DESC',
		'showParent'        => '0',
		'api'               => '1',
		'JSONformat'        => 'new',
		'filters'           => 'AND(tv:date_send:egt:' . $current . ';tv:date_send:elt:' . $next . ')'
	)
);

$content_arr = getDocument(json_decode($evoPage, true));
$groupID = $content_arr["group_id"];

/*
------------------------------------
-- Выбрать по определённой группе $groupID --
------------------------------------
*/

$table = $modx->getFullTableName( 'mailsend_users' );
// Выбор группы
$slt = "SELECT * FROM $table WHERE (`groups` LIKE '$groupID,%' OR `groups` LIKE '%,$groupID' OR `groups` LIKE '%,$groupID,%' OR `groups`='$groupID') AND `unsubscribe`='0'";
$result = $modx->db->query($slt);
while( $row = $modx->db->getRow( $result ) ) {
	$usr = json_decode(json_encode($row), false);
	$usr->user = debugDecode($usr->name);
	$usr->option = 1;
	unset($usr->name);
	$mailArray[] = $usr;
}


$mailArray = array_merge($mailerDev, $mailArray);


//outputFn("<code><pre style=\"font-family: Consolas; white-space: pre-wrap;\">" . print_r($content_arr, true) . "</pre></code><br />\n");

outputFn("START<br />\n" . str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />\n");
// ПОНЕСЛАСЬ
if($content_arr):
	
	$messageTitle = $content_arr["title"];

	$messageOut = '<div style="padding: 15px;"><table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">' . TITLE_PARENT . '</p></td></tr><tr><td colspan="2">
	<!-- // -->
	' . $content_arr["content"] . '
	<!-- // -->
	</td></tr><tr><td colspan="2" style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.<br /><a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table></div>';
	$unsub = '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';

	
	// Начало цикла
	foreach($mailArray as $key => $value):
		if($value->option):
			try {
				$user = $value->user;
				$email = $value->email;
				$userID = $value->id;
				$token = $value->token;
				//$code .= $user . " -> " . $email . "<br>";
				$re = '/%token%/';
				$msgMail = preg_replace($re, $token, $messageOut, 1);

				outputFn(str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />\n");

				$mailer = new PHPMailer(true);
				$mailer->setLanguage('ru');
				// Настройки SMTP Yandex
				$mailer->isSMTP();
				$mailer->Encoding = $mailer::ENCODING_8BIT;
				$mailer->CharSet = $mailer::CHARSET_UTF8;
				// SMTP settings
				$mailer->Mailer = 'smtp';
				$mailer->SMTPAuth = true;
				$mailer->Port = SMTP_PORT;
				$mailer->Host = SMTP_HOST;
				$mailer->Username = SEND_EMAIL;
				$mailer->Password = SEND_PASSWORD;
				// Кто шлёт
				$mailer->setFrom(SEND_EMAIL, SEND_USER);
				// Кому ответить
				$mailer->addReplyTo(SEND_EMAIL, SEND_USER);
				// Адрес получателя
				$mailer->addAddress($email, $user);
				// Разрешить HTML
				$mailer->isHTML(true);
				// Заголовок письма
				$mailer->Subject = $messageTitle;
				// HTML текст письма
				$mailer->Body    = $msgMail;
				// Текстовое сообщение
				$mailer->AltBody = $content_arr["text"];
				// Устанавливаем заоловок с рассылкой (отпиской)
				$mailer->AddCustomHeader("List-Unsubscribe: <mailto:" . SEND_EMAIL . "?subject=Unsubscribe>, <" . MODX_SITE_URL . "unsubscribe/?token=" . $token . ">");
				// Логотип
				$mailer->AddEmbeddedImage(MODX_BASE_PATH . 'assets/templates/projectsoft/images/embed.png', 'logo_2u');

				// Изображения на странице
				foreach($content_arr["matches"] as $match):
					$mailer->AddEmbeddedImage(MODX_BASE_PATH . $match[1], $match[2]);
				endforeach;
				// Файлы
				foreach($content_arr["files"] as $file):
					$mailer->addAttachment(MODX_BASE_PATH . $file["file"], $file["title"]);
				endforeach;
				// Отправляем
				if($mailer->send()){
					// Получаем ID отправленного сообщения
					$message_id = $mailer->getLastMessageID();
					$re = '/%token%/';
					$lnk = preg_replace($re, $token, $unsub, 1);
					// Запись в базу об удачной отпрвке
					outputFn("SUCCESFULL<br />\n" . $email . " -> " . $user . "<br />\n" . str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />\n");
					unset( $mailer );
					sleep( SLEEP );
				}else{
					// Запись в базу об неудачной отпрвке
					$err = print_r($mailer->ErrorInfo, true);
					outputFn("ERROR MAILER IF: " . $err . "<br />\n" . $email  . " -> " . $user . "<br />\n" . str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />\n" );
					unset( $mailer );
					sleep( SLEEP );
				}
			} catch (Exception $e) {
				// Ошибка{
				// Запись в базу об неудачной отпрвке
				$err = print_r($e->getMessage(), true);
				outputFn("ERROR MAILER CATCH: " . $err . "<br />\n" . $email  . " -> " . $user . "<br />\n" . str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />\n" );
				unset( $mailer );
				sleep( SLEEP );
			}
		endif;
	endforeach;
endif;

outputFn("<br />" . str_pad("-", $pad, "-", STR_PAD_RIGHT) . "<br />" . "END");
// Конец цикла

// Отправляем результат
foreach($mailerDev as $key => $value):
	$user = $value->user;
	$email = $value->email;
	$mailer = new PHPMailer(true);
	$mailer->setLanguage('ru');
	try {
		// Настройки SMTP Yandex
		$mailer->isSMTP();
		$mailer->Encoding = $mailer::ENCODING_8BIT;
		$mailer->CharSet = $mailer::CHARSET_UTF8;
		// SMTP settings
		$mailer->Mailer = 'smtp';
		$mailer->SMTPAuth = true;
		$mailer->Port = 465;
		$mailer->Host = 'ssl://smtp.yandex.ru';
		$mailer->Username = 'ofis@skat59.ru';
		$mailer->Password = 'U2w9O7z5';
		// Кто шлёт
		$mailer->setFrom('ofis@skat59.ru', "Результат выполнения КРОН");
		// Кому ответить
		$mailer->addReplyTo('ofis@skat59.ru', "Результат выполнения КРОН");
		// Адрес получателя
		$mailer->addAddress($email, $user);
		// Разрешить HTML
		$mailer->isHTML(true);
		// Заголовок письма
		$mailer->Subject = "Выполнение крона";

		$re = '/%ENDSCRIPT%/';
		$end = date('d-m-Y H:i:s', time() + (int) $modx->config['server_offset_time']);

		$outmsg = preg_replace($re, $end, $output, 1);
		// HTML текст письма
		$content = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">' . TITLE_PARENT . '</p></td></tr></tbody></table><h1>Результат выполнения КРОН</h1><br />' . $outmsg;
		// Текст письма
		$text = strip_tags($content);
		$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));
		// Письмо
		$mailer->Body = $content;
		// Текстовое сообщение
		$mailer->AltBody = $text;
		// Логотип
		$mailer->AddEmbeddedImage(MODX_BASE_PATH . 'assets/templates/projectsoft/images/embed.png', 'logo_2u');
		// Отправляем
		if($mailer->send()):
			unset( $mailer );
			//sleep( SLEEP );
		else:
			unset( $mailer );
			//sleep( SLEEP );
		endif;
	} catch (Exception $e) {
		// Ошибка
		unset( $mailer );
		//sleep( SLEEP );
	}
endforeach;