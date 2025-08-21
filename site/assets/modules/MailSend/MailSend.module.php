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

$rowCollationEngine = false;

// Таблица пользователей
$table_users = $modx->getFullTableName('mailsend_users');
// Таблица групп
$table_groups = $modx->getFullTableName('mailsend_groups');
// К какой группе относится пользователь
$table_members = $modx->getFullTableName('mailsend_group_member');
// Таблица ресурсов
$table_resources = $modx->getFullTableName('mailsend_resources');

function existsMailSendTable(string $table_name) {
	$modx = evolutionCMS();
	$tn = explode('.', $table_name);
	$sql = "SELECT COUNT(*) AS COUNT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=" . $tn[0] . " AND TABLE_NAME=" . $tn[1];
	$sql = preg_replace('/`/', "'", $sql);
	$rs = $modx->db->query($sql);
	$rsRow = $modx->db->getRow($rs);
	return filter_var($rsRow['COUNT'], FILTER_VALIDATE_BOOLEAN);
}

function getEngineAndCollation() {
	global $rowCollationEngine;
	if(!$rowCollationEngine):
		$modx = evolutionCMS();
		// Таблица контента сайта
		$table_content = $modx->getFullTableName('site_content');
		$table_content = preg_replace('/`/', "'", $table_content);
		$table_content = explode('.', $table_content);
		// Определить Collation и Engine
		$sql = "SHOW TABLE STATUS WHERE Name=" . $table_content[1] . ";";
		$rs = $modx->db->query($sql);
		$rsRow = $modx->db->getRow($rs);
		$Collation = $rsRow['Collation'];
		$Engine = $rsRow['Engine'];
		$rowCollationEngine = array(
			'Collation' => $Collation,
			'Engine'    => $Engine
		);
	endif;
	return $rowCollationEngine;
}

/**
 * В данном месте сделать проверку на существовании таблиц в базе.
 * Если таблиц нет, то создать.
 *
 */


// Таблица пользователей
if(!existsMailSendTable($table_users)):
	// Таблица пользователей не существует.
	// Создать
	$rowCollationEngine = getEngineAndCollation();
	$sql = "CREATE TABLE " . $table_users . " (`id` int NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, `email` varchar(255) NOT NULL, `unsubscribe` int NOT NULL DEFAULT '0', `token` varchar(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=" . $rowCollationEngine['Engine'] . " AUTO_INCREMENT=1 DEFAULT CHARSET=" . $modx->db->config['charset'] . " COLLATE=" . $rowCollationEngine['Collation'] . " COMMENT='Получатели'";
	$modx->db->query($sql);
endif;

// Таблица групп
if(!existsMailSendTable($table_groups)):
	// Таблица групп не существует
	// Создать
	$rowCollationEngine = getEngineAndCollation();
	$sql = "CREATE TABLE  " . $table_groups . " (`id` int NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=" . $rowCollationEngine['Engine'] . " AUTO_INCREMENT=1 DEFAULT CHARSET=" . $modx->db->config['charset'] . " COLLATE=" . $rowCollationEngine['Collation'] . " COMMENT='Группы рассылок'";
	$modx->db->query($sql);
endif;

// Таблица участников групп
if(!existsMailSendTable($table_members)):
	// Таблица участников групп не существует
	// Создать
	$rowCollationEngine = getEngineAndCollation();
	$sql = "CREATE TABLE " . $table_members . " (`id` int NOT NULL AUTO_INCREMENT, `id_user` int NOT NULL, `id_group` int NOT NULL, PRIMARY KEY (`id`)) ENGINE=" . $rowCollationEngine['Engine'] . " AUTO_INCREMENT=1 DEFAULT CHARSET=" . $modx->db->config['charset'] . " COLLATE=" . $rowCollationEngine['Collation'] . " COMMENT='Отношение получателей к группам'";
	$modx->db->query($sql);
endif;

// Таблица обработки ресурсов кроном
if(!existsMailSendTable($table_resources)):
	// Таблица обработки ресурсов кроном не существует
	// Создать
	$rowCollationEngine = getEngineAndCollation();
	$sql = "CREATE TABLE " . $table_resources . " (`id` int NOT NULL AUTO_INCREMENT COMMENT 'ID записи', `resource` int NOT NULL COMMENT 'ID ресурса', `groups` varchar(255) NOT NULL DEFAULT '0' COMMENT 'Группы отправки', `status` int NOT NULL DEFAULT '0' COMMENT 'Статус отправки', `count` int NOT NULL DEFAULT '0' COMMENT 'Кол-во пользователей получивших сообщение', `length` int NOT NULL DEFAULT '0' COMMENT 'Общее количество пользователей', `time` int NOT NULL DEFAULT '946670400' COMMENT 'Дата отправки', PRIMARY KEY (`id`)) ENGINE=" . $rowCollationEngine['Engine'] . " AUTO_INCREMENT=1 DEFAULT CHARSET=" . $modx->db->config['charset'] . " COLLATE=" . $rowCollationEngine['Collation'] . " COMMENT='Обработка ресурсов кроном'";
	$modx->db->query($sql);
endif;

$postAction = isset($_POST['action']) ? filter_input(INPUT_POST, 'action', FILTER_SANITIZE_ENCODED) : "";

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
	case 'import':
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.import.php';
		break;
	default:
		include_once MODX_MANAGER_PATH . 'includes/header.inc.php';
		include_once MODX_MAILSEND_BASE_PATH . 'tpl/.default.php';
		include_once MODX_MANAGER_PATH . 'includes/footer.inc.php';
		break;
}

