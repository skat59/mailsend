<?php
header("Content-type: text/plain; charset=utf8");
use \PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

include_once(dirname(__FILE__) . "/vendor/autoload.php");

$out_array = array();

$dir = dirname(__FILE__) . "/";

$file = isset($_GET["prefix"]) ? $_GET["prefix"] : "";
$file = $file . '.xlsx';

if(is_dir($dir . '/input/')):
	@mkdir(, 0777, TRUE);
endif;

if(is_dir($dir . '/xlsx/')):
	@mkdir(, 0777, TRUE);
endif;

$inputFileName = $dir . '/input/' . $file;
$outputFileName = $dir . '/xlsx/' . $file;

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
	
	/**  Счётчик  **/
	$rw = 0;
	
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
		echo "SET " . 'A' . $rw . PHP_EOL . $item[0] . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL;
		echo "SET " . 'B' . $rw . PHP_EOL . $mails   . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL;
		echo "SET " . 'C' . $rw . PHP_EOL . $groups  . PHP_EOL . str_pad("-", 100, "-", STR_PAD_LEFT) . PHP_EOL . PHP_EOL . PHP_EOL;
		echo " " . PHP_EOL . PHP_EOL;
		$sheet->setCellValue('A' . $rw, $item[0]);
		$sheet->setCellValue('B' . $rw, $mails);
		$sheet->setCellValue('C' . $rw, $groups);
	endforeach;
	
	/**  Create writter  **/
	$writer = IOFactory::createWriter($spread, "Xlsx");
	$writer->setPreCalculateFormulas(false);
	$writer->save($outputFileName);
	echo "SAVE to file: " . $outputFileName . PHP_EOL . PHP_EOL;
endif;
