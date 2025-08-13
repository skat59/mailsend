<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')):
	http_response_code(403);
	die('For');
endif;

define('MODX_MAILSEND_PATH', str_replace(MODX_BASE_PATH, '', str_replace('\\', '/', realpath(dirname(__FILE__)))) . '/');
define('MODX_MAILSEND_BASE_PATH', MODX_BASE_PATH . MODX_MAILSEND_PATH);

$action = isset($_REQUEST['action']) ? trim(strip_tags($_REQUEST['action'])) : null;
$page = (isset($_REQUEST['page']) && (int)$_REQUEST['page'] > 0) ? (int)$_REQUEST['page'] : 0;
$a = (isset($_REQUEST['a']) && (int)$_REQUEST['a'] > 0) ? (int)$_REQUEST['a'] : 0;
$formid = (isset($_REQUEST['formid']) && (int)$_REQUEST['formid'] > 0) ? (int)$_REQUEST['formid'] : 0;
$_lang = array();
$modx = evolutionCMS();

function getLang() {
	global $modx, $_lang, $manager_language;
	file_put_contents(MODX_MAILSEND_BASE_PATH . "log.txt", print_r($_lang, true) . PHP_EOL, FILE_APPEND);
	$_MailSendLang = [];
	$lang_path = MODX_MAILSEND_BASE_PATH . "lang/";
	include $lang_path . 'english.php';
	if (is_file($lang_path . $manager_language.'.php')) {
		include $lang_path . $manager_language.'.php';
	}
	$_lang = array_merge($_lang, $_MailSendLang);
	return $_lang;
}

$_lang = getLang();

include_once MODX_MANAGER_PATH . 'includes/header.inc.php';
include_once MODX_MAILSEND_BASE_PATH . 'tpl/.default.php';
include_once MODX_MANAGER_PATH . 'includes/footer.inc.php';
