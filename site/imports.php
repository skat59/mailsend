<?php
header("Content-type: text/plain; charset=utf8");
use \PhpOffice\PhpSpreadsheet\IOFactory;

define('MODX_API_MODE', true);
define('MODX_BASE_PATH', dirname(__FILE__) . "/");
define('MODX_SITE_URL', 'https://mailsend.skat59.ru/');
define('MODX_BASE_URL', 'https://mailsend.skat59.ru/');

include_once(dirname(__FILE__) . "/index.php");

function isValidEmail($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email);
}

function gen_token($nm, $eml) {
	$token = md5(microtime() . $nm . time() . $eml);
	return $token;
}
$modx->db->connect();
if (empty($modx->config)) {
	$modx->getSettings();
}

//$res = $modx->db->select("id", $modx->getFullTableName('web_users'),  "username='" . $username ."' AND password='".md5($password)."'");

$table = $modx->getFullTableName( 'mailsend_users' );
$out_array = array();

$dir = str_replace('\\','/',dirname(__FILE__));

$file = isset($_GET["prefix"]) ? $_GET["prefix"] : false;
$inputFileName = $dir . "/xlsx/" . $file . '.xlsx';

if(is_file($inputFileName)):
	echo "START" .PHP_EOL . "<------------------------------------------------>" . PHP_EOL . PHP_EOL;
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
		
		$groups = trim($item[2], "\t\n\r\s\,;");
		$groups = $groups ? $groups : 2;
		foreach($mails as $mail):
			$email = mb_convert_case(trim($mail, "\r\n\t;,."), MB_CASE_LOWER, "UTF-8");
			if(isValidEmail($email)):
				$std = new stdClass;
				$std->name = $item[0];
				$std->email = $email;
				$std->groups = $groups;
				$std->token = gen_token($std->name, $std->email);
				$std->unsubscribe = "0";
				$result = $modx->db->select("name", $table,  "email='" .$std->email ."'");
				$total_rows = $modx->db->getRecordCount( $result );
				if($total_rows < 1):
					$out_array[] = $std;
					$fields = array(
						'name'        => $std->name,  
						'email'       => $std->email,  
						'groups'      => $std->groups,  
						'unsubscribe' => $std->unsubscribe, 
						'token'       => $std->token
					);
					if($id = $modx->db->insert( $fields, $table)):
						$out_array[] = $std->email;
						echo str_pad((string)$id, 10, " ", STR_PAD_RIGHT) . $std->email . PHP_EOL;
					endif;
				endif;
			endif;
		endforeach;
	endforeach;
	$count = count($out_array);
	echo ($count ? PHP_EOL . PHP_EOL : "") . "Inserted " . $count . " records" . PHP_EOL . PHP_EOL;
	echo ">------------------------------------------------<" . PHP_EOL. "END" . PHP_EOL;
else:
	echo "Not Found File" . PHP_EOL;
endif;
echo PHP_EOL . PHP_EOL . "Developed by ProjectSoft © 2008 - all right reserved" . PHP_EOL . PHP_EOL . "Чернышёв Андрей aka ProjectSoft" . PHP_EOL;