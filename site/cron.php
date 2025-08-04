<?php
header("Content-type: text/plain; charset=utf-8");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

// Устанавливаем часовой пояс
define('TIME_ZONE', 'Asia/Yekaterinburg');
date_default_timezone_set(TIME_ZONE);

set_time_limit(0);

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
// Путь до директории модуля
define('MODX_MAILSEND_PATH', MODX_BASE_PATH . 'assets/modules/MailSend/');

$dir = str_replace('\\','/',dirname(__FILE__)) . '/';

include_once($dir . "index.php");

// Получаем все настройки сайта
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

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
// Количество сообщений за одну отправку.
define('MAIL_COUNT', $modx->config['send_count_messages']);
// Пауза
define('SLEEP', $modx->config['send_sleep_messages']);

// Крон файл
$cronFile = MODX_MAILSEND_PATH . 'cron.json';

// заполнитель
$pad = 30;
// Текст крона
$output = "";
// Группа
$groupID = 0;

// Дальше можно делать, что угодно
// Письма группе
$mailArray = array();

// Письма разработчикам.
$mailerDev = array();

// Настройки отправки
// По этим же адресам письма с результатом крона
$d_ch = $modx->config['dispatch_checkers'];
$mailerDev = json_decode($d_ch);

// Функция сбора данных
function outputFn($msg = "") {
	global $output;
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
		"id"      => "",
		"date"    => "",
		"title"   => "",
		"content" => $content,
		"text"    => $text,
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
						$file["title"] = $file["title"] . '.' . pathinfo($file["file"], PATHINFO_EXTENSION);
						$files_arr[] = $file;
					endforeach;
				endif;
			}
			$content_arr = parseContentMsg($content);
			$content_arr["id"] = $doc["id"];
			$content_arr["date"] = $doc["date_send"];
			$content_arr["group_id"] = $doc["groups_send"];
			$content_arr["title"] = $doc["pagetitle"];
			$content_arr["files"] = $files_arr;
		endforeach;
	endif;
	return $content_arr;
}

