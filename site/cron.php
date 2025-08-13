<?php
header("Content-type: text/plain; charset=utf-8");

/**
 * PHPMailer
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email валидатор
 */
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

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
// Заголовок результата
define('TITLE_RESULT', 'Результат выполнения КРОН');
// Перенос строки для текста
define('BRNL', "<br />\n");
// Путь до директории модуля
define('MODX_MAILSEND_PATH', MODX_BASE_PATH . 'assets/modules/MailSend/');

include_once(MODX_BASE_PATH . "index.php");

// Получаем все настройки сайта
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

define('PARENT_SITE_URL',    $modx->config['perent_site_url']);
define('TITLE_PHONE',        $modx->config['title_phone']);
// ПЕРЕМЕННЫЕ ДЛЯ SMTP
// Настройка отправителя
define('SEND_USER',          $modx->config['send_user_evo']);
define('SEND_EMAIL',         $modx->config['send_email_evo']);
define('SEND_PASSWORD',      $modx->config['send_password_evo']);
define('SMTP_HOST',          $modx->config['smtp_host_evo']);
define('SMTP_PORT',          (int) $modx->config['smtp_port_evo']);
define('SMTP_AUTH',          filter_var($modx->config['smtp_auth_evo'], FILTER_VALIDATE_BOOLEAN));
// Количество сообщений за одну отправку.
define('MAIL_COUNT', $modx->config['send_count_messages']);
// Пауза
define('SLEEP', $modx->config['send_sleep_messages']);

// При тестировании включаем дебаг
define('SEND_MAIL_DEBUG', filter_var($modx->config['send_debug_message'], FILTER_VALIDATE_BOOLEAN));

// Таблицы mailsend
$table = $modx->getFullTableName( 'mailsend_users' );
$table_members = $modx->getFullTableName( 'mailsend_group_member' );
$table_resources = $modx->getFullTableName( 'mailsend_resources' );

// Длина заполнитель
$pad = 30;
// Текст крона
$output = "";

// Дальше можно делать, что угодно

// Получатели в группах
$mailArray = array();
// Кол-во получателей в данном запуске
$count = 0;
// Общее кол-во получателей
$length = 0;

// Получение записи по дате
// В cron мы получаем только одну запись за текущий день
$current = strtotime(date("d-m-Y 00:00:00", time()));
$next = $current + 86400 - 1;
// Имя сайта
$site_name = $modx->config['site_name'];

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

// Получаем документ
function getDocument($object) {
	$modx = evo();
	$content_arr = array();
	if($object["rows"]):
		foreach($object["rows"] as $doc):
			$content = $modx->config['title_header'] . $doc["content"] . $modx->config['additional_signature'];
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
			$text = strip_tags($content_arr["content"]);
			$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));
			$content_arr["text"] = $text;
			$content_arr["id"] = $doc["id"];
			$content_arr["date"] = $doc["date_send"];
			$content_arr["title"] = $doc["pagetitle"];
			$content_arr["files"] = $files_arr;
			// Только один первый документ
			// Выходим
			break;
		endforeach;
	endif;
	return $content_arr;
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
	$arr_return = array(
		"id"      => "",
		"date"    => "",
		"title"   => "",
		"content" => $content,
		"text"    => "",
		"files"   => array(),
		"matches" => $return
	);
	return $arr_return;
}

// Запуск PHPMailer
function getPHPMailer() {
	$mailer = new PHPMailer(true);
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
	return $mailer;
}

// Получить проверяющих
function getMaillerDev() {
	global $modx;
	$d_ch = $modx->config['dispatch_checkers'];
	$mailerDev = json_decode($d_ch);
	$arr = array();
	// Заполним данные разработчиков
	foreach ($mailerDev as $index=>$checker):
		$arr[] = array(
			'user' => $checker->user,
			'email' => $checker->email,
			'admin' => 1,
			'id' => $index + 1,
			'groups' => '0',
			'unsubscribe' => '0',
			'token' => 'developer',
			'option' => filter_var($checker->option, FILTER_VALIDATE_BOOLEAN)
		);
	endforeach;
	// Фильтруем на разрешение отправки
	$return = array_filter($arr, function($value) {
		return $value["option"];
	});
	// Индексируем и возвращаем (приводим в порядок)
	return array_values($return);
}

// Получаем данные о ресурсе
function getMailSendResource(int $current = 0, int $next = 0){
	global $modx;
	$table_resources = $modx->getFullTableName('mailsend_resources');
	$row = array();
	$result = $modx->db->select("*", $table_resources, '`time` >= ' . $current . ' and `time` <= ' . $next . ' and `status` = 0',  "", "1");
	if($modx->db->getRecordCount($result) >= 1):
		$row = $modx->db->getRow( $result );
		return $row;
	endif;
	return $row;
}

