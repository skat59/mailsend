<?php
header("Content-type: text/plain; charset=utf8");
header('HTTP/1.0 404 Not Found');
use \PhpOffice\PhpSpreadsheet\IOFactory;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', $dir);
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

// RUN MODX Evolution CMS
include_once(MODX_BASE_PATH . "index.php");

$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

function mb_str_pad($input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT, $encoding = 'UTF-8')
{
	$input_length = mb_strlen($input, $encoding);
	$pad_string_length = mb_strlen($pad_string, $encoding);

	if ($pad_length <= 0 || ($pad_length - $input_length) <= 0) {
		return $input;
	}

	$num_pad_chars = $pad_length - $input_length;

	switch ($pad_type) {
		case STR_PAD_RIGHT:
			$left_pad = 0;
			$right_pad = $num_pad_chars;
			break;

		case STR_PAD_LEFT:
			$left_pad = $num_pad_chars;
			$right_pad = 0;
			break;

		case STR_PAD_BOTH:
			$left_pad = floor($num_pad_chars / 2);
			$right_pad = $num_pad_chars - $left_pad;
			break;
	}

	$result = '';
	for ($i = 0; $i < $left_pad; ++$i) {
		$result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
	}
	$result .= $input;
	for ($i = 0; $i < $right_pad; ++$i) {
		$result .= mb_substr($pad_string, $i % $pad_string_length, 1, $encoding);
	}

	return $result;
}

function trimToLower($n = "") {
	return trim( mb_strtolower($n) );
}

function arrFilter($n) {
	return mb_strlen($n[0]) && mb_strlen($n[1]);
}

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
}

function gen_token(string $eml = "") {
	$secret_phrase = $modx->config["secret_phrase"] || "ProjectSoft";
	$token = hash_hmac('sha256', $eml, $secret_phrase);
	return $token;
}

$len = mb_strlen('START');
$pad = ($len % 2) + $len + 5;
$padLen = 30;

if(is_dir(MODX_BASE_PATH . 'input/')):
	@mkdir(MODX_BASE_PATH . 'input/', 0777, TRUE);
endif;

if(is_dir(MODX_BASE_PATH . 'xlsx/')):
	@mkdir(MODX_BASE_PATH . 'xlsx/', 0777, TRUE);
endif;

$table = $modx->getFullTableName( 'mailsend_users' );
$table_members = $modx->getFullTableName( 'mailsend_group_member' );

$out_array = array();

$file = isset($_GET["prefix"]) ? $_GET["prefix"] : "";

$group = isset($_GET["group"]) ? intval($_GET["group"]) : (intval($file) ? intval($file) : 0);

$inputFileName = MODX_BASE_PATH . "xlsx/" . $file . '.xlsx';

echo mb_str_pad(mb_str_pad('START', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;

if(is_file($inputFileName) && $group):
	/**
	 * Определяем тип файла
	*/
	$inputFileType = IOFactory::identify($inputFileName);
	/**
	 * Создаём новый объект Reader определенного типа.
	*/
	$reader = IOFactory::createReader($inputFileType);
	/**
	 * Загружаем файл в объект и получаем SpreadSheet
	*/
	$spreadsheet = $reader->load($inputFileName);
	/**
	 * Только чтение данных
	 */
	$reader->setReadDataOnly(true);
	/**
	 * Данные в виде массива
	 */
	$data = $spreadsheet->getActiveSheet()->toArray();
	/**
	 * Цикл по данным
	 */
	foreach ($data as $item):
		/**
		 * Email's
		 */
		$mails = explode("\n", $item[1]);
		// Удаляем пробелы сначала и конца, переводим в нижний регистр
		$mails = array_map('trimToLower', $mails);
		// Удаляем пробелы сначала и конца
		$item[0] = trim($item[0]);
		// Сортируем
		sort($mails);
		$mail_array = array();
		/**
		 * Пробежим по массиву адресов
		 */
		foreach($mails as $mail):
			$email = mb_convert_case(trim($mail, "\r\n\t ;,.'\"-_/|!"), MB_CASE_LOWER, "UTF-8");
			/**
			 * Если адрес валидный
			 * И имя больше или равен трём символам
			 */
			if(isValidEmail($email) && mb_strlen($item[0]) >= 3):
				$std = new stdClass;
				$std->name = $item[0];
				$std->email = $email;
				$std->token = gen_token($std->email);
				$std->unsubscribe = "0";
				$result = $modx->db->select("*", $table,  "email='" . $std->email ."'");
				$total_rows = $modx->db->getRecordCount( $result );

				/**
				 * Если в базе нет адресов
				 */
				if($total_rows < 1):
					$fields = array(
						'name'        => $modx->db->escape($std->name),
						'email'       => $modx->db->escape($std->email),
						'unsubscribe' => $modx->db->escape($std->unsubscribe),
						'token'       => $modx->db->escape($std->token)
					);
					/**
					 * Закоментить
					 */
					// $out_array[] = $fields;
					/**
					 * Если удалось внести адрес
					 */
					$modx->db->insert( $fields, $table);
					$id = $modx->db->getInsertId();
					if(!is_null($id)):
						/**
						 * Пытаемся вставить принадлежность к группе
						 * Но сначало проверим, есть ли записи в базе
						 */
						$result = $modx->db->select("*", $table_members,  "`id_user`='$id' AND `id_group`='$group'");
						/**
						 * Если результата нет
						 */
						if( !$modx->db->getRecordCount( $result ) ):
							$fields = array(
								'id_user' => $modx->db->escape($id),
								'id_group'=> $modx->db->escape($group)
							);
							/**
							 * Вставляем сточку
							 */
							$modx->db->insert( $fields, $table_members);
						endif;
						$out_array[] = str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . "->" . $std->email . PHP_EOL;
					endif;
				else:
					/**
					 * Если в базе есть запись
					 * Достаём её.
					 */
					while($row = $modx->db->getRow( $result )):
						$id = $modx->db->escape($row['id']);
						/**
						 * Пытаемся вставить принадлежность к группе
						 * Но сначало проверим, есть ли записи в базе
						 */
						$result_members = $modx->db->select("*", $table_members,  "`id_user`='$id' AND `id_group`='$group'");
						/**
						 * Если результата нет
						 */
						if( !$modx->db->getRecordCount( $result_members ) ):
							$fields = array(
								'id_user' => $id,
								'id_group'=> $group
							);
							/**
							 * Вставляем сточку
							 */
							$modx->db->insert( $fields, $table_members);
						endif;
						$out_array[] = str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . "->" . $std->email . PHP_EOL;
					endwhile;
				endif;
			endif;
		endforeach;
	endforeach;
	$count = count($out_array);
	echo PHP_EOL . "Inserted " . $count . " records" . PHP_EOL . PHP_EOL;
	echo print_r($out_array, true) . PHP_EOL . PHP_EOL;
else:
	echo PHP_EOL . "Not Found File" . PHP_EOL . PHP_EOL;
endif;
echo mb_str_pad(mb_str_pad(' END ', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;
echo PHP_EOL . PHP_EOL . "Developed by ProjectSoft © 2008 - all right reserved" . PHP_EOL . PHP_EOL . "Чернышёв Андрей aka ProjectSoft" . PHP_EOL;