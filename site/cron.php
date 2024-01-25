<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// header("Content-type: text/plain; charset=utf-8");

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

include_once(dirname(__FILE__) . "/index.php");

// Пауза
$sleep = 10;

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


/*
------------------------------------
-- Выбрать по определённой группе --
------------------------------------
"SELECT * FROM $table WHERE (`groups` LIKE '$idGroup,%' OR `groups` LIKE '%,$idGroup' OR `groups` LIKE '%,$idGroup,%' OR `groups`='$idGroup') ORDER BY `id` ASC";
*/

// Выбрать из таблицы адреса для отправки
// Перед рассылкой раскомментировать
/*
$table = $modx->getFullTableName( 'mailsend_users' );
$result = $modx->db->select("*", $table,  "unsubscribe='0'", "id ASC");

// Выбор группы
$idGroup = 2;

$slt = "SELECT * FROM $table WHERE (`groups` LIKE '$idGroup,%' OR `groups` LIKE '%,$idGroup' OR `groups` LIKE '%,$idGroup,%' OR `groups`='$idGroup') AND `unsubscribe`='0'";

$result = $modx->db->query($slt);
// $mailArray = array();
while( $row = $modx->db->getRow( $result ) ) {
	$usr = json_decode(json_encode($row), false);
	$mailArray[] = $usr;
}
*/
// Временно
// Забор контента и его парсинг
function parseContentMsg($content) {
	$return = array();
	$re = '/<img.*src="(.+)"/Usi';
	$matches = array();
	preg_match_all($re, $content, $matches, PREG_SET_ORDER, 0);
	foreach($matches as $arr):
		$uid = gen_token($arr[1]);
		$subst = "cid:" . $uid;
		$re = "%" . $arr[1] . "%Usi";
		$arr[] = $uid;
		$content = preg_replace($re, $subst, $content);
		$return[] = $arr;
	endforeach;
	$arr_return = array(
		"content" => $content,
		"matches" => $return
	);
	return $arr_return;
}
// Парсим на изображения
$name_content = "tiger";
$attach_files = array();
$file_content = MODX_BASE_PATH . "content/" . $name_content. ".html";
$content = is_file($file_content) ? file_get_contents($file_content) : "content";

$content_arr = parseContentMsg($content);

// выбрать из таблицы нужное сообщение и заголовок для отправки
$messageTitle = "В наличии в Перми Полуприцеп лесотранспортный ТИГЕР";

$messageOut = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">ООО «СКАТ» - надёжный поставщик спецтехники на Западном Урале</p></td></tr><tr><td colspan="2">
<!-- // -->
' . $content_arr["content"] . '
<!-- // -->
<p style="text-align: center;">Телефон для обратной связи: +7(342)270-00-10 доб. 3005<br>Или просто напишите нам: <a href="mailto:ofis@skat59.ru">ofis@skat59.ru</a></p>
<p> </p>
<p style="text-align: right;"><b>С огромным уважением к Вам<br /><a href="https://www.skat59.ru/" target="_blank">Компания ООО «СКАТ»</a></b></p>
</td></tr><tr><td colspan="2" style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.<br /><a href="https://mailsend.skat59.ru/unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table>';
$unsub = '<a href="https://mailsend.skat59.ru/unsubscribe/?token=%token%" target="_blank">UNSUBSCRIBE</a>';

$messageID = 0;
// Начало цикла
echo "START" . PHP_EOL;
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
		$mailer->AltBody = strip_tags($msgMail);
		// Устанавливаем заоловок с рассылкой (отпиской)
		$mailer->AddCustomHeader("List-Unsubscribe: <mailto:ofis@skat59.ru?subject=Unsubscribe>, <https://mailsend.skat59.ru/unsubscribe/?token=" . $token . ">");
		// Логотип
		$mailer->AddEmbeddedImage(MODX_BASE_PATH . 'assets/templates/projectsoft/images/embed.png', 'logo_2u');
		
		// Изображения на странице
		foreach($content_arr["matches"] as $match):
			$mailer->AddEmbeddedImage(MODX_BASE_PATH . $match[1], $match[2]);
		endforeach;

		// Файлы
		//$mailer->addAttachment(MODX_BASE_PATH . 'assets/images/2024.01.23/yc-b30vh-blue.jpg', "YC-B30VH-Blue.jpg");
		//$mailer->addAttachment(MODX_BASE_PATH . 'assets/images/2024.01.23/yc-b30vh-yieh.jpg', 'YC-B30VH-Yieh.jpg');
		
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
			echo " -----------------------" . PHP_EOL . "SUCCESFULL" . PHP_EOL . $email . " -> " . $lnk . PHP_EOL . "-------------------------------" . PHP_EOL;
			unset( $mailer );
			sleep( $sleep );
		}else{
			// Запись в базу об неудачной отпрвке
			$err = print_r($mailer->ErrorInfo, true);
			echo PHP_EOL . $email . PHP_EOL . "-------------------------------" . PHP_EOL . "ERROR MAILER: " . $err . PHP_EOL;
			unset( $mailer );
			sleep( $sleep );
		}
	} catch (Exception $e) {
		// Ошибка{
		// Запись в базу об неудачной отпрвке
		$err = print_r($mailer->ErrorInfo, true);
		echo PHP_EOL . $email . PHP_EOL . "-------------------------------" . PHP_EOL . "ERROR MAILER: " . $err . PHP_EOL;
		unset( $mailer );
		sleep( $sleep );
	}
endforeach;
echo PHP_EOL . "END";
// Конец цикла