<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
// Добавление Пользователя или Группы
$url_a = $_GET['a'];
$url_id = $_GET['id'];
$start_link = $modx->config["site_manager_url"] . 'index.php?a=' . $url_a . '&id=' . $url_id;

$postType = isset($_POST['type']) ? filter_input(INPUT_POST, 'type', FILTER_SANITIZE_ENCODED) : "";

switch ($postType) {
	case 'user':
		// Добавление Пользователя
		break;
	case 'group':
		// Добавление Группы
		break;
	default:
		// Обдумать. Нужно уйти на перезагрузку страницы
		break;
}
