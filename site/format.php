<?php
header("Content-type: text/plain; charset=utf8");
header('HTTP/1.0 404 Not Found');
use \PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$dir = str_replace('\\','/',dirname(__FILE__)) . "/";

include_once($dir . "vendor/autoload.php");

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

$len = mb_strlen('START');
$pad = ($len % 2) + $len + 5;
$padLen = 30;

$out_array = array();

$file = isset($_GET["prefix"]) ? $_GET["prefix"] : "";
$file = $file . '.xlsx';

if(is_dir($dir . 'input/')):
	@mkdir($dir . 'input/', 0777, TRUE);
endif;

if(is_dir($dir . 'xlsx/')):
	@mkdir($dir . 'xlsx/', 0777, TRUE);
endif;

$inputFileName = $dir . 'input/' . $file;
$outputFileName = $dir . 'xlsx/' . $file;

/**  Счётчик  **/
$rw = 0;

echo mb_str_pad(mb_str_pad('START', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;
if(is_file($inputFileName)):
	/**  Identify the type of $inputFileName  **/
	$inputFileType = IOFactory::identify($inputFileName);

	/**  Create a new Reader of the type that has been identified  **/
	$reader = IOFactory::createReader($inputFileType);

	/**  Load $inputFileName to a Spreadsheet Object  **/
	$spreadsheet = $reader->load($inputFileName);
	
	/**  Только чтение данных **/
	$reader->setReadDataOnly(true);
	$sheet = $spreadsheet->setActiveSheetIndex(0);
	
	/**  Данные в виде массива  **/
	$data = $spreadsheet->setActiveSheetIndex(0)->toArray();
	
	/**  Для записи **/
	$spread = new Spreadsheet();
	$sheet = $spread->getActiveSheet();
	
	/**  Название листа  **/
	$sheet->setTitle('Worksheet 1');
	
	
	foreach ($data as $item):
		++$rw;
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
		$mails = preg_replace('/([;,\s]+)/m', "\n", $item[1]);
		$mails = mb_convert_case(trim($mails, "\r\n\t;,."), MB_CASE_LOWER, "UTF-8");
		$groups = trim($item[2], "\t\n\r\s\,;");
		$groups = $groups ? $groups : 2;
		//echo "SET " . 'A' . $rw . PHP_EOL . $item[0] . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL;
		//echo "SET " . 'B' . $rw . PHP_EOL . $mails   . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL;
		//echo "SET " . 'C' . $rw . PHP_EOL . $groups  . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL . PHP_EOL . PHP_EOL;
		//echo " " . PHP_EOL . PHP_EOL;
		$sheet->setCellValue('A' . $rw, $item[0]);
		$sheet->setCellValue('B' . $rw, $mails);
		$sheet->setCellValue('C' . $rw, $groups);
	endforeach;
	
	/**  Create writter  **/
	$writer = IOFactory::createWriter($spread, "Xlsx");
	$writer->setPreCalculateFormulas(false);
	$writer->save($outputFileName);
	//echo "SAVE to file: " . $outputFileName . PHP_EOL . PHP_EOL;
	echo PHP_EOL . "The file is saved." . PHP_EOL . $rw . " records processed" . PHP_EOL . PHP_EOL;
else:
	echo PHP_EOL . "Not Found File" . PHP_EOL . PHP_EOL;
endif;
echo mb_str_pad(mb_str_pad(' END ', $pad, ' ', STR_PAD_BOTH), $padLen, "▆", STR_PAD_BOTH) . PHP_EOL;
echo PHP_EOL . PHP_EOL . "Developed by ProjectSoft © 2008 - all right reserved" . PHP_EOL . PHP_EOL . "Чернышёв Андрей aka ProjectSoft" . PHP_EOL;