// Отправка писем
function sendMailSend($mailArray = array(), $content_arr = array()) {
	$modx = evo();
	// Запись о ресурсе
	global $resource;
	global $count;
	global $length;
	// Таблица ресурсом модуля
	$table_resources = $modx->getFullTableName('mailsend_resources');
	// Пишем в базу, что идёт отправка
	$fields = array(
		'status' => '1', /* пока ноль а не 1 */
		'count'  => $count,
		'length' => $length,
	);
	$modx->db->update( $fields, $table_resources, 'id = "' . $resource["id"] . '"' );
	// ПОНЕСЛАСЬ
	if($content_arr):
		// Вывод начала отправки результата
		outputFn("
<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" style=\"border-collapse: collapse; vertical-align: text-top; margin-bottom:20px;max-width:100%;min-width:100%;width:100%\">
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
		$messageOut = '<div style="padding: 15px;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody>
	<tr><td>
	<!-- // -->
	' . $content_arr["content"] . '
	<!-- // -->
	</td></tr><tr><td style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.' . BRNL . '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table></div>';
		$unsub = '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';
		// Начало цикла
		foreach($mailArray as $key => $value):
			// Старт вывода отправки пользователь
			outputFn("
		<tr>
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">");
			// Получаем данные пользователя
			$user = $value["user"];
			$email = $value["email"];
			// Токен пользователя для отписки
			$token = $value["token"];
			// Пользователь и Email
			outputFn("
				" . $user . "
			</td>
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				" . $email . "
			</td>");
			/**
			 * Валидность адреса
			 */
			$validator = new EmailValidator();
			if($validator->isValid($email, new MultipleValidationWithAnd([new RFCValidation(), new DNSCheckValidation()]))):
				try {
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
					if(!SEND_MAIL_DEBUG):
						// Если не включён дебаг
						if($mailer->send()):
							// Запись вывода об удачной отпрвке
							outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: green;\">УДАЧНО</span>
			</td>");
						else:
							// Запись вывода об неудачной отпрвке
							$err = print_r($mailer->ErrorInfo, true);
							outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: red;\">ОШИБКА:</span><br>" . $err . "<br>" . $lnk . "
			</td>");
						endif;
					else:
						// Если включён дебаг
						outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: green;\">В РЕЖИМЕ ДЕБАГ</span>
			</td>");
					endif;
				} catch (Exception $e) {
					// Ошибка
					// Запись вывода об неудачной отпрвке
					$err = print_r($e->getMessage(), true);
					outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: red;\">ОШИБКА:</span>" . $err . "<br>" . $lnk . "
			</td>");
				}
				// Уничтажаем объект PHPMailer
				unset( $mailer );
			else:
				outputFn("
			<td style=\"border: 1px solid #ccc;padding: 4px 14px;vertical-align: top;\">
				<span style=\"color: red;\">НЕ ВАЛИДНЫЙ АДРЕС</span>
			</td>");
			endif;
			outputFn("
		</tr>");
			// Уничтожаем валидатор
			unset( $validator );
			unset( $multipleValidations );
			if(!SEND_MAIL_DEBUG):
				// Если не включён дебаг
				// Спим
				sleep( SLEEP );
			endif;
		endforeach;
		// Конец вывода всей отправки
		outputFn("
	</tbody>
</table>
");
	endif;
}

// Выбрать из таблицы mailsend_resource запись, где
/**
 * status = 0
 * time >= $current
 */
$resource = getMailSendResource($current, $next);

if(!$resource):
	// Если $resource пуст
	exit();
elseif($resource["status"]):
	// Если статус 1 или 2
	exit();
endif;

// Получаем список получателей
// Выбор групп

$groups = explode(",", $resource["groups"]);
if(isset($groups[0])):
	$groups[0] = !$groups[0] ? "0" : $groups[0];
endif;

// Если длина массива групп равен 1
if(count($groups) == 1):
	// Если элемент равен 0
	if($groups[0] == "0"):
		// Выходим
		exit();
	endif;
endif;

// Письма разработчикам.
$mailerDev = getMaillerDev();

// Выбор пользователей по группам
if(count($groups)):
	$slt = "SELECT users.*, COUNT(users_groups.id_group) as group_count FROM " . $table . " AS users JOIN " . $table_members . " AS users_groups ON users.id = users_groups.id_user WHERE users_groups.id_group IN (" . implode(',', $groups) . ") AND users.unsubscribe = 0 GROUP BY users.id ORDER BY `users`.`id` ASC;";
	$result = $modx->db->query($slt);
	while( $row = $modx->db->getRow( $result ) ):
		$usr = json_decode(json_encode($row, JSON_PRETTY_PRINT), false);
		$mailArray[] = array(
			"id"          => $usr->id,
			"user"        => $usr->name,
			"email"       => $usr->email,
			"unsubscribe" => $usr->unsubscribe,
			"token"       => $usr->token,
			"admin"       => 0,
			"option"      => 1
		);
	endwhile;
	// Общее
	$length = count($mailArray);
	// Срез
	$mailArray = array_slice($mailArray, $resource["count"], MAIL_COUNT);
	// На отправку
	$count = count($mailArray) + $resource["count"];
endif;


// Получить документ
/**
 * Опираемся на данные из $resource
 */
// выбрать нужное сообщение, заголовок, файлы, дату отправки
// Выбираем только один документ
// Документ должен быть опубликованным

$evoPage = $modx->runSnippet('DocLister',
	array(
		'documents'         => $resource["resource"],
		'idType'            => 'documents',
		'tvList'            => 'date_send,files',
		'tvPrefix'          => '',
		'orderBy'           => 'date_send DESC',
		'sortBy'            => 'date_send',
		'sortDir'           => 'DESC',
		'queryLimit'        => '1',
		'api'               => '1',
		'JSONformat'        => 'new'
	)
);

$content_arr = getDocument(json_decode($evoPage, true));

// Старт скрипта
outputFn("
<p><strong>Часовой пояс времени:</strong> " . TIME_ZONE . "</p>");
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
// Кол-во адресов в рассылке
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Максимальное Кол-во адресов в рассылке:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . MAIL_COUNT . '</span></td>
		</tr>');
// Обработанное Кол-во адресов
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Обработанное кол-во адресов:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . $count . '</span></td>
		</tr>');
// Общее Кол-во адресов
outputFn('
		<tr>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><strong>Общее кол-во адресов:</strong></td>
			<td style="border: 1px solid #ccc;padding: 4px 14px;"><span style="font-family: Consolas;">' . $length . '</span></td>
		</tr>');
outputFn("
	</tbody>
</table>");

// Отправляем сообщения
sendMailSend($mailArray, $content_arr);

// Готовим HTML для писем проверяющим
$re = '/<!-- ENDTIME -->/';
$end = date('d-m-Y H:i:s', time());
$output = preg_replace($re, $end, $output, 1);

// HTML Текст письма
$html = '<div style="padding: 15px;">
<h1>' . TITLE_RESULT . '</h1>' . $output . '</div>';
// Готовим Текст письма
$text = strip_tags($html);
$text = preg_replace('/([\r\n]+(?:\s+)?)/m', "\n", preg_replace('/(&nbsp;| )+/', " ", $text));

// Отправляем результат проверяющим если была отправка адресатам

if($content_arr && $count && $count <= $length):
	foreach($mailerDev as $key => $value):
		$mailer = getPHPMailer();
		try {
			$user = $value["user"];
			$email = $value["email"];
			// Адрес получателя
			$mailer->addAddress($email, $user);
			// Заголовок письма
			$mailer->Subject = TITLE_RESULT;
			// Текстовое сообщение
			$mailer->AltBody = $text;
			// Письмо
			$mailer->Body = $html;
			// Отправляем
			$mailer->send();
		} catch (Exception $e) {
			// Ошибка
			// Ничего не делаем
		}
		unset( $mailer );
		if(!SEND_MAIL_DEBUG):
			sleep( SLEEP );
		endif;
	endforeach;
endif;

// Здесь определяем статус
// Либо Всё отправлено - 2
// Либо Процесс завершён (готов для следующего запука крон) - 0
// Прцесс завершён только тогда, когда кол-во получателей больше или равен общему количеству
$status = $count >= $length ? 2 : 0;

// При статусе 2 сделать бы отправку для разработчиков.
// ПОНЕСЛАСЬ для разработчиков
if($content_arr && $mailerDev && $status == 2):
	// получаем заголовок рассылки
	$messageTitle = $content_arr["title"];
	// Получаем контент рассылки
	// Каждый раз контент будет перезаписан для каждого адресата
	$messageOut = '
<div style="padding: 15px;">
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%">
		<tbody>
			<tr>
				<td>
' . $content_arr["content"] . '
				</td>
			</tr>
			<tr>
				<td style="text-align: center; font-size: 10px !important;">
					<p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.' . BRNL . '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">Отписаться</a></p>
				</td>
			</tr>
		</tbody>
	</table>
</div>';
	$unsub = '<a href="' . MODX_SITE_URL . 'unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';
	// Начало цикла
	foreach($mailerDev as $key => $value):
		// Получаем данные пользователя
		$user = $value->user;
		$email = $value->email;
		// Токен пользователя для отписки
		$token = $value->token;
		try {
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
				// Запись вывода об удачной отпрвке
			}else{
				// Запись вывода об неудачной отпрвке
				// $err = print_r($mailer->ErrorInfo, true);
			}
		} catch (Exception $e) {
			// Ошибка
			// Запись вывода об неудачной отпрвке
			// $err = print_r($e->getMessage(), true);
		}
		// Уничтажаем объект PHPMailer
		unset( $mailer );
		// Спим
		sleep( SLEEP );
	endforeach;
endif;

// Пишем в базу
$fields = array(
	'status' => $status,
	'count'  => $count,
	'length' => $length,
);
$modx->db->update( $fields, $table_resources, 'id = "' . $resource["id"] . '"' );
