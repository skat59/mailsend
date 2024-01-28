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
	global $modx;
	$_lang = array();
	$lang_path = MODX_MAILSEND_BASE_PATH . "lang/";
	$userId = $modx->getLoginUserID();
	if (!empty($userId)) {
		$lang = $modx->db->select('setting_value', $modx->getFullTableName('user_settings'), "setting_name='manager_language' AND user='{$userId}'");
		if ($lng = $modx->db->getValue($lang)) {
			$managerLanguage = $lng;
		}
	}
	include MODX_MANAGER_PATH.'includes/lang/english.inc.php';
	if($managerLanguage != 'english') {
		if (file_exists(MODX_MANAGER_PATH.'includes/lang/'.$managerLanguage.'.inc.php')) {
			include MODX_MANAGER_PATH.'includes/lang/'.$managerLanguage.'.inc.php';
		}
	}
	include $lang_path . 'english.php';
	if($managerLanguage != 'english') {
		if (file_exists($lang_path . $managerLanguage.'.php')) {
			include $lang_path . $managerLanguage.'.php';
		}
	}
	$_lang = array_merge($_lang, $_MailSendLang);
	return $_lang;
}

$_lang = getLang();

include_once MODX_MANAGER_PATH . 'includes/header.inc.php';
include_once MODX_MAILSEND_BASE_PATH . 'tpl/.default.php';
include_once MODX_MANAGER_PATH . 'includes/footer.inc.php';
