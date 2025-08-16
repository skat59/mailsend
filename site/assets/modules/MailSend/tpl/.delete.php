<?php
if(IN_MANAGER_MODE!='true' && !$modx->hasPermission('exec_module')) {
	http_response_code(403);
	die('For');
}
header("Content-type: application/json; charset=utf8");
// Удаление пользователя или Группы
$url_a = $_GET['a'];
$url_id = $_GET['id'];
$start_link = $modx->config["site_manager_url"] . 'index.php?a=' . $url_a . '&id=' . $url_id;

$postType = isset($_POST['type']) ? filter_input(INPUT_POST, 'type', FILTER_SANITIZE_ENCODED) : "";

$return = array(
	"request" => false,
	"message" => "Ничего не сделано"
);

switch ($postType) {
	case 'user':
		// Удаление Пользователя
		break;
	case 'group':
		// Удаление Группы
		$group_id = isset($_POST["group_id"]) ? $modx->db->escape((int)$_POST['group_id']) : 0;
		if($group_id):
			$name = $modx->db->getValue($modx->db->select('name', $table_groups, "id='{$group_id}'"));
			if($name):
				// Группа
				$modx->db->delete($table_groups, "id=$group_id");
				// Пользователи привязанные к группе
				$modx->db->delete($table_members, "id_group=$group_id");
				$return = array(
					"request" => true,
					"message" => "Группа\n\n«" . $name . "»\n\nудалена.",
					"id" => $group_id
				);
			else:
				$return = array(
					"request" => false,
					"message" => "Выбранная группа не удалена."
				);
			endif;
		else:
			$return = array(
				"request" => false,
				"message" => "Выбранная группа не удалена."
			);
		endif;
		break;
	default:
		// Обдумать. Нужно уйти на перезагрузку страницы
		break;
}
echo json_encode($return);
