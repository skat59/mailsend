<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

include_once(dirname(__FILE__) . "/index.php");

$modx->db->connect();
if (empty($modx->config)) {
    $modx->getSettings();
}

//дальше можно делать, что угодно

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
$idGroup = 1;
$slt = "SELECT * FROM $table WHERE (`groups` LIKE '$idGroup,%' OR `groups` LIKE '%,$idGroup' OR `groups` LIKE '%,$idGroup,%' OR `groups`='$idGroup') AND `unsubscribe`='0'";
$result = $modx->db->query($slt);
// $mailArray = array();
while( $row = $modx->db->getRow( $result ) ) {
	$usr = json_decode(json_encode($row), false);
	$mailArray[] = $usr;
}
*/

// выбрать из таблицы нужное сообщение и заголовок для отправки
$messageTitle = "Хорошая техника - в хорошие руки";
$messageOut = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;;margin-bottom:20px;max-width:100%;min-width:100%;width:100%"><tbody><tr style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;"><img style="display:inline-block;vertical-align:middle;width:100px" src="cid:logo_2u" /></td><td style="background:#002952;color:#ffffff;font-size:16px;padding:15px;width:100%!important;"><p style="display:inline-block;vertical-align:middle;width:100%;">ООО «СКАТ» - надёжный поставщик спецтехники на Западном Урале</p></td></tr><tr><td colspan="2">
<!-- // -->
<p>Компания ООО «СКАТ» предлагает вашему вниманию <b>Телескопический погрузчик г/п 4,0 тонны HELI 40H51-90.</b></p>
<p><b>Телескопический погрузчик г/п 4,0 тонны HELI 40H51-90</b> &ndash; имеет гидростатическую трансмиссию с полным приводом, высокой тягой и эффективностью обеспечивающей автономное передвижение погрузчика, работу системы предотвращения опрокидывания (динамическая стабилизация), распределение мощности между навесным оборудованием и системой движения.</p>
<p><b>Ключевые особенности телескопического погрузчика г/п 4,0 тонны HELI 40H51-90:</b></p>
<ul>
<li>Максимальная грузоподъёмность  4 000 кг.</li>
<li>Максимальный вылет стрелы 5,1 метра</li>
<li>Максимальная высота подъёма 9,0 метров</li>
<li>Экономичный и надёжный двигатель DEUTZ AG / Perkins</li>
<li>Высококачественная сборка на крупнейшем заводе КНР</li>
<li>Хороший обзор и легкое управление повышают уровень безопасности при эксплуатации телескопического погрузчика</li>
<li>Автоматическая трансмиссия обеспечивает высокую производительность</p>
<li>Простая конструкция погрузчика значительно сокращает затраты на техническое обслуживание и ремонт</li>
</ul>
<p>С более подробным описанием Вы сможете ознакомиться в прикреплённых PDF файлах.</p>
<!-- // -->
<p style="text-align: center;">Телефон для обратной связи: +7(342)270-00-10 доб. 3005<br>Или просто напишите нам: <a href="mailto:ofis@skat59.ru">ofis@skat59.ru</a></p>
<p> </p>
<p style="text-align: right;"><b>С огромным уважением к Вам<br /><a href="https://www.skat59.ru/" target="_blank">Компания ООО «СКАТ»</a></b></p>
</td></tr><tr><td colspan="2" style="text-align: center; font-size: 10px !important;"><p style="text-align: center; font-size: 10px !important;">Вы можете отписаться от нашей рассылки.<br /><a href="https://mailsend.skat59.ru/unsubscribe/?token=%token%" target="_blank">Отписаться</a></p></td></tr></tbody></table>';
$messageID = 0;
// Начало цикла
$output = "START" . PHP_EOL;
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
        
        // Файлы
		$mailer->addAttachment(MODX_BASE_PATH . 'assets/files/test/foto.pdf', "Фото.pdf");
		$mailer->addAttachment(MODX_BASE_PATH . 'assets/files/test/heli.pdf', 'Коммерческое  преложение Телескопический погрузчик HELI.pdf');
		
		// Отправляем
		if($mailer->send()){
			// Получаем ID отправленного сообщения
			$message_id = $mailer->getLastMessageID();
			$re = '/<(.+)@.+$/';
			$message_id = preg_replace($re, "$1", $message_id);
			// Запись в базу об удачной отпрвке
			ob_start();
			echo 'Отправлено: ' . $message_id . " : " . $user . " -> " . $email . PHP_EOL;
			ob_flush();
			unset($mailer);
			sleep(10);
		}else{
			// Запись в базу об неудачной отпрвке
			ob_start();
			echo PHP_EOL . $user . " -> " . $email . PHP_EOL . "Ошибка Mailler: {$mailer->ErrorInfo}" . PHP_EOL;
			ob_flush();
			unset($mailer);
		}
	} catch (Exception $e) {
		// Ошибка{
		// Запись в базу об неудачной отпрвке
		ob_start();
		echo PHP_EOL . $user . " -> " . $email . PHP_EOL . "Ошибка Mailler: {$mailer->ErrorInfo}" . PHP_EOL;
		ob_flush();
		unset($mailer);
	}
endforeach;
// Конец цикла