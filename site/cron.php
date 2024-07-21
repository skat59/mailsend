<?php

header("Content-type: text/plain; charset=utf-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

// Устанавливаем часовой пояс
define('TIME_ZONE', 'Asia/Yekaterinburg');

// Переменные для работы API Modx EVO
define('MODX_API_MODE',      true);
define('MODX_BASE_PATH',     $dir);
define('MODX_SITE_URL',      'https://mailsend.skat59.ru/');
define('MODX_BASE_URL',      'https://mailsend.skat59.ru/');
// Расположение локали для PHPMailer
define('PHPHMAILER_LANG', MODX_BASE_PATH . 'phpmailer/language/');
// Заголовок результата
define('TITLE_RESULT', 'Результат выполнения КРОН');
// Перенос строки для текста
define('BRNL', "<br />\n");

date_default_timezone_set(TIME_ZONE);

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
}

// Получение токена для файлов
function gen_token(string $assets = "") {
	$token = md5(microtime() . $assets . microtime(true) . MODX_SITE_URL);
	return $token;
}

// Функция вывода кода переменной
function varDumpFn ($var, $line) {
	outputFn("<code><pre style=\"font-family: Consolas; white-space: pre-wrap;\">" . nl2br(htmlspecialchars(print_r($var, true))) . "</pre></code>" . BRNL . $line . BRNL);
}

// CRON вывод непосредственно в кроне
function cronFn($var, $line) {
	echo PHP_EOL . print_r($var, true) . PHP_EOL . $line . PHP_EOL;
}

// Получаем все настройки сайта
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}
// varDumpFn($modx->config);
// ПЕРЕМЕННЫЕ ДЛЯ SMTP
define('PARENT_SITE_URL',    $modx->config['perent_site_url']);
define('TITLE_PARENT',       $modx->config['title_parent']);
// Настройка отправителя
define('SEND_USER',          $modx->config['send_user_evo']);
define('SEND_EMAIL',         $modx->config['send_email_evo']);
define('SEND_PASSWORD',      $modx->config['send_password_evo']);
define('SMTP_HOST',          $modx->config['smtp_host_evo']);
define('SMTP_PORT',          (int) $modx->config['smtp_port_evo']);
define('SMTP_AUTH',          filter_var($modx->config['smtp_auth_evo'], FILTER_VALIDATE_BOOLEAN));
// Логотип
define('TITLE_LOGOTIP', MODX_BASE_PATH . $modx->config['title_logotip']);
// Пауза
define('SLEEP', 2);

// Оформление заголовка письма
$messageHeader = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">' . TITLE_PARENT . '</p></td></tr></tbody></table>';

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
<p style="text-align: right;"><b>С огромным уважением к Вам<br /> &nbsp;<a href="' . PARENT_SITE_URL . '" target="_blank">' . SEND_USER . '</a></b></p';
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

