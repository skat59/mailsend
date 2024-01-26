<?php
header("Content-type: text/plain; charset=utf8");
header('HTTP/1.0 404 Not Found');
use \PhpOffice\PhpSpreadsheet\IOFactory;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

// RUN MODX Evolution CMS
include_once($dir  . "index.php");

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

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
}

function gen_token($nm, $eml) {
	$token = md5(microtime() . $nm . time() . $eml);
	return $token;
}

$len = mb_strlen('START');
$pad = ($len % 2) + $len + 5;
$padLen = 30;

$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

if(is_dir(MODX_BASE_PATH . 'input/')):
	@mkdir(MODX_BASE_PATH . 'input/', 0777, TRUE);
endif;

if(is_dir(MODX_BASE_PATH . 'xlsx/')):
	@mkdir(MODX_BASE_PATH . 'xlsx/', 0777, TRUE);
endif;

$table = $modx->getFullTableName( 'mailsend_users' );
$out_array = array();

$file = isset($_GET["prefix"]) ? $_GET["prefix"] : "";

$inputFileName = MODX_BASE_PATH . "xlsx/" . $file . '.xlsx';

echo mb_str_pad(mb_str_pad('START', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;
if(is_file($inputFileName)):
	/**  Identify the type of $inputFileName  **/
	$inputFileType = IOFactory::identify($inputFileName);

	/**  Create a new Reader of the type that has been identified  **/
	$reader = IOFactory::createReader($inputFileType);

	/**  Load $inputFileName to a Spreadsheet Object  **/
	$spreadsheet = $reader->load($inputFileName);
	// Только чтение данных
	$reader->setReadDataOnly(true);
	//$worksheet = $spreadsheet->getActiveSheet();

	// Данные в виде массива
	$data = $spreadsheet->getActiveSheet()->toArray();

	foreach ($data as $item):
		/**  Группа  **/
		$groups = trim($item[2], "\t\n\r\s\,;");
		$groups = $groups ? $groups : 2;
		
		/**  Email's  **/
		$mails = explode("\n", $item[1]);
		$mail_array = array();
		
		/**  Пробежим по массиву адресов  **/
		foreach($mails as $mail):
			$email = mb_convert_case(trim($mail, "\r\n\t;,."), MB_CASE_LOWER, "UTF-8");
			/** Если адрес валидный  **/
			if(isValidEmail($email)):
				$std = new stdClass;
				$std->name = $item[0];
				$std->email = $email;
				$std->groups = $groups;
				$std->token = gen_token($std->name, $std->email);
				$std->unsubscribe = "0";
				$result = $modx->db->select("name", $table,  "email='" .$std->email ."'");
				$total_rows = $modx->db->getRecordCount( $result );
				
				/** Если в базе нет адресов  **/
				if($total_rows < 1):
					$fields = array(
						'name'        => $std->name,  
						'email'       => $std->email,  
						'groups'      => $std->groups,  
						'unsubscribe' => $std->unsubscribe, 
						'token'       => $std->token
					);
					/** Если удалось внести адрес  **/
					if($id = $modx->db->insert( $fields, $table)):
						$out_array[] = str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . "->" . $std->email . PHP_EOL;
					endif;
				endif;
			endif;
		endforeach;
	endforeach;
	$count = count($out_array);
	echo PHP_EOL . "Inserted " . $count . " records" . PHP_EOL . PHP_EOL;
else:
	echo PHP_EOL . "Not Found File" . PHP_EOL . PHP_EOL;
endif;
echo mb_str_pad(mb_str_pad(' END ', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;
echo PHP_EOL . PHP_EOL . "Developed by ProjectSoft © 2008 - all right reserved" . PHP_EOL . PHP_EOL . "Чернышёв Андрей aka ProjectSoft" . PHP_EOL;