// Запуск PHPMailer
function getPHPMailer() {
	$mailer = new PHPMailer(true);
	// $mailer->setLanguage('ru', PHPHMAILER_LANG);
	$mailer->setLanguage('ru');
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

// Заполним данные разработчиков
$index = 0;
foreach ($mailerDev as $checker):
	$mailerDev[$index]->user = $checker->user;
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
$site_name = $modx->config['site_name'];

// Оформление заголовка письма
$messageHeader = '
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%">
	<tbody>
		<tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;">
			<td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;">
				<img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" />
			</td>
			<td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;">
				<p style="display:inline-block;vertical-align:middle;width:100%;">' . TITLE_PARENT . '</p>
			</td>
		</tr>
	</tbody>
</table>
';

// Получение записи по дате
// В cron мы получаем только одну запись за текущий день
$current = strtotime(date("d-m-Y 00:00:00", time()));
$next = $current + 86400 - 1;

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

// Обдумать!!!!
$groups = explode("||", $groupID);
if(isset($groups[0])):
	$groups[0] = !$groups[0] ? "0" : $groups[0];
endif;
/*
---------------------------------------------
-- Выбрать по определённой группе $groupID --
---------------------------------------------
*/

$table = $modx->getFullTableName( 'mailsend_users' );
$table_members = $modx->getFullTableName( 'mailsend_group_member' );

// Выбор групп
if(count($groups)):
	$slt = "SELECT users.*, COUNT(users_groups.id_group) as group_count FROM " . $table . " AS users JOIN " . $table_members . " AS users_groups ON users.id = users_groups.id_user WHERE users_groups.id_group IN (" . implode(',', $groups) . ") AND users.unsubscribe = 0 GROUP BY users.id ORDER BY `users`.`id` ASC;";
	//echo $slt . PHP_EOL;
	$result = $modx->db->query($slt);
	while( $row = $modx->db->getRow( $result ) ) {
		$usr = json_decode(json_encode($row), false);
		$usr->user = $usr->name;
		$usr->option = 1;
		$usr->admin = 0;
		unset($usr->name);
		$mailArray[] = $usr;
	}
endif;
// Присоединяем проверяющих
$mailArray = array_merge( $mailArray, $mailerDev );
$count = 0;

// Проверка записи в крон файле.
$cronObject = new stdClass;
// День отправки
$cronObject->time = 0;
// ID ресурса
$cronObject->id = 0;
// Статус отправки
/**
 * 0 - Не отправлено
 * 1 - Отправляется (процесс не завершён)
 * 2 - Отправлено (не перечитывать данные)
 */
$cronObject->status = 0;
// Общее кол-во получателей
$cronObject->length = 0;
// Обрабатываемое в данный момент
$cronObject->count = 0;

if(is_file($cronFile)):
	try {
		$txt = file_get_contents($cronFile);
		$txt = (array) json_decode($txt);
		// Запись текущей даты
		// Если нет ошибки
		if(!json_last_error()):
			// Читаем
			$cronObject->time = isset($txt[$current]->time) ? $txt[$current]->time : $current;
			$cronObject->id = isset($txt[$current]->id) ? $txt[$current]->id : (isset($content_arr["id"]) ? $content_arr["id"] : 0);
			$cronObject->length = isset($txt[$current]->length) ? (int)$txt[$current]->length : count($mailArray);
			$cronObject->count = isset($txt[$current]->count) ? (int)$txt[$current]->count : 0;
		else:
			// Нет сегодняшней даты
			// Описываем объект
			$cronObject->time = $current;
			$cronObject->id = isset($content_arr["id"]) ? $content_arr["id"] : 0;
			$cronObject->length = count($mailArray);
			$cronObject->count = 0;
		endif;
	}catch(Exception $e){
		// Какая либо ошибка чтения / парсинга
		// Описываем объект
		$cronObject->time = $current;
		$cronObject->id = isset($content_arr["id"]) ? $content_arr["id"] : 0;
		$cronObject->length = count($mailArray);
		$cronObject->count = 0;
	}
else:
	// Описываем объект
	$cronObject->time = $current;
	$cronObject->id = isset($content_arr["id"]) ? $content_arr["id"] : 0;
	$cronObject->length = count($mailArray);
	$cronObject->count = 0;
endif;

// Старт скрипта
outputFn("
<p><strong>Часовой пояс времени:</strong> " . TIME_ZONE . "</p>
");
outputFn("
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; vertical-align: text-top; margin-bottom:20px;max-width:100%;min-width:100%;width:100%\">
	<tbody>");
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Начало работы скрипта:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . date('d-m-Y H:i:s', time()) . '</span></td>
		</tr>');
// Конец работы скрипта
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Конец работы скрипта:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;"><!-- ENDTIME --></span></td>
		</tr>');
// Начало выбора
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Начальная дата выбора рассылки:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . date("d-m-Y H:i:s", $current) . '</span></td>
		</tr>');
// Конец выбора
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Конечная дата выбора рассылки:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . date("d-m-Y H:i:s", $next) . '</span></td>
		</tr>');
// Кол-во адресов
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Кол-во адресов:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;"><!-- COUNT --></span></td>
		</tr>');
outputFn("
	</tbody>
</table>
");

switch ($cronObject->status) {
	case 1:
		// Происходит отправка (процесс не завершён)
		echo "Происходит отправка";
		exit();
		break;
	case 2:
		// Отправлено (процесс завершён)
		echo "На сегодня всё отправлено";
		break;
}

// ПОНЕСЛАСЬ
if($content_arr):
	if($content_arr["date"] >= $current && $content_arr["date"] < $next):
		$cronObject->time = $content_arr["date"];
		// ID ресурса
		$cronObject->id = $content_arr["id"];
		// Статус - 1
		$cronObject->status = 1;
		// Вывод начала всей отправки
		outputFn("<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; vertical-align: text-top; margin-bottom:20px;max-width:100%;min-width:100%;width:100%\">
	<thead>
		<tr>
			<th style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">ПОЛУЧАТЕЛЬ</th>
			<th style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">EMAIL</th>
			<th style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">РЕЗУЛЬТАТ</th>
		</tr>
	</thead>
	<tbody>\n\t\t");
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
		// Выбрать из массива 
		$mailArray = array_slice($mailArray, $cronObject->count, MAIL_COUNT, false);
		print_r($mailArray);
		$cronObject->count = $cronObject->count + count($mailArray);
		// Пишем данные в файл
		$carr = array($current => $cronObject);
		file_put_contents($cronFile, json_encode($carr));
		// Начало цикла
		foreach($mailArray as $key => $value):
			if((int) $value->option):
				if(!((int) $value->admin)):
					// Это пользователь. Он посчитывается
					++$count;
				endif;
				// Старт вывода отправки пользователь
				outputFn("
		<tr>
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">");
				// Получаем данные пользователя
				$user = $value->user;
				$email = $value->email;
				// Токен пользователя для отписки
				$token = $value->token;
				// Определяем проверяющего
				if((int) $value->admin):
					// Вывод, что это адрес проверяющего
					outputFn('
			<span style="color: red;">ПРОВЕРЯЮЩИЙ НЕ ПОДСЧИТЫВАЕТСЯ</span><br>');
				endif;
				// Пользователь и Email
				outputFn("
				" . $user . "
			</td>
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				" . $email . "
			</td>");
				try {
					/*
					// Перезапишем токен и получаем контент рассылки готовый к отправке
					$re = '/%token%/';
					$msgMail = preg_replace($re, $token, $messageOut, 1);
					
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
					*/
					// Запись вывода об удачной отпрвке
						outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: green;\">УДАЧНО</span>
			</td>");
					/*
					}else{
					// Запись вывода об неудачной отпрвке
						$err = print_r($mailer->ErrorInfo, true);
						outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: red;\">ОШИБКА:</span><br>" . $err . "<br>" . $lnk . "
			</td>");
					}
					*/
				} catch (Exception $e) {
					// Ошибка
					// Запись вывода об неудачной отпрвке
					$err = print_r($e->getMessage(), true);
					outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: red;\">ОШИБКА:</span>" . $err . "<br>" . $lnk . "
			</td>");
				}
				outputFn("
		</tr>");
				// Уничтажаем объект PHPMailer
				unset( $mailer );
				// Спим
				sleep( SLEEP );
			endif;
		endforeach;
		// Конец вывода всей отправки
		outputFn("
	</tbody>
</table>
");
	else:

	endif;
	
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

if($content_arr && $cronObject->count <= $cronObject->length):
	foreach($mailerDev as $key => $value):
		$mailer = getPHPMailer();
		try {
			$user = $value->user;
			$email = $value->email;
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
		}
		unset( $mailer );
		sleep( SLEEP );
	endforeach;
endif;

$cronObject->status = $cronObject->count == $cronObject->length ? 3 : 0;
$carr = array($current => $cronObject);
file_put_contents($cronFile, json_encode($carr, JSON_PRETTY_PRINT));

//echo $output;
/**
 * 
 * cronFn($content_arr, __LINE__);
 * 
**/
// echo $text . PHP_EOL . "-----------------------------------------" . PHP_EOL . PHP_EOL;