// Запуск PHPMailer
function getPHPMailer() {
	$mailer = new PHPMailer(true);
	$mailer->setLanguage('ru', PHPHMAILER_LANG);
	// Настройки SMTP Yandex
	$mailer->isSMTP();
	// Настройки кодировки
	$mailer->Encoding = $mailer::ENCODING_8BIT;
	$mailer->CharSet = $mailer::CHARSET_UTF8;
	// Настройки SMTP подключения
	$mailer->Mailer = 'smtp';
	$mailer->SMTPAuth = SMTP_AUTH;
	$mailer->Port = SMTP_PORT;
	$mailer->Host = SMTP_HOST;
	$mailer->Username = SEND_EMAIL;
	$mailer->Password = SEND_PASSWORD;
	// Кто шлёт
	$mailer->setFrom(SEND_EMAIL, SEND_USER);
	// Кому ответить
	$mailer->addReplyTo(SEND_EMAIL, SEND_USER);
	// Разрешить HTML
	$mailer->isHTML(true);
	// Логотип
	if(is_file(TITLE_LOGOTIP)):
		$mailer->AddEmbeddedImage(TITLE_LOGOTIP, 'logo_2u');
	endif;
	return $mailer;
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
	// разработчик
	$mailerDev[$index]->admin = 1;
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

$current = strtotime(date("d-m-Y 00:00:00", time()));
$next = $current + 86400 - 1;

// Старт скрипта
outputFn("<p><strong>Часовой пояс времени:</strong> " . TIME_ZONE . "</p>\n");
outputFn("<table>\n<tbody>\n");
outputFn('<tr>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><strong>Начало работы скрипта:</strong></td>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><span style="font-family: Consolas;">' . date('d-m-Y H:i:s', time()) . '</span></td>
</tr>
');
// Конец работы скрипта
outputFn('<tr>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><strong>Конец работы скрипта:</strong></td>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><span style="font-family: Consolas;"><!-- ENDTIME --></span></td>
</tr>
');
// Начало выбора
outputFn('<tr>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><strong>Начальная дата выбора рассылки:</strong></td>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><span style="font-family: Consolas;">' . date("d-m-Y H:i:s", $current) . '</span></td>
</tr>
');
// Конец выбора
outputFn('<tr>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><strong>Конечная дата выбора рассылки:</strong></td>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><span style="font-family: Consolas;">' . date("d-m-Y H:i:s", $next) . '</span></td>
</tr>
');

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
---------------------------------------------
-- Выбрать по определённой группе $groupID --
---------------------------------------------
*/

$table = $modx->getFullTableName( 'mailsend_users' );
// Выбор группы
$slt = "SELECT * FROM $table WHERE (`groups` LIKE '$groupID,%' OR `groups` LIKE '%,$groupID' OR `groups` LIKE '%,$groupID,%' OR `groups`='$groupID') AND `unsubscribe`='0'";
$result = $modx->db->query($slt);
while( $row = $modx->db->getRow( $result ) ) {
	$usr = json_decode(json_encode($row), false);
	$usr->user = debugDecode($usr->name);
	$usr->option = 1;
	$usr->admin = 0;
	unset($usr->name);
	$mailArray[] = $usr;
}


// Присоединяем проверяющих
$mailArray = array_merge( $mailerDev, $mailArray );
$count = 0;

outputFn('<tr>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><strong>Кол-во адресов:</strong></td>
	<td style="border: 1px solid #ccc;padding: 1px 14px;"><span style="font-family: Consolas;"><!-- COUNT --></span></td>
</tr>
');
outputFn("</tbody>\n</table>" . BRNL);

// ПОНЕСЛАСЬ
if($content_arr):
	// Вывод начала всей отправки
	outputFn("START" . BRNL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . BRNL);
	// получаем заголовок рассылки
	$messageTitle = $content_arr["title"];
	// Получаем контент рассылки
	// Каждый раз контент будет перезаписан для каждого адресата
	$messageOut = '<div style="padding: 15px;">' . $messageHeader . '
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody>
	<tr><td>
	<!-- // -->
	' . $content_arr["content"] . '
	<!-- // -->
	</td></tr><tr><td style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.' . BRNL . '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table></div>';
	$unsub = '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';
	// Начало цикла
	foreach($mailArray as $key => $value):
		if((int) $value->option):
			if(!((int) $value->admin)):
				// Это пользователь. Он посчитывается
				++$count;
			endif;
			// Старт вывода отправки пользователь
			outputFn(str_pad("-", $pad, "-", STR_PAD_RIGHT) . BRNL);
			try {
				// Получаем данные пользователя
				$user = $value->user;
				$email = $value->email;
				// Токен пользователя для отписки
				$token = $value->token;
				// Перезапишем токен и получаем контент рассылки готовый к отправке
				$re = '/%token%/';
				$msgMail = preg_replace($re, $token, $messageOut, 1);
				if((int) $value->admin):
					// Вывод, что это адрес проверяющего
					outputFn('<span style="color: red;">ПРОВЕРЯЮЩИЙ НЕ ПОДСЧИТЫВАЕТСЯ</span>' . BRNL);
				endif;
				// Создаём объект PHPMailer
				$mailer = getPHPMailer();
				// Адрес получателя
				$mailer->addAddress($email, $user);
				// Устанавливаем заоловок с рассылкой (отпиской)
				$mailer->AddCustomHeader("List-Unsubscribe: <mailto:" . SEND_EMAIL . "?subject=Unsubscribe>, <" . MODX_SITE_URL . "unsubscribe/?token=" . $token . ">");

				// Заголовок письма
				$mailer->Subject = $messageTitle;
				// HTML текст письма
				$mailer->Body    = $msgMail;
				// Текстовое сообщение
				$mailer->AltBody = $content_arr["text"];
				// Изображения на странице
				foreach($content_arr["matches"] as $match):
					$mailer->AddEmbeddedImage(MODX_BASE_PATH . $match[1], $match[2]);
				endforeach;
				// Файлы
				foreach($content_arr["files"] as $file):
					$mailer->addAttachment(MODX_BASE_PATH . $file["file"], $file["title"]);
				endforeach;
				// Линк для вывода ссылки отписки в результат для проверяющего
				$re = '/%token%/';
				$lnk = preg_replace($re, $token, $unsub, 1);
				// Отправляем
				if($mailer->send()){
					// Запись вывода об удачной отпрвке
					outputFn("SUCCESFULL" . BRNL . $email . " -> " . $user . BRNL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . BRNL);
					// Уничтажаем объект PHPMailer
					unset( $mailer );
					// Спим
					sleep( SLEEP );
				}else{
					// Запись вывода об неудачной отпрвке
					$err = print_r($mailer->ErrorInfo, true);
					outputFn("ERROR MAILER IF: " . $err . BRNL . $email  . " -> " . $user . BRNL . $lnk . BRNL . str_pad("-", $pad, "-", STR_PAD_RIGHT) .BRNL );
					// Уничтажаем объект PHPMailer
					unset( $mailer );
					// Спим
					sleep( SLEEP );
				}
			} catch (Exception $e) {
				// Ошибка
				// Запись вывода об неудачной отпрвке
				$err = print_r($e->getMessage(), true);
				outputFn("ERROR MAILER CATCH: " . $err . BRNL . $email  . " -> " . $user . BRNL . $lnk . BRNL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . BRNL );
				// Уничтажаем объект PHPMailer
				unset( $mailer );
				// Спим
				sleep( SLEEP );
			}
		endif;
	endforeach;
	// Конец вывода всей отправки
	outputFn(BRNL . str_pad("-", $pad, "-", STR_PAD_RIGHT) . BRNL . "END");
endif;

// Готовим HTML для писем проверяющим
$re = '/<!-- ENDTIME -->/';
$end = date('d-m-Y H:i:s', time());
$output = preg_replace($re, $end, $output, 1);

// HTML и Текст письма
$re = '/<!-- COUNT -->/';
$output = preg_replace($re, $count, $output, 1);

// HTML Текст письма
$html = '<div style="padding: 15px;">' . $messageHeader . '<h1>' . TITLE_RESULT . '</h1>' . BRNL . $output . '</div>';
// Готовим Текст письма
$text = strip_tags($html);
$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));

// Отправляем результат проверяющим если была отправка адресатам
if($content_arr):
	foreach($mailerDev as $key => $value):
		try {
			$user = $value->user;
			$email = $value->email;
			$mailer = getPHPMailer();
			// Адрес получателя
			$mailer->addAddress($email, $user);
			// Заголовок письма
			$mailer->Subject = TITLE_RESULT;
			// Текстовое сообщение
			$mailer->AltBody = $text;
			// Письмо
			$mailer->Body = $html;
			// Отправляем
			if($mailer->send()):
				unset( $mailer );
				sleep( SLEEP );
			else:
				unset( $mailer );
				sleep( SLEEP );
			endif;
		} catch (Exception $e) {
			// Ошибка
			unset( $mailer );
			sleep( SLEEP );
		}
	endforeach;
endif;

/**
 * 
 * cronFn($content_arr, __LINE__);
 * 
**/