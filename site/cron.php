<?php

header("Content-type: text/plain; charset=utf-8");
header('HTTP/1.0 404 Not Found');

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

$dir = str_replace('\\','/',dirname(__FILE__)) . '/';

include_once($dir . "index.php");

// Пауза
$sleep = 10;
// заполнитель
$pad = 30;

$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

//дальше можно делать, что угодно

function gen_token(string $assets = "") {
	$token = md5(microtime() . $assets . microtime(true) . MODX_SITE_URL);
	return $token;
}

// Первое письмо себе
$usr = new stdClass;
$usr->user = "ProjectSoft";
$usr->email = "projectsoft2009@yandex.ru";
$usr->id = "null";
$usr->token = "developer";

$mailArray = array(
	$usr
);

$site_name = mb_convert_encoding($modx->getConfig('site_name'), 'UTF-8');

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
<br>Или просто напишите нам: <a href="mailto:ofis@skat59.ru">ofis@skat59.ru</a></p>
<p>&nbsp;</p>
<p style="text-align: right;"><b>С огромным уважением к Вам<br /> &nbsp;<a href="' . PARENT_SITR_URL . '" target="_blank">Компания ООО «СКАТ»</a></b></p';
	$text = strip_tags($content);
	$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));
	$arr_return = array(
		"title"   => "",
		"content" => $content,
		"text"    => $text,
		"files"   => array(),
		"matches" => $return
	);
	return $arr_return;
}

// Получение записи по дате
// В cron мы получаем только одну запись за текущий день
$time = time() + $modx->config['server_offset_time'];

$current = strtotime(date("d-m-Y 0:00:00", $time));
$next = strtotime(date('d-m-Y 0:00:00', $current + 86400));

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

$evoDocument = json_decode($evoPage, true);
$evoPage = json_encode($evoDocument, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

echo $time . PHP_EOL;
echo date("d-m-Y G:i:s", $current) . PHP_EOL;
echo date("d-m-Y G:i:s", $next) . PHP_EOL;

$content_arr = array();
$groupID = 0;
if($evoDocument["rows"]):
	foreach($evoDocument["rows"] as $doc):
		$title = $doc["pagetitle"];
		$content = $doc["content"];
		$files = $doc["files"];
		$groupID = $doc["groups_send"];
		$files_arr = array();
		if($files) {
			$files = json_decode($files, true);
			if($files["fieldValue"]):
				foreach($files["fieldValue"] as $file):
					$file["title"] = $file["title"] . '.' . pathinfo($file["file"], PATHINFO_EXTENSION);
					$files_arr[] = $file;
				endforeach;
			endif;
		}
		$content_arr = parseContentMsg($content);
		$content_arr["title"] = $title;
		$content_arr["files"] = $files_arr;
	endforeach;
endif;

/*
------------------------------------
-- Выбрать по определённой группе --
------------------------------------
$table = $modx->getFullTableName( 'mailsend_users' );

// Выбор группы
$slt = "SELECT * FROM $table WHERE (`groups` LIKE '$groupID,%' OR `groups` LIKE '%,$groupID' OR `groups` LIKE '%,$groupID,%' OR `groups`='$groupID') AND `unsubscribe`='0'";

$result = $modx->db->query($slt);
// $mailArray = array();
while( $row = $modx->db->getRow( $result ) ) {
	$usr = json_decode(json_encode($row), false);
	$mailArray[] = $usr;
}
*/

echo "START" . PHP_EOL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL;

if($content_arr):

	$messageTitle = $content_arr["title"];

	$messageOut = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">' . TITLE_PARENT . '</p></td></tr><tr><td colspan="2">
	<!-- // -->
	' . $content_arr["content"] . '
	<!-- // -->
	</td></tr><tr><td colspan="2" style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.<br /><a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table>';
	$unsub = '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';

	$messageID = 0;
	// Начало цикла
	foreach($mailArray as $key => $value):
		$user = $value->user;
		$email = $value->email;
		$userID = $value->id;
		$token = $value->token;
		//$code .= $user . " -> " . $email . "<br>";
		$re = '/%token%/';
		$msgMail = preg_replace($re, $token, $messageOut, 1);
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
			$mailer->setFrom('ofis@skat59.ru', $site_name);
			// Кому ответить
			$mailer->addReplyTo('ofis@skat59.ru', $site_name);
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
			$mailer->AddCustomHeader("List-Unsubscribe: <mailto:ofis@skat59.ru?subject=Unsubscribe>, <" . MODX_SITE_URL . "unsubscribe/?token=" . $token . ">");
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
				/**
				 * Отписка (TEST)
				 */
				$re = '/%token%/';
				$lnk = preg_replace($re, $token, $unsub, 1);
				// Запись в базу об удачной отпрвке
				echo str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL . "SUCCESFULL" . PHP_EOL . $email . " -> " . $lnk . PHP_EOL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL;
				unset( $mailer );
				sleep( $sleep );
			}else{
				// Запись в базу об неудачной отпрвке
				$err = print_r($mailer->ErrorInfo, true);
				echo PHP_EOL . $email . PHP_EOL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL . "ERROR MAILER: " . $err . PHP_EOL;
				unset( $mailer );
				sleep( $sleep );
			}
		} catch (Exception $e) {
			// Ошибка{
			// Запись в базу об неудачной отпрвке
			$err = print_r($mailer->ErrorInfo, true);
			echo PHP_EOL . $email . PHP_EOL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL . "ERROR MAILER: " . $err . PHP_EOL;
			unset( $mailer );
			sleep( $sleep );
		}
	endforeach;
endif;
echo PHP_EOL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . PHP_EOL . "END";
// Конец цикла
