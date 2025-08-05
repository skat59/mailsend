<?php
if (!defined('MODX_BASE_PATH')) {
	http_response_code(403);
	die('For'); 
}

$e =& $modx->event;
$params = $e->params;
$output = "";
switch($e->name){
	case "OnRichTextEditorInit":
		$output .= '<style>';
		$output .= '.mce-container-body .mce-top-part {top: 0;position: sticky;}';
		$output .= '</style>';
		$e->output($output);
		break;
}
?>