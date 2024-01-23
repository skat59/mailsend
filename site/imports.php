<?php
header("Content-type: text/plain; charset=utf8");
use \PhpOffice\PhpSpreadsheet\IOFactory;

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

include_once(dirname(__FILE__) . "/index.php");

function gen_token($nm, $eml) {
	$token = md5(microtime() . $nm . time() . $eml);
	return $token;
}

//$res = $modx->db->select("id", $modx->getFullTableName('web_users'),  "username='" . $username ."' AND password='".md5($password)."'");

$out_array = array();
$dir = dirname(__FILE__) . "/".
$inputFileName = $dir . 'data.xlsx';
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
		$re = '/(")(.*)(")$/';
		$subst = "«$2»";
		$item[0] = preg_replace($re, $subst, $item[0], 1);
		$re = '/""/';
		$subst = " » «";
		$item[0] = preg_replace($re, $subst, $item[0], 1);
		$re = '/(«")/';
		$subst = "«";
		$item[0] = preg_replace($re, $subst, $item[0], 1);
		$re = '/\s+"/';
		$subst = " «";
		$item[0] = preg_replace($re, $subst, $item[0], 1);
		$re = '/ »/';
		$subst = "»";
		$item[0] = preg_replace($re, $subst, $item[0], 1);
		$re = '/"/';
		$subst = "";
		$item[0] = preg_replace($re, $subst, $item[0], 1);

		$mails = explode("\n", $item[1]);
		$mail_array = array();

		$std = new stdClass;
		$std->name = $item[0];
		$groups = trim($item[2], "\t\n\r\s\,;");
		$groups = $groups ? $groups : 2;
		foreach($mails as $mail):
			$std = new stdClass;
			$std->name = $item[0];
			$std->email = mb_convert_case(trim($mail, "\r\n\t;,."), MB_CASE_LOWER, "UTF-8");
			$std->groups = $groups;
			$std->token = gen_token($std->name, $std->email);
			$std->unsubscribe = "0";
			$out_array[] = $std;
		endforeach;
	endforeach;
endif;
print_r($out_array);