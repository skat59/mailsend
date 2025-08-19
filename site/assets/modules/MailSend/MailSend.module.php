<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')):
	http_response_code(403);
	die('For');
endif;

define('MODX_MAILSEND_PATH', str_replace(MODX_BASE_PATH, '', str_replace('\\', '/', realpath(dirname(__FILE__)))) . '/');
define('MODX_MAILSEND_BASE_PATH', MODX_BASE_PATH . MODX_MAILSEND_PATH);

global $manager_language;

$action = isset($_REQUEST['action']) ? trim(strip_tags($_REQUEST['action'])) : null;
$page = (isset($_REQUEST['page']) && (int)$_REQUEST['page'] > 0) ? (int)$_REQUEST['page'] : 0;
$a = (isset($_REQUEST['a']) && (int)$_REQUEST['a'] > 0) ? (int)$_REQUEST['a'] : 0;
$formid = (isset($_REQUEST['formid']) && (int)$_REQUEST['formid'] > 0) ? (int)$_REQUEST['formid'] : 0;
$_lang = array();
$modx = evolutionCMS();



$_MailSendLang = [];
$lang_path = MODX_MAILSEND_BASE_PATH . "lang/";
include $lang_path . 'english.php';
if (is_file($lang_path . $manager_language . '.php')) {
	include $lang_path . $manager_language . '.php';
}
$_lang = array_merge($_lang, $_MailSendLang);



$postAction = isset($_POST['action']) ? filter_input(INPUT_POST, 'action', FILTER_SANITIZE_ENCODED) : "";

// Таблица пользователей
$table_users = $modx->getFullTableName('mailsend_users');
// Таблица групп
$table_groups = $modx->getFullTableName('mailsend_groups');
// К какой группе относится пользователь
$table_members = $modx->getFullTableName('mailsend_group_member');
// Таблица ресурсов
$table_resources = $modx->getFullTableName('mailsend_resources');

switch ($postAction) {
	case 'delete':
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.delete.php';
		break;
	case 'edit':
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.edit.php';
		break;
	case 'save':
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.save.php';
		break;
	default:
		include_once MODX_MANAGER_PATH . 'includes/header.inc.php';
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.default.php';
		include_once MODX_MANAGER_PATH . 'includes/footer.inc.php';
		break;
}

